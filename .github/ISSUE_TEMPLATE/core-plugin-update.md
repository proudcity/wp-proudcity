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

### ElasticPress

- **`wp elasticpress status` and `wp elasticpress stats` lie without `--url`.** Run from WP-CLI with no URL they report `Warning: is not currently indexed.` and `get_index_name()` returns `false`, on a site whose index is fine. That reads exactly like a broken index after a version bump and it is not. Always pass `--url=https://<site>/` when checking EP after an update
- the check that actually answers the question is whether a search query round-trips to Elasticsearch:
  ```bash
  wp eval '$q = new WP_Query(["s"=>"permit","post_type"=>"any","posts_per_page"=>5]);
    echo "found=".$q->found_posts." es=".var_export($q->elasticsearch_success,true)."\n";' \
    --allow-root --path=/app/wordpress --url=https://<site>/
  ```
  `es=true` means EP served it. `es=false` or unset means WP fell back to core search, which is the real failure mode to look for
- cross-check the index itself from inside the pod: `curl -s "http://elasticsearch-client.elasticsearch:9200/_cat/indices?h=index,docs.count,status" | grep <site>`
- `ep_last_sync=false` is normal on test sites and does not mean the index is missing — several test-namespace sites have never run a sync from the admin while still having a populated index

### Events Manager

- `Recurrence_Set::save_recurrences()` loads the recurring event's template post row once *before* the occurrence loop, then writes each occurrence's date-suffixed slug back into that same `$post_fields` array inside the loop. Iteration N reads what iteration N-1 wrote, so occurrence twelve carries all twelve dates in its URL — see #2893. `sanitize_recurrence_slug()` does not catch it; it only truncates past 200 characters, which caps the runaway rather than preventing it
- we fix it in `wp-proud-core/modules/events-manager-recurrence-slug.php`, hooking `em_event_save_events_slug` (the last filter on both the create path, `classes/recurrences/recurrence-set.php:1017`, and the update path, `:1341`) and rebuilding the slug from the recurring template's own `post_name` plus the trailing date. Covered by `wp-proud-core/tests/EventsManagerRecurrenceSlugTest.php`
- one-off repair for already-broken slugs is `wp proud fix-em-slugs` (`wp-proud-core/bin/wp-cli.php`) — `--dry-run` to preview, `--yes` for unattended fleet runs. It rewrites slugs in place and files a 301 in the Safe Redirect Manager `redirect_rule` CPT for each one. Full writeup and the fleet-wide commands are in `~/Documents/developers/Github Issue Notes/2893 - Recurring Event URL Slug Accumulation.md`
- [ ] once the fleet-wide repair has run and been verified, delete the `fix_em_slugs` command — it is a one-off, not per-release maintenance, and does not belong in `bin/deploy.sh`
- upstream report: [wordpress.org support topic](https://wordpress.org/support/topic/recurring-event-slugs-accumulate-every-previous-occurrence-date/), filed 2026-08-25 — full writeup in [#2893](https://github.com/proudcity/wp-proudcity/issues/2893) ([investigation](https://github.com/proudcity/wp-proudcity/issues/2893#issuecomment-5413574788)). **Still broken as of 7.4.3** — `recurrence-set.php` is byte-identical between 7.4.2 and 7.4.3. Maintainer acknowledgement only: angelo_nwl (Plugin Support) replied 2026-08-28, "reported it to our developers for further investigation and a possible fix" — no patch, no timeline
- [ ] **check the [forum topic](https://wordpress.org/support/topic/recurring-event-slugs-accumulate-every-previous-occurrence-date/) for movement past the 2026-08-28 acknowledgement.** The first reply has already landed, so what matters now is a shipped fix. If one has, test it and reply in the topic with whether it works
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
- [ ] we're on 7.0.0 — check for [updates](https://docs.gravityforms.com/stripe-change-log/) above 7.0.0, since we're running a "hacked" version and each update needs our changes reapplied
- [ ] if updated check the [changes we made](https://github.com/proudcity/gravityformsstripe/commit/37851018666280208936dcd844f999eaf321819c) to see if anything needs to be updated to keep them or if we have a new hook. **Make sure** you're adding the change above to the `create_refund` function

### Inuitive CPT caching

- fork updated to upstream v3.2.0 with `wp_cache_flush()` reapplied in the AJAX order-save handlers — see [proudcity/intuitive-custom-post-order#1](https://github.com/proudcity/intuitive-custom-post-order/pull/1)
- [ ] check whether [our upstream PR hijiriworld#64](https://github.com/hijiriworld/intuitive-custom-post-order/pull/64) was merged and a release newer than 3.2.0 includes cache flushing — if so, drop the fork customization and pull upstream directly
- [ ] if upstream shipped a new release that does **not** include cache flushing, merge it into [our fork](https://github.com/proudcity/intuitive-custom-post-order) and reapply the `wp_cache_flush()` calls in the three AJAX order-save handlers (`update-menu-order`, `update-menu-order-tags`, `update-menu-order-sites`), then tag and bump the composer pin
- verification: `diff` the fork's `intuitive-custom-post-order.php` against a pristine upstream copy; the result should show only the three `wp_cache_flush()` lines and their comments

### Simple Staff List

- we [fixed magic vars](https://github.com/proudcity/simple-staff-list/commit/ac9f49753a87dd6952cc1f86068e1d236d9d15b6) which cause PHP errors

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
