<?php
/**
 * Plugin Name: Disable Font Awesome Block Editor Support
 * Description: Turns off the Font Awesome plugin's block editor support on every tenant.
 *              With it on, every request for a visitor without manage_options makes a
 *              HEAD self-request to wp-content/uploads/font-awesome/v<ver>/css/svg-with-js.css,
 *              and every page links that stylesheet. The plugin can never save the file
 *              for those visitors, so both 404 on every page view. No prod site uses the
 *              Font Awesome icon block (0 posts with wp:font-awesome/icon across 105 sites,
 *              2026-10-29). The [icon] shortcode and webfont icons don't depend on block
 *              editor support and keep working. See wp-proudcity#2989.
 * Author:      ProudCity
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read once in FontAwesome's constructor, which runs when the plugin loads,
// after mu-plugins (includes/class-fontawesome.php, font-awesome 5.2.1).
add_filter( 'font_awesome_disable_block_editor_support', '__return_true' );
