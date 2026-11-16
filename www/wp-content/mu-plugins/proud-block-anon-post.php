<?php
/**
 * Plugin Name: Proud Block Anonymous POST
 * Description: Refuses anonymous POST requests that aren't a form submission or a known
 *              endpoint, before the theme loads. An anonymous POST to an ordinary
 *              front-end URL otherwise boots all of WordPress and renders a full,
 *              uncached page (or a full 404): on 2026-10-22 one scanner sent about 1,350
 *              of them to sanrafael.gov in nine hours. Real POSTs come from logged-in
 *              users, people submitting a form, and a short list of known endpoints
 *              (see proud_anon_post_decide()); everything else can be refused cheaply.
 *              This is cost control, not a security boundary: anyone who adds
 *              `gform_submit=1` to the body still gets a full render, no worse than today.
 *              See wp-proudcity#2998.
 *
 *              Ships LOG-ONLY. Every refusal writes one `proud-anon-post:` line to
 *              stderr (field NAMES only, never values) and the request carries on as
 *              before. Enforcement is a one-line change to PROUD_ANON_POST_DEFAULT_MODE.
 *
 *              Env vars, set per deployment in the k8s manifest (strict comparison,
 *              same pattern as PC_ALLOW_PASSWORD_LOGIN):
 *                PC_ALLOW_ANON_POST=1          this mu-plugin does nothing on this site.
 *                PC_ANON_POST_MODE=log|enforce overrides the code default; any other
 *                                              value falls back to the code default.
 *
 *              The `proud_anon_post_allow` filter ( apply_filters( 'proud_anon_post_allow',
 *              false, $context ) ) lets a request through. The check runs at
 *              `muplugins_loaded`, before regular plugins load, so only other mu-plugins
 *              (or wp-config.php) can hook it in time.
 *
 *              Why a hook and not file-load time: mu-plugins load alphabetically, so this
 *              file loads BEFORE trust-proxy-client-ip.php, which rewrites REMOTE_ADDR at
 *              its own file-load time. By `muplugins_loaded` REMOTE_ADDR is the real
 *              client IP. Don't move the logic to file-load time.
 *
 *              Two stages, because wp_validate_auth_cookie() is pluggable and doesn't
 *              exist yet at `muplugins_loaded`: stage 1 handles the allowlist and the
 *              no-cookie case; a request carrying a `wordpress_logged_in_*` cookie is
 *              validated at `plugins_loaded` priority PHP_INT_MIN (stage 2).
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Code default for the mode. Change to 'enforce' to start refusing requests fleet-wide;
 * PC_ANON_POST_MODE can still override it per deployment.
 */
if ( ! defined( 'PROUD_ANON_POST_DEFAULT_MODE' ) ) {
	define( 'PROUD_ANON_POST_DEFAULT_MODE', 'log' );
}

/**
 * Whether this deployment has switched the mu-plugin off (PC_ALLOW_ANON_POST=1).
 * Compared strictly to the literal "1".
 *
 * @param string|false|null $env Optional raw env value to test against.
 *
 * @return bool
 */
function proud_anon_post_disabled( $env = null ) {
	if ( null === $env ) {
		$env = getenv( 'PC_ALLOW_ANON_POST' );
	}

	return '1' === $env;
}

/**
 * Effective mode: 'log' or 'enforce'. PC_ANON_POST_MODE wins when it is exactly one of
 * those; anything else (unset, empty, wrong case) falls back to the code default.
 *
 * @param string|false|null $env Optional raw env value to test against.
 *
 * @return string
 */
function proud_anon_post_mode( $env = null ) {
	if ( null === $env ) {
		$env = getenv( 'PC_ANON_POST_MODE' );
	}

	if ( 'log' === $env || 'enforce' === $env ) {
		return $env;
	}

	return 'enforce' === PROUD_ANON_POST_DEFAULT_MODE ? 'enforce' : 'log';
}

/**
 * The executed script relative to ABSPATH, or '' when it is outside ABSPATH.
 *
 * Matching the script that is actually running (not a substring of REQUEST_URI) means
 * `/library/wp-login.php` or `/library/?x=/wp-json/` can't match: with pretty permalinks
 * those run index.php. $GLOBALS['pagenow'] isn't set yet at `muplugins_loaded`.
 *
 * @param array  $server  $_SERVER.
 * @param string $abspath ABSPATH.
 *
 * @return string
 */
