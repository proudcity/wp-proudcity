#!/bin/bash
set -e

# Clone private repos
if [[ $GOOGLE_GIT_TOKEN ]]; then

  # Add gcloud
  echo "machine source.developers.google.com login jeff@proudcity.com password ${GOOGLE_GIT_TOKEN}" >> $HOME/.netrc

  # Install Gravity Forms
  echo "Adding Gravity Forms"
  git clone https://source.developers.google.com/p/proudcity-1184/r/gravityforms /app/wordpress/wp-content/plugins/gravityforms
  # @todo: `mv` doesn't work because of a Docker FS bug: https://github.com/docker/docker/issues/4570
  cp -r /app/wordpress/wp-content/plugins/gravityforms/modules/* /app/wordpress/wp-content/plugins
  rm -r /app/wordpress/wp-content/plugins/gravityforms/modules

  echo "Adding JoomUnited plugins"
  git clone https://source.developers.google.com/p/proudcity-1184/r/wp-media-folder /app/wordpress/wp-content/plugins/wp-media-folder

  # Install other non-free plugins
  echo "Adding non-free plugins"
  cd /app/wordpress/wp-content/plugins

  # Add custom themes, comma separated
  if [[ $WORDPRESS_THEMES ]]; then
    cd /app/wordpress/wp-content/themes
    export IFS=","
    for s in $WORDPRESS_THEMES; do
      cmd="git clone ${s}"
      eval $cmd
      echo "Adding theme repo: ${s} in `pwd`"
    done
  fi

  # Add custom plugins, comma separated
  if [[ $WORDPRESS_PLUGINS ]]; then
    cd /app/wordpress/wp-content/plugins
    export IFS=","
    for s in $WORDPRESS_PLUGINS; do
      cmd="git clone ${s}"
      eval $cmd
      echo "Adding plugin repo: ${s} in `pwd`"
    done
  fi

  # Add wordpress.org plugins, comma separated
  if [[ $WORDPRESSORG_PLUGINS ]]; then
    cd /app/wordpress/wp-content/plugins
    export IFS=","
    for s in $WORDPRESSORG_PLUGINS; do
      cmd="curl -O -L http://downloads.wordpress.org/plugin/${s}.zip && unzip ${s}.zip && rm ${s}.zip"
      eval $cmd
      echo "Adding WP.org plugin: ${s} in `pwd`"
    done
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

  #if [$TLS == "true" ]; then
  #  echo 'Adding TLS REDIRECT .htaccess rule'
  #  echo "RewriteCond %{HTTP_HOST} ^${HOST}\.com [NC]"  >> $htaccess
  #  echo "RewriteRule ^(.*)$ https://${HOST}/$1 [R,L]"  >> $htaccess
  #fi

fi

# Set up php.ini config defaults
export PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-"128M"}
export UPLOAD_MAX_FILESIZE=${UPLOAD_MAX_FILESIZE:-"25M"}

exec "$@"
