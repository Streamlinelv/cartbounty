<?php

/**
 * Plugin Name: Woocommerce Live Checkout Field Capture
 * Plugin URI: 
 * Description: Plugin instantly saves Woocommerce checkout field data before they are submitted.
 * Version: 1.0
 * Author: Streamline.lv
 * Author URI: http://www.majas-lapu-izstrade.lv/en
 * Developer: Streamline.lv
 * Developer URI: http://www.majas-lapu-izstrade.lv/
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define("WCLCFC_TABLE_NAME", "captured_wc_fields");

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