# Updates for the 1.1.0 release

wp plugin deactivate --uninstall wpmandrill --allow-root
wp plugin activate wp-jwt-auth --allow-root
wp search-replace '&nbsp;' '' --allow-root