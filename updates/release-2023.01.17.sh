#!/bin/bash

# activating wp rocket
wp plugin activate wp-rocket --allow-root

# clearing revisions
wp post delete $(wp post list --post_type='revision' --format=ids --allow-root) --force --allow-root
