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
  cp .env.example wordpress/.env
  ```
3. Copy the contents of `./wordpress` to the `www-root` of a server and [install WordPress as normal](https://codex.wordpress.org/Installing_WordPress#Famous_5-Minute_Install)

4. Install WordPress plugins
  ```
  wp-plugin activate taxonomy-terms-order disable-comments events-manager google-analytics-dashboard-for-wp iframe mce-table-buttons siteorigin-panels wp-proud-document wp-proud-payment wp-proud-actions-app wp-proud-admin wp-proud-agency wp-proud-core wp-proud-map-app proudpack wp-proud-search wp-proud-social-app safe-redirect-manager simple-staff-list so-widgets-bundle wordpress-faq-manager wp-job-manager rest-api wp-api-menus wordpress-seo 
  ``