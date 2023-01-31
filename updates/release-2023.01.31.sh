#!/bin/bash

# turning off old post types order plugin
wp plugin deactivate intuitive-custom-post-order --allow-root

# turning on new post type order plugin
wp plugin activate post-types-order --allow-root

# activating wp rocket
wp plugin activate wp-rocket --allow-root

# importing wp rocket
wp rocket import --file=/app/updates/wp-rocket-2023-01-31.json --allow-root

