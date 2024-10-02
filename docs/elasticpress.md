# Running Elasticpress In Development

## Set up enviroment

### Start the docker images

```
docker compose -f docker-compose-elastic.yml up
```

### Add Elasticpress environment variables to wp-config

```
// Add Elasticpress host to
define('EP_HOST', 'http://localhost:9200');
// Optionally add file attachment support
// define('EP_HELPER_HOST', 'http://localhost:8085/send-attachments');
```

## Configure

### Enable Elasticpress + wp-proud-search-elastic

```
wp --allow-root plugin activate wp-proud-search-elastic elasticpress
```

### Go to the wo-proud-search-elastic settings `/wp-admin/admin.php?page=elasticsearch`

1. Create the index name (eg. 'beta')
2. Create a 'Search Cohort' (eg. 'Main Site', 'beta', 'http://localhost:10004', '#053951')

### Disable Elasticpress features

```
# first list all features
wp --allow-root elasticpress list-features
# disable each feature using this command
# eg. wp --allow-root elasticpress deactivate-feature documents
wp --allow-root elasticpress deactivate-feature {FEATURE}
```

### Map + index the side

```
wp --allow-root elasticpress put-mapping
wp --allow-root elasticpress sync
```
