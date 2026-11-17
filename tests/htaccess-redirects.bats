#!/usr/bin/env bats
# bats suite for bin/htaccess-redirects.sh (wp-proudcity#3000).
#
# Each test sources the script in a fresh bash subshell with REDIRECTS /
# REDIRECTS_KEEP_PATH / HOST set explicitly, and asserts on the generated
# block (stdout) or on a fixture .htaccess under a per-test temp dir. Nothing
# touches a real .htaccess.
#
# Run with: bats tests/htaccess-redirects.bats

setup() {
    HERE="$BATS_TEST_DIRNAME"
    export HTR_SCRIPT="$HERE/../bin/htaccess-redirects.sh"
    WORK="$(mktemp -d)"
    unset REDIRECTS REDIRECTS_KEEP_PATH HOST
}

teardown() {
    rm -rf "$WORK"
}

# block: run htaccess_redirects_block; $output = stdout + stderr, $stdout_only = stdout
block() {
    run bash -c 'source "$HTR_SCRIPT"; htaccess_redirects_block 2>/dev/null'
}

block_with_logs() {
    run bash -c 'source "$HTR_SCRIPT"; htaccess_redirects_block 2>&1 >/dev/null'
}

assert_no_block() {
    [ "$status" -eq 0 ]
    [ -z "$output" ]
}

@test "fixed target: host condition and fixed rule" {
    export REDIRECTS='srfd.org,https://sanrafael.gov/departments/fire/'
    block
    [ "$status" -eq 0 ]
    [[ "$output" == "# BEGIN Managed: Domain Redirects"* ]]
    [[ "$output" == *"# END Managed: Domain Redirects"* ]]
    [[ "$output" == *'RewriteCond %{HTTP_HOST} ^srfd\.org$ [NC]'* ]]
    [[ "$output" == *'RewriteRule ^ https://sanrafael.gov/departments/fire/ [R=301,L]'* ]]
}

@test "keep path: appends \$1, trailing slash stripped" {
    export REDIRECTS_KEEP_PATH='cityofsanrafael.org,https://sanrafael.gov/'
    block
    [[ "$output" == *'RewriteCond %{HTTP_HOST} ^cityofsanrafael\.org$ [NC]'* ]]
    [[ "$output" == *'RewriteRule ^(.*)$ https://sanrafael.gov/$1 [R=301,L]'* ]]
}

@test "keep path: origin without trailing slash" {
    export REDIRECTS_KEEP_PATH='a.example.org,https://sanrafael.gov'
    block
    [[ "$output" == *'RewriteRule ^(.*)$ https://sanrafael.gov/$1 [R=301,L]'* ]]
}

@test "multiple lines, CRLF, blank lines, surrounding spaces" {
    export REDIRECTS=$'  a.example.org , https://one.gov/x \r\n\r\n\n b.example.org,https://two.gov\r\n'
    block
    [[ "$output" == *'^a\.example\.org$ [NC]'* ]]
    [[ "$output" == *'RewriteRule ^ https://one.gov/x [R=301,L]'* ]]
    [[ "$output" == *'^b\.example\.org$ [NC]'* ]]
    [[ "$output" == *'RewriteRule ^ https://two.gov [R=301,L]'* ]]
    [[ "$output" != *$'\r'* ]]
}

@test "from host is lowercased" {
    export REDIRECTS='SRFD.Org,https://sanrafael.gov/'
    block
    [[ "$output" == *'^srfd\.org$ [NC]'* ]]
}

@test "split on first comma only: extra comma makes target invalid" {
    export REDIRECTS='a.example.org,https://one.gov/x,y'
    block
    assert_no_block
}

@test "relative target becomes https://HOST/path" {
    export HOST=www.playkettering.org
    export REDIRECTS='pfhy.org,/pfhy'
    block
    [[ "$output" == *'RewriteRule ^ https://www.playkettering.org/pfhy [R=301,L]'* ]]
}

@test "relative target is skipped without HOST" {
    export REDIRECTS='pfhy.org,/pfhy'
    block
    assert_no_block
}

@test "placeholder is skipped silently" {
    export REDIRECTS='home2.proudcity.com,placeholder'
    block_with_logs
    [ -z "$output" ]
}

@test "no vars set: empty output, status 0" {
    block
    assert_no_block
}

# Every one of these must write nothing.
reject_fixed() {
    REDIRECTS="$1" block
    assert_no_block
}

reject_keep() {
    REDIRECTS_KEEP_PATH="$1" block
    assert_no_block
}

@test "rejects dollar back-reference in REDIRECTS" {
    reject_fixed 'a.example.org,https://x.gov/$1'
}

