RELEASE=${1}

bash /app/updates/release-${RELEASE}.sh
if [ -z "$RELEASE" ]; then
    wp option update proud_release $RELEASE --allow-root
fi

echo "Clearing transient options in wp_options"
wp --allow-root db query "DELETE from wp_options where option_name like '%transient%';"

echo "updating core WP DB"
wp core update-db --allow-root
echo "flushing rewrites"
wp rewrite flush --allow-root
echo "flushing WP cache"
wp cache flush --allow-root

# enable redis requires the Redis Cache plugin to be active
echo "Enabling Redis"
wp redis enable --allow-root
