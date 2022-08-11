# Updates for the 1.116.0 release
# Uninstall Jquery Migrate (no longer needed for wp-fullcalendar)

PHP=${1}

# Uninstall Jquery Migrate (no longer needed for wp-fullcalendar)
wp plugin deactivate --uninstall enable-jquery-migrate-helper --allow-root
