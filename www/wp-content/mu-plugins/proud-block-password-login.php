<?php
/**
 * Plugin Name: Proud Block Password Login
 * Description: Refuses WordPress username/password authentication on every tenant
 *              except an explicit per-deployment allowlist. Every tenant logs in via
 *              the ProudCity dashboard + Auth0 SSO except Colma (colma.ca.gov), which
 *              still uses plain WP username/password. An nginx-layer rule tried to
 *              block `POST /wp-login.php` for everyone but Colma and the `action=postpass`
 *              password-protected-post unlock, keyed off `$arg_action`, but WordPress
 *              reads `$_REQUEST['action']` (POST wins over GET, the last duplicate wins,
 *              and an unrecognized action falls back to the login form), so
 *              `POST /wp-login.php?action=postpass` with body `action=login&log=...&pwd=...`,
 *              or `?action=postpass&action=login`, or `?ACTION=postpass` all still reach
 *              `wp_signon()`. A proxy-layer path/query rule can't fully close that, and it
 *              has to be re-verified against whatever ingress controller is in front
 *              (nginx today, HAProxy next per pc-dev-issues#363) every time that changes.
 *              Fixing it here makes it correct regardless of what's in front of WordPress.
 *              See pc-dev-issues#363, finding M2.
 * Author:      ProudCity
 * Version:     1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether this deployment is allowed to authenticate with a WordPress username and
 * password (Colma only, as of this writing).
 *
 * An env var rather than a hostname check: it's set per-deployment in the k8s manifest
 * (same pattern as PC_DISABLE_GF_STRIPE_RATE_LIMIT in disable-gf-stripe-rate-limit.php),
 * so the exception is visible and auditable in the deployment YAML instead of buried in
 * plugin code, and it survives a tenant's domain changing. It also doubles as the Auth0
 * `?wle=` break-glass: that query var only skips Auth0's auto-redirect to show the native
 * login form (see the auth0 plugin's `login_auto()`); without PC_ALLOW_PASSWORD_LOGIN set,
 * a password typed into that form still fails here, so break-glass access requires setting
 * this on the deployment, not just knowing the query var.
 *
 * Compared strictly to the literal "1" so an unset env var, or any other value, leaves
 * password login blocked -- the same opt-in pattern PC_DISABLE_GF_STRIPE_RATE_LIMIT uses.
 *
 * @param string|false|null $env Optional. Pass the raw env var value to test against
 *                                instead of reading PC_ALLOW_PASSWORD_LOGIN for real.
 *
 * @return bool
 */
function proud_block_password_login_allowed( $env = null ) {
	if ( null === $env ) {
		$env = getenv( 'PC_ALLOW_PASSWORD_LOGIN' );
	}

	return '1' === $env;
}

/**
 * Mirrors wp_authenticate_username_password()'s empty-credential branch -- without the
 * password check that follows it -- so removing that handler below doesn't also remove
 * the one behaviour wp-login.php itself depends on for a plain page load.
 *
 * wp-login.php's `case 'login': default:` branch runs `wp_signon( array() )` on every
 * request that falls through to it, GET included (a bare page load, `?loggedout=1`,
 * `?reauth=1`), and `wp_signon()` always calls `wp_authenticate( '', '' )` when $_POST
 * is empty. Core relies on that producing a `WP_Error` with exactly the codes
 * `['empty_username', 'empty_password']`: wp-login.php checks for that literal array
 * (`if ( empty( $_POST ) && $errors->get_error_codes() === array( 'empty_username',
 * 'empty_password' ) ) { $errors = new WP_Error( '', '' ); }`) to suppress the error box
 * on a plain view. With both password-checking handlers removed and nothing else
 * producing that specific error, `wp_authenticate()` would fall through to its own
 * generic `null` -> `WP_Error( 'authentication_failed', ... )` fallback instead -- a
 * code wp-login.php's suppression check doesn't match -- so every GET would render the
 * "Invalid username, email address or incorrect password" box with the shake animation,
 * and (before this existed) `wp_login_failed` fired on every single page view, not just
 * actual login attempts.
 *
 * Passes through untouched once a previous callback already produced a `WP_User` or a
 * `WP_Error` (nothing left to add), and once $username and $password are both non-empty
 * -- a genuine attempted login still falls through, with no password ever checked, to
 * core's generic `authentication_failed` fallback exactly as before this existed.
 *
 * Registered at priority 20, the same priority the handlers it replaces ran at, so
 * `wp_authenticate_cookie()` (priority 30, added by wp_signon() itself) still runs
 * after it and still overrides this with a real `WP_User` on a valid logged-in cookie,
 * or with `expired_session` on an invalid one -- reauth bounces are unaffected.
 *
 * @param WP_User|WP_Error|null $user     Result of an earlier `authenticate` callback.
 * @param string                $username Username or email entered.
 * @param string                $password Password entered.
 *
 * @return WP_User|WP_Error|null
 */
