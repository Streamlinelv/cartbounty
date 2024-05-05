<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0
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
		global $wpdb;
		
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$old_cart_table = $wpdb->prefix . "captured_wc_fields";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $cart_table (
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
			other_fields LONGTEXT,
			mail_sent TINYINT NOT NULL DEFAULT 0,
			wp_unsubscribed TINYINT DEFAULT 0,
			wp_steps_completed INT(3) DEFAULT 0,
			wp_complete TINYINT DEFAULT 0,
			type VARCHAR(10) DEFAULT 0,
			saved_via VARCHAR(10),
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		/**
		* Resets table Auto increment index to 1
		*/
		$sql ="ALTER TABLE $cart_table AUTO_INCREMENT = 1";
		dbDelta( $sql );

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );

		/**
		 * Handling cart transfer from the old captured_wc_fields table to new one
		 * Temporary block since version 5.0.1. Will be removed in future versions
		 *
		 * @since    5.0.1
		 */
		function cartbounty_transfer_carts( $wpdb, $cart_table, $old_cart_table ){
			$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		    $misc_settings = $admin->get_settings( 'misc_settings' );

		    if(!cartbounty_old_table_exists( $wpdb, $old_cart_table )){ //If old table no longer exists, exit
		    	return;
		    }

		    if( !$misc_settings['table_transferred'] ){ //If we have not yet transfered carts to the new table
		    	$old_carts = $wpdb->get_results( //Selecting all rows that are not empty
	    			"SELECT * FROM $old_cart_table
	    			WHERE cart_contents != ''
	    			"
		    	);

		    	if($old_carts){ //If we have carts
		    		$imported_cart_count = 0;
		    		$batch_count = 0; //Keeps count of current batch of data to insert
		    		$batches = array(); //Array containing the batches of import since SQL is having troubles importing too many rows at once
					$abandoned_cart_data = array();
					$placeholders = array();

					foreach($old_carts as $key => $cart){ // Looping through abandoned carts to create the arrays
						$batch_count++;

						array_push(
							$abandoned_cart_data,
							sanitize_text_field( $cart->id ),
							sanitize_text_field( $cart->name ),
							sanitize_text_field( $cart->surname ),
							sanitize_email( $cart->email ),
							sanitize_text_field( $cart->phone ),
							sanitize_text_field( $cart->location ),
							sanitize_text_field( $cart->cart_contents ),
							sanitize_text_field( $cart->cart_total ),
							sanitize_text_field( $cart->currency ),
							sanitize_text_field( $cart->time ),
							sanitize_text_field( $cart->session_id ),
							sanitize_text_field( $cart->mail_sent ),
							sanitize_text_field( $cart->other_fields )
						);
						$placeholders[] = "( %d, %s, %s, %s, %s, %s, %s, %0.2f, %s, %s, %s, %d, %s )";

						if($batch_count >= 100){ //If we get a full batch, add it to the array and start preparing a new one
							$batches[] = array(
								'data'			=>	$abandoned_cart_data,
								'placeholders'	=>	$placeholders
							);
							$batch_count = 0;
							$abandoned_cart_data = array();
							$placeholders = array();
						}
					}

					//In case something is left at the end of the loop, we add it to the batches so we do not loose any abandoned carts during the import process
					if($abandoned_cart_data){
						$batches[] = array(
							'data'			=>	$abandoned_cart_data,
							'placeholders'	=>	$placeholders
						);
					}
					
					foreach ($batches as $key => $batch) { //Looping through the batches and importing the carts
						$query = "INSERT INTO ". $cart_table ." (id, name, surname, email, phone, location, cart_contents, cart_total, currency, time, session_id, mail_sent, other_fields) VALUES ";
						$query .= implode(', ', $batch['placeholders']);
						$count = $wpdb->query( $wpdb->prepare("$query ", $batch['data']));
						$imported_cart_count = $imported_cart_count + $count;
					}
				}

				$misc_settings['table_transferred'] = true;
				update_option( 'cartbounty_misc_settings', $misc_settings ); //Making sure the user is not allowed to transfer carts more than once
				$wpdb->query( "DROP TABLE IF EXISTS $old_cart_table" ); //Removing old table from the database
			}
		}

		/**
		 * Determine if we have old CartBounty cart table still present
		 * Temporary block since version 5.0.1. Will be removed in future versions
		 *
		 * @since    5.0.1
		 * @return 	 Boolean
		 */
		function cartbounty_old_table_exists( $wpdb, $old_cart_table ){
			$exists = false;
			$table_exists = $wpdb->query(
				"SHOW TABLES LIKE '{$old_cart_table}'"
			);
			if($table_exists){ //In case table exists
				$exists = true;
			}
			return $exists;
		}

		//Temporary function since version 5.0.1. Will be removed in future releases
		cartbounty_transfer_carts( $wpdb, $cart_table, $old_cart_table );

		/**
		 * Since version 7.0.7.1
		 * This code will be removed in later versions
		 */
		if(get_option('cartbounty_automation_sent_emails')){
			update_option('cartbounty_automation_sends', get_option('cartbounty_automation_sent_emails'));
			delete_option('cartbounty_automation_sent_emails');
		}

		/**
		 * Since version 7.1.6
		 * Transfering time to miliseconds
		 * This code will be removed in later versions
		 */
		function transfer_time_to_miliseconds(){
			$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
			$misc_settings = $admin->get_settings( 'misc_settings' );

			if( CARTBOUNTY_VERSION_NUMBER == $misc_settings['version_number'] || empty( $misc_settings['version_number'] ) ){ //If this is a fresh install or plugin activation
				$misc_settings['converted_minutes_to_miliseconds'] = true;
				update_option( 'cartbounty_misc_settings', $misc_settings ); //setting this variable as we do not require to convert minutes to miliseconds for new installs or activations
				return;
			}

			if( $misc_settings['converted_minutes_to_miliseconds'] ) return;

			$wordpress_steps = get_option( 'cartbounty_automation_steps' );
			$notification_frequency = get_option( 'cartbounty_notification_frequency' );

			//Converting WordPres recovery time intervals
			if( $wordpress_steps ){

				if( is_array( $wordpress_steps ) && !empty( $wordpress_steps ) ){
					foreach( $wordpress_steps as $key => $step ){
						
						if( isset( $wordpress_steps[$key]['interval'] ) ){
							$wordpress_steps[$key]['interval'] = $admin->convert_minutes_to_miliseconds( $step['interval'] );
						}
					}
					update_option( 'cartbounty_automation_steps', $wordpress_steps );
				}
			}

			//Converting Notification time interval
			if( !empty( $notification_frequency ) ){

				if( isset( $notification_frequency['hours'] ) ){
					$notification_frequency['interval'] = $admin->convert_minutes_to_miliseconds( $notification_frequency['hours'] );
					update_option( 'cartbounty_notification_frequency', $notification_frequency );
				}
			}

			$misc_settings['converted_minutes_to_miliseconds'] = true;
			update_option( 'cartbounty_misc_settings', $misc_settings );

			/**
			 * Since version 7.1.6
			 * Due to moving to a different time interval add_custom_wp_cron_intervals() functions
			 */
			if( wp_next_scheduled( 'cartbounty_remove_empty_carts_hook' ) ){
				wp_clear_scheduled_hook( 'cartbounty_remove_empty_carts_hook' );
			}

		}

		transfer_time_to_miliseconds();

		/**
		 * Since version 8.0
		 * This code will be removed in later versions
		 */
		if( get_option( 'cartbounty_review_submitted' ) ){
			update_option( 'cartbounty_submitted_notices', array( 'review' => 1 ) );
			delete_option( 'cartbounty_review_submitted' );
		}
		/* End of this temporary block */

		/**
		 * Since version 8.1
		 * Transfering deprecated multiple sepparate options into a acouple single options.
		 * This code will be removed in later versions
		 */
		function transfer_deprecated_options(){

			if ( get_option( 'cartbounty_notification_email' ) || get_option( 'cartbounty_lift_email' ) || get_option( 'cartbounty_hide_images' ) || get_option( 'cartbounty_exclude_anonymous_carts' ) || get_option( 'cartbounty_exclude_recovered' ) || get_option( 'cartbounty_notification_frequency' ) ){ //If deprecated options detected
				$notification_frequency = get_option( 'cartbounty_notification_frequency' );

				if( isset( $notification_frequency['interval'] ) ){
					$notification_frequency = $notification_frequency['interval'];
				}

				$existing_settings = array(
					'exclude_anonymous_carts' 	=> get_option( 'cartbounty_exclude_anonymous_carts' ),
					'notification_email' 		=> get_option( 'cartbounty_notification_email' ),
					'notification_frequency' 	=> $notification_frequency,
					'exclude_recovered' 		=> get_option( 'cartbounty_exclude_recovered' ),
					'lift_email'				=> get_option( 'cartbounty_lift_email' ),
					'hide_images'				=> get_option( 'cartbounty_hide_images' ),
				);

				update_option( 'cartbounty_main_settings', $existing_settings );
			}

			//Transfering Exit Intent options
			if ( get_option( 'cartbounty_exit_intent_status' ) || get_option( 'cartbounty_exit_intent_type' ) || get_option( 'cartbounty_exit_intent_heading' ) || get_option( 'cartbounty_exit_intent_content' ) || get_option( 'cartbounty_exit_intent_image' ) ){ //If deprecated option detected
				$existing_settings = array(
					'status' 			=> get_option( 'cartbounty_exit_intent_status' ),
					'test_mode' 		=> get_option( 'cartbounty_exit_intent_test_mode' ),
					'style' 			=> get_option( 'cartbounty_exit_intent_type' ),
					'heading' 			=> get_option( 'cartbounty_exit_intent_heading' ),
					'content' 			=> get_option( 'cartbounty_exit_intent_content' ),
					'main_color' 		=> get_option( 'cartbounty_exit_intent_main_color' ),
					'inverse_color' 	=> get_option( 'cartbounty_exit_intent_inverse_color' ),
					'image' 			=> get_option( 'cartbounty_exit_intent_image' ),
				);

				update_option( 'cartbounty_exit_intent_settings', $existing_settings );
			}

			//Transfering reports fields
			if( get_option( 'cartbounty_active_quick_stats' ) || get_option( 'cartbounty_active_charts' ) || get_option( 'cartbounty_chart_type' ) || get_option( 'cartbounty_top_product_count' ) ){ //If deprecated option detected
				$existing_settings = array(
					'quick_stats' 			=> get_option( 'cartbounty_active_quick_stats' ),
					'charts' 				=> get_option( 'cartbounty_active_charts' ),
					'chart_type' 			=> get_option( 'cartbounty_chart_type' ),
					'top_product_count' 	=> 5,
				);

				update_option( 'cartbounty_report_settings', $existing_settings );
			}

			//Transfering WordPress recovery fields
			if( get_option( 'cartbounty_automation_from_name' ) || get_option( 'cartbounty_automation_from_email' ) || get_option( 'cartbounty_automation_reply_email' ) ){ //If deprecated option detected
				$existing_settings = array(
					'from_name' 			=> get_option( 'cartbounty_automation_from_name' ),
					'from_email' 			=> get_option( 'cartbounty_automation_from_email' ),
					'reply_email' 			=> get_option( 'cartbounty_automation_reply_email' ),
				);

				update_option( 'cartbounty_automation_settings', $existing_settings );
			}

			//Transfering notices
			if( get_option( 'cartbounty_cron_warning' ) ){ //If deprecated option detected
				$existing_settings = array(
					'cron_warning' 		=> get_option( 'cartbounty_cron_warning' ),
				);

				update_option( 'cartbounty_submitted_warnings', $existing_settings );
			}

			//Transfering misc settings
			if( get_option( 'cartbounty_version_number' ) ){ //If deprecated option detected
				$existing_settings = array(
					'version_number' 					=> get_option( 'cartbounty_version_number' ),
					'recoverable_carts' 				=> get_option( 'cartbounty_recoverable_cart_count' ),
					'anonymous_carts' 					=> get_option( 'cartbounty_anonymous_cart_count' ),
					'recovered_carts' 					=> get_option( 'cartbounty_recovered_cart_count' ),
					'time_bubble_displayed' 			=> get_option( 'cartbounty_last_time_bubble_displayed' ),
					'time_bubble_steps_displayed' 		=> get_option( 'cartbounty_last_time_bubble_steps_displayed' ),
					'times_review_declined' 			=> get_option( 'cartbounty_times_review_declined' ),
					'email_table_exists' 				=> get_option( 'cartbounty_email_table_exists' ),
					'table_transferred' 				=> get_option( 'cartbounty_transferred_table' ),
					'converted_minutes_to_miliseconds' 	=> get_option( 'cartbounty_converted_minutes_to_miliseconds' ),
				);

				update_option( 'cartbounty_misc_settings', $existing_settings );
			}

			//Deleting options that will no longer be required
			delete_option( 'cartbounty_exclude_anonymous_carts' );
			delete_option( 'cartbounty_notification_email' );
			delete_option( 'cartbounty_notification_frequency' );
			delete_option( 'cartbounty_exclude_recovered' );
			delete_option( 'cartbounty_lift_email' );
			delete_option( 'cartbounty_hide_images' );
			delete_option( 'cartbounty_exit_intent_status' );
			delete_option( 'cartbounty_exit_intent_test_mode' );
			delete_option( 'cartbounty_exit_intent_type' );
			delete_option( 'cartbounty_exit_intent_heading' );
			delete_option( 'cartbounty_exit_intent_content' );
			delete_option( 'cartbounty_exit_intent_main_color' );
			delete_option( 'cartbounty_exit_intent_inverse_color' );
			delete_option( 'cartbounty_exit_intent_image' );
			delete_option( 'cartbounty_active_quick_stats' );
			delete_option( 'cartbounty_active_charts' );
			delete_option( 'cartbounty_chart_type' );
			delete_option( 'cartbounty_top_product_count' );
			delete_option( 'cartbounty_automation_from_name' );
			delete_option( 'cartbounty_automation_from_email' );
			delete_option( 'cartbounty_automation_reply_email' );
			delete_option( 'cartbounty_cron_warning' );
			delete_option( 'cartbounty_version_number' );
			delete_option( 'cartbounty_recoverable_cart_count' );
			delete_option( 'cartbounty_anonymous_cart_count' );
			delete_option( 'cartbounty_recovered_cart_count' );
			delete_option( 'cartbounty_last_time_bubble_displayed' );
			delete_option( 'cartbounty_last_time_bubble_steps_displayed' );
			delete_option( 'cartbounty_times_review_declined' );
			delete_option( 'cartbounty_email_table_exists' );
			delete_option( 'cartbounty_transferred_table' );
			delete_option( 'cartbounty_converted_minutes_to_miliseconds' );
		}

		transfer_deprecated_options();
		/* End of this temporary block */
	}
}