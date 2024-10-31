#!/bin/bash

# set widget order for all users
wp user list --field=ID --format=ids --allow-root | xargs -0 --d ' ' -I % wp user meta update % meta-box-order_dashboard '{
  "normal": "proud_news_feed,proud_ye_checklist_widget,proud_concierge,proud_gogov,proud_training,proud_help_center,proud_support",
  "side": "proud_help_news_feed,proud_clean_up,proud_plain_language,proud_review,proud_payments_widget,proud_accounts_widget",
  "column3": "",
  "column4": ""
}' --format=json --allow-root
