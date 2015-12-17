#!/bin/bash
echo "=> Git pull latest changes (should this be temporary?)"
bash /scripts/cmd.sh "git pull -X theirs"
echo "=> Loading initial database data from $DB_DUMP_URL to $DB_NAME"
curl -o db.sql.gz "$DB_DUMP_URL"
gunzip db.sql.gz
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST -P$DB_PORT $DB_NAME < db.sql
rm db.sql
echo "=> Run post-install setup commands"
cd src
# @todo: we'll need this: wp search-replace 'http://example.dev' '${}' --skip-columns=guid
wp option update home "$URL" --allow-root
wp option update siteurl "$URL" --allow-root
# @todo: call custom proudpack wp-cli command
cd ../