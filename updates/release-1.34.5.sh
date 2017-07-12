# Updates for the 1.34.5 Hotfix release

PHP=${1}

echo "Update auth0 sso = 0"
wp option get wp_auth0_settings --format=json --allow-root | php -r '
$var->sso = 0;
print json_encode($var);
' | wp option set wp_auth0_settings --format=json --allow-root
