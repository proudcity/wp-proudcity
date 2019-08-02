# Changes the URL for a WordPress site.
# Usage
#bash ../bin/change-url.sh $HOST
#bash ../bin/change-url.sh www.johnsoncitytx.org

cd /app/wordpress

URL=${1}
OLD_URL=$(wp option get siteurl --allow-root)

echo "CHANGING URL:"
echo "OLD: $OLD_URL"
echo "NEW: $URL"

wp search-replace $OLD_URL $URL --skip-columns=guid --allow-root
wp search-replace city-template.proudcity.com $HOST --skip-columns=guid --allow-root
wp search-replace ${OLD_URL}/wp-content/uploads/ https://storage.googleapis.com/proudcity/${STATELESS_MEDIA_DIRECTORY} --skip-columns=guid --allow-root