## 2026-06-01

- PCD269: wired `EP_HELPER_USER` and `EP_HELPER_PASS` env-to-constant glue in `wp-config.php` so the WP plugin can send HTTP Basic Auth to the docsapi (commits `dbfb595`, `5ac25ec`, `43e1448`). Required bumping `wp-proud-search-elastic` in all three places in composer.json (top-level `require`, `repositories[].package.version`, and `repositories[].package.source.reference`) to plugin tag `2026.06.01.1513`.
- PCD269: added `EP_HELPER_AUTH_HOST` env-to-constant glue in `wp-config.php` and bumped composer.json to plugin tag `2026.06.02.1320` (commit `af31390`). When this constant is defined the plugin routes attachment indexing calls to the authenticated `/send-attachments-auth` endpoint instead of the legacy unauthenticated one.

References: https://github.com/proudcity/pc-dev-issues/issues/269

## 2026-06-03

- Switched `wp-stateless-gravity-forms-addon` from wpackagist to the `proudcity/wp-stateless-gravity-forms-addon` fork (composer.json, commits 90907fb and d13bd8b) to pull in the GF 2.10 JSON storage fix. The fork's `fix/gf-2.10-json-storage` branch (v0.0.4) detects the actual value shape rather than relying on `$field->multipleFiles`, so single-file fields now sync to GCS correctly under GF 2.10+. Also fixes a latent `modify_db()` bug that silently dropped Sync-tab URL rewrites. Upstream PR filed at udx/wp-stateless-gravity-forms-addon#16. Switch back to wpackagist once upstream ships a release > 0.0.3 with the fix (checklist in `.github/ISSUE_TEMPLATE/core-plugin-update.md`).
- Added "WP-Stateless Gravity Forms Addon" section to `.github/ISSUE_TEMPLATE/core-plugin-update.md` documenting fork-pull steps and a reminder checkbox to revert to wpackagist once upstream merges.

References: https://github.com/proudcity/wp-proudcity/issues/2831

## 2026-06-01

- Added `disable-gf-stripe-rate-limit.php` mu-plugin (b32acb4) to immediately disable the GF Stripe per-IP rate limiter via `gform_stripe_enable_rate_limits`, preventing shared internal k8s node IPs from tripping a platform-wide lockout. Stripe Radar server-side fraud checks remain active.
- Gated the rate-limiter override behind `PC_DISABLE_GF_STRIPE_RATE_LIMIT=true` env var (7b8909c, plugin v1.1.0) so the disable is opt-in per tenant via workload YAML rather than applied platform-wide.
- Added `trust-proxy-client-ip.php` mu-plugin (92795d2) to rewrite `REMOTE_ADDR` from the rightmost `X-Forwarded-For` entry when REMOTE_ADDR is RFC1918. Currently a no-op in production until the nginx-ingress-lb LB migration (pc-dev-issues#287) delivers real client IPs.

References: https://github.com/proudcity/wp-proudcity/issues/2829

## 2026-04-24

- Updated entrypoint.sh to write the SSH private key from the GITHUB_SSH_KEY env var to /root/.ssh/id_rsa at container startup, before any git clone runs. Previously the key was baked into the Docker image layers (the original exposure); after the key was rotated the entrypoint was never wired up to load it at runtime, so clones were failing.

References: https://github.com/proudcity/pc-dev-issues/issues/184

## 2026-04-16

- Hardened HTTP response headers: set `expose_php = Off` in `etc/php.ini` to suppress the `X-Powered-By: PHP/x.x.x` header, added `Header always unset X-Powered-By` to `etc/apache-vhost.conf` as a belt-and-suspenders removal at the Apache level, and added five standard security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `X-XSS-Protection`, `Permissions-Policy`) to the same vhost config. Enabled `mod_headers` unconditionally in `Dockerfile` to support all of the above.

References: https://github.com/proudcity/wp-proudcity/issues/2795
References: https://github.com/proudcity/wp-proudcity/issues/2796
