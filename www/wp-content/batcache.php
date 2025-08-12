<?php

/**
 * Reasonable defaults for origin caching behind Cloudflare
 */
$batcache = [
  'seconds'               => 300,   // cache lifetime per page (5m)
  'max_age'               => 300,   // serve stale while refreshing
  'use_stale'             => true,  // better resilience under load
  'debug'                 => true, // set true to see X-Batcache headers
  'cache_redirects'       => false,
  'ignore_ajax_requests'  => true,
  // Don’t cache logged-in users or commenters (Batcache already respects WP auth cookies)
  // Optional: ignore common tracking params so they don't bust cache
  'ignored_query_args'    => [ 'utm_source','utm_medium','utm_campaign','gclid','fbclid' ],
];
