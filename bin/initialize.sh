# Scrubs and configures a site after cloning
# Usage
#bash initialize.sh $URL $WIPE_PRIVATE[true|false], $ACCOUNT_EMAIL $LOCATION $STAGE[test|live|example]
#bash initialize.sh false www.cityofsanrafel.org
#bash initialize.sh true pueblo-co.proudcity.com  hsmith@pueblo.us "Pueblo/CO"

thisdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

URL=${1}
WIPE_PRIVATE=${2}
ACCOUNT_EMAIL=${3}
LOCATION=${4}
STAGE=${5:test}

echo "-------------"
echo "Running initialize.sh with parameters"
echo "URL: $URL"
echo "ACCOUNT_EMAIL: $URL"
echo "WIPE_PRIVATE: $WIPE_PRIVATE"
echo "LOCATION: $URL"
echo "STAGE: $URL"
echo "-------------"

bash $thisdir/change-url.sh "$URL"

cd /app/wordpress
wp core update-db --allow-root
#wp option update proud_stage $STAGE --allow-root
if [ "$initialize" == "true" ]; then
    wp option delete google_analytics_key --allow-root
    wp user delete demo@proudcity.com --reassign=admin --allow-root
    echo "truncate table wp_rg_form_view;" | wp db cli --allow-root
fi

if  [[ !  -z  $ACCOUNT_EMAIL  ]]; then
  wp user create  $ACCOUNT_EMAIL $ACCOUNT_EMAIL --send-email=0 --role=editor --allow-root
fi

# Get blogname, location, images, service center settings from ProudCity City API.
if  [[ !  -z  $LOCATION  ]]; then
  wp plugin activate wp-proud-dashboard --allow-root
  wp proudcity phonehome "$LOCATION" --allow-root
fi