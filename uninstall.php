<?php
/**
 * @package Internals
 *
 * Code used when the plugin is removed (not just deactivated but actively deleted through the WordPress Admin).
 */

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

foreach ( array('site_plan', 'legacy_page', 'legacy_post', 'url_map', 'old_url', 'new_url') as $option) {
	delete_option( $option );
}