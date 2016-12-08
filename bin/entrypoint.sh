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

  # Install other non-free plugins
  echo "Adding non-free plugins"
  cd /app/wordpress/wp-content/plugins
  git clone https://source.developers.google.com/p/proudcity-1184/r/auth0

  # Add custom themes
  if [[ $WORDPRESS_THEMES ]]; then
    echo $WORDPRESS_THEMES > /tmp/themes
    cd /app/wordpress/wp-content/plugins
    while read s; do
      git clone "${s}"
      echo "Adding theme repo: ${s}"
    done < /tmp/themes
  fi

  # Add custom plugins
  if [[ $WORDPRESS_PLUGINS ]]; then
    echo $WORDPRESS_PLUGINS > /tmp/plugins
    cd /app/wordpress/wp-content/plugins
    while read s; do
      git clone "${s}"
      echo "Adding plugin repo: ${s}"
    done < /tmp/plugins
  fi

fi

exec "$@"
