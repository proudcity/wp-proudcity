ProudCity Wordpress
===================

Builds the ProudCity Wordpress distribution.

This is based on the structure outlined on https://deliciousbrains.com/install-wordpress-subdirectory-composer-git-submodule/.


## Building

```
composer install
```

Worpress will be in the `./public` dir with the wwwroot at `./public/wp`.


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