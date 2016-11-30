## Docker
Dockerfile and run.sh are forked from (tutumcloud/wordpress-stackable)[https://github.com/tutumcloud/wordpress-stackable].

### Building
```
docker build -t proudcity/wp-proudcity .
docker-compose up # Test image
docker images
export IMAGE=
docker tag $IMAGE proudcity/wp-proudcity:0.11
docker push proudcity/wp-proudcity:0.11
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