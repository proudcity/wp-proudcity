# Updates for the 1.24.0 release
# (no manual updates)

PHP=${1}

echo "Update auth0 welcome note"
wp option get wp_auth0_settings --format=json --allow-root | php -r '
$var = json_decode( fgets(STDIN) );
$var->form_title = "Welcome to ProudCity1";
$var->icon_url = "https://my.proudcity.com/images/IconBlack.png";
print json_encode($var);
' | wp option set wp_auth0_settings --format=json --allow-root
