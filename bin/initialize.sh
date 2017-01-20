# Scrubs and configures a site after cloning
# Usage
#bash initialize.sh $URL $ACCOUNT_EMAIL $LOCATION $STAGE[test|live|example]
#bash initialize.sh www.cityofsanrafel.org
#bash initialize.sh oakland-ca.proudcity.com  jeffrey.m.lyon@gmail.com Oakland CA

thisdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

URL=${1}
ACCOUNT_EMAIL=${2}
LOCATION=${3}
STAGE=${4:test}

bash $thisdir/change-url.sh "$URL"

cd /app/wordpress
wp core update-db --allow-root
wp option update proud_stage $STAGE --allow-root
wp option delete google_analytics_key --allow-root
wp user delete demo@proudcity.com --reassign=admin --allow-root
echo "truncate table wp_rg_form_view;" | wp db cli --allow-root

if  [[ !  -z  $ACCOUNT_EMAIL  ]]; then
  wp user create  $ACCOUNT_EMAIL $ACCOUNT_EMAIL --send-email=0 --role=editor --allow-root
fi

if  [[ !  -z  $LOCATION  ]]; then
  wp option update blogname "$LOCATION" --allow-root
  # @todo: use string to lookup api...
fi