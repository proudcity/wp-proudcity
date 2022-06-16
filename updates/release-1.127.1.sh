# activating the media library plugin
wp activate plugin wp-media-folder --allow-root

# deactivate and then uninstall W3TC
wp plugin deactivate w3-total-cache --uninstall --allow-root

# activate wp rocket
wp plugin activate wp-rocket --allow-root

# activate wp rocket rest cache
wp plugin activate wp-rocket-cache-rest-api --allow-root

# import the wp-rocket .json config
wp rocket import /app/updates/wp-rocket-base-2022-06-14.json --allow-root

# library-calendar page exclusion
wp post update $(wp post list --post_type=page --pagename=library-calendar --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root

# events exclusion
wp post update $(wp post list --post_type=page --pagename=events --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root

# calendar exclusion
wp post update $(wp post list --post_type=page --pagename=calendar --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root

# events calendar exclusion
wp post update $(wp post list --post_type=page --pagename=events-calendar --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root