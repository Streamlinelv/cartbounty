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
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$hooks = array(
			'cartbounty_sync_hook',
			'cartbounty_notification_sendout_hook',
			'cartbounty_remove_empty_carts_hook'
		);

		foreach( $hooks as $key => $hook ){

			if( $admin->action_scheduler_enabled() ){ //If WooCommerce Action scheduler library exists
				as_unschedule_action( $hook, array(), CARTBOUNTY ); //Deactivating scheduled Action Scheduler actions

			}else{ //Fallback to WP Cron and clear these events
				wp_clear_scheduled_hook( $hook ); //Deactivating scheduled WP Cron actions
			}
		}
	}
}