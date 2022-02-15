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
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		/**
		* Resets table Auto increment index to 1
		*/
		$sql ="ALTER TABLE $cart_table AUTO_INCREMENT = 1";
		dbDelta( $sql );

		/**
		 * Handling cart transfer from the old captured_wc_fields table to new one
		 * Temporary block since version 5.0.1. Will be removed in future versions
		 *
		 * @since    5.0.1
		 */
		function cartbounty_transfer_carts( $wpdb, $cart_table, $old_cart_table ){
		    if(!cartbounty_old_table_exists( $wpdb, $old_cart_table )){ //If old table no longer exists, exit
		    	return;
		    }
		    if(!get_option('cartbounty_transferred_table')){ //If we have not yet transfered carts to the new table
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

		    	update_option('cartbounty_transferred_table', true); //Making sure the user is not allowed to transfer carts more than once
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

		//Registering email notification frequency in case it has not been set before
		add_option('cartbounty_notification_frequency', array('hours' => 60));

		//Setting default Exit Intent type if it has not been previously set
		add_option('cartbounty_exit_intent_type', 1);

		//Since version 5.0 this option updated
		if (get_option( 'cartbounty_captured_abandoned_cart_count' )){
			update_option( 'cartbounty_recoverable_cart_count', get_option( 'cartbounty_captured_abandoned_cart_count' ));
			delete_option( 'cartbounty_captured_abandoned_cart_count' );
		}

		/**
		 * Since version 7.0.7.1
		 * This code will be removed in later versions
		 */
		if(get_option('cartbounty_automation_sent_emails')){
			update_option('cartbounty_automation_sends', get_option('cartbounty_automation_sent_emails'));
			delete_option('cartbounty_automation_sent_emails');
		}
	}
}