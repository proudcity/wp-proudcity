<?php
/**
 * Plugin Name: Proud Cron Integrity
 * Description: Closes a wp-cron race where one process's read-modify-write of the
 *              `cron` option silently drops events another process scheduled in the
 *              meantime. Also stops a web request to wp-cron.php from running events
 *              when DISABLE_WP_CRON is on, so a dedicated runner is the sole executor.
 *              See wp-proudcity#2961.
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks whether a cron mutation (wp_schedule_single_event(), wp_unschedule_event(),
 * etc.) is in flight and every read of the `cron` option until it finishes needs to
 * be fresh, not whatever this process's in-memory/object-cache copy holds.
 *
 * A plain static flag is enough: cron mutations in a single PHP process run one at a
 * time, never concurrently with each other, so there is nothing to key this by.
 *
 * @param bool|null $set Pass true/false to set the flag, or null (default) to read it.
 *
 * @return bool The flag's state after any requested change.
 */
function proud_cron_integrity_armed( $set = null ) {
	static $armed = false;

	if ( null !== $set ) {
		$armed = (bool) $set;
	}

	return $armed;
}

/**
 * Arms the flag ahead of a cron mutation, without changing its outcome.
 *
 * Hooked to pre_schedule_event, pre_unschedule_event, pre_reschedule_event,
 * pre_clear_scheduled_hook and pre_unschedule_hook -- every core entry point that
 * is about to read the `cron` option, change it, and write it back. Each of these
 * filters defaults to null/false to mean "don't short-circuit," so returning $pre
 * unchanged leaves core's own logic (and any other plugin already using the
 * filter) exactly as it was.
 *
 * @param mixed $pre Whatever core (or another plugin) passed in.
 *
 * @return mixed $pre, unchanged.
 */
function proud_cron_integrity_arm( $pre ) {
	proud_cron_integrity_armed( true );

	return $pre;
}

/**
 * While armed, serves every read of the `cron` option straight from the database
 * instead of whatever this process's in-memory/object-cache copy of `alloptions`
 * holds. That copy can be minutes stale if this process has been running a long
 * cron hook, and core's cron functions read-modify-write that whole option, so a
 * stale read silently erases any event another process scheduled in between.
 *
 * Deliberately does NOT disarm here, even though each arm usually only needs one
 * read. A mutator such as wp_unschedule_event() calls get_option('cron') itself via
 * _get_cron_array(), then _set_cron_array() calls update_option('cron', ...), which
 * calls get_option('cron') again for its own $old_value comparison (see
 * wp-includes/option.php) BEFORE firing pre_update_option_cron. If this filter
 * disarmed after the first of those two reads, the second ($old_value) would come
 * back stale. update_option() skips the write entirely when `$value === $old_value`,
 * so a stale $old_value can make it look like nothing changed when it did -- exactly
 * what happened here: process A's fresh-mutated array and a stale $old_value can
 * both still equal the snapshot A started from, silently keeping an event A meant to
 * remove (or dropping one it meant to add). proud_cron_integrity_disarm(), hooked to
 * pre_update_option_cron, disarms once that $old_value read has happened, so both
 * reads for this mutation are fresh.
 *
 * Trade-off: several paths arm and then return before ever reaching update_option():
 * wp_clear_scheduled_hook() / wp_unschedule_hook() when nothing matched,
 * wp_schedule_single_event() on its duplicate-within-10-minutes check,
 * wp_schedule_event() for an event that already exists, and any other plugin's pre_*
 * filter short-circuiting the call. Each leaves the flag armed into whatever
 * cron-array read comes next in this request. The only cost is extra fresh SELECTs
 * for get_option('cron') until the next cron write disarms it. Those reads are more
 * correct, not less, so this is acceptable.
 *
 * Fails open: any sign the direct read did not work (no row, empty value, or a
 * reported DB error) returns false, the same default this filter would otherwise
 * see, so get_option() falls through to its normal (possibly cached) read. It does
 * not disarm on failure either, for the same reason: the mutation this arm is for is
 * still in flight.
 *
 * @param mixed $pre_option The value get_option() would otherwise return. Always
 *                          false unless something already short-circuited it.
 *
 * @return mixed $pre_option unchanged while unarmed; the freshly read and
 *               unserialized `cron` array while armed and the read succeeded;
 *               false while armed and the read failed.
 */
