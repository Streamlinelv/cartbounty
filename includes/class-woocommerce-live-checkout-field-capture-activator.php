<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
 
class Woocommerce_Live_Checkout_Field_Capture_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		
		/**
		* Creating table
		*/
		global $wpdb, $table_name;
		
		$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(60),
			surname VARCHAR(60),
			email VARCHAR(100),
			phone VARCHAR(20),
			cart_contents LONGTEXT,
			cart_total DECIMAL(10,2),
			currency VARCHAR(10),
			time DATETIME DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		/**
		* Resets table Auto increment index to 1
		*/
		$sql ="ALTER TABLE $table_name AUTO_INCREMENT = 1";
		dbDelta( $sql );
		
	}
}