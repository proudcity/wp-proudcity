#!/bin/bash
# htaccess-redirects.sh — build the managed domain-redirect block for
# .htaccess from the REDIRECTS / REDIRECTS_KEEP_PATH env vars (wp-proudcity#3000).
#
# The old entrypoint code appended these rules to the END of .htaccess, after
# the WordPress catch-all (`RewriteRule . /index.php [L]`), so they only fired
# for "/" and for paths that exist on disk. The block is now prepended (above
# the robots and WordPress rules) between stable markers, so it is rewritten
# on every run, and every value is validated against a strict allowlist before
# it is written: a bad value is skipped with a warning and can never produce
# an .htaccess syntax error (which would 500 every request on the site).
#
# Env vars (newline-separated `from,to` lines):
#   REDIRECTS            from,https?://host(/path)?  |  from,/path  |  from,placeholder
#                        Fixed target; the request path is dropped, the query
#                        string is still appended. `/path` becomes
#                        https://$HOST/path (Apache has no canonical name behind
#                        HAProxy and would emit http://host:8080/path).
#   REDIRECTS_KEEP_PATH  from,https://host  (origin only)
#                        The request path (and query string) is appended.
#                        Set by ops with `kubectl set env`; the dashboard and API
#                        never read or write it. Wins over REDIRECTS for a host.
#
# Sourced by bin/entrypoint.sh, which calls htaccess_redirects_apply(). This
# file only defines functions -- sourcing it does nothing, so it can be sourced
# from a bats test (tests/htaccess-redirects.bats). It never changes IFS
# globally, and the functions never return non-zero from apply, so a bad value
# can't abort entrypoint.sh under `set -e`.

readonly HTACCESS_REDIRECTS_BEGIN="# BEGIN Managed: Domain Redirects"
readonly HTACCESS_REDIRECTS_END="# END Managed: Domain Redirects"

# Same character class as the API's REDIRECT_RE (no $ % ? # space quotes
# backslash [ ]).
readonly HTACCESS_REDIRECTS_PATH_RE='[A-Za-z0-9._~/-]*'

htaccess_redirects_log() {
    echo "htaccess-redirects: $*" >&2
}

