## Running on Kubernetes
```
# Set up system variables
source etc-kube/globals.sh 

# Set up Kubernetes namespace, SSL, settings secrets
kubectl create secret generic proudcity-tls --from-file=/home/jeff/workspace/ssl/proudcity/localcerts/tls.crt --from-file=/home/jeff/workspace/ssl/proudcity/localcerts/tls.key --namespace $NAMESPACE
kubectl create ns $NAMESPACE && kubectl create --namespace $NAMESPACE -f etc-kube/secrets-proudcity.yml

# Automatically build
bash etc-kube/build.sh kube-san-rafael-ca

# Manual setup
export ACTION=create # delete, etc
kubectl $ACTION --namespace $NAMESPACE -f secrets.yml
kubectl $ACTION --namespace $NAMESPACE -f deployment.json
kubectl $ACTION --namespace $NAMESPACE -f service.json

# Update ingress (@todo: nodejs?)
kubectl apply --namespace $NAMESPACE -f etc-kube/ingress.yml

# Set up ingress (kube-lego for Lets Encrypt)
kubectl apply -f kube-lego/00-namespace.yaml
kubectl apply -f kube-lego/configmap.yaml
kubectl apply -f kube-lego/deployment.yaml
```


### Helpful commands
```
# Get ingress IP
kubectl --namespace $NAMESPACE get ing

# SSH into pod
kubectl --namespace $NAMESPACE get po
export POD=
kubectl --namespace $NAMESPACE exec -ti $POD bash

# Get logs
kubectl logs --namespace $NAMESPACE $POD  --tail=100

# Force rebuild pod
kubectl --namespace $NAMESPACE get pod $POD -o=json > /tmp/a.json && kubectl replace --force -f /tmp/a.json

# Rebuild all pods in namespace
﻿⁠⁠⁠⁠kubectl --namespace $NAMESPACE get pod -o=json > /tmp/a.json && kubectl replace --force -f /tmp/a.json

# Mysql commands on in docker container
mysql -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD -h$WORDPRESS_DB_HOST -P$WORDPRESS_DB_PORT

# Mysql commands host
mysql -u$MYSQL_USER -p$MYSQL_PASS -h$MYSQL_HOST -P$MYSQL_PORT

# Login to Jenkins
kubectl --namespace jenkins exec -ti jenkins-3978681046-gyliw bash

# Look at Kube-Lego (Lets Encrypt) logs
kubectl logs --namespace kube-lego kube-lego-3701941839-pyjxg  --tail=100


```

## Notes

Tested with [BrowserStack](https://www.browserstack.com/).  Licensed under [GNU Affero GPL](https://github.com/proudcity/wp-proudcity/blob/master/LICENSE.txt).  Made by [ProudCity](https://proudcity.com/)
