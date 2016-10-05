<?php

define('WP_CACHE', true);
define('WP_DEBUG', false);
define('SAVEQUERIES', true);

/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */


ini_set('display_errors', '0');

// $db = getenv("CLEARDB_DATABASE_URL");
// $secretFile = '/etc/secrets/sql';
// if (file_exists($secretFile)) {
//     $db = file_get_contents($secretFile);
// }

// $wpSecrets = [];
// $wpSecretFile = '/etc/secrets/wpsecrets';
// if (file_exists($wpSecretFile)) {
//     $wpSecrets = json_decode(file_get_contents($wpSecretFile), true);
//     // error_log("We got data from /etc/secrets/wpsecrets");
// }


// $url = parse_url($db);
define("DB_NAME", getenv("WORDPRESS_DB_NAME"));
define("DB_USER", getenv("WORDPRESS_DB_USER"));
define("DB_PASSWORD", getenv("WORDPRESS_DB_PASSWORD"));
define("DB_HOST", getenv("WORDPRESS_DB_HOST"));


define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */


define( 'AUTH_KEY',         getenv('AUTH_KEY'));
define( 'SECURE_AUTH_KEY',  getenv('SECURE_AUTH_KEY'));
define( 'LOGGED_IN_KEY',    getenv('LOGGED_IN_KEY'));
define( 'NONCE_KEY',        getenv('NONCE_KEY'));
define( 'AUTH_SALT',        getenv('AUTH_SALT'));
define( 'SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT'));
define( 'LOGGED_IN_SALT',   getenv('LOGGED_IN_SALT'));
define( 'NONCE_SALT',       getenv('NONCE_SALT'));


define( 'WP_STATELESS_MEDIA_BUCKET', 			  getenv('STATELESS_MEDIA_BUCKET'));
define( 'WP_STATELESS_MEDIA_MODE', 				  'cdn');
define( 'WP_STATELESS_MEDIA_ROOT_DIR',      'files/');
define( 'WP_STATELESS_MEDIA_KEY_FILE_PATH', '/etc/secrets/gcserviceaccount.json');
//define( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT', 	getenv('STATELESS_MEDIA_SERVICE_ACCOUNT'));



//define('JETPACK_DEV_DEBUG', false);

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define ('WPLANG', 'nb_NO');


/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', true);



$url = (getenv('TLS') === 'true' ? 'https' : 'http') . '://' . getenv('HOST');
define("WP_SITEURL", $url.'/');
define('WP_HOME', $url);


/* Special redirect and handlibg based upon headers.... */
$headersArr = apache_request_headers();
$headers = [];
foreach ($headersArr as $header => $value) {
    $headers[strtolower($header)] = $value;
}



if (isset($headers["user-agent"]) && $headers["user-agent"] === 'GoogleHC/1.0') {
  echo 'OK';
  exit;
}
if (getenv('TLS') === 'true' && isset($headers["x-forwarded-proto"]) && $headers["x-forwarded-proto"] === 'http') {
  $redirect = 'https://' . getenv('HOST') . $_SERVER['REQUEST_URI'];
  header('HTTP/1.1 301 Moved Permanently');
  header('Location: ' . $redirect);
  exit();
}
if (getenv('TLS') === 'true') {
  $_SERVER['HTTPS']='on';
}



/* ------ --- --- -- -- --- -- .... */



//  echo "\nWP_HOME " . WP_HOME ;
// * Point both directory and URLs to content/ instead of the default wp-content/ *
// if ( ! defined( 'WP_CONTENT_DIR' ) ) {
//     define( 'WP_CONTENT_DIR', __DIR__ . '/content' );
// }
// if ( ! defined( 'WP_CONTENT_URL' ) ) {
//     define( 'WP_CONTENT_URL', WP_HOME . '/content' );
// }
//  echo "\nWP_CONTENT_DIR " . WP_CONTENT_DIR;
//  echo "\nWP_CONTENT_URL " . WP_CONTENT_URL;
// echo "\nABSPATH " . ABSPATH;

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
// if ( !defined('ABSPATH') )
//    define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
