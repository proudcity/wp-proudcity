# Docker with ProudCity Wordpress

We highly recommend you use the ProudCity CLI to run local development environments.  You can also quickly spin up
a local Docker environment by running Docker compose in the root `wp-proudcity` directory:
```
cp docker-compose.yml.example docker-compose.yml
docker-compose up
```

`docker-compose.yml` is gitignored so that each developer keeps their own copy and no local credentials or tokens are
ever committed.  Only `docker-compose.yml.example` is tracked, so edit your copy freely.  `pc dev up` creates the copy
for you the first time it runs.

Neither file is used to build or deploy anything — the image is built by `cloudbuild.yaml` with a plain `docker build`,
and pods take their environment from the Kubernetes deployment and the `proudcity` secret.

### Use a current image

The `image:` line in `docker-compose.yml.example` is pinned to a specific tag, and that pin goes stale as soon as
anything ships.  Before you start working, point your copy at a current build rather than whatever the example
happens to carry:

```
gcloud container images list-tags gcr.io/proudcity-1184/wp-proudcity --limit 10
```

Take a recent `master-<sha>` tag and set it in your `docker-compose.yml`.  Re-check this whenever you come back to
local development after a break — an old image means you are debugging against months-old plugin and theme code.

### Credentials and ports

The database passwords default to `wordpress` and the MariaDB port is published on `127.0.0.1` only.  To override the
passwords, either edit your copy directly or create a `.env` file in the repository root, which Docker Compose reads
automatically:

```
MYSQL_ROOT_PASSWORD=something-else
MYSQL_PASSWORD=something-else
WORDPRESS_DB_PASSWORD=something-else
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