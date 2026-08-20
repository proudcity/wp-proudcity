#!/bin/bash
# release-2026.08.18.1821.sh — WP-Stateless 4.x data migration pass. See wp-proudcity#2889.
# This release intentionally contains nothing else, so a stalled migration cannot
# hold up a functional release.

if ! wp plugin is-active wp-stateless; then
    echo "wp-stateless not active, skipping migration"
    exit 0
fi

echo "Pending WP-Stateless migrations before run:"
wp stateless migrate

# Batches are handled by a background loopback POST to admin-ajax.php. If the pod
# cannot reach itself, the migration queues and never runs, and the poll loop inside
# `migrate auto` spins until the timeout fires. Check first and bail loudly.
code=$(wp eval 'echo wp_remote_retrieve_response_code( wp_remote_post( admin_url("admin-ajax.php"), ["timeout" => 10, "body" => ["action" => "heartbeat"]] ) );')
if [ "$code" != "200" ]; then
    echo "ERROR: loopback to admin-ajax.php returned '$code'. Skipping WP-Stateless migration." >&2
    exit 0
fi

echo "Running pending WP-Stateless migrations"
timeout 1800 wp stateless migrate auto --yes
status=$?

if [ $status -ne 0 ]; then
    echo "ERROR: WP-Stateless migration exited $status (124 = timeout). Check 'wp stateless migrate'." >&2
fi

echo "WP-Stateless migration status after run:"
wp stateless migrate
