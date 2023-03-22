#!/bin/bash

# updating google site kit so that editors can see content
wp option update googlesitekit_dashboard_sharing '{"pagespeed-insights":{"sharedRoles":[],"management":"all_admins"},"analytics":{"sharedRoles":["editor"],"management":"owner"},"search-console":{"sharedRoles":["editor"]}}' --format=json --allow-root

