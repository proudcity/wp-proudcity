ProudCity WordPress
===================

[ProudCity](https://proudcity.com) is a Wordpress platform for modern, standards-compliant municipal websites. [Find your city's demo](https://proudcity.com/start) or [view an example website](https://example.proudcity.com).

## 5-minute setup
1. Install [Composer](https://getcomposer.org) and [WP-CLI](http://wp-cli.org/).
  ```
  curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
  curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar | chmod +x wp-cli.phar | sudo mv wp-cli.phar /usr/local/bin/wp
  ```

2. Clone and build code
  ```
  git clone https://github.com/proudcity/wp-proudcity.git
  cd wp-proudcity
  composer install --no-dev
  cp wp-config.php wordpress
  cp .env.example wordpress/.env
  ```
3. Copy the contents of `./wordpress` to the `www-root` of a server and [install WordPress as normal](https://codex.wordpress.org/Installing_WordPress#Famous_5-Minute_Install)

4. Install WordPress plugins
  ```
  wp-plugin activate taxonomy-terms-order disable-comments events-manager google-analytics-dashboard-for-wp iframe mce-table-buttons siteorigin-panels wp-proud-document wp-proud-payment wp-proud-actions-app wp-proud-admin wp-proud-agency wp-proud-core wp-proud-map-app proudpack wp-proud-search wp-proud-social-app safe-redirect-manager simple-staff-list so-widgets-bundle wordpress-faq-manager wp-job-manager rest-api wp-api-menus wordpress-seo 
  ```

[Watch Getting Started videos](https://proudcity.com/getting-started)


## Contributing

This repo contains a composer make file that will build a complete Wordpress installation.  See [Composer](#composer) for more details.  Most of the actual code is in our individual Plugin and Theme repos:
* [wp-proud-theme](https://github.com/proudcity/wp-proud-theme): The ProudCity Wordpress theme built on top of [Bootstrap](http://getbootstrap.com) and [Sage](https://roots.io/sage/).
* [wp-proud-core](https://github.com/proudcity/wp-proud-core): Core customizations and helper modules, including proud-teaser-list, which creates easy, filterable lists of content.
* [wp-proud-admin](https://github.com/proudcity/wp-proud-admin): Administration theme, permissions tweaks, and ProudCity settings pages.
* [wp-proud-search](https://github.com/proudcity/wp-proud-search): Provides title suggestions while typing a search query.
* [wp-proud-agency](https://github.com/proudcity/wp-proud-agency): Creates an Agency post type. 
* [wp-proud-document](https://github.com/proudcity/wp-proud-document): Creates a Document post type for file uploads and forms.
* [wp-proud-payment](https://github.com/proudcity/wp-proud-payment): Creates a Payment post type.
* [wp-proud-actions-app](https://github.com/proudcity/wp-proud-actions-app): An interactive, Angular-based 311 interface for FAQ, Payments, Issue reporting and Issue lookup.
* [wp-proud-social-app](https://github.com/proudcity/wp-proud-social-app): Social media feed that pulls from an aggregated JSON feed and can display media as a wall, timeline, or simple feed. 
* [wp-proud-map-app](https://github.com/proudcity/wp-proud-map-app): Creates an interactive city services map with [Mapbox](http://mapbox.com).

All bug reports, feature requests and other issues should be added to the [wp-proudcity Issue Queue](https://github.com/proudcity/wp-proudcity/issues).  If you are using ProudCity, or are interested in getting involved, please join our [Community Forum](https://groups.google.com/d/forum/proudcitydevelopers). 

Visit [our website](https://proudcity.com/developers) for more information about ProudCity for developers.

## Composer

### Building

```
composer install
```
Wordpress will be in the `./src` dir.

You can automate the management of custom plugins and themes using the bash scripts in ./scripts:
```
cd ./wp-proudcity
bash scripts/cmd.sh "git status"
```


### Installing
```
wp db drop
wp db create
wp core install \
  --url=wordpress.local \
  --title="Proud" \
  --admin_user=admin \
  --admin_password=demo \
  --admin_email="jeff@getproudcity.com"
wp plugin activate --all
```

Or from an existing db:
```
wp db drop
wp db create
wp db import dump.sql
wp option update siteurl "http://wordpress.local"
wp option update home "http://wordpress.local"
```


## Docker
Dockerfile and run.sh are forked from (tutumcloud/wordpress-stackable)[https://github.com/tutumcloud/wordpress-stackable].

### Building
```
docker build -t proudcity/wp-proudcity .
docker-compose up # Test image
docker images
export IMAGE=
docker tag $IMAGE proudcity/wp-proudcity:0.10
docker push proudcity/wp-proudcity:0.10
```



### Running
Variables:
* API_PUBLIC : proudpack api client (site id)
* API_SECRET : proudpack api secret
* API_URL : proudcity api url (https://api.proudcity.com)
* DB_DUMP_URL : url to .sql.gz base database dump
* URL : the url of the site 
* DB_NAME
* DB_PASS

We need to create a mysql container and link it first
```
docker run -d -e MYSQL_PASS="<your_password>" --name dbc -p 3306:3306 tutum/mysql:5.5
docker run -p 8080:80 \
  --link dbc:db -e DB_PASS="<your_password>" \
  -e DB_DUMP_URL="http://getproudcity.com/db.sql.gz" \
  -e PROUD_ID="PROUD_ID" \
  -e PROUD_PUBLIC="PROUD_PUBLIC" \
  -e PROUD_SECRET="PROUD_SECRET" \
  -e URL="http://localhost:8080" \
  proudcity/wp-proud-composer
```
To ssh into the box
```
docker exec -it `docker ps -aq --filter="name=wpproudcity_wordpress_1"` bash
```

```
mysql -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD -h${WORDPRESS_DB_HOST} -P${WORDPRESS_DB_PORT} $WORDPRESS_DB_NAME
```


## Running on Kubernetes
```
# Set up system variables
source etc-kube/globals.sh 

# Set up Kubernetes namespace, SSL, settings secrets
kubectl create secret generic tls --from-file=~/workspace/ssl/proudcity/localcerts/combined.crt --from-file=~/workspace/ssl/proudcity/localcerts/proudcity.com.key --namespace jenkins
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

# Mysql commands on in docker container
mysql -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD -h$WORDPRESS_DB_HOST -P$WORDPRESS_DB_PORT

$Mysql commandshost
mysql -u$MYSQL_USER -p$MYSQL_PASS -h$MYSQL_HOST -P$MYSQL_PORT

```

## Notes

Tested with [BrowserStack](https://www.browserstack.com/).  Licensed under [GNU Affero GPL](https://github.com/proudcity/wp-proudcity/blob/master/LICENSE.txt).  Made by [ProudCity](https://proudcity.com/)
