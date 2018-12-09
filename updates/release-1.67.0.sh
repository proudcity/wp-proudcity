# Updates for the 1.67.0 Release

PHP=${1}

echo "wp --allow-root plugin activate classic-editor"
wp --allow-root plugin activate classic-editor
wp --allow-root core update-db