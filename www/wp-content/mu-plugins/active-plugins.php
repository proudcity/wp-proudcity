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
}

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

	if ( ! in_array( 'gravityforms/gravityforms.php', $active_plugins ) || ! in_array( 'wp-media-folder/wp-media-folder.php', $active_plugins ) ) {

		// need to
		$emailed = get_transient( 'proud_admin_notified' );

		if ( false == $emailed ){

            $curl = curl_init( 'https://hooks.slack.com/services/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' );

            $m = 'Gravity Forms or WP Media Folder is not active on ' . get_bloginfo( 'name' ) .'! Link: ' . site_url();

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