function proud_block_password_login_empty_credentials( $user, $username, $password ) {
	if ( $user instanceof WP_User || is_wp_error( $user ) ) {
		return $user;
	}

	if ( empty( $username ) || empty( $password ) ) {
		$error = new WP_Error();

		if ( empty( $username ) ) {
			$error->add( 'empty_username', __( '<strong>Error:</strong> The username field is empty.' ) );
		}

		if ( empty( $password ) ) {
			$error->add( 'empty_password', __( '<strong>Error:</strong> The password field is empty.' ) );
		}

		return $error;
	}

	return $user;
}

/**
 * Backstop for every `authenticate` callback, core's or any other plugin's: on a
 * non-allowlisted deployment, refuse a successful password-based result even if one
 * somehow occurs, instead of relying solely on the removal below having worked. Runs
 * at PHP_INT_MAX so it sees the final result of the whole filter chain.
 *
 * `'' === $password` is what excludes cookie re-authentication here, not the presence
 * of a `WP_User`: wp_authenticate_cookie() (priority 30) only ever turns a `WP_User`
 * into the result when $username and $password were BOTH empty (an already-logged-in
 * visit, not a credentials submission) -- so a non-empty password is exactly what
 * distinguishes an actual password-based success from a cookie-based one reaching here.
 *
 * REST/XML-RPC are excluded using the same condition
 * `wp_authenticate_application_password()` itself uses to decide it applies
 * (`wp-includes/user.php`), so Application Passwords -- deliberately left alone
 * everywhere else in this mu-plugin -- can't be broken by this backstop either.
 *
 * @param WP_User|WP_Error|null $user           Result of every earlier `authenticate` callback.
 * @param string                $username       Username or email entered.
 * @param string                $password       Password entered.
 * @param bool|null             $is_api_request Optional. Pass true/false to test against
 *                                              instead of reading REST_REQUEST/XMLRPC_REQUEST
 *                                              for real. `authenticate` is only ever called
 *                                              with 3 args, so this is never filled by the
 *                                              hook itself.
 *
 * @return WP_User|WP_Error|null
 */
function proud_block_password_login_final_guard( $user, $username, $password, $is_api_request = null ) {
	if ( proud_block_password_login_allowed() ) {
		return $user;
	}

	if ( ! ( $user instanceof WP_User ) || '' === $password ) {
		return $user;
	}

	if ( null === $is_api_request ) {
		$is_api_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST );
	}

	if ( $is_api_request ) {
		return $user;
	}

	return new WP_Error( 'authentication_failed', __( '<strong>Error:</strong> Invalid username, email address or incorrect password.' ) );
}

/**
 * Removes core's own username/password `authenticate` handlers for every deployment
 * except the allowlisted one(s), so no username/password combination -- right or
 * wrong -- can ever authenticate, and installs the two replacements above.
 *
 * Removing the handlers outright, rather than only hooking `authenticate` at a later
 * priority to override a successful result, is the robust choice here: it fixes the
 * actual cause (no password is ever checked) instead of reacting to one, so it can't
 * be reopened by a new way of reaching `wp_signon()`/`wp_authenticate()` -- which is
 * exactly the class of bug this closes, since the nginx `$arg_action` rule was reacting
 * to the request shape instead of the outcome.
 * proud_block_password_login_final_guard() adds the late-priority check on top,
 * purely as a backstop in case something else ever re-adds one of the two handlers
 * removed below.
 *
 * `wp_authenticate_username_password` and `wp_authenticate_email_password` are core's
 * only two `authenticate` handlers that check a password at all (both at priority 20,
 * see wp-includes/default-filters.php). Removing them bare would leave
 * `wp_authenticate()` with nothing to return for an empty-credentials call, which is
 * why proud_block_password_login_empty_credentials() is added at the same priority to
 * take over just that one case -- see its docblock for why that's needed. For a real,
 * non-empty attempted login, nothing here returns anything either, and core's own
 * fallback in wp-includes/pluggable.php converts that final `null` into a generic
 * `WP_Error( 'authentication_failed', 'Invalid username, email address or incorrect
 * password.' )` -- already the generic, account-existence-blind error this needs, with
 * no WP_Error of our own required for that case. `do_action( 'wp_login_failed', ... )`
 * still fires from that same core fallback, so anything already watching failed logins
 * keeps working.
 *
 * Deliberately NOT removed: `wp_authenticate_application_password` (also priority 20).
 * It only matters for an XML-RPC request carrying an Application Password as the
 * $password argument to `wp_authenticate()` (`wp-includes/user.php`, gated on
 * `REST_REQUEST`/`XMLRPC_REQUEST`) -- REST's own Application Password check
 * (`wp_validate_application_password()`, hooked to `determine_current_user`) calls
 * `wp_authenticate_application_password()` directly and never goes through the
 * `authenticate` filter at all, so it was never at risk from removing the other two
 * handlers here regardless. Neither path is an interactive wp-login.php form post, so
 * leaving this one in place can't reopen the form-login bypass this closes. No tenant
 * integration was found depending on Application Passwords, but nothing proves none
 * exists either, and the blast radius of guessing wrong (a silently broken integration)
 * is worse than leaving an already-scoped-down core feature alone. XML-RPC itself is
 * already denied fleet-wide at the web-server layer (`www/.htaccess`:
 * `<Files xmlrpc.php> deny from all`), which is the actual common brute-force vector for
 * password auth over XML-RPC; this mu-plugin covers it a second time by removing the
 * same two handlers wp_authenticate() would use there, in case that .htaccess rule is
 * ever lost in a future web-server migration.
 *
 * Also NOT touched: lost-password/reset (`action=lostpassword`/`rp`/`resetpass`) --
 * wp-login.php validates the reset key/cookie and calls `reset_password()` directly,
 * never `wp_authenticate()`/the `authenticate` filter, and does not log the user in --
 * and `action=postpass` -- it only sets a cookie from `$_POST['post_password']` and
 * never calls `wp_authenticate()` either. Neither can be affected by this at all.
 *
 * Hooked on `muplugins_loaded` rather than run at file-load time: core's own handlers
 * are already registered by the time any mu-plugin loads (`default-filters.php` runs
 * before must-use plugins in wp-settings.php), so either timing removes them before
 * any request can reach `wp_authenticate()`. `muplugins_loaded` is used so the PHPUnit
 * bootstrap can `require` this file without the bare `remove_filter()` call needing to
 * be a real function at require time.
 */
