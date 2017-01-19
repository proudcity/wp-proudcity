# Scrubs and configures a site after cloning
# Usage
#bash initialize.sh $URL $ACCOUNT_EMAIL $CITY $STATE
#bash initialize.sh www.cityofsanrafel.org
#bash initialize.sh oakland-ca.proudcity.com  jeffrey.m.lyon@gmail.com Oakland CA
URL=${1}
ACCOUNT_EMAIL=${2}
CITY=${3}
STATE=${4}
STAGE=${4}

bash ./change-url.sh "$URL"

cd /app/wordpress
wp core update-db --allow-root
wp option update proud_stage test --allow-root
wp option delete google_analytics_key --allow-root
wp user delete demo@proudcity.com --reassign=admin --allow-root

if  [[ !  -z  $ACCOUNT_EMAIL  ]]; then
  wp user create  $ACCOUNT_EMAIL $ACCOUNT_EMAIL --send-email=0 --role=editor --allow-root
fi

if  [[ !  -z  $CITY  ]]; then
  wp option update blogname "$CITY" --allow-root
  # @todo
fi