# Changes the URL for a WordPress site.
# Usage
#bash ../bin/change-url.sh $HOST
#bash ../bin/change-url.sh www.johnsoncitytx.org

cd /app/wordpress || exit
URL=${1}
OLD_URL=$(wp option get siteurl)

echo "CHANGING URL:"
echo "OLD: $OLD_URL"
echo "NEW: $URL"

wp search-replace "$OLD_URL" "$URL" --skip-columns=guid
wp search-replace city-template.proudcity.com "$HOST" --skip-columns=guid
wp search-replace agency-template.proudcity.com "$HOST" --skip-columns=guid
wp search-replace recovers.proudcity.com "$HOST" --skip-columns=guid
wp search-replace coronavirus.proudcity.com "$HOST" --skip-columns=guid
wp search-replace "${OLD_URL}"/wp-content/uploads/ https://storage.googleapis.com/proudcity/"${STATELESS_MEDIA_DIRECTORY}" --skip-columns=guid
