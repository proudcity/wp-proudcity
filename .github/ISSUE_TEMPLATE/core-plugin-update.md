---
name: Core/plugin update
about: Core/plugin update template
title: 'Core/plugin update: DATE'
labels: core/plugin
assignees: curtismchale

---
## Notes

### Fix Alt Text

- fix-alt-text 1.9.1 calls `wp_suspend_cache_addition( true )` at the top of its `save_post`, `attachment_updated`, `add_attachment`, `saved_term` and `delete_term` handlers (all priority 999) and never restores it, so everything after the first save in a request runs with cache priming disabled — see #2886
- we restore it in `www/wp-content/mu-plugins/restore-cache-addition.php`, hooking the same five actions at priority 1000. It only acts when the flag is actually set, so it is already a no-op if upstream fixes this
- upstream report: [wordpress.org support topic](https://wordpress.org/support/topic/wp_suspend_cache_addition-true-is-never-restored-degrading-the-object-cache/) — full writeup in [this gist](https://gist.github.com/curtismchale/04070858f1cf20d9211d3ad89b3112cb)
- the plugin developer replied in that forum topic saying a fix is underway for **version 2.0**
- [ ] **watch for fix-alt-text 2.0** — when it lands, test it and reply in the [forum topic](https://wordpress.org/support/topic/wp_suspend_cache_addition-true-is-never-restored-degrading-the-object-cache/) with feedback on whether the fix works
- [ ] check whether a release above 1.9.1 restores the flag — grep the new version for `wp_suspend_cache_addition`. Two calls (one `true`, one `false`), or a `try`/`finally` around the scan, means it is fixed and `restore-cache-addition.php` can be deleted
- [ ] **check this even if the flag bug is unfixed:** confirm the five `add_action` calls in `inc/Scan.php` are still priority 999. If any moved above 1000, our restore now runs *before* their suspend and silently stops working
- verification: `wp eval 'wp_insert_post(["post_type"=>"page","post_title"=>"t","post_status"=>"draft"]); var_dump( wp_suspend_cache_addition() );'` — must print `bool(false)`

### Gravity Forms Stripe

- we [updated the plugin to handle connected account transfers](https://github.com/proudcity/gravityformsstripe/commit/10ed1155c74b7811e0b7b75bedb6f4fdfd42089e)
- [ ] check for [updates](https://docs.gravityforms.com/stripe-change-log/) above 5.9.1 as we're currently running a "hacked" version of it and need to update
- [ ] if updated check the [changes we made](https://github.com/proudcity/gravityformsstripe/commit/37851018666280208936dcd844f999eaf321819c) to see if anything needs to be updated to keep them or if we have a new hook. **Make sure** you're adding the change above to the `create_refund` function

### Inuitive CPT caching

- fork updated to upstream v3.2.0 with `wp_cache_flush()` reapplied in the AJAX order-save handlers — see [proudcity/intuitive-custom-post-order#1](https://github.com/proudcity/intuitive-custom-post-order/pull/1)
- [ ] check whether [our upstream PR hijiriworld#64](https://github.com/hijiriworld/intuitive-custom-post-order/pull/64) was merged and a release newer than 3.2.0 includes cache flushing — if so, drop the fork customization and pull upstream directly
- [ ] if upstream shipped a new release that does **not** include cache flushing, merge it into [our fork](https://github.com/proudcity/intuitive-custom-post-order) and reapply the `wp_cache_flush()` calls in the three AJAX order-save handlers (`update-menu-order`, `update-menu-order-tags`, `update-menu-order-sites`), then tag and bump the composer pin
- verification: `diff` the fork's `intuitive-custom-post-order.php` against a pristine upstream copy; the result should show only the three `wp_cache_flush()` lines and their comments

### Simple Staff List

- we [fixed magic vars](https://github.com/proudcity/simple-staff-list/commit/ac9f49753a87dd6952cc1f86068e1d236d9d15b6) which cause PHP errors

### WP Media Folder

- WPMF 6.2.6's "Upload folder" bulk uploader (`wp_ajax_wpmf_upload_folder` -> `WpmfMediaFolder::uploadFolder()`) bypasses `wp_handle_upload()` and only calls `wp_update_attachment_metadata()` for image/video/audio. WP Stateless offloads solely on that filter, so **every document uploaded through it stays on the pod's local disk and is destroyed on the next restart.** Duchesne County lost 338 meeting documents this way — see #2887
- we disable the uploader in `wp-proud-core/plugin_override/wp-media-folder/proud-wp-media-folder.php`: the AJAX action is refused with a 403 and the injected "Upload folder" button is hidden with CSS. Covered by `wp-proud-core/tests/WpMediaFolderFolderUploadTest.php`
- upstream report: TODO — add JoomUnited ticket URL
- [ ] check whether the new release fixes all three defects in `class/class-main.php`. **All three must be fixed before re-enabling** — the metadata gate causes the data loss, but the other two cause silent overwrites and leave rejected files in a public directory:
  1. **Metadata gate** — search for `$is_generate_metadata`. The fix is `wp_update_attachment_metadata()` being called unconditionally, the way core does at `wp-admin/includes/media.php:446`. If the `preg_match('!^image/!', ...)` / `wp_attachment_is('video'|'audio')` branches still gate it, the data-loss bug is still there
  2. **Filename handling** — in `createFileFromChunks()`, the destination name must go through `sanitize_file_name()` and `wp_unique_filename()`. Unfixed, uploads keep raw spaces, skip our `stateless_skip_cache_busting` suffix, and silently overwrite a same-named file while creating a duplicate attachment row
  3. **Rejected file types** — the `wp_check_filetype_and_ext()` failure path must `unlink()` the assembled file (or validate before writing). Unfixed, a disallowed file stays in `wp-content/uploads/YYYY/MM/` under an attacker-chosen name
- [ ] if all three are fixed, delete the two guard functions plus `proudcity_wpmf_seize_folder_upload_action()` from the override, drop `WpMediaFolderFolderUploadTest.php`, and remove the `require_once` from `wp-proud-core/tests/bootstrap.php`
- [ ] if only some are fixed, leave the block in place and update this note with what still fails
- **also check on every release, whether or not you re-enable:** our block registers at priority 1 *and* seizes the hook on `admin_init`. If WPMF adds a second registration or a new entry point into `uploadFolder()` (a REST route, an `admin_post_` action, a nopriv variant), the `admin_init` seize may not cover it. Grep for `uploadFolder` and `wpmf_upload_folder` and confirm the count is still one registration
- verification, on a WP Stateless site with our override **temporarily disabled** (the block will otherwise mask the test): upload a PDF through the "Upload folder" button, then confirm the attachment has non-empty `sm_cloud` post meta and that `wp_get_attachment_url()` returns a `storage.googleapis.com` URL. If it returns a `/wp-content/uploads/` URL, the bug is still present

### WP-Stateless

- pinned to an exact version in `composer.json` (`wpackagist-plugin/wp-stateless`, currently 4.4.1), so a bump is always a deliberate edit — run these checks every time that pin moves
- the plugin ships data migrations as files in `static/migrations/`. A new file there means every site needs a migration pass after the release, or sites show the "your data still needs to be updated using this new method" nag and keep reading the legacy storage format. Known migrations as of 4.4.1: `20240219175240` (Update data for Google Cloud files), `20240423174109` (Optimize Compatibility Files) — see #2889
- [ ] diff `static/migrations/` between the old and new version. Any new file means a platform-wide migration pass is required
- [ ] check whether `DB::DB_VERSION` in `lib/classes/class-db.php` changed (4.4.1 is `1.2`). A bump means a schema change on top of the data migration
- [ ] if either changed, schedule the migration pass as its own off-hours release. `wp stateless migrate` lists status, `wp stateless migrate auto --yes` runs everything still pending (a no-op on an already-current site). Wrap it in `timeout` — `_auto_migrate()` polls the migration state option in an unbounded `while(true)` and will hang the deploy forever if the background process stalls
- **do not put this in `bin/entrypoint.sh`.** Batches are not processed in-process: `Migrator::start_migration()` queues the batch and calls `dispatch()`, a non-blocking loopback POST to `admin-ajax.php`. Entrypoint runs before `exec "$@"` starts `apache2-foreground`, so nothing is listening and the migration registers as started and then sits. Entrypoint also runs on every pod restart and scale-up, not just deploys. `sm_batch_process_cron` is not a fallback — `handle_cron_healthcheck()` also only calls `dispatch()`
- migrations are insert-only (no `DELETE`, no `DROP`, legacy `sm_cloud` postmeta left intact) and each attachment is processed in a transaction against unique keys, so a concurrent upload cannot corrupt data. Still run off-hours: `20240423174109` makes a live GCS API call per row, and edits/deletes of existing media mid-run are the untested path
- verification: after the release, `wp stateless migrate` on a site should list every migration as finished and the upgrade nag should be gone from wp-admin

### WP-Stateless Gravity Forms Addon

- after #2831 (Gravity Forms 2.10 broke File Upload sync to GCS) we forked the plugin and patched it for the new JSON storage format. `composer.json` is currently pulling from [proudcity/wp-stateless-gravity-forms-addon](https://github.com/proudcity/wp-stateless-gravity-forms-addon) on `dev-latest` instead of wpackagist.
- our patch branch: [fix/gf-2.10-json-storage](https://github.com/proudcity/wp-stateless-gravity-forms-addon/tree/fix/gf-2.10-json-storage)
- upstream PR: [udx/wp-stateless-gravity-forms-addon#16](https://github.com/udx/wp-stateless-gravity-forms-addon/pull/16) (issue [udx#15](https://github.com/udx/wp-stateless-gravity-forms-addon/issues/15))
- [ ] check if upstream has shipped a release > 0.0.3 that includes the GF 2.10 JSON storage fix. If yes, switch the `proudcity/wp-stateless-gravity-forms-addon` require in `composer.json` back to `wpackagist-plugin/wp-stateless-gravity-forms-addon` at that version and remove the fork repository entry.

## Builds

## Updates

**Plugin Name** - 1.0 -> 1.1

- [release notes]()
- ?? what was updated
