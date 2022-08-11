# Updates for the 1.60.1 Release

PHP=${1}

echo "Uninstall wp-jwt-auth"
wp plugin uninstall --allow-root wp-jwt-auth
