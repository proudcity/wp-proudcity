#!/bin/bash

# updating google site kit so that editors can see content
wp option update googlesitekit_dashboard_sharing '{"pagespeed-insights":{"sharedRoles":[],"management":"all_admins"},"analytics":{"sharedRoles":["editor"],"management":"owner"},"search-console":{"sharedRoles":["editor"]}}' --format=json --allow-root

# turning off page attributes for everyone
wp user list --field=ID --format=ids --allow-root | xargs -0 --d ' ' -I % wp user meta update % metaboxhidden_page '["pageparentdiv", "revisionsdiv", "postcustom", "slugdiv", "authordiv"]' --format=json --allow-root

