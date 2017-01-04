# Updates for the 1.16.0 release
# (no manual updates)

PHP=${1}

# 
#wp --allow-root eval-file $PHP

# google_api_key didn't get properly set in all instances previously
wp plugin uninstall --deactivate rest-api --allow-root
wp plugin install rest-filter --allow-root
