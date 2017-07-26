# Updates for the 1.35.0 Release

PHP=${1}

# Set wp-stateless option "delete remote when deleted locally"
wp option get sm --format=json --allow-root | php -r '
$var = json_decode( fgets(STDIN) );
$var->delete_remote = "true";
print json_encode($var);
' | wp option set sm --format=json --allow-root