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
 */
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
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

define( 'PROUD_CRON_INTEGRITY_PLUGIN', dirname( __DIR__ ) . '/www/wp-content/mu-plugins/proud-cron-integrity.php' );

if ( file_exists( PROUD_CRON_INTEGRITY_PLUGIN ) ) {
	require_once PROUD_CRON_INTEGRITY_PLUGIN;
}
