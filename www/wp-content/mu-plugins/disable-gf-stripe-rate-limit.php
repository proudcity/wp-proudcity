<?php
/**
 * Plugin Name: Disable GF Stripe IP Rate Limit
 * Description: Disables the Gravity Forms Stripe add-on per-IP rate limiter. Required while all
 *              customers share the same internal k8s IP from WordPress's perspective, which causes
 *              one customer's card errors to lock out every other customer for an hour.
 *              Remove once the k8s ingress forwards the real client IP to PHP.
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'gform_stripe_enable_rate_limits', '__return_false' );
