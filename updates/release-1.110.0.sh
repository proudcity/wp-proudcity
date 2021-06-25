# Updates for the 1.110.0 release
# Update wp-fullcalendar settings

PHP=${1}

# Calendar: Update formatting, replacing yellow with more neutral gray #1855
wp option set wpfc_theme_css smoothness --allow-root
