# Usage
#bash etc-kube/build.sh san-rafael-ca

# Globals
thisdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source $thisdir/globals.sh

# Make sure mysql is installed.  This will gracefully degrade locally (you won't be sudo).
apt-get update && apt-get install mysql-client -y

echo "NAMESPACE set to $NAMESPACE"
echo "THIS DIRECTORY: $thisdir"

# Arguments
key=${1:beta}
deploy=${2:true}
suffix=".proudcity.com"
host="${key}${suffix}"
key=${key//-/}
echo "KEY: ${key}"
echo "HOST: ${host}"

# Variables setup
dbname=`echo ${key//-/_} | cut -c 1-16`
dbpass=`date +%s | sha256sum | base64 | head -c 32 ; echo`

# Copy the Kube config
if [ ! -d builds ]; then mkdir "${thisdir}/builds"; fi
dir="${thisdir}/builds/${key}"
echo "BUILD DIR: ${dir}"
if [ -d $dir ]; then rm -Rf $dir; fi
mkdir $dir
cd $thisdir
cp deployment.json post-import.sql service.json $dir
cd $dir

# Create our secrets file
echo "
apiVersion: v1
kind: Secret
metadata:
  name: $key
type: Opaque
data:
  dbhost: `echo -n $MYSQL_HOST | base64`
  dbuser: `echo -n $dbname | base64`
  dbpassword: `echo -n $dbpass | base64`
  dbname: `echo -n $dbname | base64`
  dbport: `echo -n $MYSQL_PORT | base64`
  auth-key: `echo -n openssl rand -base64 32 | base64`
  secure-auth-key: `echo -n openssl rand -base64 32 | base64`
  logged-in-key: `echo -n openssl rand -base64 32 | base64`
  nonce-key: `echo -n openssl rand -base64 32 | base64`
  auth-salt: `echo -n openssl rand -base64 32 | base64`
  secure-auth-salt: `echo -n openssl rand -base64 32 | base64`
  logged-in-salt: `echo -n openssl rand -base64 32 | base64`
  nonce-salt: `echo -n openssl rand -base64 32 | base64`
" > $dir/secrets.yml

# Mysql setup
echo "
DROP DATABASE IF EXISTS ${dbname} ;
CREATE DATABASE ${dbname};
GRANT ALL ON ${dbname}.* TO ${dbname}@'%' IDENTIFIED BY '${dbpass}';
" > create.sql
mysql -u${MYSQL_USER} -p${MYSQL_PASS} -h${MYSQL_HOST} -P${MYSQL_PORT} < create.sql
echo "Downloading database dump from ${DATABASE_DUMP} ..."
curl $DATABASE_DUMP | gunzip > $dir/db.sql

# REPLACE!
# Key (this is the big one)
sed -i "s/corvallisor/${key}/g" *.yml
sed -i "s/corvallisor/${key}/g" *.json
sed -i "s/example/${key}/g" *.sql
# Domain
sed -i "s/corvallis-or\.proudcity\.com/${host}/g" *
sed -i "s/example\.proudcity\.com/${host}/g" *

if [ "$deploy" == "true" ]; then

  # Import Mysql db
  echo "Importing Mysql db $dir/db.sql ..."
  mysql -u${MYSQL_USER} -p${MYSQL_PASS} -h${MYSQL_HOST} -P${MYSQL_PORT} ${dbname} < $dir/db.sql
  mysql -u${MYSQL_USER} -p${MYSQL_PASS} -h${MYSQL_HOST} -P${MYSQL_PORT} ${dbname} < $dir/post-import.sql

  # Build Kubernetes
  echo "Build Kubernetes ..."
  kubectl apply --namespace $NAMESPACE -f $dir/secrets.yml
  kubectl apply --namespace $NAMESPACE -f $dir/deployment.json
  kubectl apply --namespace $NAMESPACE -f $dir/service.json
  # kubectl create --namespace $NAMESPACE -f etc-kube/ingress-ssl.yml
  #kubectl apply --namespace $NAMESPACE -f ingress.yml

  # Update ingress to add new host
  # @todo: For Lets Encrypt (kube-lego)
  #kubectl --namespace $NAMESPACE get ing --output=json \
  #  | jq ".items[0].spec.tls |= .+ [{\"hosts\": [\"${host}\"], \"secretName\": \"${key}-tls\"}]" \
  #  | jq ".items[0].spec.rules |= .+ [{ \"host\": \"${host}\", \"http\": { \"paths\": [ { \"path\": \"/*\", \"backend\": { \"serviceName\": \"${key}\", \"servicePort\": 80 } } ] } }]" \
  #  > $dir/ingress.json
  # For ProudCity wildcard SSL cert
  kubectl --namespace $NAMESPACE get ing --output=json \
    | jq ".items[0].spec.rules |= .+ [{ \"host\": \"${host}\", \"http\": { \"paths\": [ { \"backend\": { \"serviceName\": \"${key}\", \"servicePort\": 80 } } ] } }]" \
    > $dir/ingress.json
  kubectl apply --namespace $NAMESPACE -f $dir/ingress.json

  # Clean up
  #rm secrets.yml
  #rm -r $key

  echo 'Done building, waiting for website to become available'
  #until $(curl --output /dev/null --silent --head --fail https://${host}); do
  #    echo '.'
  #    sleep 5
  #done

  kubectl --namespace $NAMESPACE get po
  kubectl --namespace $NAMESPACE get ing

else

  echo "Deploy set to false, skipping kubernetes creation, mysql db import"

fi
