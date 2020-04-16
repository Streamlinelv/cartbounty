<?php

/**
 * Plugin Name: CartBounty - Save and recover abandoned carts for WooCommerce
 * Plugin URI: https://www.cartbounty.com
 * Description: Save abandoned carts by instantly capturing WooCommerce checkout form before submission.
 * Version: 4.4.1
 * Author: Streamline.lv
 * Author URI: http://www.majas-lapu-izstrade.lv/en
 * Developer: Streamline.lv
 * Developer URI: http://www.majas-lapu-izstrade.lv/en
 * 
 * WC requires at least: 2.2
 * WC tested up to: 3.8
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Retrieve email notification frequency from database
$user_settings_notification_frequency = get_option('cartbounty_notification_frequency'); //Retrieving notification frequency
if($user_settings_notification_frequency == '' || $user_settings_notification_frequency == NULL){
	$frequency = 60; //Default 60 minutes
}else{
	$frequency = intval($user_settings_notification_frequency['hours']);
}

//Defining constants
if (!defined('CARTBOUNTY_VERSION_NUMBER')) define( 'CARTBOUNTY_VERSION_NUMBER', '4.4.1');
if (!defined('CARTBOUNTY_PLUGIN_NAME')) define( 'CARTBOUNTY_PLUGIN_NAME', 'CartBounty - Save and recover abandoned carts for WooCommerce');
if (!defined('CARTBOUNTY')) define( 'CARTBOUNTY', 'cartbounty');
if (!defined('CARTBOUNTY_PLUGIN_NAME_SLUG')) define( 'CARTBOUNTY_PLUGIN_NAME_SLUG', 'cartbounty');
if (!defined('CARTBOUNTY_BASENAME')) define( 'CARTBOUNTY_BASENAME', plugin_basename( __FILE__ ));
if (!defined('CARTBOUNTY_TABLE_NAME')) define( 'CARTBOUNTY_TABLE_NAME', 'captured_wc_fields');
if (!defined('CARTBOUNTY_LICENSE_SERVER_URL')) define('CARTBOUNTY_LICENSE_SERVER_URL', 'https://www.cartbounty.com');
if (!defined('CARTBOUNTY_REVIEW_LINK')) define('CARTBOUNTY_REVIEW_LINK', 'https://wordpress.org/support/plugin/woo-save-abandoned-carts/reviews/#new-post');
if (!defined('CARTBOUNTY_TEXT_DOMAIN')) define( 'CARTBOUNTY_TEXT_DOMAIN', 'woo-save-abandoned-carts');
if (!defined('CARTBOUNTY_ABREVIATION')) define( 'CARTBOUNTY_ABREVIATION', 'CartBounty');
if (!defined('CARTBOUNTY_EMAIL_INTERVAL')) define( 'CARTBOUNTY_EMAIL_INTERVAL', $frequency); //In minutes. Defines the interval at which e-mailing function is fired
if (!defined('CARTBOUNTY_STILL_SHOPPING')) define( 'CARTBOUNTY_STILL_SHOPPING', 60); //In minutes. Defines the time period after which an e-mail notice will be sent to admin and the cart is presumed abandoned
if (!defined('CARTBOUNTY_NEW_NOTICE')) define( 'CARTBOUNTY_NEW_NOTICE', 240); //Defining time in minutes how long New status is shown in the table

//Registering custom options
register_setting( 'cartbounty-settings', 'cartbounty_notification_email' );
register_setting( 'cartbounty-settings', 'cartbounty_notification_frequency' );
register_setting( 'cartbounty-settings-review', 'cartbounty_review_submitted' );
register_setting( 'cartbounty-settings-declined', 'cartbounty_times_review_declined' );
register_setting( 'cartbounty-settings-time', 'cartbounty_last_time_bubble_displayed' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_status' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_test_mode' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_type' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_main_color' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_inverse_color' );

/**
 * The code that runs during plugin activation.
 *
 * @since    1.0
 */
function activate_cartbounty(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cartbounty-activator.php';
	CartBounty_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since    1.0
 */
function deactivate_cartbounty(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cartbounty-deactivator.php';
	CartBounty_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cartbounty' );
register_deactivation_hook( __FILE__, 'deactivate_cartbounty' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @since    1.0
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cartbounty.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0
 */
function run_cartbounty(){
	$plugin = new CartBounty();
	$plugin->run();
}
run_cartbounty();
