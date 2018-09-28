<?php

# Updates for the 1.53.1 Hotfix release
# Events plugin update, deal with timezones #1310 (wp-proud-core, wp-proud-theme, wp-proud-search-elastic, wp-proud-sr-theme) Apr 15 2018

include '../wordpress/wp-content/plugins/events-manager/classes/em-admin-notices.php';
include '../wordpress/wp-content/plugins/events-manager/admin/settings/updates/timezone-backcompat.php';

print_R(em_admin_update_timezone_backcompat_site());

