<?php

/*
Plugin Name: Heyendaal Flickr (HYFlickr)
Version: 1.0
Author: Sébastiaan Versteeg
*/

require_once('includes/phpFlickr.php');
require_once('config.php');
require_once('backend.php');
require_once('frontend.php');

new HYFlickrFrontend();
if (is_admin()) {
    new HYFlickrBackend();
}

$hyFlickrDir = plugin_dir_path( __FILE__ );

function hyflickrInstall()
{
    global $wpdb;
    global $wp_rewrite;

    $table_name = $wpdb->prefix . 'hyflickr_cache';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
		`request` varchar(128) NOT NULL,
		`response` mediumtext NOT NULL,
		`expiration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY `request` (`request`)
	) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $wp_rewrite->flush_rules();
}

register_activation_hook(__FILE__, 'hyflickrInstall');