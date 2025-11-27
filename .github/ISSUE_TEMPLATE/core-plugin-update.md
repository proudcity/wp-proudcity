---
name: Core/plugin update
about: Core/plugin update template
title: 'Core/plugin update: DATE'
labels: core/plugin
assignees: curtismchale

---
## Notes

### Events Manager

**Staying on 7.1.7 because 7.2.1 is broken in multiple ways**

- after #2691 we found there is a bug where Events Manager gets over zealous with modifying post type content. [This forum thread](https://wordpress.org/support/topic/wpml-issue-with-eventsmanager/) provided a fix to stop EM from messing with post titles that it doesn't own.
- [ ] Check `em-event-post.php` line `49` to make sure it excludes posts that are not events
  - should be this code: `$is_post_type = (Archetypes::is_repeating( $post_type ) ? Archetypes::get_repeating_archetype( $post_type ) : ($post_type == "event")) ? $post_type : false;`
- also see #2694 which had issues with the UI for recurring events and [forum](https://wordpress.org/support/topic/events-manager-recurring-events-error/) [threads](https://wordpress.org/support/topic/unable-to-create-recurring-events/#post-18671937) from others stating that recurring events were broken on 7.2.1

### Gravity Forms Stripe

- we [updated the plugin to handle connected account transfers](https://github.com/proudcity/gravityformsstripe/commit/10ed1155c74b7811e0b7b75bedb6f4fdfd42089e)
- [ ] check for [updates](https://docs.gravityforms.com/stripe-change-log/) above 5.9.1 as we're currently running a "hacked" version of it and need to update
- [ ] if updated check the [changes we made](https://github.com/proudcity/gravityformsstripe/commit/37851018666280208936dcd844f999eaf321819c) to see if anything needs to be updated to keep them or if we have a new hook. **Make sure** you're adding the change above to the `create_refund` function

### Inuitive CPT caching

- check to see if [our PR](https://github.com/hijiriworld/intuitive-custom-post-order/pull/64) was accepted if not modify our plugin again

### Simple Staff List

- we [fixed magic vars](https://github.com/proudcity/simple-staff-list/commit/ac9f49753a87dd6952cc1f86068e1d236d9d15b6) which cause PHP errors

## Builds

## Updates

**Plugin Name** - 1.0 -> 1.1

- [release notes]()
- ?? what was updated
