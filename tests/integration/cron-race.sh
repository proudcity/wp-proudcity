#!/usr/bin/env bash
#
# Manual integration repro for the wp-cron read-modify-write race (wp-proudcity#2961).
#
# Scenario 1 (schedule vs. schedule): process A stands in for a long-running cron
# hook -- it loads the `cron` option, sleeps, then makes an ordinary cron mutation.
# Process B stands in for an editor scheduling a post while A is asleep. Without the
# fix, A's mutation silently erases B's event because A is still working from the
# stale copy of `cron` it loaded before sleeping.
#
# Scenario 2 (schedule vs. clear): process A loads `cron`, sleeps, then clears an
# event (proud2961_b) it has no way of knowing was scheduled while it slept; process
# B schedules that event partway through A's sleep. An earlier, buggier version of
# proud-cron-integrity.php disarmed its database read after the mutator's own first
# read, so update_option()'s later $old_value read fell back to this process's
# stale in-memory copy. That stale $old_value happened to equal the value A was
# about to write (both missing B's event), so update_option() skipped the write
# entirely -- the event B scheduled survived A's explicit clear. The fix keeps the
# flag armed until pre_update_option_cron fires, so that $old_value read is fresh
# too. Scenario 2 needs `--skip-plugins`: it depends on exact equality between the
# old and new `cron` arrays, and this site's real cron jobs (Action Scheduler, BLC,
# etc.) ticking in the background during the 15s sleep is enough unrelated noise to
# mask that exact-equality check. Scenario 1 only cares whether its own hook is
# present, so it does not need `--skip-plugins`.
#
# Usage:
#   tests/integration/cron-race.sh [path-to-local-wp-install]
#
# Defaults to $HOME/Sites/proudtest (see AGENTS.md / reference_local_domain.md).
# Requires `wp` on PATH and a working local site; makes no changes other than
# temporarily copying the mu-plugin into that site's wp-content/mu-plugins and
# scheduling/clearing two throwaway cron hooks (proud2961_a, proud2961_b).

set -euo pipefail

WP_PATH="${1:-$HOME/Sites/proudtest}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SRC="$SCRIPT_DIR/../../www/wp-content/mu-plugins/proud-cron-integrity.php"
PLUGIN_DEST="$WP_PATH/wp-content/mu-plugins/proud-cron-integrity.php"
PLUGIN_COPIED=0

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"; if [ "$PLUGIN_COPIED" = "1" ]; then rm -f "$PLUGIN_DEST"; fi; ( cd "$WP_PATH" && wp --skip-plugins eval "wp_clear_scheduled_hook( \"proud2961_a\" ); wp_clear_scheduled_hook( \"proud2961_b\" );" ) >/dev/null 2>&1 || true' EXIT

if [ ! -d "$WP_PATH" ]; then
	echo "No WordPress install at $WP_PATH" >&2
	exit 1
fi

if [ ! -f "$PLUGIN_SRC" ]; then
	echo "Expected mu-plugin at $PLUGIN_SRC" >&2
	exit 1
fi

if [ -e "$PLUGIN_DEST" ] || [ -L "$PLUGIN_DEST" ]; then
	echo "$PLUGIN_DEST already exists; remove it before running this script." >&2
	exit 1
fi

# --- Scenario 1 fixtures: A schedules, B schedules ---

cat > "$TMP_DIR/a.php" <<'EOF'
<?php
// Process A: stands in for a long cron run. Loads the cron array at start,
// "runs a long hook" (sleep), then makes a normal cron mutation.
_get_cron_array();
echo "A: loaded cron, sleeping 15s\n";
sleep( 15 );
wp_schedule_single_event( time() + 3600, 'proud2961_a' );
echo "A: scheduled proud2961_a\n";
EOF

cat > "$TMP_DIR/b.php" <<'EOF'
<?php
// Process B: stands in for an editor saving a scheduled post.
wp_schedule_single_event( time() + 7200, 'proud2961_b' );
echo "B: scheduled proud2961_b -> ", var_export( (bool) wp_next_scheduled( 'proud2961_b' ), true ), "\n";
EOF

cat > "$TMP_DIR/check.php" <<'EOF'
<?php
wp_cache_delete( 'alloptions', 'options' );
echo "after both: proud2961_a=", var_export( (bool) wp_next_scheduled( 'proud2961_a' ), true ),
     " proud2961_b=", var_export( (bool) wp_next_scheduled( 'proud2961_b' ), true ), "\n";
