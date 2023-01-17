#!/bin/bash

# activating wp rocket
wp plugin activate wp-rocket --allow-root

# importing wp rocket
wp rocket import --file=/app/updates/wp-rocket-2023-01-17.json --allow-root

# clearing revisions
wp post delete $(wp post list --post_type='revision' --format=ids --allow-root) --force --allow-root
