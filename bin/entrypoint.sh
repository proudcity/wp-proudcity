#!/bin/bash
set -e

# Clone private repos
if [[ $GOOGLE_GIT_TOKEN ]]; then

    # Fail silently if a git repo fails to clone
    set +e

    # Write SSH key so git can clone private GitHub repos
    if [[ $GITHUB_SSH_KEY ]]; then
        mkdir -p /root/.ssh
        printf '%s\n' "${GITHUB_SSH_KEY}" >/root/.ssh/id_rsa
        chmod 600 /root/.ssh/id_rsa
    fi

    # Install other non-free plugins
    echo "Adding non-free plugins"
    cd /app/wordpress/wp-content/plugins

    # Add custom themes, comma separated. Ensure that we fail silently.
    if [[ $WORDPRESS_THEMES ]]; then
        cd /app/wordpress/wp-content/themes
        IFS=',' read -ra themes <<< "$WORDPRESS_THEMES"
        for s in "${themes[@]}"; do
            git clone -- "$s"
            echo "Adding theme repo: ${s} in $(pwd)"
        done
    fi

    # Add custom plugins, comma separated. Ensure that we fail silently.
    # just need to trigger a full new build
    if [[ $WORDPRESS_PLUGINS ]]; then
        cd /app/wordpress/wp-content/plugins
        IFS=',' read -ra plugins <<< "$WORDPRESS_PLUGINS"
        for s in "${plugins[@]}"; do
            git clone -- "$s"
            echo "Adding plugin repo: ${s} in $(pwd)"
        done
    fi

    # Add wordpress.org plugins, comma separated
    if [[ $WORDPRESSORG_PLUGINS ]]; then
        cd /app/wordpress/wp-content/plugins
        IFS=',' read -ra org_plugins <<< "$WORDPRESSORG_PLUGINS"
        for s in "${org_plugins[@]}"; do
            curl -O -L "http://downloads.wordpress.org/plugin/${s}.zip" && unzip "${s}.zip" && rm "${s}.zip"
            echo "Adding WP.org plugin: ${s} in $(pwd)"
        done
    fi

    # Add support for Business directory plugin (for ELGL)
    if [ -d "/app/wordpress/wp-content/plugins/business-directory" ]; then
        echo "Adding Business Directory Plugin"
        # @todo: `mv` doesn't work because of a Docker FS bug: https://github.com/docker/docker/issues/4570
        cp -r /app/wordpress/wp-content/plugins/business-directory/* /app/wordpress/wp-content/plugins
        rm -r /app/wordpress/wp-content/plugins/business-directory
    fi

    # adding the proper domain to our security.txt file
    echo "Fixing security.txt domain"
    sed -i "s/URL/$HOST/g" "/app/wordpress/.well-known/security.txt"

    htaccess=/app/wordpress/.htaccess
    bots_csv=/app/wordpress/bots.csv

    # Block bots at the top of .htaccess (bots.csv: one regex fragment per line; blank lines and # comments allowed)
    if [[ -f "$bots_csv" ]]; then
        tmp_htaccess="$(mktemp)"
        tmp_block="$(mktemp)"

        # Remove any previous managed block to avoid duplicates on restart
        sed '/^# BEGIN Managed: Blocked Bots$/,/^# END Managed: Blocked Bots$/d' "$htaccess" >"$tmp_htaccess"
        mv "$tmp_htaccess" "$htaccess"

        # Build the block in a temp file
        echo "# BEGIN Managed: Blocked Bots" >>"$tmp_block"
        echo "<IfModule mod_rewrite.c>" >>"$tmp_block"
        echo "RewriteEngine On" >>"$tmp_block"

        have_any=0
        last_line_is_or=0

        while IFS= read -r bot || [[ -n "$bot" ]]; do
            # Strip leading/trailing whitespace
            bot="$(echo "$bot" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"

            # Skip blank lines and comments
            [[ -z "$bot" ]] && continue
            [[ "$bot" =~ ^# ]] && continue

            echo "RewriteCond %{HTTP_USER_AGENT} ${bot} [NC,OR]" >>"$tmp_block"
            have_any=1
            last_line_is_or=1
        done <"$bots_csv"

        # Only add rule if we actually wrote at least one condition
        if [[ $have_any -eq 1 ]]; then
            # Remove trailing OR on the last RewriteCond
            sed -i '$ s/ \[NC,OR\]$/ [NC]/' "$tmp_block"

            echo "RewriteRule ^ - [F,L]" >>"$tmp_block"
            echo "</IfModule>" >>"$tmp_block"
            echo "# END Managed: Blocked Bots" >>"$tmp_block"
            echo "" >>"$tmp_block"

            # Prepend the block to the htaccess file
            cat "$tmp_block" "$htaccess" >"$tmp_htaccess"
            mv "$tmp_htaccess" "$htaccess"

            echo "Added blocked bot rules (from ${bots_csv}) to top of ${htaccess}"
        else
            # No bots found; do not modify htaccess further (beyond removing old block above)
            echo "No bot entries found in ${bots_csv}; skipping bot block insert"
        fi

        rm -f "$tmp_block"
    fi

    # Route /robots.txt through WordPress so the proud-robots.php mu-plugin owns the output (wp-proudcity#2846)
    tmp_htaccess="$(mktemp)"
    tmp_block="$(mktemp)"

    # Remove any previous managed block to avoid duplicates on restart
    sed '/^# BEGIN Managed: Dynamic robots.txt$/,/^# END Managed: Dynamic robots.txt$/d' "$htaccess" >"$tmp_htaccess"
    mv "$tmp_htaccess" "$htaccess"

    {
        echo "# BEGIN Managed: Dynamic robots.txt"
        echo "<IfModule mod_rewrite.c>"
        echo "RewriteEngine On"
        echo "RewriteRule ^robots\\.txt\$ /index.php [L]"
        echo "</IfModule>"
        echo "# END Managed: Dynamic robots.txt"
        echo ""
    } >"$tmp_block"

    cat "$tmp_block" "$htaccess" >"$tmp_htaccess"
    mv "$tmp_htaccess" "$htaccess"
    rm -f "$tmp_block"

    echo "Added dynamic robots.txt rewrite to top of ${htaccess}"

    htaccess=/app/wordpress/.htaccess
    # Add domain redirects to .htaccess as CSV (from, to) with newlines between each redirect
    if [[ $REDIRECTS ]]; then
        export IFS=","
        echo "${REDIRECTS}" >/tmp/redirects.csv
        while read from to; do
            echo "RewriteCond %{HTTP_HOST} ^${from}$ [NC]" >>$htaccess
            echo "RewriteRule ^(.*)$ ${to} [R=301,L]" >>$htaccess
            echo "Adding redirect from ${from} to ${to}"
        done </tmp/redirects.csv
    fi

    # deny URLS
    if [[ $BLOCK_LOGIN ]]; then
        echo "RewriteCond %{REQUEST_URI} ^/wp-login\.php$ [NC]" >>$htaccess
        echo "RewriteCond %{QUERY_STRING} !action=(logout|postpass) [NC]" >>$htaccess
        echo "RewriteCond %{QUERY_STRING} !loggedout=true [NC]" >>$htaccess
        echo "RewriteCond %{QUERY_STRING} !redirect_to=.* [NC]" >>$htaccess
        echo "RewriteRule ^(.*)$ - [F,L]" >>$htaccess
    fi

    #  echo 'Adding TLS REDIRECT .htaccess rule'
    #  echo "RewriteCond %{HTTP_HOST} ^${HOST}\.com [NC]"  >> $htaccess
    #  echo "RewriteRule ^(.*)$ https://${HOST}/$1 [R,L]"  >> $htaccess
    #fi

fi

# robots.txt is owned by the proud-robots.php mu-plugin via WordPress's robots_txt filter
# (wp-proudcity#2846). Remove any leftover physical file so Apache doesn't serve it
# before WP gets the request.
rm -f /app/wordpress/robots.txt

# Set up php.ini config defaults
export PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-"128M"}
export UPLOAD_MAX_FILESIZE=${UPLOAD_MAX_FILESIZE:-"25M"}

# enable redis requires the Redis Cache plugin to be active
echo "Enabling Redis"
wp plugin activate redis-cache --allow-root
wp redis enable --allow-root

exec "$@"
