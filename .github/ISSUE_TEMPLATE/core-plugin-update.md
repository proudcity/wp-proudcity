---
name: Core/plugin update
about: Core/plugin update template
title: 'Core/plugin update: DATE'
labels: core/plugin
assignees: curtismchale

---
## Migration check

Runs on **every** update, not just the plugins listed under Notes. A plugin with no migrations today can add one in any release, and a migration that silently fails on a subset of sites is invisible without checking. That is exactly what happened with WP-Stateless (#2889): migrations shipped, ran on 111 sites, silently did not finish on 41 others, and nobody noticed for about two years.

- [ ] `git diff composer.json` on the update branch to list which plugins actually moved and from/to what. 62 of 64 are exact-pinned and `composer.lock` is gitignored, so `composer.json` **is** the version record
- [ ] For each plugin that moved, check whether the new version ships a data migration or a schema-version bump. Look for a `migrations/` directory, a `*_db_version` / `*_DB_VERSION` constant, or use of `WP_Background_Process`. If the plugin is in `~/Sites/proudtest/wp-content/plugins/` after `composer update`, diff it there
- [ ] If anything moved, note it here so the post-release check below has something to compare against

After the release has rolled out:

- [ ] Run the fleet audit and confirm no new drift:
  ```bash
  bash ~/Workspace/proudcity/proudcity-kubernetes/bin/check-migrations.sh
  ```
  Exit `0` clean, `1` drift found. It compares every site's migration and schema-version options against the fleet consensus, so it catches migrations that failed on a subset without needing to know which plugins have migrations. See proudcity-kubernetes#migration-watcher.
- [ ] Anything it flags: **investigate before running anything.** Of the first five options triaged this way (#2892), exactly one was a migration that needed running — the rest were orphaned options from uninstalled plugins, an inactive plugin, and a plugin version mismatch. Read the owning plugin's upgrade code first
- [ ] Known-benign outliers go in `proudcity-kubernetes/bin/check-migrations.exclude` **with the reason**

The `migration-watcher` CronJob runs this weekly and files a `migration-drift` issue on its own, so this step is a faster feedback loop rather than the only safety net.

## Notes

### Events Manager

- `Recurrence_Set::save_recurrences()` loads the recurring event's template post row once *before* the occurrence loop, then writes each occurrence's date-suffixed slug back into that same `$post_fields` array inside the loop. Iteration N reads what iteration N-1 wrote, so occurrence twelve carries all twelve dates in its URL — see #2893. `sanitize_recurrence_slug()` does not catch it; it only truncates past 200 characters, which caps the runaway rather than preventing it
- we fix it in `wp-proud-core/modules/events-manager-recurrence-slug.php`, hooking `em_event_save_events_slug` (the last filter on both the create path, `classes/recurrences/recurrence-set.php:1017`, and the update path, `:1341`) and rebuilding the slug from the recurring template's own `post_name` plus the trailing date. Covered by `wp-proud-core/tests/EventsManagerRecurrenceSlugTest.php`
- one-off repair for already-broken slugs is `wp proud fix-em-slugs` (`wp-proud-core/bin/wp-cli.php`) — `--dry-run` to preview, `--yes` for unattended fleet runs. It rewrites slugs in place and files a 301 in the Safe Redirect Manager `redirect_rule` CPT for each one. Full writeup and the fleet-wide commands are in `~/Documents/developers/Github Issue Notes/2893 - Recurring Event URL Slug Accumulation.md`
- [ ] once the fleet-wide repair has run and been verified, delete the `fix_em_slugs` command — it is a one-off, not per-release maintenance, and does not belong in `bin/deploy.sh`
- upstream report: [wordpress.org support topic](https://wordpress.org/support/topic/recurring-event-slugs-accumulate-every-previous-occurrence-date/), filed 2026-08-25 — full writeup in [#2893](https://github.com/proudcity/wp-proudcity/issues/2893) ([investigation](https://github.com/proudcity/wp-proudcity/issues/2893#issuecomment-5413574788)). Broken as of 7.4.2, no upstream fix
- [ ] **check the [forum topic](https://wordpress.org/support/topic/recurring-event-slugs-accumulate-every-previous-occurrence-date/) for a maintainer reply.** If a fix has landed, test it and reply in the topic with whether it works
- [ ] **check whether the new version fixes the accumulation.** Grep `classes/recurrences/recurrence-set.php` for `$post_fields['post_name'] =` — if the assignment inside `foreach ( $matching_days as $day )` is gone, or the slug is built from a base captured before the loop, it is fixed and our module plus its test can be deleted
- [ ] **check this even if the bug is unfixed:** confirm the `em_event_save_events_slug` filter still fires on both call sites and still passes `$EM_Event` as its fifth argument. If the signature changed or the filter was dropped, our module silently stops working and slugs start growing again
- [ ] if the accumulation is fixed upstream but old slugs were not migrated, run the repair script once more before deleting it
- verification: on a test site, create a recurring event with 12 monthly occurrences, publish, then `wp db query "SELECT event_start_date, event_slug FROM wp_em_events WHERE recurrence_set_id = <id> ORDER BY event_start_date;"`. Every slug must be `base-YYYY-MM-DD` with exactly one date. Re-save the event and check again — the update path regressed separately from the create path

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

- **background:** WPMF 6.2.6's "Upload folder" bulk uploader (`wp_ajax_wpmf_upload_folder` -> `WpmfMediaFolder::uploadFolder()`) bypassed `wp_handle_upload()` and only called `wp_update_attachment_metadata()` for image/video/audio. WP Stateless offloads solely on that filter, so **every document uploaded through it stayed on the pod's local disk and was destroyed on the next restart.** Duchesne County lost 338 meeting documents this way — see #2887
- we disable the uploader in `wp-proud-core/plugin_override/wp-media-folder/proud-wp-media-folder.php`: the AJAX action is refused with a 403 and the injected "Upload folder" button is hidden with CSS. Covered by `wp-proud-core/tests/WpMediaFolderFolderUploadTest.php`
- upstream report: https://www.joomunited.com/component/uvdeskwebkul/viewticket/19251 (submitted 2026-08-19, all three defects in one ticket)
- **all three blocking defects are fixed upstream as of 6.2.7.** Verified by reading `class/class-main.php`. JoomUnited also supplied a custom build tagged [`2887.2026.08.21.1019`](https://github.com/proudcity/wp-media-folder/releases/tag/2887.2026.08.21.1019) whose upload path is functionally identical to 6.2.7 — the only difference across `uploadFolder()` and `createFileFromChunks()` is an `if (` line-wrap — so anything tested against that build carries over to the release unchanged:
  1. **Metadata gate — fixed.** `$is_generate_metadata` is gone from the codebase; `wp_generate_attachment_metadata()` / `wp_update_attachment_metadata()` are now called unconditionally at `class-main.php:3862-3863`, matching core at `wp-admin/includes/media.php:446`. This is the one that caused the data loss
  2. **Filename handling — fixed.** `createFileFromChunks()` runs `sanitize_file_name()` then `wp_unique_filename()` at `class-main.php:3992-3995`. The `$newName` parameter is by-reference (`class-main.php:3975`) and the caller passes `$newname` (`class-main.php:3747`), so the corrected name reaches `wp_insert_attachment()` at `class-main.php:3854` — the file on disk and `_wp_attached_file` stay in sync. Worth re-checking that by-reference plumbing on future releases; if it breaks, the symptom is an attachment row pointing at a path that does not exist
  3. **Rejected file types — fixed in two places.** A new pre-write check at `class-main.php:3720` runs `wp_check_filetype($resumableFilename, $mimes)` *before* `mkdir` and `move_uploaded_file`, so a rejected type never reaches disk at all; the post-assembly failure path now also calls `wp_delete_file()` (`class-main.php:3841`), as does the `wp_insert_attachment()` failure path (`class-main.php:3856`)
  4. **also fixed, though not on our blocking list:** `uploadFolder()` now verifies a nonce on POST (`class-main.php:3664`) and the JS plumbs it through `wpmfUploader.opts.query` (`assets/js/script.js:1626`), closing the CSRF hole reported in the ticket without breaking the uploader
- **still broken upstream as of 6.2.7.** Neither was addressed in the release or the custom build, and neither blocks re-enabling:
  - `syncAttachmentMetadata()` (`class/class-wp-folder-option.php:514`) still gates its metadata call on `strpos($mime_type, 'image')` at line 565 — the same data-loss shape as defect 1, on the server/FTP import path. Only reachable when `WPMF_SYNC_ATTACHMENT_IMPORT` is defined, and we do not define it. Reported on ticket 19251 and not actioned
  - the chunk temp directory is still created with `mkdir($temp_dir, 0777, true)` (`class-main.php:3734`) — world-writable, under the web root. Interrupted uploads leave `uploads/<year>/<month>/<32-hex>/` directories that nothing cleans up
- [ ] **run the live verification below.** It is the only thing left before the override can come out — the code read establishes the mechanism, not the result
- [ ] once it passes, delete the two guard functions plus `proudcity_wpmf_seize_folder_upload_action()` from the override, drop `WpMediaFolderFolderUploadTest.php`, and remove the `require_once` from `wp-proud-core/tests/bootstrap.php`
- [ ] if any of the three defects has regressed, leave the block in place and update this note with what failed
- verification, on a WP Stateless site with our override **temporarily disabled** (the block will otherwise mask the test): upload a PDF through the "Upload folder" button, then confirm the attachment has non-empty `sm_cloud` post meta and that `wp_get_attachment_url()` returns a `storage.googleapis.com` URL. If it returns a `/wp-content/uploads/` URL, the bug is back. Also confirm the stored filename carries the `-[8 hex]` suffix from our `stateless_skip_cache_busting` filter — that interaction was never verified from the plugin source
- **also check on every release, whether or not the override is still in place:** grep for `uploadFolder` and `wpmf_upload_folder` and confirm there is still exactly **one** registration. As of 6.2.7 that is `add_action('wp_ajax_wpmf_upload_folder', array($this, 'uploadFolder'))` at `class-main.php:154`, default priority 10, with no `wp_ajax_nopriv_` variant and no REST or `admin_post_` route. Our block registers at priority 1 *and* seizes the hook on `admin_init`; if WPMF adds a second registration or a new entry point into `uploadFolder()`, the seize may not cover it
- the button selector our CSS hides, `.wpmf_btn_upload_folder`, is unchanged as of 6.2.7 (`assets/js/script.js:184-188`)

### WP-Stateless

- pinned to an exact version in `composer.json` (`wpackagist-plugin/wp-stateless`, currently 4.4.1), so a bump is always a deliberate edit — run these checks every time that pin moves
- the plugin ships data migrations as files in `static/migrations/`. A new file there means every site needs a migration pass after the release, or sites show the "your data still needs to be updated using this new method" nag and keep reading the legacy storage format. Known migrations as of 4.4.1: `20240219175240` (Update data for Google Cloud files), `20240423174109` (Optimize Compatibility Files) — see #2889
- [ ] diff `static/migrations/` between the old and new version. Any new file means a platform-wide migration pass is required
- [ ] check whether `DB::DB_VERSION` in `lib/classes/class-db.php` changed (4.4.1 is `1.2`). A bump means a schema change on top of the data migration
- [ ] if either changed, schedule the migration pass as its own off-hours release. `wp stateless migrate` lists status, `wp stateless migrate auto --yes` runs everything still pending (a no-op on an already-current site). Wrap it in `timeout` — `_auto_migrate()` polls the migration state option in an unbounded `while(true)` and will hang the deploy forever if the background process stalls
- **do not put this in `bin/entrypoint.sh`.** Batches are not processed in-process: `Migrator::start_migration()` queues the batch and calls `dispatch()`, a non-blocking loopback POST to `admin-ajax.php`. Entrypoint runs before `exec "$@"` starts `apache2-foreground`, so nothing is listening and the migration registers as started and then sits. Entrypoint also runs on every pod restart and scale-up, not just deploys. `sm_batch_process_cron` is not a fallback — `handle_cron_healthcheck()` also only calls `dispatch()`
- migrations are insert-only (no `DELETE`, no `DROP`, legacy `sm_cloud` postmeta left intact) and each attachment is processed in a transaction against unique keys, so a concurrent upload cannot corrupt data. Still run off-hours: `20240423174109` makes a live GCS API call per row, and edits/deletes of existing media mid-run are the untested path
- verification: after the release, `wp stateless migrate` on a site should list every migration as finished and the upgrade nag should be gone from wp-admin. The fleet-wide version of this is the Migration check at the top of this template
- the 2026-08 pass (#2889) is the worked example of all of the above. Two things it turned up that are not obvious from the code: sites behind the `AUTH_REQUIRED` Basic Auth wall 401 their own loopback, which stops the background processor dead until the `loopback-basic-auth` mu-plugin attaches credentials; and a migration recorded as `skipped` is as finished as one recorded as `finished` — it means `should_run()` returned false for that site

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
