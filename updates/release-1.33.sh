# Updates for the 1.32.3 release
# Installing the new PC Dashboard

PHP=${1}

#wp --allow-root eval-file $PHP

echo "Uninstall plugins"
wp plugin uninstall --deactivate wp-api-menus --allow-root
wp plugin uninstall --deactivate wp-jwt-auth --allow-root
wp plugin uninstall --deactivate proudpack --allow-root
