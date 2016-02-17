ProudCity Wordpress
===================

[ProudCity](http://proudcity.com) is a Wordpress platform for modern, standards-compliant municipal websites.

# Contributing

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
  