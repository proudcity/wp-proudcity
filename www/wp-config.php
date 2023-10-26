<?php

define('WP_DEBUG', 		getenv( "WP_DEBUG") );
define( 'WP_DEBUG_LOG', getenv( "WP_DEBUG" ) );
define('SAVEQUERIES', 	getenv( "WP_DEBUG" ) );

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

// Support legacy sites without an explicit APP env variable.
define("APP", (getenv('APP') ? getenv('APP') : getenv('WORDPRESS_DB_NAME')));

// $url = parse_url($db);
define("DB_NAME", trim(getenv("WORDPRESS_DB_NAME")));
define("DB_USER", trim(getenv("WORDPRESS_DB_USER")));
define("DB_PASSWORD", trim(getenv("WORDPRESS_DB_PASSWORD")));
define("DB_HOST", trim(getenv("WORDPRESS_DB_HOST")) . ':' . trim(getenv("WORDPRESS_DB_PORT")));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Auth keys and salts
// https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service
define( 'AUTH_KEY',         getenv('WORDPRESS_AUTH_KEY'));
define( 'SECURE_AUTH_KEY',  getenv('WORDPRESS_SECURE_AUTH_KEY'));
define( 'LOGGED_IN_KEY',    getenv('WORDPRESS_LOGGED_IN_KEY'));
define( 'NONCE_KEY',        getenv('WORDPRESS_NONCE_KEY'));
define( 'AUTH_SALT',        getenv('WORDPRESS_AUTH_SALT'));
define( 'SECURE_AUTH_SALT', getenv('WORDPRESS_SECURE_AUTH_SALT'));
define( 'LOGGED_IN_SALT',   getenv('WORDPRESS_LOGGED_IN_SALT'));
define( 'NONCE_SALT',       getenv('WORDPRESS_NONCE_SALT'));

// Set the cookie lifetime, default to 14 days
if ( !empty(getenv("COOKIE_LIFETIME")) ) {
    define( 'COOKIE_LIFETIME', getenv("COOKIE_LIFETIME") );
}
else {
    define( 'COOKIE_LIFETIME', 1209600 ); // Default: 14 days
}

// wp-stateless
define( 'WP_STATELESS_MEDIA_BUCKET', getenv('STATELESS_MEDIA_BUCKET'));
define( 'WP_STATELESS_MEDIA_MODE', 'ephemeral');
define( 'WP_STATELESS_MEDIA_ROOT_DIR', getenv('STATELESS_MEDIA_DIRECTORY') . '/%date_year/date_month%/');
define( 'WP_STATELESS_MEDIA_REWRITE_BODY_CONTENT_URL', 1);
define( 'WP_STATELESS_COMPATIBILITY_GF', 1);
define( 'WP_STATELESS_MEDIA_CACHE_BUSTING', getenv('STATELESS_MEDIA_CACHE_BUSTING') ?? false);
define( 'WP_STATELESS_MEDIA_BODY_REWRITE', 'false');



// For ProudCity, to fix issues related to migration
define( 'PROUD_WP_STATELESS_FORCE', getenv('PROUD_WP_STATELESS_FORCE'));

define( 'WP_STATELESS_MEDIA_KEY_FILE_PATH', getenv('STATELESS_MEDIA_KEY_FILE_PATH'));
//define( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT', 	getenv('STATELESS_MEDIA_SERVICE_ACCOUNT')); // @todo: this is only referenced in readme, not in the actual wp-stateless code

// wp-mail-smtp settings
define('WPMS_ON', true);
define('WPMS_MAIL_FROM', 'notify@proudcity.com');
define('WPMS_MAIL_FROM_NAME', 'ProudCity');
define('WPMS_MAILER', 'smtp'); // Possible values 'smtp', 'mail', or 'sendmail'
define('WPMS_SET_RETURN_PATH', 'false'); // Sets $phpmailer->Sender if true
define('WPMS_SMTP_PORT', 587); // The SMTP server port number
define('WPMS_SSL', 'tls'); // Possible values '', 'ssl', 'tls' - note TLS is not STARTTLS
define('WPMS_SMTP_AUTH', true); // True turns on SMTP authentication, false turns it off
define('WPMS_SMTP_HOST', getenv('SMTP_HOST')); // The SMTP mail host
define('WPMS_SMTP_USER', getenv('SMTP_USER')); // SMTP authentication username, only used if WPMS_SMTP_AUTH is true
define('WPMS_SMTP_PASS', getenv('SMTP_PASS')); // SMTP authentication password, only used if WPMS_SMTP_AUTH is true

// Varnish settings
define('WP_CACHE', getenv("WP_CACHE"));
define('WP_CACHE_KEY_SALT', getenv("HOST"));
//WP_REDIS_BACKEND_PORT
//WP_REDIS_BACKEND_D
//WP_REDIS_SERIALIZER

// Sets up our Redis configuration
// The WP plugin is required: https://en-ca.wordpress.org/plugins/redis-cache/
if ( getenv( 'WP_REDIS_HOST' ) ){
	define( 'WP_REDIS_PREFIX', getenv('HOST') );
	define( 'WP_REDIS_HOST', getenv('WP_REDIS_HOST') );
	define( 'WP_REDIS_TIMEOUT', 1 );
	define( 'WP_REDIS_READ_TIMEOUT', 1 );
}

// Elasticpress / search settings
if ( getenv( 'ELASTICSEARCH_HOST' ) ) {
  define( 'EP_HOST', 'http://' . getenv('ELASTICSEARCH_HOST'). ':9200' );
}
if ( getenv( 'ELASTICSEARCH_DOCS_HOST' ) ) {
  define('EP_HELPER_HOST', 'http://' . getenv( 'ELASTICSEARCH_DOCS_HOST' ) . '/send-attachments');
}


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

/**
 * Defines the environment type for WordPress. This lets us run different
 * settings on different sites.
 *
 * https://make.wordpress.org/core/2020/08/27/wordpress-environment-types/
 */
$environment_type = 'production';
if ( ! empty( getenv( 'ENV_TYPE' ) ) ){
    $environment_type = getenv( 'ENV_TYPE' );
}
define( 'WP_ENVIRONMENT_TYPE', (string) $environment_type );


$url = (getenv('TLS') === 'true' ? 'https' : 'http') . '://' . getenv('HOST');
define("WP_SITEURL", $url.'/');
define('WP_HOME', $url);


if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] === 'GoogleHC/1.0') {
  echo 'OK';
  exit;
}

if (getenv('TLS') === 'true'){
    $_SERVER['HTTPS'] = 'on';
    if (
        isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === 'http' ||
        ( !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != getenv('HOST') )
    ) {
        $redirect = 'https://' . getenv('HOST') . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 302 Moved Temporarily');
        header('Location: ' . $redirect);
        exit();
    }
}


require_once(ABSPATH . 'wp-settings.php');
