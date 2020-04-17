<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/includes
 * @author     Streamline.lv
 */
 
class CartBounty_Activator{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.3
	 */
	public static function activate() {
		
		//Deactivating CartBounty Pro plugin
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins('woo-save-abandoned-carts-pro/cartbounty-pro-abandoned-carts.php');
		
		/**
		* Creating table
		*/
		global $wpdb, $table_name;
		
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(60),
			surname VARCHAR(60),
			email VARCHAR(100),
			phone VARCHAR(20),
			location VARCHAR(100),
			cart_contents LONGTEXT,
			cart_total DECIMAL(10,2),
			currency VARCHAR(10),
			time DATETIME DEFAULT '0000-00-00 00:00:00',
			session_id VARCHAR(60),
			mail_sent TINYINT NOT NULL DEFAULT 0,
			other_fields LONGTEXT,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		/**
		* Resets table Auto increment index to 1
		*/
		$sql ="ALTER TABLE $table_name AUTO_INCREMENT = 1";
		dbDelta( $sql );

		//Registering email notification frequency
		if ( get_option('cartbounty_notification_frequency') !== false ) {
			// The option already exists, so we do not do nothing
		}else{
			// The option hasn't been added yet
			add_option('cartbounty_notification_frequency', array('hours' => 60));
		}

		//Setting default Exit Intent type if it has not been previously set
		add_option('cartbounty_exit_intent_type', 1);

		if (! wp_next_scheduled ( 'cartbounty_remove_empty_carts_hook' )) {
			wp_schedule_event(time(), 'cartbounty_remove_empty_carts_interval', 'cartbounty_remove_empty_carts_hook');
		}

		//Since version 4.0 due to plugin naming changes - making sure that during the update process old options are transfered to new ones and the old ones are removed from database
		if (get_option( 'wclcfc_last_time_bubble_displayed' )){
			update_option( 'cartbounty_last_time_bubble_displayed', get_option( 'wclcfc_last_time_bubble_displayed' ));
		}
		delete_option( 'wclcfc_last_time_bubble_displayed' );
		if (get_option( 'wclcfc_review_submitted' )){
			update_option( 'cartbounty_review_submitted', get_option( 'wclcfc_review_submitted' ));
		}
		delete_option( 'wclcfc_review_submitted' );
		if (get_option( 'wclcfc_version_number' )){
			update_option( 'cartbounty_version_number', get_option( 'wclcfc_version_number' ));
		}
		delete_option( 'wclcfc_version_number' );
		if (get_option( 'wclcfc_captured_abandoned_cart_count' )){
			update_option( 'cartbounty_captured_abandoned_cart_count', get_option( 'wclcfc_captured_abandoned_cart_count' ));
		}
		delete_option( 'wclcfc_captured_abandoned_cart_count' );
		if (get_option( 'wclcfc_times_review_declined' )){
			update_option( 'cartbounty_times_review_declined', get_option( 'wclcfc_times_review_declined' ));
		}
		delete_option( 'wclcfc_times_review_declined' );
		if (get_option( 'wclcfc_exit_intent_status' )){
			update_option( 'cartbounty_exit_intent_status', get_option( 'wclcfc_exit_intent_status' ));
		}
		delete_option( 'wclcfc_exit_intent_status' );
		if (get_option( 'wclcfc_exit_intent_test_mode' )){
			update_option( 'cartbounty_exit_intent_test_mode', get_option( 'wclcfc_exit_intent_test_mode' ));
		}
		delete_option( 'wclcfc_exit_intent_test_mode' );
		if (get_option( 'wclcfc_exit_intent_type' )){
			update_option( 'cartbounty_exit_intent_type', get_option( 'wclcfc_exit_intent_type' ));
		}
		delete_option( 'wclcfc_exit_intent_type' );
		if (get_option( 'wclcfc_exit_intent_main_color' )){
			update_option( 'cartbounty_exit_intent_main_color', get_option( 'wclcfc_exit_intent_main_color' ));
		}
		delete_option( 'wclcfc_exit_intent_main_color' );
		if (get_option( 'wclcfc_exit_intent_inverse_color' )){
			update_option( 'cartbounty_exit_intent_inverse_color', get_option( 'wclcfc_exit_intent_inverse_color' ));
		}
		delete_option( 'wclcfc_exit_intent_inverse_color' );

		/**
		 * Starting WordPress cron function in order to send out e-mails on a set interval
		 *
		 * @since    4.3
		 */
		$user_settings_notification_frequency = get_option('cartbounty_notification_frequency');
		if(intval($user_settings_notification_frequency['hours']) == 0){ //If Email notifications have been disabled, we disable cron job
			wp_clear_scheduled_hook( 'cartbounty_notification_sendout_hook' );
		}else{
			if (! wp_next_scheduled ( 'cartbounty_notification_sendout_hook' )) {
				wp_schedule_event(time(), 'cartbounty_notification_sendout_interval', 'cartbounty_notification_sendout_hook');
			}
		}
	}
}