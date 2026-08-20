<?php
/**
 * Plugin Name: Loopback Basic Auth
 * Description: Attaches the site's own HTTP Basic Auth credentials to the two loopback URLs
 *              WordPress calls on itself: wp-cron.php and wp-admin/admin-ajax.php. Sites gated
 *              by the AUTH_REQUIRED env var (wp-proud-core's ProudAuthentication module) answer
 *              every unauthenticated request with a 401, including WordPress's own loopbacks.
 *              That breaks WP-Cron and anything built on the deliciousbrains background-processing
 *              library, including the WP-Stateless 4.x data migrations. See wp-proudcity#2889.
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize a URL down to the parts we are willing to compare: scheme, host, port, path.
 *
 * Comparing the whole origin rather than just the host matters. wp_parse_url() with
 * PHP_URL_HOST drops both the scheme and the port, so a host-only check would happily
 * attach credentials to http:// (cleartext, and base64 is not encryption) or to another
 * service sharing the hostname on a different port. Query and fragment are dropped
 * because the background processor appends its own action/nonce args.
 *
 * @param string $url URL to normalize.
 *
 * @return string|false Normalized "scheme://host:port/path", or false if unparseable.
 */
function proudcity_loopback_normalize_url( $url ) {
	$parts = wp_parse_url( $url );

	if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return false;
	}

	$scheme   = strtolower( $parts['scheme'] );
	$defaults = array(
		'http'  => 80,
		'https' => 443,
	);

	if ( ! isset( $defaults[ $scheme ] ) ) {
		return false;
	}

	$port = isset( $parts['port'] ) ? (int) $parts['port'] : $defaults[ $scheme ];
	$path = isset( $parts['path'] ) ? $parts['path'] : '/';

	return $scheme . '://' . strtolower( $parts['host'] ) . ':' . $port . $path;
}

/**
 * Add an Authorization header to this site's own loopback requests when the Basic Auth wall is on.
 *
 * Why http_request_args rather than patching each caller: every loopback in core and in
 * plugins goes through WP_Http::request(), so filtering the args once covers WP-Cron and
 * the deliciousbrains background-processing library WP-Stateless uses for its 4.x data
 * migrations. Patching callers would mean patching third-party code.
 *
 * Why an exact URL allowlist rather than a host match: this must never send our credentials
 * anywhere but ourselves. A host match would also cover every other same-host request the
 * install makes -- link checking, oEmbed discovery, update checks -- turning any same-host
 * SSRF sink into a way to make the site emit its own credentials on an attacker-influenced
 * path. Only two URLs need this, so only two URLs get it.
 *
 * Why redirection => 0: the host/URL check can only see the URL we are about to request.
 * WP delegates redirect following to the bundled Requests library, which re-sends the
 * original headers to the new location WITHOUT re-running http_request_args and WITHOUT
 * stripping Authorization on a host change. A single off-host redirect would therefore
 * hand our credentials to whoever we were redirected to. Neither wp-cron.php nor
 * admin-ajax.php has any business redirecting, so refusing to follow costs nothing.
 *
 * Why we bail on a string $args['headers']: WP accepts headers as a raw string and only
 * normalizes them to an array AFTER this filter runs, so we cannot reliably inspect or
 * extend them here. Leaving such a request alone is better than silently discarding the
 * caller's headers.
 *
 * The 'wordpress' value of AUTH_REQUIRED is a redirect-to-my.proudcity.com gate, not a
 * 401 wall, so there is nothing to authenticate against and we leave those sites alone.
 * AUTH_REQUIRED='0' is the fleet's "off" value and is falsy to both empty() here and the
 * truthiness check in ProudAuthentication::checkAuthentication(), so the two agree.
 *
 * Note for future maintainers: the header set here is visible to anything hooked on
 * 'http_api_debug', which receives the fully filtered args. Do not add a logger there
 * that dumps headers.
 *
 * @param array  $args HTTP request arguments.
 * @param string $url  The request URL.
 *
 * @return array
 */
add_filter(
	'http_request_args',
	function ( $args, $url ) {
		$auth_required = getenv( 'AUTH_REQUIRED' );

		if ( empty( $auth_required ) || 'wordpress' === $auth_required ) {
			return $args;
		}

		$user = getenv( 'AUTH_USERNAME' );
		$pass = getenv( 'AUTH_PASSWORD' );

		if ( false === $user || false === $pass || '' === $user ) {
			return $args;
		}

		// Headers as a string are not safe to inspect or extend at this point; leave them be.
		if ( isset( $args['headers'] ) && ! is_array( $args['headers'] ) ) {
			return $args;
		}

		if ( ! empty( $args['headers'] ) ) {
			foreach ( array_keys( $args['headers'] ) as $header ) {
				if ( 0 === strcasecmp( $header, 'Authorization' ) ) {
					return $args;
				}
			}
		}

		$target = proudcity_loopback_normalize_url( $url );

		if ( false === $target ) {
			return $args;
		}

		$allowed = array_filter(
			array_map(
				'proudcity_loopback_normalize_url',
				array(
					admin_url( 'admin-ajax.php' ),
					site_url( 'wp-cron.php' ),
				)
			)
		);

		if ( ! in_array( $target, $allowed, true ) ) {
			return $args;
		}

		if ( ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
			$args['headers'] = array();
		}

		$args['headers']['Authorization'] = 'Basic ' . base64_encode( $user . ':' . $pass );

		// Do not let a redirect carry the credentials to another host. See docblock.
		$args['redirection'] = 0;

		return $args;
	},
	10,
	2
);