function proud_cron_integrity_pre_option_cron( $pre_option ) {
	if ( ! proud_cron_integrity_armed() ) {
		return $pre_option;
	}

	global $wpdb;

	$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", 'cron' ) );

	if ( null === $value || '' === $value || '' !== $wpdb->last_error ) {
		return false;
	}

	return maybe_unserialize( $value );
}

/**
 * Disarms once update_option('cron', ...) has fetched $old_value and is about to
 * write, so both reads for this mutation (the mutator's own _get_cron_array() read
 * and this $old_value read) were fresh. See proud_cron_integrity_pre_option_cron()
 * for why disarming any earlier would leave $old_value stale.
 *
 * `pre_update_option_{$option}` passes ($value, $old_value, $option); only $value is
 * needed here, so this is registered with the default accepted_args of 1.
 *
 * @param mixed $value The new, not-yet-serialized `cron` array.
 *
 * @return mixed $value, unchanged.
 */
function proud_cron_integrity_disarm( $value ) {
	proud_cron_integrity_armed( false );

	return $value;
}

/**
 * Decides whether this web request should be stopped before wp-cron.php runs any
 * events, so that a dedicated runner (not page-load cron) is the only thing that
 * ever executes them on sites that have opted into DISABLE_WP_CRON.
 *
 * DISABLE_WP_CRON only stops wp_cron() from spawning a loopback to wp-cron.php; it
 * does not stop wp-cron.php from running events if something hits it directly
 * (wp-cron.php itself, line ~1051 of wp-includes/cron.php). This guard closes that
 * gap. WP-CLI is exempt because `wp cron event run` is how the runner itself
 * executes events, and it also sets DOING_CRON.
 *
 * Each parameter defaults to the real, live value so the production call site
 * (proud_cron_integrity_maybe_block_wp_cron()) can call this with no arguments.
 * Tests pass explicit booleans instead, so the DISABLE_WP_CRON/WP_CLI constants
 * -- which can only ever be defined once per PHP process -- never need to be
 * defined differently across test cases.
 *
 * @param bool|null $is_wp_cli       Optional. Whether this is a WP-CLI request.
 * @param bool|null $disable_wp_cron Optional. Whether DISABLE_WP_CRON is on.
 * @param bool|null $doing_cron      Optional. Whether this request is running cron.
 *
 * @return bool True if the request should be stopped before running any events.
 */
function proud_cron_integrity_should_block_wp_cron( $is_wp_cli = null, $disable_wp_cron = null, $doing_cron = null ) {
	if ( null === $is_wp_cli ) {
		$is_wp_cli = defined( 'WP_CLI' ) && WP_CLI;
	}

	if ( $is_wp_cli ) {
		return false;
	}

	if ( null === $disable_wp_cron ) {
		$disable_wp_cron = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
	}

	if ( ! $disable_wp_cron ) {
		return false;
	}

	if ( null === $doing_cron ) {
		$doing_cron = wp_doing_cron();
	}

	return (bool) $doing_cron;
}

/**
 * Thin wrapper around proud_cron_integrity_should_block_wp_cron() that actually
 * stops the request. Kept separate from the boolean check so tests can exercise
 * the decision without the process exiting.
 *
 * Exits quietly, the same way wp-cron.php itself bails (plain die(), no body):
 * by this point wp-cron.php has already sent its no-cache headers, so there is
 * nothing more to add.
 */
function proud_cron_integrity_maybe_block_wp_cron() {
	if ( ! proud_cron_integrity_should_block_wp_cron() ) {
		return;
	}

	exit;
}

add_filter( 'pre_schedule_event', 'proud_cron_integrity_arm' );
add_filter( 'pre_unschedule_event', 'proud_cron_integrity_arm' );
add_filter( 'pre_reschedule_event', 'proud_cron_integrity_arm' );
add_filter( 'pre_clear_scheduled_hook', 'proud_cron_integrity_arm' );
add_filter( 'pre_unschedule_hook', 'proud_cron_integrity_arm' );
add_filter( 'pre_option_cron', 'proud_cron_integrity_pre_option_cron' );
add_filter( 'pre_update_option_cron', 'proud_cron_integrity_disarm' );

// muplugins_loaded is the earliest hook available: wp-cron.php requires wp-load.php
// (which loads mu-plugins) before it ever reads or runs a single event, so exiting
// here stops the request before any event has a chance to run.
add_action( 'muplugins_loaded', 'proud_cron_integrity_maybe_block_wp_cron' );
