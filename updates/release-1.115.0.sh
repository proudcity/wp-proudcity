# Updates for the 1.115.0 release
# Fix w3tc JS minify wp-fullcalendar incompatibility

PHP=${1}

# if wp-fullcalendar is enabled, change JS minify to "combine"
php ../bin/w3-total-cache-config.php 