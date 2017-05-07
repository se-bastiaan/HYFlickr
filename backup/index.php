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
if(is_admin()) {
	new HYFlickrBackend();
}

function hyflickrInstall() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'hyflickr_cache';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
		`request` varchar(128) NOT NULL,
		`response` mediumtext NOT NULL,
		`expiration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY `request` (`request`)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'hyflickrInstall' );