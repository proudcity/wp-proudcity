# Usage
#bash etc-kube/build.sh san-rafael-ca

# Globals
thisdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source $thisdir/globals.sh

echo "NAMESPACE set to $NAMESPACE"
echo $thisdir

# Arguments
key=${1:beta}
suffix=".proudcity.com"
host="${key}${suffix}"
key=${key//-/}
echo "KEY: ${key}"
echo "HOST: ${host}"

# Variables setup
dbname=`echo ${key//-/_} | cut -c 1-16`
dbpass=`date +%s | sha256sum | base64 | head -c 32 ; echo`

# Copy the Kube config
if [ ! -d ../builds ]; then mkdir ../builds; fi
dir="$thisdir/../builds/${key}"
echo "BUILD DIR: ${dir}"
if [ -d $dir ]; then rm -Rf $dir; fi
cp -r "$thisdir/../etc-kube" $dir
cd $dir

# Create our secrets file
echo "
apiVersion: v1
kind: Secret
metadata:
  name: $key
type: Opaque
data:
  dbhost: `echo $MYSQL_HOST | base64 -w0`
  dbuser: `echo $dbname | base64 -w0`
  dbpassword: `echo $dbpass | base64 -w0`
  dbname: `echo $dbname | base64 -w0`
  dbport: `echo $MYSQL_PORT | base64 -w0`
  auth-key: `echo openssl rand -base64 32 | base64 -w0`
  secure-auth-key: `echo openssl rand -base64 32 | base64 -w0`
  logged-in-key: `echo openssl rand -base64 32 | base64 -w0`
  nonce-key: `echo openssl rand -base64 32 | base64 -w0`
  auth-salt: `echo openssl rand -base64 32 | base64 -w0`
  secure-auth-salt: `echo openssl rand -base64 32 | base64 -w0`
  logged-in-salt: `echo openssl rand -base64 32 | base64 -w0`
  nonce-salt: `echo openssl rand -base64 32 | base64 -w0`
  gcserviceaccount.json: bm90aGluZw==
" > $dir/secrets.yml

# Mysql setup
echo "Downloading database dump ..."
echo "
DROP DATABASE IF EXISTS ${dbname} ;
CREATE DATABASE ${dbname};
GRANT ALL ON ${dbname}.* TO ${dbname}@'%' IDENTIFIED BY '${dbpass}';
" > create.sql
mysql -u${MYSQL_USER} -p${MYSQL_PASS} -h${MYSQL_HOST} -P${MYSQL_PORT} < create.sql
curl $DATABASE_DUMP | gunzip > $dir/db.sql

# REPLACE!
# Key (this is the big one)
sed -i "s/corvallis_or/${key}/g" *.yml
sed -i "s/corvallis_or/${key}/g" *.json
sed -i "s/corvallis_or/${key}/g" *.sql
# Domain
sed -i "s/corvallis-or\.proudcity\.com/${host}/g" *
sed -i "s/example\.proudcity\.com/${host}/g" *

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

# Update ingress
kubectl --namespace $NAMESPACE get ing --output=json \
  | jq ".items[0].spec.tls |= .+ [{\"hosts\": [\"${host}\"], \"secretName\": \"${key}-tls\"}]" \
  | jq ".items[0].spec.rules |= .+ [{ \"host\": \"${host}\", \"http\": { \"paths\": [ { \"path\": \"/*\", \"backend\": { \"serviceName\": \"${key}\", \"servicePort\": 80 } } ] } }]" \
  > $dir/ingress.json
kubectl apply --namespace $NAMESPACE -f $dir/ingress.json

echo 'done'
kubectl --namespace $NAMESPACE get po
# Clean up
#rm secrets.yml
#rm -r $key