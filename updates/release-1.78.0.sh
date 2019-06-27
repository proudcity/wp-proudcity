# Updates for the 1.78.0 release
# (no manual updates)

PHP=${1}

echo "Get rid of lingering references to city-template.proudcity.com #1513"
wp search-replace city-template.proudcity.com $HOST --skip-columns=guid --allow-root
