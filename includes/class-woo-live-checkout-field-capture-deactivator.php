<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    WooCommerce Live Checkout Field Capture
 * @subpackage WooCommerce Live Checkout Field Capture/includes
 * @author     Streamline.lv
 */
class Woo_Live_Checkout_Field_Capture_Deactivator{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0
	 */
	public static function deactivate() {

		//Deactivating Wordpress cron job functions and stop sending out e-mails
		wp_clear_scheduled_hook( 'wclcfc_remove_empty_carts_hook' );

	}
}