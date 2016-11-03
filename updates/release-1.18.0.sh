# Updates for the 1.16.0 release
# (no manual updates)

PHP=${1}

# Update order of Service Center link
wp --allow-root eval-file $PHP

# Allow service centger page callbacks
wp --allow-root rewrite flush

# google_api_key didn't get properly set in all instances previously
wp option update google_api_key "AIzaSyCwfrA-_2rE-IiNf1z74xe1YeLolSeapnU" --allow-root
