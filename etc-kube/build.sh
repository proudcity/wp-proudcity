# SSL One-time setup:
#source etc-kube/globals.sh 
#kubectl create secret generic tls --from-file=combined.crt --from-file=proudcity.com.key --namespace jenkins
#kubectl create ns $NAMESPACE && kubectl create --namespace $NAMESPACE -f etc-kube/secrets-proudcity.yml


# Globals
thisdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source $thisdir/globals.sh

echo "NAMESPACE set to $NAMESPACE"
echo $thisdir
# Arguments
state=OR
city="Corn Valley"

# Clean up
state="${state,,}"
city="${city,,}"

# Optional arguments?
glue=""
key="${city// /}$glue$state"
key=beta
glue="-"
suffix=".proudcity.com"
host="${city// /-}$glue$state$suffix"

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


# Mysql setup
echo "
CREATE database ${dbname};
GRANT ALL ON ${dbname}.* TO ${dbname}@'%' IDENTIFIED BY '${dbpass}';
" > create.sql
mysql -u${MYSQL_USER} -p${MYSQL_PASS} -h${MYSQL_HOST} -P${MYSQL_PORT} < create.sql

# Create our secrets file
echo "
apiVersion: v1
kind: Secret
metadata:
  name: $key
type: Opaque
data:
  dbhost: `base64 <<< $MYSQL_HOST`
  dbuser: `base64 <<< $dbname`
  dbpassword: `base64 <<< $dbpass`
  dbname: `base64 <<< $dbname`
  dbport: `base64 <<< $MYSQL_PORT`
  auth-key: `base64 --wrap=0 /dev/urandom |head -c 64`
  secure-auth-key: `base64 --wrap=0 /dev/urandom |head -c 64`
  logged-in-key: `base64 --wrap=0 /dev/urandom |head -c 64`
  nonce-key: `base64 --wrap=0 /dev/urandom |head -c 64`
  auth-salt: `base64 --wrap=0 /dev/urandom |head -c 64`
  secure-auth-salt: `base64 --wrap=0 /dev/urandom |head -c 64`
  logged-in-salt: `base64 --wrap=0 /dev/urandom |head -c 64`
  nonce-salt: `base64 --wrap=0 /dev/urandom |head -c 64`
  gcserviceaccount.json: bm90aGluZw==
" > $dir/secrets.yml
# Key (this is the big one)
sed -i "s/corvallis_or/${key}/g" *
# Domain
sed -i "s/corvallis-or\.proudcity\.com/${host}/g" *


kubectl apply --namespace $NAMESPACE -f $dir/secrets.yml
kubectl apply --namespace $NAMESPACE -f $dir/deployment.json
kubectl apply --namespace $NAMESPACE -f $dir/service.json
# kubectl create --namespace $NAMESPACE -f etc-kube/ingress-ssl.yml
#kubectl apply --namespace $NAMESPACE -f ingress.yml

echo 'done'
# Clean up
#rm secrets.yml
#rm -r $key