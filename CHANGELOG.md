## 2026-06-25

- Added an Apache `[F]` guard in `www/.htaccess` (ships to `/app/wordpress/.htaccess`) to short-circuit backdoor/webshell scanner probes for non-existent root-level script paths (e.g. `/asdf.php`, `/root.php`, `/xll.asp`, `/shell.php5`). These probes were falling through the WordPress rewrite to `index.php`, triggering a full WP bootstrap and ~80–96 KB themed 404 render per request, burning PHP/Apache workers, CPU, and memory fleet-wide. The load was driving OOMKills and repeated up/down flapping on the kalamazoomi (ktwp.org) tenant in the prod namespace (CPU pinned at 306%, HPA at MAXPODS=4, memory OOMKilling at the 800 Mi cap). The rule (`RewriteRule ^[^/]+\.(php|phtml|php[0-9]?|asp|aspx|jsp|cgi|pl)$ - [F,L]` behind a `RewriteCond %{REQUEST_FILENAME} !-f` guard) returns a cheap 403 (239 bytes, no PHP) for non-existent root-level script paths. The `!-f` condition exempts real on-disk WP entry points (index.php, wp-login.php, wp-cron.php, xmlrpc.php, etc.); the `^[^/]+\.` root anchor prevents it from ever matching `/wp-admin/*.php` or `/wp-includes/*.php`. The rule is placed in the existing top-of-file hardening zone above the WP catch-all, alongside the UA-block rules. **The rule must live in `.htaccess`, not the vhost `<Directory>` block**: `/app/wordpress/.htaccess` declares its own `RewriteEngine On` context, which overrides the vhost rewrite rules (they are not merged/inherited), so a vhost-placed rule never fires for doc-root requests. This was verified on beta — the initial vhost placement (commit `fdb4b43`) left probes getting the full 96 KB WP 404; moving the rule to `.htaccess` (commit `083ce92`) dropped probes to 403 (239 bytes) with no PHP invoked. Post-rollout: CPU fell to ~19%, memory stabilized ~450 Mi, HPA scaled back to MIN 2, no further OOM events on ktwp.org. (commits `fdb4b43`, `083ce92`)

References: https://github.com/proudcity/wp-proudcity/issues/2847

## 2026-06-24

- Added `proud-robots.php` mu-plugin to append expensive-path `Disallow` rules to every tenant's `robots.txt` via the `robots_txt` filter (priority 10, before Yoast's priority-99999 sitemap append). A generic `User-agent: *` block covers Googlebot and any other compliant crawler; named blocks for Applebot, Bingbot, YandexBot, and DuckDuckBot add `Crawl-delay: 10` plus the same path list. Initial disallow set: `/search-site`, `/search-site/*`, `/*?pager=`, `/*?s=`, `/page/*/?s=`, `/events/page/*/`, `/locations/page/*/`, `/documents/page/*/`. Googlebot obeys the `*` block but gets no `Crawl-delay` (Google ignores it). Applebot and Bingbot are currently hard-blocked via `bots.csv` before they can fetch `robots.txt`; this rule is belt-and-suspenders and effective for any future state where that block is relaxed. Server-side ingress enforcement is tracked as pc-dev-issues#297.
- Updated `bin/entrypoint.sh` to make the mu-plugin effective fleet-wide. Beta smoke testing revealed that the entrypoint had been writing a static `/app/wordpress/robots.txt` on every container start (the `# START YOAST BLOCK` / `# END YOAST BLOCK` shell-echo block, lines 148–172), which Apache served before WordPress ran, silently suppressing the `robots_txt` filter on every pod in the fleet. Removed that entire writer block and replaced it with an unconditional `rm -f /app/wordpress/robots.txt` at boot so any pre-existing file — from the prior image, a Yoast Tools admin save, or a backup — is cleared on startup. Added a managed `.htaccess` block (same `# BEGIN Managed: ... / # END Managed: ...` prepend pattern as the bots.csv block) that inserts `RewriteRule ^robots\.txt$ /index.php [L]` above the standard WP rewrite block; this ensures mod_rewrite routes `/robots.txt` through WordPress even if a static file is recreated mid-life, so the mu-plugin always controls the output regardless of container state.

References: https://github.com/proudcity/wp-proudcity/issues/2846
References: https://github.com/proudcity/pc-dev-issues/issues/297

## 2026-06-11

- Removed the plaintext `.netrc` credential write from `bin/entrypoint.sh` (the `echo "machine source.developers.google.com ..."` line) along with ~18 lines of dead commented-out Google Cloud Source Repos clone code (gravity forms, wp-media-folder, the polling `until` loop). The credential was written to support a `gravityview` clone on `solanocountyca`, but that plugin is no longer installed anywhere. Eliminating the write removes a plaintext token that previously sat on `/root/.netrc` for the lifetime of every WP pod — readable by any in-container process, compounded by the container running as root (PCD186).

References: https://github.com/proudcity/pc-dev-issues/issues/191

## 2026-06-11

- Removed `eval` from three loops in `bin/entrypoint.sh` that built shell commands from the `WORDPRESS_THEMES`, `WORDPRESS_PLUGINS`, and `WORDPRESSORG_PLUGINS` env vars. Replaced the `IFS=","` + `for s in $VAR` + `eval $cmd` pattern with `IFS=',' read -ra arr <<< "$VAR"; for s in "${arr[@]}"` (scopes IFS to the read, quotes the iteration variable) and direct command execution: `git clone -- "$s"` (the `--` defends against argument injection per CVE-2018-17456) and the curl/unzip/rm chain with all `${s}` references quoted. Eliminates a root-level RCE primitive — anyone who could write those env vars via a k8s ConfigMap could previously inject arbitrary shell commands. (commit `ca548bc`)

References: https://github.com/proudcity/pc-dev-issues/issues/188

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
