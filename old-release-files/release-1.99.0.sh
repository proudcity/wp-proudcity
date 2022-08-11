# Updates for the 1.96.0 release
# Increase the cache-control settings in wp-stateless to improve site speed scores

PHP=${1}

# disable debug-bar plugins that got activated on citytemplate
wp plugin deactivate --allow-root debug-bar debug-bar-console

wp eval-file /app/updates/release-1.99.0.php --allow-root
