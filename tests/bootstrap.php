<?php
/**
 * PHPUnit bootstrap for wp-proudcity mu-plugin tests.
 *
 * Defines ABSPATH before any mu-plugin is required, since every mu-plugin in
 * www/wp-content/mu-plugins opens with `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
 */

require_once __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/*
 * The mu-plugin registers its filters/actions at file-load time, the same way
 * every other mu-plugin in www/wp-content/mu-plugins does. These plain
 * fallbacks exist only so that one-time `require` below does not fatal; the
 * hook *dispatch* itself is never under test, only the callbacks it points at,
 * which tests call directly.
 *
 * `add_filter()` also records every call into $GLOBALS['proud_test_added_filters']
 * so a test can assert *which* filters a function registered without Brain Monkey:
 * Patchwork can't redefine a function this file already defined outside its own
 * interception (it throws DefinedTooEarly), so `Functions\when('add_filter')` isn't
 * available the way it is for functions, like `remove_filter()`, that core itself
 * would otherwise provide and that are left undefined here.
 */
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['proud_test_added_filters'][] = array( $hook, $callback, $priority, $accepted_args );

		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
	function maybe_unserialize( $data ) {
		if ( ! is_string( $data ) ) {
			return $data;
		}

		$unserialized = @unserialize( $data );

		if ( false !== $unserialized || serialize( false ) === $data ) {
			return $unserialized;
		}

		return $data;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

/*
 * Minimal stand-ins for the two core classes proud-block-password-login.php's
 * `authenticate` callbacks check with `instanceof`/`is_wp_error()`. Only the bits
 * those callbacks and their tests actually use are implemented.
 */
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		protected $errors = array();

		public function __construct( $code = '', $message = '' ) {
			if ( '' !== $code ) {
				$this->add( $code, $message );
			}
		}

		public function add( $code, $message ) {
			$this->errors[ $code ][] = $message;
		}

		public function get_error_codes() {
			return array_keys( $this->errors );
		}

		public function get_error_code() {
			$codes = $this->get_error_codes();

			return $codes ? $codes[0] : '';
		}

		public function has_errors() {
			return (bool) $this->errors;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {
		public $ID;

		public function __construct( $id = 0 ) {
			$this->ID = $id;
		}
	}
}

define( 'PROUD_CRON_INTEGRITY_PLUGIN', dirname( __DIR__ ) . '/www/wp-content/mu-plugins/proud-cron-integrity.php' );

if ( file_exists( PROUD_CRON_INTEGRITY_PLUGIN ) ) {
	require_once PROUD_CRON_INTEGRITY_PLUGIN;
}

define( 'PROUD_BLOCK_PASSWORD_LOGIN_PLUGIN', dirname( __DIR__ ) . '/www/wp-content/mu-plugins/proud-block-password-login.php' );

if ( file_exists( PROUD_BLOCK_PASSWORD_LOGIN_PLUGIN ) ) {
	require_once PROUD_BLOCK_PASSWORD_LOGIN_PLUGIN;
}
