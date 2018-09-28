# Updates for the 1.60.1 Release

PHP=${1}

echo "Install post-expirator, duplicate-post"
wp plugin activate --allow-root post-expirator
wp plugin activate --allow-root duplicate-post
