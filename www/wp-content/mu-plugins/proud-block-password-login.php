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
 * Version:     1.0.0
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
 * Removes core's own username/password `authenticate` handlers for every deployment
 * except the allowlisted one(s), so no username/password combination -- right or
 * wrong -- can ever authenticate.
 *
 * Removing the handlers outright, rather than hooking `authenticate` at a later
 * priority to override a successful result, is the robust choice here: it fixes the
 * actual cause (no password is ever checked) instead of reacting to one, so it can't
 * be reopened by a new way of reaching `wp_signon()`/`wp_authenticate()` -- which is
 * exactly the class of bug this closes, since the nginx `$arg_action` rule was reacting
 * to the request shape instead of the outcome. It's also unconditional: unlike a
 * late-priority check, it doesn't need to re-derive "was this actually a password
 * login" to avoid misclassifying a legitimate result.
 *
 * `wp_authenticate_username_password` and `wp_authenticate_email_password` are core's
 * only two `authenticate` handlers that check a password at all (both at priority 20,
 * see wp-includes/default-filters.php); removing them leaves `wp_authenticate()` with
 * nothing to return, and core's own fallback in wp-includes/pluggable.php converts that
 * `null` into a generic `WP_Error( 'authentication_failed', 'Invalid username, email
 * address or incorrect password.' )` -- already the generic, account-existence-blind
 * error this needs, with no WP_Error of our own required. `do_action( 'wp_login_failed',
 * ... )` still fires from that same core fallback, so anything already watching failed
 * logins keeps working.
 *
 * Deliberately NOT removed: `wp_authenticate_application_password` (also priority 20).
 * It only acts on a REST or XML-RPC request carrying an Application Password
 * (`wp-includes/user.php`, gated on `REST_REQUEST`/`XMLRPC_REQUEST`), never on an
 * interactive wp-login.php form post, so leaving it in place can't reopen the
 * form-login bypass this closes. No tenant integration was found depending on
 * Application Passwords, but nothing proves none exists either, and the blast radius
 * of guessing wrong (a silently broken integration) is worse than leaving a
 * already-scoped-down core feature alone. XML-RPC itself is already denied fleet-wide
 * at the web-server layer (`www/.htaccess`: `<Files xmlrpc.php> deny from all`), which
 * is the actual common brute-force vector for password auth over XML-RPC; this mu-plugin
 * covers it a second time by removing the same two handlers wp_authenticate() would use
 * there, in case that .htaccess rule is ever lost in a future web-server migration.
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
}

/**
 * Low-volume visibility into blocked password-login attempts, for the pc-dev-issues#16
 * banner/metrics work.
 *
 * On an allowlisted deployment this is a no-op -- Colma's logins are untouched and
 * failing one is not evidence of anything. On every other deployment,
 * `wp_authenticate_username_password`/`wp_authenticate_email_password` are the only
 * core handlers that ever produce a `WP_Error` here (see
 * proud_block_password_login_maybe_disable_core_handlers()), and both are removed, so
 * once this mu-plugin is active, `wp_login_failed` firing at all on such a deployment
 * means someone just posted a username and password to a login form that cannot
 * succeed -- not a typo'd password. Logs the IP only: no username (already low value
 * once every attempt fails the same way) and never the password, which `wp_login_failed`
 * does not even receive.
 *
 * `trust-proxy-client-ip.php` rewrites REMOTE_ADDR from a trusted X-Forwarded-For hop
 * before this runs, so this already sees the real client IP, not the ingress pod IP.
 *
 * @param string $username Unused; kept so the hook signature matches `wp_login_failed`.
 */
function proud_block_password_login_log_attempt( $username ) {
	if ( proud_block_password_login_allowed() ) {
		return;
	}

	$ip = empty( $_SERVER['REMOTE_ADDR'] ) ? 'unknown' : $_SERVER['REMOTE_ADDR'];

	error_log( sprintf( 'proud-block-password-login: blocked password-login attempt from %s', $ip ) );
}

add_action( 'muplugins_loaded', 'proud_block_password_login_maybe_disable_core_handlers' );
add_action( 'wp_login_failed', 'proud_block_password_login_log_attempt' );
