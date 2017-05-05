# Updates for the 1.24.0 release
# (no manual updates)

PHP=${1}

echo "Week should start on Sunday, not Monday"
wp option update start_of_week 0 --allow-root

