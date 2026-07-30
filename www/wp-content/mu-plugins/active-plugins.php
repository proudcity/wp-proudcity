<?php

/**
 * Sets up our configuration system and functions
 */
switch( wp_get_environment_type() ){
	case 'production':
		sfn_config_production();
		break;
	case 'staging':
		sfn_config_staging();
		break;
	case 'development':
		sfn_config_development();
		break;
	case 'local':
		sfn_config_local();
	default:
		//sfn_config_default();
		break;
}

/**
 * Local configuration stuff
 */
function sfn_config_local(){

}

/**
 * Make sure that development is configured properly
 */
function sfn_config_development(){

}

/**
 * Returns the recovery email if set or the admin email
 */
function proud_get_recovery_email(){

	if ( defined( 'RECOVERY_MODE_EMAIL' ) && RECOVERY_MODE_EMAIL ){
		$email = RECOVERY_MODE_EMAIL;
	} else {
		$email = get_option( 'admin_email' );
	}

	return sanitize_email( $email );

} // proud_get_recovery_email

/**
 * Make sure that staging is configured properly
 */
function sfn_config_staging(){

}

/**
 * Make sure that production has any required settings
 */
function sfn_config_production(){
	add_action( 'shutdown', 'proud_plugins_not_active', 999 );
	add_filter( 'option_active_plugins', 'proud_force_auth0_active' );
}

/**
 * Force the Auth0 SSO plugin to load on production, every request.
 *
 * A `pckube db-import` overwrites active_plugins with the source dump's list,
 * and on affected sites a plain `wp plugin activate auth0` reported success but
 * did not stick (non-persisting option write / stale object cache). With auth0
 * inactive the ?auth0=1 callback has no handler, so SSO logins silently land on
 * the homepage. Injecting the plugin into the active list at read time via the
 * option_active_plugins filter guarantees it loads without depending on a DB
 * write, so it survives both the import and any cache staleness.
 *
 * Production only (registered from sfn_config_production) — local/dev keep auth0
 * deactivated on purpose via load-site.sh.
 */
function proud_force_auth0_active( $plugins ){

	$auth0 = 'auth0/WP_Auth0.php';

	if ( ! is_array( $plugins ) ) {
		$plugins = array();
	}

	// Only add it if the plugin files are actually present, otherwise WP would
	// try to include a missing file.
	if ( ! in_array( $auth0, $plugins, true ) && file_exists( WP_PLUGIN_DIR . '/' . $auth0 ) ) {
		$plugins[] = $auth0;
	}

	return $plugins;

} // proud_force_auth0_active

function test_email(){
    update_option( 'sfn_test','emailed'. time() );
    wp_mail( 'curtis@curtismchale.ca', 'testing', 'did this work');
}

/**
 * Let someone know that we didn't detect an environment
 */
function sfn_config_default(){
	$message = "There is no environment configuration setting for wp_get_environment_type()";
	$subject = "No wp_get_environment_type() defined";
	$email = ( defined( 'RECOVERY_MODE_EMAIL' ) ? RECOVERY_MODE_EMAIL : get_option( 'admin_email' ) );

	wp_mail( sanitize_email( $email ), $subject, $message );
}

/**
 * Emails admin or recovery if Restricted Site Access is on
 */
function proud_plugins_not_active(){

	$active_plugins = get_option( 'active_plugins' );

	$missing = array();

	if ( ! in_array( 'gravityforms/gravityforms.php', $active_plugins ) ) {
		$missing[] = 'Gravity Forms';
	}

	if ( ! in_array( 'wp-media-folder/wp-media-folder.php', $active_plugins ) ) {
		$missing[] = 'WP Media Folder';
	}

	// Auth0 is force-added to active_plugins by proud_force_auth0_active(), so an
	// in_array() check would always pass. class_exists() catches the real failure
	// mode: the plugin didn't load (files missing or fatal on include).
	if ( ! class_exists( 'WP_Auth0' ) ) {
		$missing[] = 'Auth0 SSO';
	}

	if ( ! empty( $missing ) ) {

		// need to
		$emailed = get_transient( 'proud_admin_notified' );

		$slack_key = getenv( 'PROUD_SLACK_KEY' );

		if ( false == $emailed ){

            $curl = curl_init( $slack_key );

            $m = implode( ', ', $missing ) . ' not active on ' . get_bloginfo( 'name' ) .'! Link: ' . site_url();

            $message = array( 'payload' => json_encode( array( 'text' => $m ) ) );

            curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $message );

            $result = curl_exec( $curl );
            curl_close( $curl );

			// setting our transient for 1 hour it keeps bugging us if the plugins are off
			set_transient( 'proud_admin_notified', true, 3600 );

		} // if false

	} // in_array

} // proud_plugins_not_active
