<?php

define('WP_CACHE', getenv("WP_CACHE"));
define('WP_DEBUG', getenv("WP_DEBUG"));
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


//ini_set('display_errors', '0');

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
define("DB_NAME", trim(getenv("WORDPRESS_DB_NAME")));
define("DB_USER", trim(getenv("WORDPRESS_DB_USER")));
define("DB_PASSWORD", trim(getenv("WORDPRESS_DB_PASSWORD")));
define("DB_HOST", trim(getenv("WORDPRESS_DB_HOST")) . ':' . trim(getenv("WORDPRESS_DB_PORT")));

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


// wp-stateless
define( 'WP_STATELESS_MEDIA_BUCKET', getenv('STATELESS_MEDIA_BUCKET'));
define( 'WP_STATELESS_MEDIA_MODE', 'cdn');
define( 'WP_STATELESS_MEDIA_ROOT_DIR', getenv('STATELESS_MEDIA_DIRECTORY'));
define( 'WP_STATELESS_MEDIA_KEY_FILE_PATH', getenv('STATELESS_MEDIA_GOOGLE_KEY'));
define( 'WP_STATELESS_MEDIA_REWRITE_BODY_CONTENT_URL', 1);
// For ProudCity, to fix issues related to migration
define( 'PROUD_WP_STATELESS_FORCE', getenv('PROUD_WP_STATELESS_FORCE'));

//define( 'WP_STATELESS_MEDIA_KEY_FILE_PATH', '/etc/secrets/gcserviceaccount.json');
//define( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT', 	getenv('STATELESS_MEDIA_SERVICE_ACCOUNT'));


// wp-mail-smtp settings
define('WPMS_ON', true);
define('WPMS_MAIL_FROM', 'notify@proudcity.com');
define('WPMS_MAIL_FROM_NAME', 'ProudCity');
define('WPMS_MAILER', 'smtp'); // Possible values 'smtp', 'mail', or 'sendmail'
define('WPMS_SET_RETURN_PATH', 'false'); // Sets $phpmailer->Sender if true
define('WPMS_SMTP_PORT', 2587); // The SMTP server port number
define('WPMS_SSL', 'tls'); // Possible values '', 'ssl', 'tls' - note TLS is not STARTTLS
define('WPMS_SMTP_AUTH', true); // True turns on SMTP authentication, false turns it off
define('WPMS_SMTP_HOST', getenv('SMTP_HOST')); // The SMTP mail host
define('WPMS_SMTP_USER', getenv('SMTP_USER')); // SMTP authentication username, only used if WPMS_SMTP_AUTH is true
define('WPMS_SMTP_PASS', getenv('SMTP_PASS')); // SMTP authentication password, only used if WPMS_SMTP_AUTH is true



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
//define ('WPLANG', 'nb_NO');


//$url = (getenv('TLS') === 'true' ? 'https' : 'http') . '://' . getenv('HOST');
//define("WP_SITEURL", $url.'/');
//define('WP_HOME', $url);



if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] === 'GoogleHC/1.0') {
  echo 'OK';
  exit;
}

if (getenv('TLS') === 'true' && isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === 'http') {
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
