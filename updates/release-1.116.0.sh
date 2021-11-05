# Updates for the 1.115.0 release
# Fix w3tc JS minify wp-fullcalendar incompatibility

PHP=${1}

# Uninstall 
wp plugin deactivate --uninstall enable-jquery-migrate-helper --allow-root
