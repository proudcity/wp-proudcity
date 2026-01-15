#!/bin/bash
set -e

# Clone private repos
if [[ $GOOGLE_GIT_TOKEN ]]; then

    # Fail silently if a git repo fails to clone
    set +e

    # Add gcloud
    echo "machine source.developers.google.com login jeff@proudcity.com password ${GOOGLE_GIT_TOKEN}" >>$HOME/.netrc

    # making sure google repositories exist
    #until $( curl --output /dev/null --silent --head --fail https://source.developers.google.com ); do
    #  echo 'Google repository not available'
    #  sleep 5
    #done

    #echo 'Google repository available'

    # Install Gravity Forms
    #echo "Exploding Gravity Forms"
    #git clone https://source.developers.google.com/p/proudcity-1184/r/gravityforms /app/wordpress/wp-content/plugins/gravityforms
    # @todo: `mv` doesn't work because of a Docker FS bug: https://github.com/docker/docker/issues/4570
    #cp -r /app/wordpress/wp-content/plugins/gravityforms/modules/* /app/wordpress/wp-content/plugins
    #rm -r /app/wordpress/wp-content/plugins/gravityforms/modules

    #echo "Adding JoomUnited plugins"
    #git clone https://source.developers.google.com/p/proudcity-1184/r/wp-media-folder /app/wordpress/wp-content/plugins/wp-media-folder

    # Install other non-free plugins
    echo "Adding non-free plugins"
    cd /app/wordpress/wp-content/plugins

    # Add custom themes, comma separated. Ensure that we fail silently.
    if [[ $WORDPRESS_THEMES ]]; then
        cd /app/wordpress/wp-content/themes
        export IFS=","
        for s in $WORDPRESS_THEMES; do
            cmd="git clone ${s}"
            eval $cmd
            echo "Adding theme repo: ${s} in $(pwd)"
        done
    fi

    # Add custom plugins, comma separated. Ensure that we fail silently.
    if [[ $WORDPRESS_PLUGINS ]]; then
        cd /app/wordpress/wp-content/plugins
        export IFS=","
        for s in $WORDPRESS_PLUGINS; do
            cmd="git clone ${s}"
            eval $cmd
            echo "Adding plugin repo: ${s} in $(pwd)"
        done
    fi

    # Add wordpress.org plugins, comma separated
    if [[ $WORDPRESSORG_PLUGINS ]]; then
        cd /app/wordpress/wp-content/plugins
        export IFS=","
        for s in $WORDPRESSORG_PLUGINS; do
            cmd="curl -O -L http://downloads.wordpress.org/plugin/${s}.zip && unzip ${s}.zip && rm ${s}.zip"
            eval $cmd
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

# creating dynamic robots.txt file
robots=/app/wordpress/robots.txt
if [[ $HOST ]]; then
    cd /app/wordpress
    echo "# START YOAST BLOCK" >>$robots
    echo "# ----" >>$robots
    echo "User-agent: *" >>$robots
    echo "Disallow: /wp-content/redis-error.php" >>$robots
    echo "User-agent: ShapBot" >>$robots
    echo "Disallow: /" >>$robots
    echo "User-agent: Amazonbot" >>$robots
    echo "Disallow: /" >>$robots
    echo "User-agent: rogerbot" >>$robots
    echo "Disallow: /" >>$robots
    echo "User-agent: dotbot" >>$robots
    echo "Disallow: /" >>$robots
    echo "User-agent: MJ12bot" >>$robots
    echo "Disallow: /" >>$robots
    echo "User-agent: RecordedFuture-ASI" >>$robots
    echo "Disallow: /" >>$robots
    echo " " >>$robots
    echo "Sitemap: https://${HOST}/sitemap_index.xml" >>$robots
    echo "# ----" >>$robots
    echo "# END YOAST BLOCK" >>$robots
fi

# Set up php.ini config defaults
export PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-"128M"}
export UPLOAD_MAX_FILESIZE=${UPLOAD_MAX_FILESIZE:-"25M"}

# enable redis requires the Redis Cache plugin to be active
echo "Enabling Redis"
wp redis enable --allow-root

exec "$@"
