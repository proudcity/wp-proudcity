# Updates for the 1.24.0 release
# (no manual updates)

PHP=${1}

echo "Update auth0 #893"
wp option get wp_auth0_settings --format=json --allow-root | php -r '
$var = json_decode( fgets(STDIN) );
$var->client_secret_b64_encoded = null;
print json_encode($var);
' | wp option set wp_auth0_settings --format=json --allow-root

echo "Update say_what string #868"
echo "INSERT INTO wp_say_what_strings (orig_string, domain, replacement_string, context) VALUES ('Agency list','wp-proud-core','Department list','');" | wp db cli --allow-root