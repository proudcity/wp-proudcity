# Updates for the 1.32.3 release
# Installing the new PC Dashboard

PHP=${1}

# Update the menus for the new dashboard
wp --allow-root eval-file $PHP

wp plugin activate wp-proud-dashboard --allow-root
wp proudcity apiplugins --allow-root


wp eval-file /app/updates/release-1.32.3.php --allow-root

