RELEASE=${1}

if [[ ! "$RELEASE" =~ ^[0-9]{4}\.[0-9]{2}\.[0-9]{2}\.[0-9]{4}$ ]]; then
    echo "Invalid release format" >&2
    exit 1
fi

bash "/app/updates/release-${RELEASE}.sh"
if [ -z "$RELEASE" ]; then
    wp option update proud_release $RELEASE
fi

echo "Clearing transient options in wp_options"
wp db query "DELETE from wp_options where option_name like '%transient%';"

echo "updating core WP DB"
wp core update-db
echo "flushing rewrites"
wp rewrite flush
echo "flushing WP cache"
wp cache flush

# enable redis requires the Redis Cache plugin to be active
echo "Enabling Redis"
wp plugin activate redis-cache
wp redis enable
