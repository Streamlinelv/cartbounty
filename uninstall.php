<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' )){
	exit;
}

//Deletes database table and all it's data on uninstall
global $wpdb;
$table_name = $wpdb->prefix . "captured_wc_fields";

$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

//Removing Custom options created with the plugin
delete_option( 'cartbounty_notification_email' );
delete_option( 'cartbounty_notification_frequency' );
delete_option( 'cartbounty_last_time_bubble_displayed' );
delete_option( 'cartbounty_review_submitted' );
delete_option( 'cartbounty_version_number' );
delete_option( 'cartbounty_captured_abandoned_cart_count' );
delete_option( 'cartbounty_times_review_declined' );
delete_option( 'cartbounty_exit_intent_status' );
delete_option( 'cartbounty_exit_intent_test_mode' );
delete_option( 'cartbounty_exit_intent_type' );
delete_option( 'cartbounty_exit_intent_main_color' );
delete_option( 'cartbounty_exit_intent_inverse_color' );