# Updates for the 1.24.0 release
# (no manual updates)

PHP=${1}

wp elasticpress deactivate-feature search --allow-root
wp elasticpress deactivate-feature related_posts --allow-root