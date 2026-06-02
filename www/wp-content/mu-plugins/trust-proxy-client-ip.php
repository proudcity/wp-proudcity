<?php
/**
 * Plugin Name: Trust Proxy Client IP
 * Description: Rewrites $_SERVER['REMOTE_ADDR'] from the X-Forwarded-For header set by our
 *              nginx-ingress controller, so PHP/WordPress see the real visitor IP instead of
 *              the internal k8s pod IP. Required so anything IP-based (Gravity Forms Stripe
 *              rate limiter, audit logs, geo, login throttling, abuse heuristics) works.
 *              Requires nginx-ingress-lb service to use externalTrafficPolicy: Local so the
 *              GCP NLB preserves the real client IP up to nginx-ingress. See wp-proudcity#2829.
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite REMOTE_ADDR from the last hop in X-Forwarded-For when the connection
 * comes from inside our cluster.
 *
 * Why "last hop": nginx-ingress sets the header with `$proxy_add_x_forwarded_for`,
 * which appends the connecting client's IP to whatever the client sent. So the
 * RIGHTMOST entry is the one nginx-ingress observed (trusted); leading entries
 * are whatever the client put there and must not be trusted. Taking the last
 * entry prevents spoofing via a forged X-Forwarded-For from the public client.
 *
 * Why the RFC1918 gate: WordPress pods only receive traffic from inside the
 * cluster (via nginx-ingress). If REMOTE_ADDR is a public address, something
 * unexpected is talking to us and we should not rewrite anything.
 */
( function () {
	if ( empty( $_SERVER['REMOTE_ADDR'] ) || empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		return;
	}

	$remote = $_SERVER['REMOTE_ADDR'];

	// Only trust X-Forwarded-For when the request reached us from an internal
	// (RFC1918/reserved) address — i.e. an in-cluster proxy hop. filter_var with
	// these flags returns the IP only if it is NOT private/reserved (i.e. public).
	$is_public = filter_var(
		$remote,
		FILTER_VALIDATE_IP,
		FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
	);

	if ( $is_public !== false ) {
		return;
	}

	$parts = array_map( 'trim', explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	$last  = end( $parts );

	if ( ! $last || ! filter_var( $last, FILTER_VALIDATE_IP ) ) {
		return;
	}

	$_SERVER['REMOTE_ADDR'] = $last;
} )();