function proud_block_password_login_maybe_disable_core_handlers() {
	if ( proud_block_password_login_allowed() ) {
		return;
	}

	remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
	remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );

	add_filter( 'authenticate', 'proud_block_password_login_empty_credentials', 20, 3 );
	add_filter( 'authenticate', 'proud_block_password_login_final_guard', PHP_INT_MAX, 3 );
}

/**
 * Low-volume visibility into blocked password-login attempts, for the pc-dev-issues#16
 * banner/metrics work.
 *
 * On an allowlisted deployment this is a no-op -- Colma's logins are untouched and
 * failing one is not evidence of anything. On every other deployment, `wp_login_failed`
 * fires for more than just a blocked password attempt -- an expired or missing login
 * cookie on a `reauth` bounce produces its own `expired_session` error through this
 * same filter chain, unrelated to this mu-plugin and already core's behaviour -- so this
 * only logs when the error code is specifically `authentication_failed`: the code core's
 * own fallback uses once nothing (including proud_block_password_login_empty_credentials())
 * has already returned a result, which only happens once $username and $password are
 * both non-empty. That is exactly "someone posted a username and password to a login
 * form that cannot succeed," without the false positives a bare `wp_login_failed` count
 * would include. Logs the IP only: no username, and never the password, which
 * `wp_login_failed` does not even receive.
 *
 * `trust-proxy-client-ip.php` rewrites REMOTE_ADDR from a trusted X-Forwarded-For hop
 * before this runs, so this already sees the real client IP, not the ingress pod IP.
 *
 * Written straight to `php://stderr` rather than a plain `error_log()` call: under
 * mod_php, PHP's error log goes through Apache's own logging, and `etc/apache-vhost.conf`
 * sets `LogLevel emerg`, which silently drops anything below that severity -- including
 * an ordinary `error_log()` line. Writing directly to stderr bypasses that threshold
 * entirely so the line still reaches the pod's stdout/stderr (and from there, Cloud
 * Logging).
 *
 * @param string          $username Unused; kept so the hook signature matches `wp_login_failed`.
 * @param WP_Error|null   $error    The authentication error. `wp_login_failed` has passed
 *                                  this as its second argument since WP 5.4.
 */
function proud_block_password_login_log_attempt( $username, $error = null ) {
	if ( proud_block_password_login_allowed() ) {
		return;
	}

	if ( ! is_wp_error( $error ) || 'authentication_failed' !== $error->get_error_code() ) {
		return;
	}

	$ip = empty( $_SERVER['REMOTE_ADDR'] ) ? 'unknown' : $_SERVER['REMOTE_ADDR'];

	error_log( sprintf( "proud-block-password-login: blocked password-login attempt from %s\n", $ip ), 3, 'php://stderr' );
}

add_action( 'muplugins_loaded', 'proud_block_password_login_maybe_disable_core_handlers' );
add_action( 'wp_login_failed', 'proud_block_password_login_log_attempt', 10, 2 );
