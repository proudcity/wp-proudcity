# Updates for the 1.24.0 release
# (no manual updates)

PHP=${1}

echo "Hotfix 1.52.3: Undo: Auth0 update to 3.5.1 #1262, Forced to enter username and password twice #1303 (Apr 1 2018)"
wp option get wp_auth0_settings --format=json --allow-root | php -r '
$var = json_decode( fgets(STDIN) );
$var->client_signing_algorithm = "HS256";
$var->sso = "1";
print json_encode($var);
' | wp option set wp_auth0_settings --format=json --allow-root
