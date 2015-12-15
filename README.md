ProudCity Wordpress
===================

Builds the ProudCity Wordpress distribution.

This is based on the structure outlined on https://deliciousbrains.com/install-wordpress-subdirectory-composer-git-submodule/.

# Composer

## Building

```
composer install
```
Wordpress will be in the `./src` dir.

You can automate the management of custom plugins and themes using the bash scripts in ./scripts:
```
cd ./wp-proud-composer
bash scripts/cmd.sh "git status"
```


## Installing
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


# Docker
Dockerfile and run.sh are heavily influenced by (CenturyLinkLabs/docker-wordpress)[https://github.com/CenturyLinkLabs/docker-wordpress/blob/master/Dockerfile].

## Building
```
docker build -t proudcity/wp-proud-composer .
docker images
```



## Running
Variables:
* API_PUBLIC : proudpack api client (site id)
* API_SECRET : proudpack api secret
* API_URL : proudcity api url (https://api.proudcity.com)
* DB_DUMP_URL : url to .sql.gz base database dump
* URL : the url of the site 
* DB_NAME
* DB_PASSWORD

```
docker run -p 80:8080
  -e "API_URL: "http://api.getproudcity.com" \
  -e "DB_DUMP_URL: "http://getproudcity.com/db.sql.gz" \
  -e "DB_NAME: wordpress \
  -e "DB_PASSWORD: password \
  -e "API_PUBLIC: "$API_PUBLIC" \
  -e "API_SECRET: "$API_SECRET" \
  -e "URL: "$URL" \
  proudcity/wp-proud-composer
```
To ssh into the box
```
docker exec -t -i VOLUME_NAME bash
```
  