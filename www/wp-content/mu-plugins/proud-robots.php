<?php
/**
 * Plugin Name: Proud Robots
 * Description: Appends expensive-path Disallow rules to robots.txt for all tenants.
 *              Adds a generic User-agent: * block so any compliant crawler stays off
 *              deep pagination and search endpoints. Adds per-crawler blocks with
 *              Crawl-delay: 10 for the crawlers that honor it (Applebot, Bingbot,
 *              YandexBot, DuckDuckBot). Server-side enforcement is tracked separately
 *              as pc-dev-issues#297. See wp-proudcity#2846.
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

( function () {
	$disallow_paths = array(
		'/search-site',
		'/search-site/*',
		'/*?pager=',
		'/*?s=',
		'/page/*/?s=',
		'/events/page/*/',
		'/locations/page/*/',
		'/documents/page/*/',
	);

	$crawlers = array(
		'Applebot',
		'Bingbot',
		'YandexBot',
		'DuckDuckBot',
	);

	add_filter( 'robots_txt', function ( $output ) use ( $disallow_paths, $crawlers ) {
		$lines = array( '' );

		// Generic block: catches Googlebot and any other compliant crawler not named below.
		$lines[] = 'User-agent: *';
		foreach ( $disallow_paths as $path ) {
			$lines[] = 'Disallow: ' . $path;
		}

		// Per-crawler blocks with Crawl-delay for crawlers that honor it.
		foreach ( $crawlers as $ua ) {
			$lines[] = '';
			$lines[] = 'User-agent: ' . $ua;
			$lines[] = 'Crawl-delay: 10';
			foreach ( $disallow_paths as $path ) {
				$lines[] = 'Disallow: ' . $path;
			}
		}

		// AI opt-out tokens. These never appear in a User-Agent header, so bots.csv
		// can't block them — robots.txt is the only place they're honored. Googlebot
		// and Applebot still crawl for search; this opts out of Gemini / Apple
		// Intelligence training and grounding (pc-dev-issues#391).
		foreach ( array( 'Google-Extended', 'Applebot-Extended' ) as $ua ) {
			$lines[] = '';
			$lines[] = 'User-agent: ' . $ua;
			$lines[] = 'Disallow: /';
		}

		return $output . implode( "\n", $lines ) . "\n";
	} );
} )();
