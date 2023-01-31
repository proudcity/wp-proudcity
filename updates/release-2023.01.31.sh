#!/bin/bash

# turning off old post types order plugin
wp plugin deactivate intuitive-custom-post-order --allow-root

# turning on new post type order plugin
wp plugin activate post-types-order --allow-root

# setting the default options for the plugin
wp option update cpto_options '{
	"show_reorder_interfaces": {
		"post": "hide",
		"attachment": "hide",
		"wp_block": "hide",
		"wp_navigation": "hide",
		"job_listing": "show",
		"location": "hide",
		"event": "hide",
		"event-recurring": "hide",
		"redirect_rule": "hide",
		"staff-member": "show",
		"service_center_tab": "hide",
		"agency": "show",
		"document": "show",
		"issue": "show",
		"proud_location": "show",
		"meeting": "hide",
		"payment": "show",
		"question": "show"
	},
	"autosort": 1,
	"adminsort": 1,
	"use_query_ASC_DESC": "",
	"archive_drag_drop": 1,
	"capability": "publish_pages",
	"navigation_sort_apply": 1
}' --format=json --allow-root

# activating wp rocket
wp plugin activate wp-rocket --allow-root

# importing wp rocket
wp rocket import --file=/app/updates/wp-rocket-2023-01-31.json --allow-root

