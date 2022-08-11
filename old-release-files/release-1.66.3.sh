# Updates for the 1.66.3 Release

PHP=${1}

echo "wp --allow-root plugin activate disable-xml-rpc"
wp --allow-root plugin activate disable-xml-rpc

echo "wp --allow-root plugin activate disable-xml-rpc"
wp --allow-root db query "DELETE from wp_options where option_name like '%transient%';"
wp --allow-root db query "select count(*) from wp_options where option_name like '%transient%';"
