# Docker with ProudCity Wordpress

We highly recommend you use the ProudCity CLI to run local development environments.  You can also quickly spin up
a local Docker environment by running Docker compose in the root `wp-proudcity` directory:
```
docker-compose up
```

### Building
```
docker build -t proudcity/wp-proudcity .
docker-compose up # Test image
docker images
export IMAGE=
docker tag $IMAGE proudcity/wp-proudcity:0.11
docker push proudcity/wp-proudcity:0.11
```

To ssh into the box
```
CONTAINER=
docker exec -it $CONTAINER bash
docker exec -it `docker ps -aq --filter="name=wpproudcity_wordpress_1"` bash
```

```
mysql -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD -h${WORDPRESS_DB_HOST} -P${WORDPRESS_DB_PORT} $WORDPRESS_DB_NAME
```