function proud_anon_post_script( array $server, $abspath ) {
	$script = isset( $server['SCRIPT_FILENAME'] ) && is_string( $server['SCRIPT_FILENAME'] ) ? $server['SCRIPT_FILENAME'] : '';
	$base   = rtrim( $abspath, '/' ) . '/';

	if ( '' === $script || 0 !== strpos( $script, $base ) ) {
		return '';
	}

	return substr( $script, strlen( $base ) );
}

/**
 * The URL path of the request, without the query string.
 *
 * @param array $server $_SERVER.
 *
 * @return string
 */
function proud_anon_post_request_path( array $server ) {
	$uri = isset( $server['REQUEST_URI'] ) && is_string( $server['REQUEST_URI'] ) ? $server['REQUEST_URI'] : '';
	$uri = explode( '#', explode( '?', $uri, 2 )[0], 2 )[0];

	return '' === $uri ? '/' : $uri;
}

/**
 * Decides what to do with a request. Pure apart from the `proud_anon_post_allow` filter.
 *
 * Returns:
 *   'allow:<rule>'    request is fine (rule is a short name)
 *   'block:no_cookie' anonymous and not on the allowlist
 *   'defer'           has a wordpress_logged_in_* cookie; validate it at plugins_loaded
 *
 * @param array       $server          $_SERVER.
 * @param array       $get             $_GET.
 * @param array       $post            $_POST.
 * @param array       $cookie          $_COOKIE.
 * @param string      $abspath         ABSPATH.
 * @param string      $gf_upload_slug  Value of the gform_upload_page_slug option ('' if none).
 * @param string|null $sapi            PHP_SAPI by default.
 *
 * @return string
 */
function proud_anon_post_decide( array $server, array $get, array $post, array $cookie, $abspath, $gf_upload_slug = '', $sapi = null ) {
	if ( null === $sapi ) {
		$sapi = PHP_SAPI;
	}

	$method = isset( $server['REQUEST_METHOD'] ) && is_string( $server['REQUEST_METHOD'] ) ? strtoupper( $server['REQUEST_METHOD'] ) : '';

	if ( 'POST' !== $method || 'cli' === $sapi || 'phpdbg' === $sapi ) {
		return 'allow:not_post';
	}

	$script = proud_anon_post_script( $server, $abspath );
	$names  = array(
		'wp-login.php'              => 'wp_login',
		'wp-admin/admin-ajax.php'   => 'admin_ajax',
		'wp-cron.php'               => 'wp_cron',
		'wp-comments-post.php'      => 'comments_post',
	);

	if ( isset( $names[ $script ] ) ) {
		return 'allow:' . $names[ $script ];
	}

	$path = proud_anon_post_request_path( $server );

	if ( '/wp-json' === $path || 0 === strpos( $path, '/wp-json/' ) || isset( $get['rest_route'] ) ) {
		return 'allow:rest';
	}

	// GF Stripe webhook: payments, must never be blocked. Mirrors class-gf-stripe.php.
	if ( ( isset( $get['callback'] ) && 'gravityformsstripe' === $get['callback'] ) || isset( $get['stripe_callback'] ) ) {
		return 'allow:gf_stripe';
	}

	if ( isset( $get['gf_page'] ) && is_string( $get['gf_page'] ) && '' !== $gf_upload_slug && $get['gf_page'] === $gf_upload_slug ) {
		return 'allow:gf_upload';
	}

	if ( isset( $get['mailoptin-ajax'] ) ) {
		return 'allow:mailoptin';
	}

	$post_keys = array(
		'gform_submit'             => 'gf_submit',
		'gform_send_resume_link'   => 'gf_resume_link',
		'search_term'              => 'search',
		'form-proud-teaser-filter' => 'teaser_filter',
	);

	foreach ( $post_keys as $key => $rule ) {
		if ( array_key_exists( $key, $post ) ) {
			return 'allow:' . $rule;
		}
	}

	if ( isset( $post['pct_translate_page'] ) && '1' === $post['pct_translate_page'] ) {
		return 'allow:translate';
	}

	$context = array(
		'path'   => proud_anon_post_request_path( $server ),
		'qkeys'  => array_map( 'strval', array_keys( $get ) ),
		'fields' => array_map( 'strval', array_keys( $post ) ),
	);

	if ( true === apply_filters( 'proud_anon_post_allow', false, $context ) ) {
		return 'allow:filter';
	}

	foreach ( array_keys( $cookie ) as $name ) {
		if ( 0 === strpos( (string) $name, 'wordpress_logged_in_' ) ) {
			return 'defer';
		}
	}

	return 'block:no_cookie';
}

