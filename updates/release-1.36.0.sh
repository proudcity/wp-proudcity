# Updates for the 1.36.0 Release

PHP=${1}

#echo "Running gravityforms update php script"
#wp --allow-root eval-file /app/updates/release-1.36.0.php

echo "Switching to WP_Widget_Custom_HTML widget."
wp search-replace WP_Widget_Text WP_Widget_Custom_HTML --allow-root

