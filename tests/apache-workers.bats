#!/usr/bin/env bats
# bats suite for bin/apache-workers.sh (wp-proudcity#2980).
#
# APACHE_WORKERS_CGROUP_V2_TEST_PATH/_V1_TEST_PATH point at fixture files
# under a per-test temp dir (or a path that doesn't exist, for the
# missing-file/no-limit cases), so nothing here ever reads the real pod's
# cgroup. Those are the only env-overridable settings in apache-workers.sh;
# every other tunable is a readonly constant, precisely so a stray env var
# in a real pod can never reach them. Each test sources the script in a
# fresh bash subshell and calls apache_workers_compute() directly, then
# asserts on the logged line and the exported APACHE_MAX_REQUEST_WORKERS.
#
# Run with: bats tests/apache-workers.bats
# (bats is provided by ~/Workspace/proudcity/shell.nix, same as the bats
# suite in proudcity-kubernetes/tests/bats.)

setup() {
    HERE="$BATS_TEST_DIRNAME"
    export APACHE_WORKERS_SCRIPT="$HERE/../bin/apache-workers.sh"

    WORK="$(mktemp -d)"
    export APACHE_WORKERS_CGROUP_V2_TEST_PATH="$WORK/memory.max"
    export APACHE_WORKERS_CGROUP_V1_TEST_PATH="$WORK/memory.limit_in_bytes"

    unset APACHE_MAX_REQUEST_WORKERS
}

teardown() {
    rm -rf "$WORK"
}

mib_to_bytes() {
    echo $(( $1 * 1048576 ))
}

compute() {
    run bash -c 'source "$APACHE_WORKERS_SCRIPT"; apache_workers_compute; echo "RESULT=$APACHE_MAX_REQUEST_WORKERS"'
}

assert_tier() {
    local computed="$1"
    [ "$status" -eq 0 ]
    [[ "$output" == *"computed=${computed} using=${computed} (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=${computed}"* ]]
}

@test "800Mi computes 7 workers" {
    mib_to_bytes 800 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 7
}

@test "1200Mi computes 14 workers" {
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 14
}

@test "1500Mi computes 18 workers" {
    mib_to_bytes 1500 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 18
}

@test "1600Mi computes 20 workers" {
    mib_to_bytes 1600 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 20
}

@test "2000Mi computes 26 workers" {
    mib_to_bytes 2000 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 26
}

@test "2200Mi computes 29 workers" {
    mib_to_bytes 2200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 29
}

@test "3000Mi computes 42 workers" {
    mib_to_bytes 3000 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 42
}

@test "4000Mi computes 57 workers" {
    mib_to_bytes 4000 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 57
}

@test "5000Mi computes 64 workers (clamped from 73)" {
    mib_to_bytes 5000 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    assert_tier 64
}

@test "cgroup v2 'max' is treated as no limit (1200Mi, 14 workers)" {
    echo "max" > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"limit=none (treated as 1200Mi) computed=14 using=14 (source=no-limit)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "missing cgroup files are treated as no limit (1200Mi, 14 workers)" {
    # Neither fixture file is created; the TEST_PATH vars point at paths
    # under $WORK that don't exist.
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"limit=none (treated as 1200Mi) computed=14 using=14 (source=no-limit)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "falls back to cgroup v1 when v2 is missing (1200Mi, 14 workers)" {
    # No v2 file at all; only v1.
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V1_TEST_PATH"
    compute
    assert_tier 14
}

@test "cgroup v1's huge 'unlimited' value is treated as no limit" {
    # No v2 file; v1 reports the near-LONG_MAX sentinel cgroup v1 uses for
    # "no limit" instead of v2's "max" string.
    echo "9223372036854771712" > "$APACHE_WORKERS_CGROUP_V1_TEST_PATH"
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"limit=none (treated as 1200Mi) computed=14 using=14 (source=no-limit)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "override: a valid APACHE_MAX_REQUEST_WORKERS wins" {
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=8
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"limit=1200Mi computed=14 using=8 (source=override)"* ]]
    [[ "$output" == *"RESULT=8"* ]]
}

@test "override: 0 is invalid, falls back to computed with a warning" {
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=0
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARNING ignoring APACHE_MAX_REQUEST_WORKERS='0'"* ]]
    [[ "$output" == *"limit=1200Mi computed=14 using=14 (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "override: 'abc' is invalid, falls back to computed with a warning" {
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=abc
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARNING ignoring APACHE_MAX_REQUEST_WORKERS='abc'"* ]]
    [[ "$output" == *"limit=1200Mi computed=14 using=14 (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "override: 999 is above the 256 cap, falls back to computed with a warning" {
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=999
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARNING ignoring APACHE_MAX_REQUEST_WORKERS='999'"* ]]
    [[ "$output" == *"limit=1200Mi computed=14 using=14 (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "override: a negative value is invalid, falls back to computed with a warning" {
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=-5
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARNING ignoring APACHE_MAX_REQUEST_WORKERS='-5'"* ]]
    [[ "$output" == *"limit=1200Mi computed=14 using=14 (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "override: a leading zero ('010') is invalid, falls back to computed with a warning" {
    # The regex requires a nonzero leading digit, so bash's arithmetic
    # context never gets a chance to read this as octal.
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=010
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARNING ignoring APACHE_MAX_REQUEST_WORKERS='010'"* ]]
    [[ "$output" == *"limit=1200Mi computed=14 using=14 (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "override: a leading zero ('08') is invalid, falls back to computed with a warning" {
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=08
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARNING ignoring APACHE_MAX_REQUEST_WORKERS='08'"* ]]
    [[ "$output" == *"limit=1200Mi computed=14 using=14 (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "override: a 64-bit-overflow value is invalid, falls back to computed with a warning" {
    # 3-digit cap on the regex rejects this outright, before it could ever
    # reach the (( )) comparison and wrap.
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_MAX_REQUEST_WORKERS=18446744073709551621
    compute
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARNING ignoring APACHE_MAX_REQUEST_WORKERS='18446744073709551621'"* ]]
    [[ "$output" == *"limit=1200Mi computed=14 using=14 (source=cgroup)"* ]]
    [[ "$output" == *"RESULT=14"* ]]
}

@test "a garbage tunable in the pod environment has no effect (tunables are readonly)" {
    # APACHE_WORKERS_PER_WORKER_MI used to be env-overridable; 0 there would
    # have divided by zero. It's a readonly constant now, so a pod setting
    # it (accidentally or otherwise) changes nothing and the script still
    # computes the normal 1200Mi tier.
    mib_to_bytes 1200 > "$APACHE_WORKERS_CGROUP_V2_TEST_PATH"
    export APACHE_WORKERS_PER_WORKER_MI=0
    export APACHE_WORKERS_RESERVE_MI=0
    export APACHE_WORKERS_MIN=0
    export APACHE_WORKERS_MAX=0
    export APACHE_WORKERS_NO_LIMIT_MI=0
    export APACHE_WORKERS_OVERRIDE_MAX=0
    compute
    assert_tier 14
}
