#!/bin/bash

# activating wp rocket
wp plugin activate wp-rocket --allow-root

# importing wp rocket
wp rocket import --file=/app/updates/wp-rocket-2023-01-31.json --allow-root

