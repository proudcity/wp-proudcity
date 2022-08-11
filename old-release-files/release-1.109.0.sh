# Updates for the 1.109.0 release
# Update wp-fullcalendar settings

PHP=${1}

# Calendar: Update formatting, replacing yellow with more neutral gray #1855
wp option set wpfc_limit 10 --allow-root

# Calendar: Update formatting, replacing yellow with more neutral gray #1855
wp option set wpfc_theme_css ui-lightness --allow-root

# Disable calendar tooltips
wp option set wpfc_qtips 0 --allow-root