@test "rejects dollar back-reference in KEEP_PATH" {
    reject_keep 'a.example.org,https://x.gov/$1'
}

@test "rejects server variable %{...}" {
    reject_fixed 'a.example.org,https://x.gov/%{REQUEST_URI}'
}

@test "rejects space in target" {
    reject_fixed 'a.example.org,https://x.gov/a b'
}

@test "rejects flag injection [R=302]" {
    reject_fixed 'a.example.org,https://x.gov/ [R=302]'
    reject_fixed 'a.example.org,https://x.gov/a[R=302]'
}

@test "rejects embedded newline adding a RewriteRule" {
    REDIRECTS=$'a.example.org,https://x.gov/\nRewriteRule ^ https://evil.com [R=301,L]' block
    [[ "$output" != *evil.com* ]]
    [[ "$output" == *'RewriteRule ^ https://x.gov/ [R=301,L]'* ]]
    [[ "$(grep -c '^RewriteRule' <<<"$output")" -eq 1 ]]
}

@test "rejects backslash" {
    reject_fixed 'a.example.org,https://x.gov/a\b'
}

@test "rejects quotes" {
    reject_fixed 'a.example.org,https://x.gov/"a"'
    reject_fixed "a.example.org,https://x.gov/'a'"
}

@test "rejects question mark and fragment" {
    reject_fixed 'a.example.org,https://x.gov/a?b=1'
    reject_fixed 'a.example.org,https://x.gov/a#b'
}

@test "rejects http:// in KEEP_PATH" {
    reject_keep 'a.example.org,http://x.gov'
}

@test "rejects a path in KEEP_PATH" {
    reject_keep 'a.example.org,https://x.gov/old-site'
}

@test "rejects bad from hosts" {
    reject_fixed '-x.org,https://x.gov/'
    reject_fixed 'a..b,https://x.gov/'
    reject_fixed '*.x.org,https://x.gov/'
    reject_fixed 'x_y.org,https://x.gov/'
    reject_fixed 'nodot,https://x.gov/'
    reject_fixed 'a.example.org$,https://x.gov/'
}

@test "rejects bad target hosts" {
    reject_fixed 'a.example.org,https://-x.gov/'
    reject_fixed 'a.example.org,https://x..gov/'
    reject_fixed 'a.example.org,https://nodot/'
}

@test "rejects from equal to HOST" {
    HOST=sanrafael.gov REDIRECTS='sanrafael.gov,https://x.gov/' block
    assert_no_block
}

@test "rejects self-loop" {
    reject_fixed 'a.example.org,https://a.example.org/x'
    reject_keep 'a.example.org,https://A.example.org'
}

@test "rejected lines are logged with %q and valid lines still written" {
    export REDIRECTS=$'bad host,https://x.gov/\nok.example.org,https://x.gov/'
    block
    [[ "$output" == *'^ok\.example\.org$'* ]]
    block_with_logs
    [[ "$output" == *"WARNING skipping bad\\ host\\,https://x.gov/"* ]]
}

@test "control characters in a rejected value are escaped in the log" {
    export REDIRECTS=$'a.example.org,https://x.gov/\x01\x1b[31m'
    block_with_logs
    [[ "$output" == *'WARNING skipping'* ]]
    [[ "$output" != *$'\x1b'* ]]
    [[ "$output" != *$'\x01'* ]]
}

@test "duplicate host across both vars: KEEP_PATH wins, warning logged" {
    export REDIRECTS='a.example.org,https://fixed.gov/'
    export REDIRECTS_KEEP_PATH='a.example.org,https://keep.gov'
    block
    [[ "$output" == *'https://keep.gov/$1'* ]]
    [[ "$output" != *fixed.gov* ]]
    block_with_logs
    [[ "$output" == *"REDIRECTS_KEEP_PATH, which wins"* ]]
}

@test "duplicate host within one var: first wins" {
    export REDIRECTS=$'a.example.org,https://one.gov/\na.example.org,https://two.gov/'
    block
    [[ "$output" == *one.gov* ]]
    [[ "$output" != *two.gov* ]]
}

@test "nothing in a generated block can carry a stray dollar, percent or bracket" {
    export REDIRECTS=$'a.example.org,https://x.gov/p\nb.example.org,https://x.gov/$1\nc.example.org,https://x.gov/%41'
    export REDIRECTS_KEEP_PATH='d.example.org,https://y.gov'
    block
    # Only the generated $ anchors, the one $1 and [NC]/[R=301,L] flags are allowed.
    stripped="$output"
    stripped="${stripped//\$ \[NC\]/}"
    stripped="${stripped//^(.*)\$ /}"
    stripped="${stripped//\/\$1 \[R=301,L\]/}"
    stripped="${stripped//\[R=301,L\]/}"
    [[ "$stripped" != *'$'* ]]
    [[ "$stripped" != *'%41'* ]]
    [[ "$stripped" != *'['* ]] || [[ "$stripped" == *'[NC]'* ]]
}

