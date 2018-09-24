<?php

/**
 * Plugin Name: WooCommerce Live Checkout Field Capture
 * Plugin URI: 
 * Description: Plugin instantly saves WooCommerce checkout field data before they are submitted.
 * Version: 2.0.5
 * Author: Streamline.lv
 * Author URI: http://www.majas-lapu-izstrade.lv/en
 * Developer: Streamline.lv
 * Developer URI: http://www.majas-lapu-izstrade.lv/
 * 
 * WC requires at least: 2.2
 * WC tested up to: 3.4
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
if (!defined('WCLCFC_VERSION_NUMBER')) define( 'WCLCFC_VERSION_NUMBER', '2.0.5');
if (!defined('WCLCFC_BASENAME')) define( 'WCLCFC_BASENAME', plugin_basename( __FILE__ ));
if (!defined('WCLCFC_PLUGIN_NAME_SLUG')) define( 'WCLCFC_PLUGIN_NAME_SLUG', 'woocommerce-live-checkout-field-capture');
if (!defined('WCLCFC_TABLE_NAME')) define( 'WCLCFC_TABLE_NAME', 'captured_wc_fields');
if (!defined('WCLCFC_LICENSE_SERVER_URL')) define('WCLCFC_LICENSE_SERVER_URL', 'https://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro/');
if (!defined('WCLCFC_REVIEW_LINK')) define('WCLCFC_REVIEW_LINK', 'https://wordpress.org/support/plugin/woo-save-abandoned-carts/reviews/#new-post');

//Registering custom options
register_setting( 'wclcfc-settings-group', 'wclcfc_review_submitted' );
register_setting( 'wclcfc-settings-group', 'wclcfc_last_time_bubble_displayed' );

/**
 * The code that runs during plugin activation.
 */
function activate_woocommerce_live_checkout_field_capture(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-live-checkout-field-capture-activator.php';
	WooCommerce_Live_Checkout_Field_Capture_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_woocommerce_live_checkout_field_capture(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-live-checkout-field-capture-deactivator.php';
	WooCommerce_Live_Checkout_Field_Capture_Deactivator::deactivate();
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
 * @since    1.0
 */
function run_woocommerce_live_checkout_field_capture(){
	$plugin = new WooCommerce_Live_Checkout_Field_Capture();
	$plugin->run();
}
run_woocommerce_live_checkout_field_capture();
