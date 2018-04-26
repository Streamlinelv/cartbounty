<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

//Deletes database table and all it's data on uninstall
global $wpdb;
$table_name = $wpdb->prefix . "captured_wc_fields";

$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

//Removing Custom options created with the plugin
delete_option( 'wclcfc_last_time_bubble_displayed' );
delete_option( 'wclcfc_plugin_activation_time' );
delete_option( 'wclcfc_review_submitted' );
delete_option( 'wclcfc_version_number' );