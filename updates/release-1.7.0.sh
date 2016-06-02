# Updates for the 1.5.0 release
# (no manual updates)

PHP=${1}
wp --allow-root option set admin_email "notify@proudcity.com"

wp eval "
wp_mail ( 'jeff@proudcity.com', 'Testing wp_mail', 'Testing wp_mail' );
"