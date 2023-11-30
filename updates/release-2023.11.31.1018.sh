#!/bin/bash

# turning off attachment pages: https://github.com/proudcity/wp-proudcity/issues/2405
wp option update wp_attachment_pages_enabled 0 --autoload='no' --allow-root

# deleting jetpack options we do not use: https://github.com/proudcity/wp-proudcity/issues/2424
wp option delete jetpack_options jetpack_activated jetpack_file_data jetpack_available_modules jetpack_security_report jetpack_log jetpack_private_options jetpack_unique_connection jetpack_protect_key --allow-root