# --- apply ---

fixture() {
    cat >"$WORK/.htaccess" <<'HT'
# BEGIN Managed: Dynamic robots.txt
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^robots\.txt$ /index.php [L]
</IfModule>
# END Managed: Dynamic robots.txt

# BEGIN WordPress
RewriteRule . /index.php [L]
# END WordPress
HT
}

apply() {
    run bash -c 'source "$HTR_SCRIPT"; htaccess_redirects_apply "$WORK/.htaccess" 2>/dev/null; echo "rc=$?"'
}

@test "apply twice: one block, above robots and WordPress rules" {
    fixture
    export WORK
    export REDIRECTS='srfd.org,https://sanrafael.gov/departments/fire/'
    apply
    [[ "$output" == *"rc=0"* ]]
    apply
    [[ "$output" == *"rc=0"* ]]

    [ "$(grep -c '^# BEGIN Managed: Domain Redirects$' "$WORK/.htaccess")" -eq 1 ]
    [ "$(grep -c '^# END Managed: Domain Redirects$' "$WORK/.htaccess")" -eq 1 ]

    redir=$(grep -n '^# BEGIN Managed: Domain Redirects$' "$WORK/.htaccess" | cut -d: -f1)
    robots=$(grep -n '^# BEGIN Managed: Dynamic robots.txt$' "$WORK/.htaccess" | cut -d: -f1)
    [ "$redir" -lt "$robots" ]
    [ "$robots" -lt "$(grep -n "^# BEGIN WordPress$" "$WORK/.htaccess" | cut -d: -f1)" ]
}

@test "apply is byte-identical when run twice" {
    fixture
    export WORK
    export REDIRECTS='srfd.org,https://sanrafael.gov/departments/fire/'
    apply
    cp "$WORK/.htaccess" "$WORK/first"
    apply
    cmp "$WORK/.htaccess" "$WORK/first"
}

@test "apply with empty vars strips an old block and adds nothing" {
    fixture
    export WORK
    export REDIRECTS='srfd.org,https://sanrafael.gov/'
    apply
    grep -q 'Domain Redirects' "$WORK/.htaccess"
    unset REDIRECTS
    apply
    ! grep -q 'Domain Redirects' "$WORK/.htaccess"
    ! grep -q 'srfd' "$WORK/.htaccess"
    grep -q 'END WordPress' "$WORK/.htaccess"
}

@test "apply on a missing file returns 0" {
    export WORK
    export REDIRECTS='srfd.org,https://sanrafael.gov/'
    run bash -c 'set -e; source "$HTR_SCRIPT"; htaccess_redirects_apply "$WORK/nope" 2>/dev/null; echo survived'
    [ "$status" -eq 0 ]
    [[ "$output" == *survived* ]]
}

@test "apply with only invalid values leaves the file untouched and returns 0" {
    fixture
    export WORK
    cp "$WORK/.htaccess" "$WORK/orig"
    export REDIRECTS='a.example.org,https://x.gov/$1'
    run bash -c 'set -e; source "$HTR_SCRIPT"; htaccess_redirects_apply "$WORK/.htaccess" 2>/dev/null; echo survived'
    [ "$status" -eq 0 ]
    [[ "$output" == *survived* ]]
    cmp "$WORK/.htaccess" "$WORK/orig"
}

@test "IFS is unchanged after sourcing and running" {
    fixture
    export WORK
    export REDIRECTS=$'a.example.org,https://x.gov/\nb.example.org,https://y.gov/'
    export REDIRECTS_KEEP_PATH='c.example.org,https://z.gov'
    run bash -c '
        before="$IFS"
        source "$HTR_SCRIPT"
        htaccess_redirects_block >/dev/null 2>&1
        htaccess_redirects_apply "$WORK/.htaccess" >/dev/null 2>&1
        [[ "$IFS" == "$before" ]] && ! declare -p IFS | grep -q "^declare -x" && echo same'
    [ "$status" -eq 0 ]
    [[ "$output" == *same* ]]
}

# --- regression: live values from 2026-11-17 ---

@test "live values produce the expected lines" {
    export HOST=ketteringohparks.org
    export REDIRECTS=$'srfd.org,https://sanrafael.gov/departments/fire/\nsrpubliclibrary.org,https://sanrafael.gov/library/\nlivelifelocally.org,https://sanrafael.gov/classes-and-programs\npfhy.org,/pfhy\nwww.pfhy.org,/pfhy\nktpd.org,https://www.kalamazoopublicsafety.org\nhome2.proudcity.com,placeholder'
    export REDIRECTS_KEEP_PATH=$'cityofsanrafael.org,https://sanrafael.gov\nwww.cityofsanrafael.org,https://sanrafael.gov\nhellamtownship.com,https://www.hellamtownship.gov'
    block
    [[ "$output" == *'^srfd\.org$ [NC]'* ]]
    [[ "$output" == *'RewriteRule ^ https://sanrafael.gov/departments/fire/ [R=301,L]'* ]]
    [[ "$output" == *'RewriteRule ^ https://sanrafael.gov/library/ [R=301,L]'* ]]
    [[ "$output" == *'RewriteRule ^ https://sanrafael.gov/classes-and-programs [R=301,L]'* ]]
    [[ "$output" == *'^pfhy\.org$ [NC]'* ]]
    [[ "$output" == *'^www\.pfhy\.org$ [NC]'* ]]
    [[ "$output" == *'RewriteRule ^ https://ketteringohparks.org/pfhy [R=301,L]'* ]]
    [[ "$output" == *'RewriteRule ^ https://www.kalamazoopublicsafety.org [R=301,L]'* ]]
    [[ "$output" == *'^cityofsanrafael\.org$ [NC]'* ]]
    [[ "$output" == *'^www\.cityofsanrafael\.org$ [NC]'* ]]
    [[ "$output" == *'RewriteRule ^(.*)$ https://sanrafael.gov/$1 [R=301,L]'* ]]
    [[ "$output" == *'RewriteRule ^(.*)$ https://www.hellamtownship.gov/$1 [R=301,L]'* ]]
    [[ "$output" != *placeholder* ]]
    [ "$(grep -c '^RewriteCond' <<<"$output")" -eq 9 ]
}

# --- hardening ---

@test "overlong target is skipped with a warning; other lines still written" {
    long="https://x.gov/$(printf 'a%.0s' $(seq 1 9000))"
    export REDIRECTS=$'a.example.org,'"$long"$'\nb.example.org,https://ok.gov/'
    block
    [[ "$output" == *'^b\.example\.org$'* ]]
    [[ "$output" != *'a.example.org'* ]]
    [ "$(wc -L <<<"$output")" -lt 500 ]
    block_with_logs
    [[ "$output" == *"WARNING skipping"* ]]
}

@test "apply with overlong target still writes the valid lines" {
    fixture
    export WORK
    long="https://x.gov/$(printf 'a%.0s' $(seq 1 9000))"
    export REDIRECTS=$'a.example.org,'"$long"$'\nb.example.org,https://ok.gov/'
    apply
    grep -q 'b\\.example\\.org' "$WORK/.htaccess"
    [ "$(wc -L <"$WORK/.htaccess")" -lt 500 ]
}

@test "stray BEGIN without END: file untouched, WordPress block kept" {
    export WORK
    printf '# BEGIN Managed: Domain Redirects\nstale\n\n# BEGIN WordPress\nRewriteRule . /index.php [L]\n# END WordPress\n' >"$WORK/.htaccess"
    cp "$WORK/.htaccess" "$WORK/orig"
    export REDIRECTS='srfd.org,https://sanrafael.gov/'
    apply
    [[ "$output" == *"rc=0"* ]]
    grep -q '^# BEGIN WordPress$' "$WORK/.htaccess"
    grep -q '^# END WordPress$' "$WORK/.htaccess"
    cmp "$WORK/.htaccess" "$WORK/orig"
}

@test "stray END without BEGIN: file untouched" {
    export WORK
    printf '# END Managed: Domain Redirects\n# BEGIN WordPress\n# END WordPress\n' >"$WORK/.htaccess"
    cp "$WORK/.htaccess" "$WORK/orig"
    export REDIRECTS='srfd.org,https://sanrafael.gov/'
    apply
    cmp "$WORK/.htaccess" "$WORK/orig"
}

@test "non-ASCII host is rejected under a UTF-8 locale" {
    locale -a | grep -qi '^en_US\.utf-\?8$' || skip "en_US.UTF-8 not available"
    LC_ALL=en_US.UTF-8 REDIRECTS='é.com,https://x.gov/' block
    assert_no_block
    LC_ALL=en_US.UTF-8 REDIRECTS='a.example.org,https://é.gov/' block
    assert_no_block
}
