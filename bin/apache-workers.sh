#!/bin/bash
# apache-workers.sh — size Apache prefork's MaxRequestWorkers to the pod's
# cgroup memory limit (wp-proudcity#2980).
#
# A fixed MaxRequestWorkers (previously 1500 for every pod, regardless of its
# memory limit) lets prefork keep forking PHP workers under a crawler or
# scanner burst until the container passes its cgroup limit and the kernel
# OOM-kills it, taking every in-flight request on that pod down with it.
#
# Sourced by bin/entrypoint.sh, which calls apache_workers_compute() itself.
# This file only defines the function -- sourcing it does nothing, so it can
# be sourced from a bats test and exercised in isolation with fixture cgroup
# files (see tests/apache-workers.bats).

# Tunables. Changing these needs an image build. Deliberately NOT
# env-overridable: these used to read `${VAR:-default}` from the pod's own
# environment, which meant a bad deployment env var (e.g.
# APACHE_WORKERS_PER_WORKER_MI=0) could divide by zero and abort
# entrypoint.sh under `set -e`. A plain pod cannot change these any more.
readonly APACHE_WORKERS_RESERVE_MI=300
readonly APACHE_WORKERS_PER_WORKER_MI=64
readonly APACHE_WORKERS_MIN=5
readonly APACHE_WORKERS_MAX=64
readonly APACHE_WORKERS_OVERRIDE_MAX=256

# No limit (or an unreadable/unparseable limit) is treated as the 1200Mi tier,
# which is where most of the fleet sits.
readonly APACHE_WORKERS_NO_LIMIT_MI=1200

# cgroup v1 has no "max" string for unlimited; it reports a huge byte count
# instead (near LONG_MAX). Anything at or above 1Pi is treated as no limit --
# real pod memory limits never get anywhere close to that.
readonly APACHE_WORKERS_V1_UNLIMITED_BYTES=1125899906842624

# cgroup paths. These are the only overridable settings in this file, kept
# that way only so tests/apache-workers.bats can point them at fixture files
# -- the names say so, and nothing in a real pod's environment sets them.
APACHE_WORKERS_CGROUP_V2_TEST_PATH=${APACHE_WORKERS_CGROUP_V2_TEST_PATH:-/sys/fs/cgroup/memory.max}
APACHE_WORKERS_CGROUP_V1_TEST_PATH=${APACHE_WORKERS_CGROUP_V1_TEST_PATH:-/sys/fs/cgroup/memory/memory.limit_in_bytes}

# Echoes the pod's memory limit in MiB, or an empty string if there is no
# limit or it couldn't be read. Guarded throughout: entrypoint.sh runs under
# `set -e` and this must never abort the boot.
apache_workers_read_limit_mi() {
    local raw

    if [[ -r "$APACHE_WORKERS_CGROUP_V2_TEST_PATH" ]]; then
        raw="$(cat "$APACHE_WORKERS_CGROUP_V2_TEST_PATH" 2>/dev/null)" || raw=""

        if [[ "$raw" == "max" ]]; then
            echo ""
            return
        fi

        if [[ "$raw" =~ ^[0-9]+$ ]]; then
            echo $(( raw / 1048576 ))
            return
        fi
    fi

    if [[ -r "$APACHE_WORKERS_CGROUP_V1_TEST_PATH" ]]; then
        raw="$(cat "$APACHE_WORKERS_CGROUP_V1_TEST_PATH" 2>/dev/null)" || raw=""

        if [[ "$raw" =~ ^[0-9]+$ ]]; then
            if [[ "$raw" -ge "$APACHE_WORKERS_V1_UNLIMITED_BYTES" ]]; then
                echo ""
                return
            fi

            echo $(( raw / 1048576 ))
            return
        fi
    fi

    echo ""
}

# Computes and exports APACHE_MAX_REQUEST_WORKERS, and logs one line
# describing the decision. An already-set APACHE_MAX_REQUEST_WORKERS (a
# per-deployment override) wins if it's a positive integer up to
# APACHE_WORKERS_OVERRIDE_MAX with no leading zero -- no leading zero so bash
# can never read it as octal, and the regex caps it at 3 digits so the
# `(( ))` comparison can never see a value large enough to overflow/wrap.
# Numeric validation also keeps anything odd from being substituted into the
# Apache config via ${APACHE_MAX_REQUEST_WORKERS}.
apache_workers_compute() {
    local limit_mi limit_label source computed using v

    limit_mi="$(apache_workers_read_limit_mi)"

    if [[ -n "$limit_mi" ]]; then
        limit_label="${limit_mi}Mi"
        source="cgroup"
    else
        limit_mi="$APACHE_WORKERS_NO_LIMIT_MI"
        limit_label="none (treated as ${limit_mi}Mi)"
        source="no-limit"
    fi

    computed=$(( (limit_mi - APACHE_WORKERS_RESERVE_MI) / APACHE_WORKERS_PER_WORKER_MI ))
    if [[ "$computed" -lt "$APACHE_WORKERS_MIN" ]]; then
        computed="$APACHE_WORKERS_MIN"
    fi
    if [[ "$computed" -gt "$APACHE_WORKERS_MAX" ]]; then
        computed="$APACHE_WORKERS_MAX"
    fi

    using="$computed"
    v="$APACHE_MAX_REQUEST_WORKERS"

    if [[ -n "$v" ]]; then
        if [[ "$v" =~ ^[1-9][0-9]{0,2}$ ]] && (( 10#$v <= APACHE_WORKERS_OVERRIDE_MAX )); then
            using="$v"
            source="override"
        else
            echo "apache-workers: WARNING ignoring APACHE_MAX_REQUEST_WORKERS='${v}' (must be an integer 1-${APACHE_WORKERS_OVERRIDE_MAX}, no leading zero); using computed value ${computed}" >&2
        fi
    fi

    export APACHE_MAX_REQUEST_WORKERS="$using"
    echo "apache-workers: limit=${limit_label} computed=${computed} using=${using} (source=${source})"
}