wp_clear_scheduled_hook( 'proud2961_a' );
wp_clear_scheduled_hook( 'proud2961_b' );
EOF

# --- Scenario 2 fixtures: A clears proud2961_b, B schedules it ---

cat > "$TMP_DIR/a_clear.php" <<'EOF'
<?php
// Process A (clear scenario): loads the cron array, "runs a long hook" (sleep),
// then clears an event it has no way of knowing was scheduled while it slept.
_get_cron_array();
echo "A: loaded cron, sleeping 15s\n";
sleep( 15 );
wp_clear_scheduled_hook( 'proud2961_b' );
echo "A: cleared proud2961_b\n";
EOF

cat > "$TMP_DIR/check_clear.php" <<'EOF'
<?php
wp_cache_delete( 'alloptions', 'options' );
echo "after both: proud2961_b=", var_export( (bool) wp_next_scheduled( 'proud2961_b' ), true ), "\n";
wp_clear_scheduled_hook( 'proud2961_b' );
EOF

run_scenario() {
	local a_file="$1" b_file="$2" check_file="$3"

	( cd "$WP_PATH" && wp eval-file "$TMP_DIR/$a_file" ) &
	local a_pid=$!
	sleep 4
	( cd "$WP_PATH" && wp eval-file "$TMP_DIR/$b_file" )
	wait "$a_pid"
	( cd "$WP_PATH" && wp eval-file "$TMP_DIR/$check_file" )
}

run_scenario_no_plugins() {
	local a_file="$1" b_file="$2" check_file="$3"

	( cd "$WP_PATH" && wp --skip-plugins eval-file "$TMP_DIR/$a_file" ) &
	local a_pid=$!
	sleep 4
	( cd "$WP_PATH" && wp --skip-plugins eval-file "$TMP_DIR/$b_file" )
	wait "$a_pid"
	( cd "$WP_PATH" && wp --skip-plugins eval-file "$TMP_DIR/$check_file" )
}

clean_baseline() {
	( cd "$WP_PATH" && wp --skip-plugins eval 'wp_clear_scheduled_hook( "proud2961_a" ); wp_clear_scheduled_hook( "proud2961_b" );' ) >/dev/null 2>&1
}

fail=0

echo "=== Scenario 1 (schedule vs. schedule) ==="
echo "--- Control: without the mu-plugin (expect proud2961_b=false) ---"
clean_baseline
run_scenario a.php b.php check.php

echo
echo "=== Scenario 2 (schedule vs. clear) ==="
echo "--- Control: without the mu-plugin (expect proud2961_b=true, wrongly surviving A's clear) ---"
clean_baseline
run_scenario_no_plugins a_clear.php b.php check_clear.php

echo
echo "=== Scenario 1, with the mu-plugin, 3 overlap runs (expect proud2961_a=true proud2961_b=true every time) ==="
cp "$PLUGIN_SRC" "$PLUGIN_DEST"
PLUGIN_COPIED=1

for i in 1 2 3; do
	echo "run $i:"
	clean_baseline
	out="$(run_scenario a.php b.php check.php)"
	echo "$out"
	if ! echo "$out" | grep -q "proud2961_a=true proud2961_b=true"; then
		echo "run $i did not survive the race" >&2
		fail=1
	fi
done

echo
echo "=== Scenario 2, with the mu-plugin, 3 runs (--skip-plugins for a noise-free exact-equality check; expect proud2961_b=false every time: A's clear must win) ==="
for i in 1 2 3; do
	echo "run $i:"
	clean_baseline
	out="$(run_scenario_no_plugins a_clear.php b.php check_clear.php)"
	echo "$out"
	if ! echo "$out" | grep -q "proud2961_b=false"; then
		echo "run $i: proud2961_b wrongly survived A's clear" >&2
		fail=1
	fi
done

rm -f "$PLUGIN_DEST"
PLUGIN_COPIED=0

echo
echo "--- Control re-check: mu-plugin removed again (expect proud2961_b=false for scenario 1) ---"
clean_baseline
run_scenario a.php b.php check.php

if [ "$fail" != "0" ]; then
	echo "FAILED: proud-cron-integrity.php did not behave correctly in every run" >&2
	exit 1
fi

echo
echo "OK: proud-cron-integrity.php closed both races in every run."
