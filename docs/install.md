## 5-minute setup
1. Install [Composer](https://getcomposer.org) and [WP-CLI](http://wp-cli.org/).
  ```
  curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
  curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar | chmod +x wp-cli.phar | sudo mv wp-cli.phar /usr/local/bin/wp
  ```

2. Clone and build code
  ```
  git clone https://github.com/proudcity/wp-proudcity.git
  cd wp-proudcity
  composer install --no-dev
  cp wp-config.php wordpress
  # Edit your wp-config.php values
  ```
3. Copy the contents of `./wordpress` to the `www-root` of a server and [install WordPress as normal](https://codex.wordpress.org/Installing_WordPress#Famous_5-Minute_Install)

4. Install WordPress plugins 

#### Wordpress Plugins (list last updated 1/1/2018)
  ```
  # Third Party
  wp plugin activate --allow-root \
    admin-menu-editor \
    intuitive-custom-post-order \
    disable-comments \
    events-manager \
    iframe \
    mce-table-buttons \
    siteorigin-panels \
    safe-redirect-manager \
    simple-staff-list \
    so-widgets-bundle \
    wordpress-faq-manager \
    wp-job-manager \
    wordpress-seo \
    wp-quick-menu \
    say-what                    

  # ProudCity
  wp plugin activate --allow-root \
    wp-proud-core \
    wp-proud-actions-app \
    wp-proud-admin \
    wp-proud-agency \
    wp-proud-dashboard \
    wp-proud-directory \
    wp-proud-document \
    wp-proud-issue \
    wp-proud-location \
    wp-proud-map-app \
    wp-proud-payment \
    wp-proud-search
  ```
  These commands require [wp-cli](http://wp-cli.org/).