#!/bin/bash

# activate our new plugin
wp plugin activate wp-proud-admin-theme --allow-root

# set widget order
wp user meta update '{
  "normal": "proud_news_feed,proud_training,proud_help_center,proud_support",
  "side": "proud_clean_up,proud_plain_language,proud_review",
  "column3": "",
  "column4": ""
}' --format=json --allow-root
