# Updates for the 1.34.2 release
# Fix issues with wp-stateless

PHP=${1}
#wp --allow-root eval-file $PHP

echo "Body search and replace"
URL=$(wp option get siteurl --allow-root)
APP="${APP:-$WORDPRESS_DB_NAME}"
echo "RUNNING COMMAND: wp search-replace ${URL}/wp-content/uploads https://storage.googleapis.com/proudcity/${APP}/uploads --allow-root"
wp search-replace ${URL}/wp-content/uploads https://storage.googleapis.com/proudcity/${APP}/uploads --allow-root

echo "Update option: sm"
wp option get sm --allow-root


wp option get sm --format=json --allow-root | php -r '
$var = json_decode( fgets(STDIN) );
$var->body_rewrite = "true";
print json_encode($var);
' | wp option set sm --format=json --allow-root