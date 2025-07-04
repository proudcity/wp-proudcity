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
- [ ] if updated check the [changes we made](https://github.com/proudcity/gravityformsstripe/commit/10ed1155c74b7811e0b7b75bedb6f4fdfd42089e) to see if anything needs to be updated to keep them or if we have a new hook

### Elasticspress

- [2651](https://github.com/proudcity/wp-proudcity/issues/2651) needs to be fixed before we can update

### Events Manager Cache fixes

- [Forum post on caching](https://wordpress.org/support/topic/redis-caching-issue-with-saving-colour-on-event-categories-2/)
   If not we need to change `class/em-taxonomy-admin.php` [line 172](https://github.com/proudcity/events-manager/blob/d1a0385b5b587ca51f67d98e0e4add919151b16d/classes/em-taxonomy-admin.php#L172) so that `wp_cache_flush` clears the taxonomy

### Inuitive CPT caching

- check to see if [our PR](https://github.com/hijiriworld/intuitive-custom-post-order/pull/64) was accepted if not modify our plugin again

### Simple Staff List

- we [fixed magic vars](https://github.com/proudcity/simple-staff-list/commit/ac9f49753a87dd6952cc1f86068e1d236d9d15b6) which cause PHP errors

## Builds

## Updates

**Plugin Name** - 1.0 -> 1.1

- [release notes]()
- ?? what was updated
