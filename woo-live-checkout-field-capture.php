<?php

/**
 * Plugin Name: WooCommerce Live Checkout Field Capture
 * Plugin URI: https://wordpress.org/plugins/woo-save-abandoned-carts/
 * Description: Plugin instantly saves WooCommerce checkout field data before they are submitted.
 * Version: 3.0
 * Author: Streamline.lv
 * Author URI: http://www.majas-lapu-izstrade.lv/en
 * Developer: Streamline.lv
 * Developer URI: http://www.majas-lapu-izstrade.lv/
 * 
 * WC requires at least: 2.2
 * WC tested up to: 3.5
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
if (!defined('WCLCFC_VERSION_NUMBER')) define( 'WCLCFC_VERSION_NUMBER', '3.0');
if (!defined('WCLCFC_BASENAME')) define( 'WCLCFC_BASENAME', plugin_basename( __FILE__ ));
if (!defined('WCLCFC_PLUGIN_NAME')) define( 'WCLCFC_PLUGIN_NAME', 'WooCommerce Live Checkout Field Capture');
if (!defined('WCLCFC_PLUGIN_NAME_SLUG')) define( 'WCLCFC_PLUGIN_NAME_SLUG', 'woo-live-checkout-field-capture');
if (!defined('WCLCFC_TABLE_NAME')) define( 'WCLCFC_TABLE_NAME', 'captured_wc_fields');
if (!defined('WCLCFC_LICENSE_SERVER_URL')) define('WCLCFC_LICENSE_SERVER_URL', 'https://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro/');
if (!defined('WCLCFC_REVIEW_LINK')) define('WCLCFC_REVIEW_LINK', 'https://wordpress.org/support/plugin/woo-save-abandoned-carts/reviews/#new-post');
if (!defined('WCLCFC_TEXT_DOMAIN')) define( 'WCLCFC_TEXT_DOMAIN', 'wclcfc');
if (!defined('WCLCFC_STILL_SHOPPING')) define( 'WCLCFC_STILL_SHOPPING', 60); //In minutes. Defines the time period after which an e-mail notice will be sent to admin and the cart is presumed abandoned
if (!defined('WCLCFC_NEW_NOTICE')) define( 'WCLCFC_NEW_NOTICE', 240); //Defining time in minutes how long New status is shown in the table

//Registering custom options
register_setting( 'wclcfc-settings-review', 'wclcfc_review_submitted' );
register_setting( 'wclcfc-settings-declined', 'wclcfc_times_review_declined' );
register_setting( 'wclcfc-settings-time', 'wclcfc_last_time_bubble_displayed' );
register_setting( 'wclcfc-settings-exit-intent', 'wclcfc_exit_intent_status' );
register_setting( 'wclcfc-settings-exit-intent', 'wclcfc_exit_intent_test_mode' );
register_setting( 'wclcfc-settings-exit-intent', 'wclcfc_exit_intent_type' );
register_setting( 'wclcfc-settings-exit-intent', 'wclcfc_exit_intent_main_color' );
register_setting( 'wclcfc-settings-exit-intent', 'wclcfc_exit_intent_inverse_color' );

/**
 * The code that runs during plugin activation.
 *
 * @since    1.0
 */
function activate_woo_save_abandoned_carts(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-live-checkout-field-capture-activator.php';
	Woo_Live_Checkout_Field_Capture_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since    1.0
 */
function deactivate_woo_save_abandoned_carts(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-live-checkout-field-capture-deactivator.php';
	Woo_Live_Checkout_Field_Capture_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woo_save_abandoned_carts' );
register_deactivation_hook( __FILE__, 'deactivate_woo_save_abandoned_carts' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @since    1.0
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woo-live-checkout-field-capture.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0
 */
function run_woo_save_abandoned_carts(){
	$plugin = new Woo_Live_Checkout_Field_Capture();
	$plugin->run();
}
run_woo_save_abandoned_carts();
