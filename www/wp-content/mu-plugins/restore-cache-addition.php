<?php
/**
 * Plugin Name: Restore Object Cache Addition
 * Description: Re-enables WordPress object cache addition after fix-alt-text suspends it.
 *              fix-alt-text calls wp_suspend_cache_addition( true ) at the top of its
 *              save/scan handlers (priority 999) and never restores it, so everything after
 *              the first post save in a request runs with cache priming disabled — see
 *              wp-proudcity#2886. Its scan completes within its own handler, so restoring
 *              at a later priority on the same hooks is safe. Remove this once fix-alt-text
 *              restores the flag itself upstream.
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns object cache addition back on.
 *
 * Only acts when it is actually suspended, so this stays a no-op if fix-alt-text
 * is deactivated or fixes the behavior upstream.
 */
function proudcity_restore_cache_addition() {

	if ( function_exists( 'wp_suspend_cache_addition' ) && wp_suspend_cache_addition() ) {
		wp_suspend_cache_addition( false );
	}

}

// Priority 1000 — one past fix-alt-text's 999, so its scan has already finished.
add_action( 'save_post', 'proudcity_restore_cache_addition', 1000 );
add_action( 'attachment_updated', 'proudcity_restore_cache_addition', 1000 );
add_action( 'add_attachment', 'proudcity_restore_cache_addition', 1000 );
add_action( 'saved_term', 'proudcity_restore_cache_addition', 1000 );
add_action( 'delete_term', 'proudcity_restore_cache_addition', 1000 );
