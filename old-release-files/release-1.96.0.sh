# Updates for the 1.96.0 release
# Increase the cache-control settings in wp-stateless to improve site speed scores

PHP=${1}

# Increase cache lifetime in wp-stateless from 10 hrs to 30 days
wp option get sm --format=json --allow-root | php -r '
$var = json_decode( fgets(STDIN) );
$var->cache_control = "public, max-age=2592000, must-revalidate";
print json_encode($var);
' | wp option set sm --format=json --allow-root