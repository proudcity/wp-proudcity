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

wp search-replace $(wp option get siteurl --allow-root)/wp-content/uploads/ https://storage.googleapis.com/proudcity/${STATELESS_MEDIA_DIRECTORY} --allow-root