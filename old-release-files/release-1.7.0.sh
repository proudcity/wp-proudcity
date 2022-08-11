# Updates for the 1.7.0 release
# Update admin_email
# Deactivate wp-ses
# Activate wp-mail-smtp
# Send test email

PHP=${1}

# Update admin_email
wp --allow-root option update admin_email "notify@proudcity.com"

# Deactivate wp-ses
wp --allow-root plugin deactivate wp-ses

# Activate wp-mail-smtp
wp --allow-root plugin install --activate wp-mail-smtp


# Send test email
wp --allow-root eval "
wp_mail ( 'jeff@proudcity.com', 'Testing wp_mail ' . get_site_url (), 'Testing wp_mail' );
"