RELEASE=${1}

bash /app/updates/release-${RELEASE}.sh
if [ -z "$RELEASE" ]; then
    wp option update proud_release $RELEASE --allow-root
fi

echo "Clearing transient options in wp_options"
wp --allow-root db query "DELETE from wp_options where option_name like '%transient%';"

wp core update-db --allow-root
wp rewrite flush --allow-root
wp cache flush --allow-root

# enable redis requires the Redis Cache plugin to be active
echo "Enabling Redis"
wp redis enable --allow-root
