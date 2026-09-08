#!/bin/sh
# This is a wrapper so that wp-cli can run as the www-data user so that permissions
# remain correct.
#
# Arguments are forwarded with "$@", not $*. Unquoted $* re-splits every
# argument on whitespace and lets the shell glob what is left, which made
# `wp eval`, `wp db query` and any --field="some value" unusable through this
# wrapper (proudcity/wp-proudcity#2921). Callers must pass each wp-cli
# argument separately; a whole command handed over as one string will now
# reach wp as a single argv entry.
exec wp "$@"
