// import the wp-rocket .json config
wp rocket import /app/updates/wp-rocket-base-2022-06-14.json --allow-root

// library-calendar page exclusion
wp post update $(wp post list --post_type=page --pagename=library-calendar --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root

// events exclusion
wp post update $(wp post list --post_type=page --pagename=events --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root

// calendar exclusion
wp post update $(wp post list --post_type=page --pagename=calendar --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root

// events calendar exclusion
wp post update $(wp post list --post_type=page --pagename=events-calendar --format=ids --allow-root) --meta_input='{"_rocket_exclude_minify_js":"1","_rocket_exclude_defer_all_js":"1"}' --allow-root