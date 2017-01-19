# Changes the URL for a WordPress site.
# Usage
#bash change-url.sh $URL
#bash change-url.sh www.cityofsanrafael.org

cd /app/wordpress

URL=${1}
OLD_URL=$(wp option get siteurl --allow-root)

echo "CHANGING URL:"
echo "OLD: $OLD_URL"
echo "NEW: $URL"

wp search-replace $OLD_URL $URL --allow-root