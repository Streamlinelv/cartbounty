<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since    1.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' )){
	exit;
}

//Deletes database table and all it's data on uninstall
global $wpdb;
$cart_table = $wpdb->prefix . "cartbounty";

$wpdb->query( "DROP TABLE IF EXISTS $cart_table" );

//Removing Custom options created with the plugin
delete_option( 'cartbounty_notification_email' );
delete_option( 'cartbounty_notification_frequency' );
delete_option( 'cartbounty_exclude_ghost_carts' );
delete_option( 'cartbounty_last_time_bubble_displayed' );
delete_option( 'cartbounty_review_submitted' );
delete_option( 'cartbounty_version_number' );
delete_option( 'cartbounty_recoverable_cart_count' );
delete_option( 'cartbounty_ghost_cart_count' );
delete_option( 'cartbounty_times_review_declined' );
delete_option( 'cartbounty_exit_intent_status' );
delete_option( 'cartbounty_exit_intent_test_mode' );
delete_option( 'cartbounty_exit_intent_type' );
delete_option( 'cartbounty_exit_intent_main_color' );
delete_option( 'cartbounty_exit_intent_inverse_color' );
delete_option( 'cartbounty_exit_intent_image' );
delete_option( 'cartbounty_lift_email' );
delete_option( 'cartbounty_hide_images' );
delete_option( 'cartbounty_carts_per_page' );
delete_option( 'cartbounty_transferred_table' ); //Temporary option since version 5.0.1

delete_metadata( 'user', 0, 'cartbounty_carts_per_page', '', true ); //Removes cartbounty_carts_per_page from wp_usermeta table on plugin uninstall