# Updates for the 1.96.0 release
# Increase the cache-control settings in wp-stateless to improve site speed scores

PHP=${1}

# disable debug-bar plugins that got activated on citytemplate
wp option update _jquery_migrate_downgrade_version "yes" --allow-root

