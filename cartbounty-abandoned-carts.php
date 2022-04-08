<?php

/**
 * Plugin Name: CartBounty - Save and recover abandoned carts for WooCommerce
 * Plugin URI: https://www.cartbounty.com
 * Description: Save abandoned carts by instantly capturing WooCommerce checkout form before submission.
 * Version: 7.1.2.5
 * Text Domain: woo-save-abandoned-carts
 * Author: Streamline.lv
 * Author URI: http://www.majas-lapu-izstrade.lv/en
 * Requires at least: 4.6
 * Requires PHP: 7.0
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
if (!defined('CARTBOUNTY_VERSION_NUMBER')) define( 'CARTBOUNTY_VERSION_NUMBER', '7.1.2.5' );
if (!defined('CARTBOUNTY_PLUGIN_NAME')) define( 'CARTBOUNTY_PLUGIN_NAME', 'CartBounty - Save and recover abandoned carts for WooCommerce' );
if (!defined('CARTBOUNTY')) define( 'CARTBOUNTY', 'cartbounty' );
if (!defined('CARTBOUNTY_PLUGIN_NAME_SLUG')) define( 'CARTBOUNTY_PLUGIN_NAME_SLUG', 'cartbounty' );
if (!defined('CARTBOUNTY_TABLE_NAME_EMAILS')) define( 'CARTBOUNTY_TABLE_NAME_EMAILS', 'cartbounty_emails' );
if (!defined('CARTBOUNTY_BASENAME')) define( 'CARTBOUNTY_BASENAME', plugin_basename( __FILE__ ) );
if (!defined('CARTBOUNTY_TABLE_NAME')) define( 'CARTBOUNTY_TABLE_NAME', 'cartbounty' );
if (!defined('CARTBOUNTY_LICENSE_SERVER_URL')) define('CARTBOUNTY_LICENSE_SERVER_URL', 'https://www.cartbounty.com' );
if (!defined('CARTBOUNTY_REVIEW_LINK')) define('CARTBOUNTY_REVIEW_LINK', 'https://wordpress.org/support/plugin/woo-save-abandoned-carts/reviews/#new-post' );
if (!defined('CARTBOUNTY_TEXT_DOMAIN')) define( 'CARTBOUNTY_TEXT_DOMAIN', 'woo-save-abandoned-carts' ); //No longer used since v6.1.3. Will be removed in future releases
if (!defined('CARTBOUNTY_ABREVIATION')) define( 'CARTBOUNTY_ABREVIATION', 'CartBounty' );
if (!defined('CARTBOUNTY_EMAIL_INTERVAL')) define( 'CARTBOUNTY_EMAIL_INTERVAL', $frequency ); //In minutes. Defines the interval at which email function is fired
if (!defined('CARTBOUNTY_NEW_NOTICE')) define( 'CARTBOUNTY_NEW_NOTICE', 240 ); //Defining time in minutes how long New status is shown in the table
if (!defined('CARTBOUNTY_MAX_SYNC_PERIOD')) define( 'CARTBOUNTY_MAX_SYNC_PERIOD', 30 ); //Defining maximum period in days that is up for recovery. We do not want to remind about very old abandoned carts
if (!defined('CARTBOUNTY_ENCRYPTION_KEY')) define('CARTBOUNTY_ENCRYPTION_KEY', '6c7f0ff3c5b607b0762gbsEwuqSb5c0e5461611791f2ff8d4d45009853795c' ); //Defines encryption key used for creating checkout URL hash part of the link
if (!defined('CARTBOUNTY_ACTIVECAMPAIGN_TRIAL_LINK')) define('CARTBOUNTY_ACTIVECAMPAIGN_TRIAL_LINK', 'https://www.activecampaign.com/?_r=5347LGDC' ); //ActiveCampaign trial link
if (!defined('CARTBOUNTY_GETRESPONSE_TRIAL_LINK')) define('CARTBOUNTY_GETRESPONSE_TRIAL_LINK', 'https://www.getresponse.com/features/marketing-automation?a=vPJGRchyVX&c=integrate_cartbounty' ); //GetResponse free trial link
if (!defined('CARTBOUNTY_MAILCHIMP_LINK')) define('CARTBOUNTY_MAILCHIMP_LINK', 'http://eepurl.com/hHjfrX' ); //MailChimp link
if (!defined('CARTBOUNTY_FAQ_LINK')) define('CARTBOUNTY_FAQ_LINK', 'https://wordpress.org/plugins/woo-save-abandoned-carts/#faq' );
if (!defined('CARTBOUNTY_FEATURE_LINK')) define('CARTBOUNTY_FEATURE_LINK', 'https://www.cartbounty.com/contact/' ); //This is the URL where users can provide new ideas and suggestions
if (!defined('CARTBOUNTY_SUPPORT_LINK')) define('CARTBOUNTY_SUPPORT_LINK', 'https://wordpress.org/support/plugin/woo-save-abandoned-carts/#new-topic-0' ); //This is the URL where users can get support
if (!defined('CARTBOUNTY_BULKGATE_TRIAL_LINK')) define('CARTBOUNTY_BULKGATE_TRIAL_LINK', 'https://portal.bulkgate.com/join/55713/en/solutions/sms-gateway' );

//Registering custom options
register_setting( 'cartbounty-settings', 'cartbounty_notification_email' );
register_setting( 'cartbounty-settings', 'cartbounty_notification_frequency' );
register_setting( 'cartbounty-settings', 'cartbounty_lift_email' );
register_setting( 'cartbounty-settings', 'cartbounty_hide_images' );
register_setting( 'cartbounty-settings', 'cartbounty_exclude_ghost_carts' );
register_setting( 'cartbounty-settings', 'cartbounty_exclude_recovered' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_status' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_test_mode' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_type' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_heading' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_content' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_main_color' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_inverse_color' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_image' );
register_setting( 'cartbounty-wordpress-settings', 'cartbounty_automation_steps' );
register_setting( 'cartbounty-wordpress-settings', 'cartbounty_automation_from_name' );
register_setting( 'cartbounty-wordpress-settings', 'cartbounty_automation_from_email' );
register_setting( 'cartbounty-wordpress-settings', 'cartbounty_automation_reply_email' );

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
 * @since    1.0
 */
function run_cartbounty(){
	$plugin = new CartBounty();
	$plugin->run();
}

run_cartbounty();