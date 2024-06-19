#!/bin/bash

# set widget order for all users
wp option update fix_alt_text '{
  "option": "fix_alt_text",
  "db_version": "1.7.0",
  "db_version_history": [],
  "access_tool_roles": [
    "editor"
  ],
  "access_settings_roles": [
    "editor"
  ],
  "debug": false,
  "blocks": [
    "core/image",
    "core/media-text",
    "core/gallery"
  ],
  "others": [
    "Media Library"
  ],
  "scan_post_types": [
    "attachment",
    "page",
    "wp_block",
    "post",
    "wp_template_part",
    "wp_template"
  ],
  "scan_taxonomies": [
    "category",
    "post_tag",
    "wp_template_part_area",
    "wp_theme"
  ],
  "scan_users": true,
  "site_id": 1
}' --format=json --allow-root
