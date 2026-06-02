<?php
/**
 * Plugin Name: Disable GF Stripe IP Rate Limit
 * Description: Conditionally disables the Gravity Forms Stripe add-on per-IP rate
 *              limiter when the k8s pod is launched with PC_DISABLE_GF_STRIPE_RATE_LIMIT=true.
 *              Required while all customers share the same internal k8s IP from WordPress's
 *              perspective (see pc-dev-issues#287), which causes one customer's card errors
 *              to lock out every other customer for an hour. Set the env var per-tenant in
 *              the deployment YAML so the override is opt-in and tracked, not platform-wide.
 *              Once pc-dev-issues#287 lands, remove the env var from any pod that has it.
 * Author:      ProudCity
 * Version:     1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// getenv() is used (rather than $_ENV) so it works under PHP-FPM regardless of
// variables_order. Compared strictly to the literal string "true" so an unset
// or any other value leaves the rate limiter enabled.
if ( getenv( 'PC_DISABLE_GF_STRIPE_RATE_LIMIT' ) === 'true' ) {
	add_filter( 'gform_stripe_enable_rate_limits', '__return_false' );
}
