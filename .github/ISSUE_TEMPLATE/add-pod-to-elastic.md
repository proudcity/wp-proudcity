---
name: Add Pod to Elastic Search
about: Checklist for onboarding a new ProudCity site (pod) to the Elasticsearch cluster
title: 'ADD TO ELASTIC: <site-slug>'
labels: elastic, onboarding
assignees: curtismchale

---

Use this every time we add a new pod/site that needs ElasticPress search. Skipping the cluster allowlist step will silently break sync once the new index name is rejected by `action.auto_create_index`.

See full walkthrough: `~/Documents/developers/ProudCity Developers/How To/How to Add a New Pod to Elastic Search.md`.

## Site info

- Site slug (becomes the ES index name): `<lowercase-slug>`
- Pod namespace: `prod` / `test` / other
- Search cohort? (does this site appear in another site's "search all" cohort): yes / no

## Pre-flight

- [ ] Slug is lowercase, starts with `[a-z0-9]`, only contains `[a-z0-9_-]`, ≤ 64 chars (matches the validation regex in `wp-proud-search-elastic`)
- [ ] Confirm the slug is not already in use as an ES index: `kubectl exec -n elasticsearch elasticsearch-master-0 -- curl -s 'localhost:9200/_cat/indices?v' | grep <slug>`

## Update the cluster `auto_create_index` allowlist

If the new slug doesn't already match an existing prefix-wildcard entry in the allowlist, add it.

- [ ] Read current setting:
  ```
  kubectl exec -n elasticsearch elasticsearch-master-0 -- \
    curl -s 'localhost:9200/_cluster/settings?pretty' | grep auto_create_index
  ```
- [ ] PUT updated setting (append `,<slug>*` to the existing comma-separated list, keeping `.*` as the last entry for system indices):
  ```
  kubectl exec -n elasticsearch elasticsearch-master-0 -- curl -s -X PUT \
    'localhost:9200/_cluster/settings' \
    -H 'Content-Type: application/json' \
    -d '{"persistent":{"action.auto_create_index":"<full updated allowlist>"}}'
  ```
- [ ] Verify the response contains `"acknowledged":true` and the new entry is present.

## Update the cohort option (only if this site participates in cross-site search)

The `proud-elastic-search-cohort` WP option controls which slugs are valid `filter_index` values in the search form. The plugin allowlist enforces this. If the new site should appear in a parent site's "search all", add it there.

- [ ] On the parent site that owns the cohort, update `proud-elastic-search-cohort` to include the new slug.
- [ ] Verify by inspecting the option: `wp --allow-root option get proud-elastic-search-cohort --format=json`.

## Run the initial ElasticPress sync

- [ ] SSH into the new pod: `pc kube ssh <namespace> <pod-name>`
- [ ] Run the full sync sequence:
  ```
  wp --allow-root elasticpress stop-sync && \
  wp --allow-root elasticpress clear-sync && \
  wp --allow-root elasticpress put-mapping && \
  wp --allow-root elasticpress sync --force --show-errors
  ```
- [ ] Confirm the new index appears with docs:
  ```
  kubectl exec -n elasticsearch elasticsearch-master-0 -- \
    curl -s 'localhost:9200/_cat/indices?v' | grep <slug>
  ```

## Verification

- [ ] ES cluster status still green: `kubectl exec -n elasticsearch elasticsearch-master-0 -- curl -s localhost:9200/_cluster/health?pretty`
- [ ] Search on the new site returns expected results.
- [ ] If site joined a cohort, search on the parent site with `filter_index` set to the new slug returns results scoped to the new site only.

## Rollback

If something is wrong, restoring the previous allowlist is a single PUT with the old value. Removing a probationary index is `DELETE /<slug>`. The cohort option is just a WP option update.

## References

- Cluster allowlist motivation: pc-dev-issues#266 (Copy Fail-era ES path-injection fix)
- Plugin validation: `wp-proud-search-elastic/lib/elasticsearch.class.php` `query_alter` and `ep_search_request_path`
- Reindex troubleshooting: `~/Documents/developers/ProudCity Developers/How To/How to Fix Elastic Search Index Failures.md`