/**
 * Log-injection-safe scalar: printable ASCII only, quote and backslash replaced, capped.
 *
 * @param mixed $value Attacker-controlled value.
 * @param int   $max   Maximum length.
 *
 * @return string
 */
function proud_anon_post_clean( $value, $max ) {
	$value = is_scalar( $value ) ? (string) $value : '';
	$value = preg_replace( '/[^\x20-\x7e]/', '', $value );
	$value = str_replace( array( '"', '\\' ), '_', $value );

	return substr( $value, 0, $max );
}

/**
 * Comma-separated list of array key NAMES (never values), capped by count, per-name
 * length and total length; `+N` is appended when names were dropped.
 *
 * @param array $arr   $_GET or $_POST.
 * @param int   $total Total character budget.
 *
 * @return string
 */
function proud_anon_post_names( array $arr, $total = 150 ) {
	$out   = '';
	$count = 0;
	$keys  = array_keys( $arr );

	foreach ( $keys as $key ) {
		$name = substr( preg_replace( '/[^A-Za-z0-9_.\-\[\]]/', '', (string) $key ), 0, 40 );
		$name = '' === $name ? '?' : $name;
		$next = '' === $out ? $name : $out . ',' . $name;

		if ( $count >= 25 || strlen( $next ) > $total ) {
			break;
		}

		$out = $next;
		++$count;
	}

	if ( count( $keys ) > $count ) {
		$out .= '+' . ( count( $keys ) - $count );
	}

	return '' === $out ? 'none' : $out;
}

/**
 * Builds the one-line log message. Never includes a request value, only names.
 *
 * @param string $mode   'log' or 'enforce'.
 * @param string $reason 'no_cookie' or 'invalid_cookie'.
 * @param array  $server $_SERVER.
 * @param array  $get    $_GET.
 * @param array  $post   $_POST.
 *
 * @return string Without a trailing newline.
 */
