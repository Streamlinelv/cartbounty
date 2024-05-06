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
$email_table = $wpdb->prefix . "cartbounty_emails";

$wpdb->query( "DROP TABLE IF EXISTS $cart_table" );
$wpdb->query( "DROP TABLE IF EXISTS $email_table" );

//Removing Custom options created with the plugin
delete_option( 'cartbounty_main_settings' );
delete_option( 'cartbounty_misc_settings' );
delete_option( 'cartbounty_exit_intent_settings' );
delete_option( 'cartbounty_automation_settings' );
delete_option( 'cartbounty_automation_steps' );
delete_option( 'cartbounty_automation_sends' );
delete_option( 'cartbounty_submitted_notices' );
delete_option( 'cartbounty_submitted_warnings' );
delete_option( 'cartbounty_report_settings' );

delete_metadata( 'user', 0, 'cartbounty_carts_per_page', '', true );
delete_metadata( 'user', 0, 'cartbounty_unsupported_plugin_notice', '', true );