#!/bin/bash
set -e

# Clone private repos
if [[ $GOOGLE_GIT_TOKEN ]]; then

  # Fail silently if a git repo fails to clone
  set +e

  # Add gcloud
  echo "machine source.developers.google.com login jeff@proudcity.com password ${GOOGLE_GIT_TOKEN}" >> "$HOME"/.netrc

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

  #echo "Adding wp-rocket plugins"
  #git clone https://source.developers.google.com/p/proudcity-1184/r/wp-rocket /app/wordpress/wp-content/plugins/wp-rocket
  #git clone https://source.developers.google.com/p/proudcity-1184/r/wp-rocket-cache-rest-api /app/wordpress/wp-content/plugins/wp-rocket-cache-rest-api

  # Install other non-free plugins
  echo "Adding non-free plugins"
  cd /app/wordpress/wp-content/plugins

  # Add custom themes, comma separated. Ensure that we fail silently.
  if [[ $WORDPRESS_THEMES ]]; then
    cd /app/wordpress/wp-content/themes
    export IFS=","
    for s in $WORDPRESS_THEMES; do
      cmd="git clone ${s}"
      eval "$cmd"
      echo "Adding theme repo: ${s} in $(pwd)"
    done
  fi

  # Add custom plugins, comma separated. Ensure that we fail silently.
  if [[ $WORDPRESS_PLUGINS ]]; then
    cd /app/wordpress/wp-content/plugins
    export IFS=","
    for s in $WORDPRESS_PLUGINS; do
      cmd="git clone ${s}"
      eval "$cmd"
      echo "Adding plugin repo: ${s} in $(pwd)"
    done
  fi

  # Add wordpress.org plugins, comma separated
  if [[ $WORDPRESSORG_PLUGINS ]]; then
    cd /app/wordpress/wp-content/plugins
    export IFS=","
    for s in $WORDPRESSORG_PLUGINS; do
      cmd="curl -O -L http://downloads.wordpress.org/plugin/${s}.zip && unzip ${s}.zip && rm ${s}.zip"
      eval "$cmd"
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

  htaccess=/app/wordpress/.htaccess
  # Add domain redirects to .htaccess as CSV (from, to) with newlines between each redirect
  if [[ $REDIRECTS ]]; then
    export IFS=","
    echo "${REDIRECTS}" > /tmp/redirects.csv
    while read from to; do
      echo "RewriteCond %{HTTP_HOST} ^${from}$ [NC]" >> $htaccess
      echo "RewriteRule ^(.*)$ ${to} [R=301,L]" >> $htaccess
      echo "Adding redirect from ${from} to ${to}"
    done < /tmp/redirects.csv
  fi

  # deny URLS
	if [[ $BLOCK_LOGIN ]]; then
		echo "RewriteCond %{REQUEST_URI} ^/wp-login\.php$ [NC]" >> $htaccess
		echo "RewriteCond %{QUERY_STRING} !action=(logout|postpass) [NC]" >> $htaccess
		echo "RewriteCond %{QUERY_STRING} !loggedout=true [NC]" >> $htaccess
		echo "RewriteCond %{QUERY_STRING} !redirect_to=.* [NC]" >> $htaccess
		echo "RewriteRule ^(.*)$ - [F,L]" >> $htaccess
	fi

  #if [$TLS == "true" ]; then
  #  echo 'Adding TLS REDIRECT .htaccess rule'
  #  echo "RewriteCond %{HTTP_HOST} ^${HOST}\.com [NC]"  >> $htaccess
  #  echo "RewriteRule ^(.*)$ https://${HOST}/$1 [R,L]"  >> $htaccess
  #fi

fi

# creating dynamic robots.txt file
robots=/app/wordpress/robots.txt
if [[ $HOST ]]; then
	cd /app/wordpress
	echo "# START YOAST BLOCK" >> $robots
	echo "# ----" >> $robots
	echo "User-agent: *" >> $robots
	echo "Disallow: /wp-content/redis-error.php" >> $robots
	echo " " >> $robots
	echo "Sitemap: https://${HOST}/sitemap_index.xml" >> $robots
	echo "# ----" >> $robots
	echo "# END YOAST BLOCK" >> $robots
fi


# Set up php.ini config defaults
export PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-"128M"}
export UPLOAD_MAX_FILESIZE=${UPLOAD_MAX_FILESIZE:-"25M"}

# Set up redis session configuration
# Note: Sep 2022: we no longer do anything with REDIS
# if [[ $REDIS_SESSION == "1" ]]; then
#   redisfile=/usr/local/etc/php/conf.d/redissessions.ini
#   echo "session.save_handler = redis" > $redisfile
#   echo "session.save_path = ${WORDPRESS_DB_NAME}redis:6379" >> $redisfile
# fi

# Ensure that we have the latest wp-rocket con
echo "Importing WP Rocket configuration"
# wp rocket import --allow-root /app/bin/wp-rocket.json

# enable redis requires the Redis Cache plugin to be active
echo "Enabling Redis"
# wp redis enable --allow-root

exec "$@"

# #!/bin/sh
# # set -e

# # first arg is `-f` or `--some-option`
# if [ "${1#-}" != "$1" ]; then
# 	set -- apache2-foreground "$@"
# fi

# # exec "$@"
# cd /app/wordpress
# apache2-foreground