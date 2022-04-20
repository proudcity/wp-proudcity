# Deactivate the old xml sitemaps plugin
wp plugin deactivate google-sitemap-generator --allow-root

# turn on the xml sitemaps in Yoast
wp option update wpseo '{

  "tracking": false,

  "license_server_version": false,

  "ms_defaults_set": false,

  "ignore_search_engines_discouraged_notice": false,

  "indexing_first_time": false,

  "indexing_started": false,

  "indexing_reason": "permalink_settings_changed",

  "indexables_indexing_completed": false,

  "version": "18.3",

  "previous_version": "",

  "disableadvanced_meta": true,

  "enable_headless_rest_endpoints": true,

  "ryte_indexability": true,

  "baiduverify": "",

  "googleverify": "",

  "msverify": "",

  "yandexverify": "",

  "site_type": "",

  "has_multiple_authors": false,

  "environment_type": "",

  "content_analysis_active": true,

  "keyword_analysis_active": true,

  "enable_admin_bar_menu": true,

  "enable_cornerstone_content": true,

  "enable_xml_sitemap": true,

  "enable_text_link_counter": true,

  "show_onboarding_notice": false,

  "first_activated_on": false,

  "myyoast-oauth": false,

  "semrush_integration_active": true,

  "semrush_tokens": [],

  "semrush_country_code": "us",

  "permalink_structure": "/%postname%/",

  "home_url": "https://proudbugs.local",

  "dynamic_permalinks": false,

  "category_base_url": "",

  "tag_base_url": "",

  "custom_taxonomy_slugs": {

    "job_listing_type": "job_listing_type",

    "event-categories": "events/categories",

    "staff-member-group": "group",

    "faq-topic": "topics",

    "faq-tags": "faq-tags",

    "document_taxonomy": "document_taxonomy",

    "location-taxonomy": "location-taxonomy",

    "meeting-taxonomy": "meeting-taxonomy"

  },

  "enable_enhanced_slack_sharing": true,

  "zapier_integration_active": false,

  "zapier_subscription": [],

  "zapier_api_key": "",

  "enable_metabox_insights": false,

  "enable_link_suggestions": false,

  "algolia_integration_active": false,

  "import_cursors": [],

  "workouts_data": {

    "configuration": {

      "priority": 10,

      "finishedSteps": []

    }

  },

  "dismiss_configuration_workout_notice": false,

  "importing_completed": [],

  "wincher_integration_active": true,

  "wincher_tokens": [],

  "wincher_automatically_add_keyphrases": false,

  "wincher_website_id": "",

  "first_time_install": false,

  "should_redirect_after_install_free": false,

  "activation_redirect_timestamp_free": false

}' --allow-root

# delete the old xml sitemaps
wp plugin delete google-sitemap-generator --allow-root