# Lowercase host name: dot-separated labels of [a-z0-9-], no leading/trailing
# hyphen, at least one dot, at most 253 characters.
htaccess_redirects_valid_host() {
    local LC_ALL=C host="$1"
    local label='[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?'

    [[ ${#host} -le 253 ]] || return 1
    [[ "$host" =~ ^(${label}\.)+${label}$ ]]
}

# htaccess_redirects_target <to> <mode>
# mode "fixed" or "keep". Prints the normalized target, or returns 1.
htaccess_redirects_target() {
    local LC_ALL=C to="$1" mode="$2" host

    [[ ${#to} -le 2000 ]] || return 1

    if [[ "$mode" == "keep" ]]; then
        [[ "$to" =~ ^https://([A-Za-z0-9.-]+)/?$ ]] || return 1
        host="${BASH_REMATCH[1]}"
        htaccess_redirects_valid_host "${host,,}" || return 1
        echo "https://${host}"
        return 0
    fi

    if [[ "$to" =~ ^https?://([A-Za-z0-9.-]+)(/${HTACCESS_REDIRECTS_PATH_RE})?$ ]]; then
        host="${BASH_REMATCH[1]}"
        htaccess_redirects_valid_host "${host,,}" || return 1
        echo "$to"
        return 0
    fi

    if [[ "$to" =~ ^/${HTACCESS_REDIRECTS_PATH_RE}$ ]]; then
        htaccess_redirects_valid_host "${HOST,,}" || return 1
        echo "https://${HOST,,}${to}"
        return 0
    fi

    return 1
}

# Host part of a normalized target, lowercased.
htaccess_redirects_target_host() {
    local rest="${1#*://}"
    echo "${rest%%/*}" | tr '[:upper:]' '[:lower:]'
}

# Prints the full managed block on stdout (nothing if no entry is valid).
# Logs to stderr.
htaccess_redirects_block() {
    local mode data line from to target thost reason
    local body="" count=0
    local -A seen=()
    local primary="${HOST,,}"

    for mode in keep fixed; do
        if [[ "$mode" == "keep" ]]; then
            data="$REDIRECTS_KEEP_PATH"
        else
            data="$REDIRECTS"
        fi
        [[ -n "$data" ]] || continue

        while IFS= read -r line || [[ -n "$line" ]]; do
            # Trim CR and surrounding whitespace
            if [[ "$line" =~ ^[[:space:]]*(.*[^[:space:]])[[:space:]]*$ ]]; then
                line="${BASH_REMATCH[1]}"
            else
                continue
            fi
            [[ -n "$line" ]] || continue

            if [[ "$line" != *,* ]]; then
                htaccess_redirects_log "WARNING skipping $(printf %q "$line"): no comma"
                continue
            fi

            # Split on the first comma only
            from="${line%%,*}"
            to="${line#*,}"
            from="${from,,}"
            [[ "$from" =~ ^[[:space:]]*(.*[^[:space:]])?[[:space:]]*$ ]] && from="${BASH_REMATCH[1]}"
            [[ "$to" =~ ^[[:space:]]*(.*[^[:space:]])?[[:space:]]*$ ]] && to="${BASH_REMATCH[1]}"

            [[ "$to" == "placeholder" ]] && continue

            reason=""
            if ! htaccess_redirects_valid_host "$from"; then
                reason="invalid from host"
            elif [[ -n "$primary" && "$from" == "$primary" ]]; then
                reason="from host is this site's HOST"
            elif ! target="$(htaccess_redirects_target "$to" "$mode")"; then
                reason="invalid target"
            else
                thost="$(htaccess_redirects_target_host "$target")"
                if [[ "$thost" == "$from" ]]; then
                    reason="target is the same host (redirect loop)"
                elif [[ -n "${seen[$from]}" ]]; then
                    if [[ "${seen[$from]}" == "keep" && "$mode" == "fixed" ]]; then
                        reason="host also in REDIRECTS_KEEP_PATH, which wins"
                    else
                        reason="duplicate host"
                    fi
                fi
            fi

            if [[ -n "$reason" ]]; then
                htaccess_redirects_log "WARNING skipping $(printf %q "$line"): ${reason}"
                continue
            fi

            seen[$from]="$mode"
            body+="RewriteCond %{HTTP_HOST} ^${from//./\\.}\$ [NC]"$'\n'
            if [[ "$mode" == "keep" ]]; then
                body+="RewriteRule ^(.*)\$ ${target}/\$1 [R=301,L]"$'\n'
            else
                body+="RewriteRule ^ ${target} [R=301,L]"$'\n'
            fi
            count=$((count + 1))
            htaccess_redirects_log "Adding redirect from ${from} to ${target} (${mode})"
        done <<<"$data"
    done

    [[ "$count" -gt 0 ]] || return 0

    printf '%s\n' "$HTACCESS_REDIRECTS_BEGIN" \
        "<IfModule mod_rewrite.c>" \
        "RewriteEngine On"
    printf '%s' "$body"
    printf '%s\n%s\n' "</IfModule>" "$HTACCESS_REDIRECTS_END"
}

# htaccess_redirects_apply <htaccess path>
# Strips any previous managed block, then prepends the current one. Always
# returns 0 (see header).
htaccess_redirects_apply() {
    local htaccess="$1" block tmp="" tmp2="" begins ends

    if [[ ! -f "$htaccess" ]]; then
        htaccess_redirects_log "WARNING ${htaccess} not found; skipping"
        return 0
    fi

    # Never strip on an unbalanced marker pair: the range delete would run to
    # end of file and take the WordPress block with it.
    begins="$(grep -c "^${HTACCESS_REDIRECTS_BEGIN}\$" "$htaccess")" || true
    ends="$(grep -c "^${HTACCESS_REDIRECTS_END}\$" "$htaccess")" || true
    if [[ "$begins" != "$ends" ]]; then
        htaccess_redirects_log "WARNING unbalanced Domain Redirects markers in ${htaccess} (${begins} BEGIN, ${ends} END); leaving the file untouched"
        return 0
    fi

    block="$(htaccess_redirects_block)" || block=""
    tmp="$(mktemp)" && tmp2="$(mktemp)" || { rm -f "$tmp" "$tmp2"; return 0; }

    # Write back only after the new content is fully built; `cat >` keeps the
    # file's ownership and mode.
    if sed "/^${HTACCESS_REDIRECTS_BEGIN}\$/,/^${HTACCESS_REDIRECTS_END}\$/d" "$htaccess" >"$tmp" \
        && { [[ -z "$block" ]] || { printf '%s\n' "$block" | cat - "$tmp" >"$tmp2" && cat "$tmp2" >"$tmp"; }; } \
        && cat "$tmp" >"$htaccess"; then
        [[ -z "$block" ]] || echo "Added domain redirects to top of ${htaccess}"
    else
        htaccess_redirects_log "WARNING could not update ${htaccess}"
    fi

    rm -f "$tmp" "$tmp2"
    return 0
}
