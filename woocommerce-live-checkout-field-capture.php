<?php

/**
 * Plugin Name: Woocommerce Live Checkout Field Capture
 * Plugin URI: 
 * Description: Plugin instantly saves Woocommerce checkout field data before they are submitted.
 * Version: 1.1
 * Author: Streamline.lv
 * Author URI: http://www.majas-lapu-izstrade.lv/en
 * Developer: Streamline.lv
 * Developer URI: http://www.majas-lapu-izstrade.lv/
 * 
 * WC requires at least: 2.2
 * WC tested up to: 3.3
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Defining constants
if (!defined('WCLCFC_TABLE_NAME')) define( 'WCLCFC_TABLE_NAME', 'captured_wc_fields');
if (!defined('WCLCFC_LICENSE_SERVER_URL')) define('WCLCFC_LICENSE_SERVER_URL', 'https://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro/');


/**
 * The code that runs during plugin activation.
 */
function activate_woocommerce_live_checkout_field_capture() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-live-checkout-field-capture-activator.php';
	Woocommerce_Live_Checkout_Field_Capture_Activator::activate();
}


/**
 * The code that runs during plugin deactivation.
 */
function deactivate_woocommerce_live_checkout_field_capture() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-live-checkout-field-capture-deactivator.php';
	Woocommerce_Live_Checkout_Field_Capture_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_live_checkout_field_capture' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_live_checkout_field_capture' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-live-checkout-field-capture.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_live_checkout_field_capture() {

	$plugin = new Woocommerce_Live_Checkout_Field_Capture();
	$plugin->run();

}
run_woocommerce_live_checkout_field_capture();


/**
 * Adds custom action link on Plugin page under plugin name
 *
 * @since    1.0.2
 */
function woocommerce_live_checkout_field_capture_add_plugin_action_links( $actions, $plugin_file ) {
	if ( ! is_array( $actions ) ) {
		return $actions;
	}
	
	$action_links = array();
	$action_links['wlcfc_get_pro'] = array(
		'label' => 'Get Pro',
		'url'   => WCLCFC_LICENSE_SERVER_URL
	);

	return woocommerce_live_checkout_field_capture_add_display_plugin_action_links( $actions, $plugin_file, $action_links, 'before' );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_live_checkout_field_capture_add_plugin_action_links', 10, 2 );


/**
 * Function that merges the links on Plugin page under plugin name
 *
 * @since    1.0.2
 * @return array
 */
function woocommerce_live_checkout_field_capture_add_display_plugin_action_links( $actions, $plugin_file, $action_links = array(), $position = 'after' ) {
	static $plugin;
	if ( ! isset( $plugin ) ) {
		$plugin = plugin_basename( __FILE__ );
	}
	if ( $plugin === $plugin_file && ! empty( $action_links ) ) {
		foreach ( $action_links as $key => $value ) {
			$link = array( $key => '<a href="' . $value['url'] . '">' . $value['label'] . '</a>' );
			if ( 'after' === $position ) {
				$actions = array_merge( $actions, $link );
			} else {
				$actions = array_merge( $link, $actions );
			}
		}
	}
	return $actions;
}