function proud_anon_post_format_log( $mode, $reason, array $server, array $get, array $post ) {
	$host = isset( $server['HTTP_HOST'] ) ? strtolower( (string) $server['HTTP_HOST'] ) : '';
	$host = substr( preg_replace( '/[^a-z0-9.:\-]/', '', $host ), 0, 100 );

	$ip = isset( $server['REMOTE_ADDR'] ) && is_string( $server['REMOTE_ADDR'] ) && false !== filter_var( $server['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ? $server['REMOTE_ADDR'] : 'unknown';

	$ct = isset( $server['CONTENT_TYPE'] ) && is_string( $server['CONTENT_TYPE'] ) ? strtolower( explode( ';', $server['CONTENT_TYPE'], 2 )[0] ) : '';
	$ct = substr( preg_replace( '/[^a-z0-9.+\/\-]/', '', $ct ), 0, 50 );

	$ref     = 'none';
	$referer = isset( $server['HTTP_REFERER'] ) && is_string( $server['HTTP_REFERER'] ) ? $server['HTTP_REFERER'] : '';

	if ( '' !== $referer ) {
		$ref_host = parse_url( $referer, PHP_URL_HOST );
		$ref      = ( is_string( $ref_host ) && strtolower( $ref_host ) === strtolower( isset( $server['HTTP_HOST'] ) ? (string) $server['HTTP_HOST'] : '' ) ) ? 'same' : 'other';
	}

	$ua = isset( $server['HTTP_USER_AGENT'] ) ? proud_anon_post_clean( $server['HTTP_USER_AGENT'], 150 ) : '';

	return sprintf(
		'proud-anon-post: %s host=%s ip=%s reason=%s path="%s" qkeys=%s fields=%s ct=%s ref=%s ua="%s"',
		'enforce' === $mode ? 'blocked' : 'would_block',
		'' === $host ? 'unknown' : $host,
		$ip,
		proud_anon_post_clean( $reason, 20 ),
		proud_anon_post_clean( proud_anon_post_request_path( $server ), 200 ),
		proud_anon_post_names( $get ),
		proud_anon_post_names( $post ),
		'' === $ct ? 'none' : $ct,
		$ref,
		$ua
	);
}

/**
 * Writes a line straight to php://stderr, bypassing Apache's `LogLevel emerg` (same
 * reason as proud-block-password-login.php).
 *
 * @param string $line Log line without trailing newline.
 */
function proud_anon_post_write_log( $line ) {
	error_log( $line . "\n", 3, 'php://stderr' );
}

/**
 * Sends the enforce-mode 403. Plain text rather than wp_die(): no HTML page and no
 * wp_die_handler filters for a request we're throwing away.
 *
 * @param bool $terminate Pass false in tests to skip the exit.
 */
function proud_anon_post_send_forbidden( $terminate = true ) {
	status_header( 403 );
	nocache_headers();

	if ( ! headers_sent() ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Proud-Anon-Post: blocked' );
	}

	echo 'Forbidden';

	if ( $terminate ) {
		exit;
	}
}

/**
 * Logs a refusal and, in enforce mode, answers 403. In log mode it returns and the
 * request carries on exactly as before.
 *
 * @param string $mode   'log' or 'enforce'.
 * @param string $reason 'no_cookie' or 'invalid_cookie'.
 * @param array  $io     Optional 'log' and 'respond' callables, for tests.
 */
function proud_anon_post_refuse( $mode, $reason, array $io = array() ) {
	$line = proud_anon_post_format_log( $mode, $reason, $_SERVER, $_GET, $_POST );

	if ( isset( $io['log'] ) ) {
		call_user_func( $io['log'], $line );
	} else {
		proud_anon_post_write_log( $line );
	}

	if ( 'enforce' !== $mode ) {
		return;
	}

	if ( isset( $io['respond'] ) ) {
		call_user_func( $io['respond'] );
	} else {
		proud_anon_post_send_forbidden();
	}
}

/**
 * Stage 1, on `muplugins_loaded`: allowlist and no-cookie check.
 *
 * @param string|null $sapi Optional PHP_SAPI override for tests.
 * @param array       $io   Optional 'log' and 'respond' callables, for tests.
 */
function proud_anon_post_check_early( $sapi = null, array $io = array() ) {
	if ( proud_anon_post_disabled() ) {
		return;
	}

	$get  = is_array( $_GET ) ? $_GET : array();
	$slug = '';

	if ( isset( $get['gf_page'] ) && is_string( $get['gf_page'] ) ) {
		$option = get_option( 'gform_upload_page_slug' );
		$slug   = is_string( $option ) ? $option : '';
	}

	$result = proud_anon_post_decide( $_SERVER, $get, is_array( $_POST ) ? $_POST : array(), is_array( $_COOKIE ) ? $_COOKIE : array(), ABSPATH, $slug, $sapi );

	if ( 'defer' === $result ) {
		add_action( 'plugins_loaded', 'proud_anon_post_check_cookie', PHP_INT_MIN );
		return;
	}

	if ( 'block:no_cookie' === $result ) {
		proud_anon_post_refuse( proud_anon_post_mode(), 'no_cookie', $io );
	}
}

/**
 * Stage 2, on `plugins_loaded` at PHP_INT_MIN: validate the logged_in cookie.
 *
 * wp_validate_auth_cookie() rather than is_user_logged_in(): the latter sets the current
 * user before `init` via determine_current_user, which some plugins don't expect. This
 * only checks the HMAC, expiry and session token.
 *
 * @param array $io Optional 'log' and 'respond' callables, for tests.
 */
function proud_anon_post_check_cookie( array $io = array() ) {
	if ( wp_validate_auth_cookie( '', 'logged_in' ) ) {
		return;
	}

	proud_anon_post_refuse( proud_anon_post_mode(), 'invalid_cookie', $io );
}

add_action( 'muplugins_loaded', 'proud_anon_post_check_early' );
