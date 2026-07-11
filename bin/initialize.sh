# Scrubs and configures a site after cloning
# Usage
#bash initialize.sh $URL $WIPE_PRIVATE[true|false] $LOCATION $STAGE[test|live|example]
#bash initialize.sh www.cityofsanrafel.org false
#bash initialize.sh granger-ia.proudcity.com  true IA/Granger

thisdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

URL=${1}
WIPE_PRIVATE=${2}
LOCATION=${3}
STAGE=${4:test}

echo "-------------"
echo "Running initialize.sh with parameters"
echo "URL: $URL"
echo "WIPE_PRIVATE: $WIPE_PRIVATE"
echo "LOCATION: $URL"
echo "STAGE: $STAGE"
echo "-------------"

bash $thisdir/change-url.sh "$URL"

cd /app/wordpress
wp core update-db
#wp option update proud_stage $STAGE
if [ "$initialize" == "true" ]; then
    wp option delete google_analytics_key
    wp user delete demo@proudcity.com --reassign=admin
    echo "truncate table wp_rg_form_view;" | wp db cli
fi

# Get blogname, location, images, service center settings from ProudCity City API.
if  [[ !  -z  $LOCATION  ]]; then
  wp plugin activate wp-proud-dashboard
  wp proudcity phonehome "$LOCATION"
fi