<?php
/**
 * The base configurations of a Dockerized WordPress.
 * You can find more information by visiting
 * {@link http://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 */

# Load the .env if present.
if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'.env')) {
  require 'vendor/autoload.php';
  $dotenv = new Dotenv\Dotenv(__DIR__);
  $dotenv->load();
}

# The name of the database for WordPress.
define('DB_NAME', getenv('DB_NAME'));

# MySQL database username.
define('DB_USER', getenv('DB_USER'));

# MySQL database password.
define('DB_PASSWORD', getenv('DB_PASS'));

# MySQL hostname.
define('DB_HOST', getenv('DB_HOST').":".getenv('DB_PORT'));

# Database Charset to use in creating database tables.
define('DB_CHARSET', getenv('WORDPRESS_DB_CHARSET'));

# The Database Collate type. Don't change this if in doubt.
define('DB_COLLATE', getenv('WORDPRESS_DB_COLLATE'));

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         getenv('WORDPRESS_AUTH_KEY'));
define('SECURE_AUTH_KEY',  getenv('WORDPRESS_SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY',    getenv('WORDPRESS_LOGGED_IN_KEY'));
define('NONCE_KEY',        getenv('WORDPRESS_NONCE_KEY'));
define('AUTH_SALT',        getenv('WORDPRESS_AUTH_SALT'));
define('SECURE_AUTH_SALT', getenv('WORDPRESS_SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT',   getenv('WORDPRESS_LOGGED_IN_SALT'));
define('NONCE_SALT',       getenv('WORDPRESS_NONCE_SALT'));

# Enable Amazon S3 and Cloudfront.
define('AWS_ACCESS_KEY_ID', getenv('WORDPRESS_AWS_ACCESS_KEY_ID'));
define('AWS_SECRET_ACCESS_KEY', getenv('WORDPRESS_AWS_SECRET_ACCESS_KEY'));

# Enable WP Cache.
define('WP_CACHE', getenv('WORDPRESS_WP_CACHE'));
define('WPCACHEHOME', getenv('WORDPRESS_WPCACHEHOME')); //Added by WP-Cache Manager

# Enable Secure Logins.
define('FORCE_SSL_LOGIN', getenv('WORDPRESS_FORCE_SSL_LOGIN'));
define('FORCE_SSL_ADMIN', getenv('WORDPRESS_FORCE_SSL_ADMIN'));

# Use external cron.
define('DISABLE_WP_CRON', getenv('WORDPRESS_DISABLE_WP_CRON'));

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = getenv('WORDPRESS_TABLE_PREFIX');

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', getenv('WORDPRESS_WPLANG'));

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', getenv('WORDPRESS_WP_DEBUG'));

# Absolute path to the WordPress directory.
if (!defined('ABSPATH'))
  define('ABSPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);

# To enable the possibility to update plugins directly from back-end (locally).
define('FS_METHOD', getenv('WORDPRESS_FS_METHOD'));

# Disable updates of plugins and themes together with plugins and themes editor.
define('DISALLOW_FILE_MODS', getenv('WORDPRESS_DISALLOW_FILE_MODS'));

# Disable all automatic updates.
define('AUTOMATIC_UPDATER_DISABLED', getenv('WORDPRESS_AUTOMATIC_UPDATER_DISABLED'));

# How to handle all core updates.
define('WP_AUTO_UPDATE_CORE', getenv('WORDPRESS_WP_AUTO_UPDATE_CORE'));

# WP Siteurl.
define('WP_SITEURL', getenv('WORDPRESS_WP_SITEURL'));

# WP Home.
define('WP_HOME', getenv('WORDPRESS_WP_HOME'));

# Enable Multisite.
define('WP_ALLOW_MULTISITE', getenv('WORDPRESS_WP_ALLOW_MULTISITE'));

# Uploads directory.
define('UPLOADS', getenv('WORDPRESS_UPLOADS'));

/** Sets up WordPress vars and included files. */
require_once(ABSPATH.'wp-settings.php');