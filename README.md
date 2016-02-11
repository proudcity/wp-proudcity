ProudCity Wordpress
===================

[ProudCity](http://proudcity.com) is a Wordpress platform for modern, standards-compliant municipal websites.

# Contributing

This repo contains a composer make file that will build a complete Wordpress installation.  See [Composer](#composer) for more details.  Most of the actual code is in our individual Plugin and Theme repos:



All issues should be added to the [wp-proudcity Issue Queue](https://github.com/proudcity/wp-proudcity/issues)

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
Dockerfile and run.sh are forked from (tutumcloud/wordpress-stackable)[https://github.com/tutumcloud/wordpress-stackable].

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
docker exec -t -i VOLUME_NAME bash
```
  