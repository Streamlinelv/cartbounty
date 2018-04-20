<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 * @author     Your Name <email@example.com>
 */
 
class Woocommerce_Live_Checkout_Field_Capture_Public {
	
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		global $wpdb;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}
	
	
	
	
	/**
	 * Function in order to add aditional JS file to the checkout field and read data from inputs
	 *
	 * @since    1.0.0
	 */
	function add_additional_scripts_on_checkout() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-live-checkout-field-capture-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'ajaxLink', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	}
	
	
	
	
	/**
	 * Function in order to receive data from Checkout input fields, sanitize it and save to Database
	 *
	 * @since    1.0.0
	 */
	function save_user_data() {
		// first check if data is being sent and that it is the data we want
		if ( isset( $_POST["wlcfc_email"] ) ) {
			
			global $wpdb;
			$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix
			
			//Retrieving cart total value and currency
			$cart_total = WC()->cart->total;
			$cart_currencty = get_woocommerce_currency();
			
			//Retrieving cart products and their quantities
			$products = WC()->cart->get_cart_contents();
			$product_array = array();
			
			foreach($products as $product => $values){
				$item = wc_get_product( $values['data']->get_id());
				$product_title = $item->get_title();
				$product_quantity = $values['quantity'];
				
				//Inserting Product title and Quantity into array
				$product_array[] = $product_title." (".$product_quantity.") ";
			}
			
			//Retrieving current time
			$current_time = current_time( 'mysql', false );
			
			//Starting session in order to check if we have to insert or update database row with the data from input boxes 
			if (!session_id()) session_start();
			
			
			//If we have already inserted the ID of the last inserted row in Session variable and it is larger than 0. 0 - Sometimes caused errors and created multiple rows in DB
			if(isset($_SESSION['last_inserted_id']) && $_SESSION['last_inserted_id'] > 0 ) {

				if(isset($_POST['wlcfc_name'])){
					$name = $_POST['wlcfc_name'];
				}else{
					$name = '';
				}

				if(isset($_POST['wlcfc_surname'])){
					$surname = $_POST['wlcfc_surname'];
				}else{
					$surname = '';
				}

				if(isset($_POST['wlcfc_phone'])){
					$phone = $_POST['wlcfc_phone'];
				}else{
					$phone = '';
				}
				
				//Updating row in the Database
				$wpdb->prepare('%s',
					$wpdb->replace(
						$table_name,
						array(
							'id'			=>	sanitize_key($_SESSION['last_inserted_id']),
							'name'			=>	sanitize_text_field( $name ),
							'surname'		=>	sanitize_text_field( $surname ),
							'email'			=>	sanitize_email( $_POST['wlcfc_email'] ),
							'phone'			=>	filter_var( $phone, FILTER_SANITIZE_NUMBER_INT),
							'cart_contents'	=>	sanitize_text_field( serialize($product_array) ),
							'cart_total'	=>	sanitize_text_field( $cart_total ),
							'currency'		=>	sanitize_text_field( $cart_currencty ),
							'time'			=>	sanitize_text_field( $current_time )
						),
						array('%d', '%s', '%s', '%s', '%s', '%s', '%0.2f', '%s', '%s')
					)
				);
				
			}else{
				
				//Inserting row into Database
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO ". $table_name ."
						( name, surname, email, phone, cart_contents, cart_total, currency, time )
						VALUES ( %s, %s, %s, %s, %s, %0.2f, %s, %s )",
						array(
							sanitize_text_field( $_POST['wlcfc_name'] ),
							sanitize_text_field( $_POST['wlcfc_surname'] ),
							sanitize_email( $_POST['wlcfc_email'] ),
							filter_var($_POST['wlcfc_phone'], FILTER_SANITIZE_NUMBER_INT),
							sanitize_text_field( serialize($product_array) ),
							sanitize_text_field( $cart_total ),
							sanitize_text_field( $cart_currencty ),
							sanitize_text_field( $current_time )
						) 
					)
				);
				
				//Saving in session variable last inserted row ID by Wordpress
				$_SESSION['last_inserted_id'] = $wpdb->insert_id;
			}
			
			die();
		}
	}
	
	
	/**
	 * Function in order to delete row from table if the user completes the checkout
	 *
	 * @since    1.0.0
	 */
	function delete_user_data() {
		
		global $wpdb;
		$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix
		
		//Starting session in order to check if we have to insert or update database row with the data from input boxes 
		if (!session_id()) session_start();
		
		if(isset($_SESSION['last_inserted_id'])) {
			
			//Deleting row from database
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM ". $table_name ."
					 WHERE id = %d",
					sanitize_key($_SESSION['last_inserted_id'])
				)
			);
		}
		
		//Removing stored ID value from Session
		unset($_SESSION['last_inserted_id']);
	}
	
}
