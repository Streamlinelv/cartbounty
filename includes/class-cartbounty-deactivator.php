<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/includes
 * @author     Streamline.lv
 */
class CartBounty_Deactivator{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0
	 */
	public static function deactivate() {

		//Deactivating Wordpress cron job functions and stop sending out e-mails
		wp_clear_scheduled_hook( 'cartbounty_notification_sendout_hook' );
		wp_clear_scheduled_hook( 'cartbounty_remove_empty_carts_hook' );

	}
}