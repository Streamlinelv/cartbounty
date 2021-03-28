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
	 * Deactivation function
	 *
	 * @since    1.0
	 */
	public static function deactivate() {
		//Deactivating Wordpress cron job functions and stop sending out emails
		wp_clear_scheduled_hook( 'cartbounty_notification_sendout_hook' );
		wp_clear_scheduled_hook( 'cartbounty_sync_hook' );
		wp_clear_scheduled_hook( 'cartbounty_remove_empty_carts_hook' );
		delete_transient( 'cartbounty_recoverable_cart_count' );
	}
}