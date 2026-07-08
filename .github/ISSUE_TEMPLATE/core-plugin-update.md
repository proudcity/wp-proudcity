---
name: Core/plugin update
about: Core/plugin update template
title: 'Core/plugin update: DATE'
labels: core/plugin
assignees: curtismchale

---
## Notes

### Gravity Forms Stripe

- we [updated the plugin to handle connected account transfers](https://github.com/proudcity/gravityformsstripe/commit/10ed1155c74b7811e0b7b75bedb6f4fdfd42089e)
- [ ] check for [updates](https://docs.gravityforms.com/stripe-change-log/) above 5.9.1 as we're currently running a "hacked" version of it and need to update
- [ ] if updated check the [changes we made](https://github.com/proudcity/gravityformsstripe/commit/37851018666280208936dcd844f999eaf321819c) to see if anything needs to be updated to keep them or if we have a new hook. **Make sure** you're adding the change above to the `create_refund` function

### Inuitive CPT caching

- fork updated to upstream v3.2.0 with `wp_cache_flush()` reapplied in the AJAX order-save handlers — see [proudcity/intuitive-custom-post-order#1](https://github.com/proudcity/intuitive-custom-post-order/pull/1)
- [ ] check whether [our upstream PR hijiriworld#64](https://github.com/hijiriworld/intuitive-custom-post-order/pull/64) was merged and a release newer than 3.2.0 includes cache flushing — if so, drop the fork customization and pull upstream directly
- verification: `diff` the fork's `intuitive-custom-post-order.php` against a pristine upstream copy; the result should show only the three `wp_cache_flush()` lines and their comments

### Simple Staff List

- we [fixed magic vars](https://github.com/proudcity/simple-staff-list/commit/ac9f49753a87dd6952cc1f86068e1d236d9d15b6) which cause PHP errors

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
