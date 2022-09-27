<?php

require_once( 'wp-load.php');
require_once( 'wp-content/plugins/events-manager/classes/em-admin-notices.php');

		global $EM_Notices; /* @var EM_Notices $EM_Notices */
		// deal with v6 migration
			update_option( 'sfn_test_v62', 'after nonce');
			$data = get_option('dbem_data');

					// migrate all options over
					$data['v6'] = 'undo'; // this is for legacy widgets, v7 will remove it
					// disable preview in case
					remove_filter('em_formats_filter', 'EM_v6_Migration::preview_formats', 1);
					// copy over new formats overriding old ones, but putting them in an 'undo' var
					$undo = array();
					foreach( EM_Formats::get_default_formats( true ) as $format ){
						$format_content = call_user_func('EM_Formats::'.$format, '');
						$undo[$format] = get_option($format);
						update_option($format, $format_content );
					}
					update_option('dbem_v6_undo', $undo, false); // no auto-loading this
					// add overriding styling
					update_option('dbem_advanced_formatting', 0);
					update_option('dbem_css_theme_font_weight', 0);
					update_option('dbem_css_theme_font_family', 0);
					update_option('dbem_css_theme_font_size', 0);
					update_option('dbem_css_theme_line_height', 0);
					// remove notices and add confirmation
					EM_Admin_Notices::remove('v6-update', is_multisite());
					EM_Admin_Notices::remove('v6-update2', is_multisite());
					$EM_Notices->add_confirm(esc_html__('You nave successfully migrated to the default v6 formatting options, enjoy! We have an undo option, just in case...', 'events-manager'), true);

		update_option('dbem_data', $data);



/* Example URL
https://www.townoffairfax.org/wp-admin/admin.php?page=events-manager-options&action=v6_migrate&nonce=71726a3296&do=migrate
*/

/**
 * wp eval --user=1 'site_url() ."/wp-admin/admin.php?page=events-manager-options&action=v6_migrate&nonce=".wp_create_nonce( 'v6-migrate')."&do=migrate';
 */