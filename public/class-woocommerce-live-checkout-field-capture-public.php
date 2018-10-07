<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce Live Checkout Field Capture
 * @subpackage WooCommerce Live Checkout Field Capture/public
 * @author     Streamline.lv
 */
 
class WooCommerce_Live_Checkout_Field_Capture_Public{
	
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ){
		global $wpdb;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}
	
	/**
	 * Function in order to add aditional JS file to the checkout field and read data from inputs
	 *
	 * @since    1.0
	 */
	function add_additional_scripts_on_checkout(){
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-live-checkout-field-capture-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'ajaxLink', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	}
	
	/**
	 * Function in order to receive data from Checkout input fields, sanitize it and save to Database
	 *
	 * @since    1.4.1
	 */
	function save_user_data(){
		// first check if data is being sent and that it is the data we want
		if ( isset( $_POST["wlcfc_email"] ) ) {

			
			global $wpdb;
			$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix
			
			//Retrieving cart total value and currency
			$cart_total = WC()->cart->total;
			$cart_currencty = get_woocommerce_currency();
			
			//Retrieving cart products and their quantities
			$products = WC()->cart->get_cart();
			$product_array = array();
			
			foreach($products as $product => $values){
				$item = wc_get_product( $values['data']->get_id());

				$product_title = $item->get_title();
				$product_quantity = $values['quantity'];

				// Handling product variations
				$product_variations = $values['variation'];

				if($product_variations){ //If we have variations
					$total_variations = count($product_variations);
					$increment = 0;
					$product_attribute = '';

					foreach($product_variations as $product_variation_key => $product_variation_name){

						$product_variation_name = $this->attribute_slug_to_title($product_variation_key, $product_variation_name);

						if($increment === 0 && $increment != $total_variations - 1){ //If this is first variation and we have multiple variations
							$colon = ': ';
							$comma = ', ';
						}
						elseif($increment === 0 && $increment === $total_variations - 1){ //If we have only one variation
							$colon = ': ';
							$comma = false;
						}
						elseif($increment === $total_variations - 1) { //If this is the last variation
							$comma = '';
							$colon = false;
						}else{
							$comma = ', ';
							$colon = false;
						}
						$product_attribute .= $colon . $product_variation_name . $comma;
						$increment++;
					}
				}else{
					$product_attribute = false;
				}
				
				//Inserting Product title, Variation and Quantity into array
				$product_array[] = array($product_title . $product_attribute, $product_quantity, $values['product_id'] );
			}
			
			//Retrieving current time
			$current_time = current_time( 'mysql', false );
			
			//Retrieving customer ID from WooCommerce sessions variable in order to use it as a session_id value	
			$session_id = WC()->session->get_customer_id();
			
			//Checking if we have values coming from the input fields
			(isset($_POST['wlcfc_name'])) ? $name = $_POST['wlcfc_name'] : $name = ''; //If/Else shorthand (condition) ? True : False
			(isset($_POST['wlcfc_surname'])) ? $surname = $_POST['wlcfc_surname'] : $surname = '';
			(isset($_POST['wlcfc_phone'])) ? $phone = $_POST['wlcfc_phone'] : $phone = '';
			(isset($_POST['wlcfc_country'])) ? $country = $_POST['wlcfc_country'] : $country = '';
			(isset($_POST['wlcfc_city']) && $_POST['wlcfc_city'] != '') ? $city = ", ". $_POST['wlcfc_city'] : $city = '';
			(isset($_POST['wlcfc_billing_company'])) ? $company = $_POST['wlcfc_billing_company'] : $company = '';
			(isset($_POST['wlcfc_billing_address_1'])) ? $address_1 = $_POST['wlcfc_billing_address_1'] : $address_1 = '';
			(isset($_POST['wlcfc_billing_address_2'])) ? $address_2 = $_POST['wlcfc_billing_address_2'] : $address_2 = '';
			(isset($_POST['wlcfc_billing_state'])) ? $state = $_POST['wlcfc_billing_state'] : $state = '';
			(isset($_POST['wlcfc_billing_postcode'])) ? $postcode = $_POST['wlcfc_billing_postcode'] : $postcode = '';
			(isset($_POST['wlcfc_shipping_first_name'])) ? $shipping_name = $_POST['wlcfc_shipping_first_name'] : $shipping_name = '';
			(isset($_POST['wlcfc_shipping_last_name'])) ? $shipping_surname = $_POST['wlcfc_shipping_last_name'] : $shipping_surname = '';
			(isset($_POST['wlcfc_shipping_company'])) ? $shipping_company = $_POST['wlcfc_shipping_company'] : $shipping_company = '';
			(isset($_POST['wlcfc_shipping_country'])) ? $shipping_country = $_POST['wlcfc_shipping_country'] : $shipping_country = '';
			(isset($_POST['wlcfc_shipping_address_1'])) ? $shipping_address_1 = $_POST['wlcfc_shipping_address_1'] : $shipping_address_1 = '';
			(isset($_POST['wlcfc_shipping_address_2'])) ? $shipping_address_2 = $_POST['wlcfc_shipping_address_2'] : $shipping_address_2 = '';
			(isset($_POST['wlcfc_shipping_city'])) ? $shipping_city = $_POST['wlcfc_shipping_city'] : $shipping_city = '';
			(isset($_POST['wlcfc_shipping_state'])) ? $shipping_state = $_POST['wlcfc_shipping_state'] : $shipping_state = '';
			(isset($_POST['wlcfc_shipping_postcode'])) ? $shipping_postcode = $_POST['wlcfc_shipping_postcode'] : $shipping_postcode = '';
			(isset($_POST['wlcfc_order_comments'])) ? $comments = $_POST['wlcfc_order_comments'] : $comments = '';
			
			$other_fields = array(
				'wlcfc_billing_company' 	=> $company,
				'wlcfc_billing_address_1' 	=> $address_1,
				'wlcfc_billing_address_2' 	=> $address_2,
				'wlcfc_billing_state' 		=> $state,
				'wlcfc_billing_postcode' 	=> $postcode,
				'wlcfc_shipping_first_name' => $shipping_name,
				'wlcfc_shipping_last_name' 	=> $shipping_surname,
				'wlcfc_shipping_company' 	=> $shipping_company,
				'wlcfc_shipping_country' 	=> $shipping_country,
				'wlcfc_shipping_address_1' 	=> $shipping_address_1,
				'wlcfc_shipping_address_2' 	=> $shipping_address_2,
				'wlcfc_shipping_city' 		=> $shipping_city,
				'wlcfc_shipping_state' 		=> $shipping_state,
				'wlcfc_shipping_postcode' 	=> $shipping_postcode,
				'wlcfc_order_comments' 		=> $comments
			);
			
			$location = $country . $city;
			
			//If we have already inserted the Users session ID in Session variable and it is not NULL we update the abandoned cart row
			if( WC()->session->get('wclcfc_session_id') !== NULL ){
				
				//Updating row in the Database where users Session id = same as prevously saved in Session
				$wpdb->prepare('%s',
					$wpdb->update(
						$table_name,
						array(
							'name'			=>	sanitize_text_field( $name ),
							'surname'		=>	sanitize_text_field( $surname ),
							'email'			=>	sanitize_email( $_POST['wlcfc_email'] ),
							'phone'			=>	filter_var( $phone, FILTER_SANITIZE_NUMBER_INT),
							'location'		=>	sanitize_text_field( $location ),
							'cart_contents'	=>	sanitize_text_field( serialize($product_array) ),
							'cart_total'	=>	sanitize_text_field( $cart_total ),
							'currency'		=>	sanitize_text_field( $cart_currencty ),
							'time'			=>	sanitize_text_field( $current_time ),
							'other_fields'	=>	sanitize_text_field( serialize($other_fields) )
						),
						array('session_id' => WC()->session->get('wclcfc_session_id')),
						array('%s', '%s', '%s', '%s', '%s', '%s', '%0.2f', '%s', '%s', '%s'),
						array('%s')
					)
				);

			}else{
				
				//Inserting row into Database
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO ". $table_name ."
						( name, surname, email, phone, location, cart_contents, cart_total, currency, time, session_id, other_fields )
						VALUES ( %s, %s, %s, %s, %s, %s, %0.2f, %s, %s, %s, %s)",
						array(
							sanitize_text_field( $name ),
							sanitize_text_field( $surname ),
							sanitize_email( $_POST['wlcfc_email'] ),
							filter_var($phone, FILTER_SANITIZE_NUMBER_INT),
							sanitize_text_field( $location ),
							sanitize_text_field( serialize($product_array) ),
							sanitize_text_field( $cart_total ),
							sanitize_text_field( $cart_currencty ),
							sanitize_text_field( $current_time ),
							sanitize_text_field( $session_id ),
							sanitize_text_field( serialize($other_fields) )
						) 
					)
				);
				
				//Storing session_id in WooCommerce session
				WC()->session->set('wclcfc_session_id', $session_id);
			}
			
			die();
		}
	}
	
	/**
	 * Function in order to delete row from table if the user completes the checkout
	 *
	 * @since    1.3
	 */
	function delete_user_data(){
		global $wpdb;
		$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix

		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if(isset(WC()->session)){

			$session_id = WC()->session->get('wclcfc_session_id');

			if(isset($session_id)){
				
				//Deleting row from database
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM ". $table_name ."
						 WHERE session_id = %s",
						sanitize_key($session_id)
					)
				);
			}
			
			//Removing stored ID value from WooCommerce Session
			WC()->session->__unset('wclcfc_session_id');
		}
	}

	/**
	 * Function returns attributes name from slug in order to display Variations
	 *
	 * @since    1.4.1
	 * Return: String
	 */
	function attribute_slug_to_title( $attribute, $slug ) {
		global $woocommerce;
		$value = '';

		if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $attribute ) ) ) ) {
			$term = get_term_by( 'slug', $slug, esc_attr( str_replace( 'attribute_', '', $attribute ) ) );
			if ( ! is_wp_error( $term ) && $term->name )
				$value = $term->name;
		} else {
			$value = apply_filters( 'woocommerce_variation_option_name', $value );
		}

		return $value;
	}
	
	/**
	 * Function restores previous Checkout form data for users that are not registered
	 *
	 * @since    2.0.0
	 * Return: Input field values
	 */
	public function restore_input_data( $fields = array() ) {
		global $wpdb;
		
		if(!is_user_logged_in()){ //If the user is not logged in
			$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME;
			$customer_id = WC()->session->get('wclcfc_session_id'); //Retrieving current session ID from WooCommerce Session
			
			//Retrieve a single row with current customer ID
			$row = $wpdb->get_row($wpdb->prepare(
				"SELECT *
				FROM ". $table_name ."
				WHERE session_id = %s",
				$customer_id)
			);
			
			if($row){ //If we have a user with such session ID in the database
				$other_fields = unserialize($row->other_fields);
				
				$parts = explode(',', $row->location); //Splits the Location field into parts where there are commas
				if (count($parts) > 1) {
				   $country = $parts[0];
				   $city = trim($parts[1]); //Trim removes white space before and after the string
				}
				else{
					$country = $parts[0];
					$city = '';
				}
				
				//Filling Checkout field values back with previously entered values
				(empty( $_POST['billing_first_name'])) ? $_POST['billing_first_name'] = sprintf('%s', esc_html($row->name)) : true;
				(empty( $_POST['billing_last_name'])) ? $_POST['billing_last_name'] = sprintf('%s', esc_html($row->surname)) : '';
				(empty( $_POST['billing_company'])) ? $_POST['billing_company'] = sprintf('%s', esc_html($other_fields['wlcfc_billing_company'])) : '';
				(empty( $_POST['billing_country'])) ? $_POST['billing_country'] = sprintf('%s', esc_html($country)) : '';
				(empty( $_POST['billing_address_1'])) ? $_POST['billing_address_1'] = sprintf('%s', esc_html($other_fields['wlcfc_billing_address_1'])) : '';
				(empty( $_POST['billing_address_2'])) ? $_POST['billing_address_2'] = sprintf('%s', esc_html($other_fields['wlcfc_billing_address_2'])) : '';
				(empty( $_POST['billing_city'])) ? $_POST['billing_city'] = sprintf('%s', esc_html($city)) : '';
				(empty( $_POST['billing_state'])) ? $_POST['billing_state'] = sprintf('%s', esc_html($other_fields['wlcfc_billing_state'])) : '';
				(empty( $_POST['billing_postcode'])) ? $_POST['billing_postcode'] = sprintf('%s', esc_html($other_fields['wlcfc_billing_postcode'])) : '';
				(empty( $_POST['billing_phone'])) ? $_POST['billing_phone'] = sprintf('%s', esc_html($row->phone)) : '';
				(empty( $_POST['billing_email'])) ? $_POST['billing_email'] = sprintf('%s', esc_html($row->email)) : '';
				
				(empty( $_POST['shipping_first_name'])) ? $_POST['shipping_first_name'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_first_name'])) : '';
				(empty( $_POST['shipping_last_name'])) ? $_POST['shipping_last_name'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_last_name'])) : '';
				(empty( $_POST['shipping_company'])) ? $_POST['shipping_company'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_company'])) : '';
				(empty( $_POST['shipping_country'])) ? $_POST['shipping_country'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_country'])) : '';
				(empty( $_POST['shipping_address_1'])) ? $_POST['shipping_address_1'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_address_1'])) : '';
				(empty( $_POST['shipping_address_2'])) ? $_POST['shipping_address_2'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_address_2'])) : '';
				(empty( $_POST['shipping_city'])) ? $_POST['shipping_city'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_city'])) : '';
				(empty( $_POST['shipping_state'])) ? $_POST['shipping_state'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_state'])) : '';
				(empty( $_POST['shipping_postcode'])) ? $_POST['shipping_postcode'] = sprintf('%s', esc_html($other_fields['wlcfc_shipping_postcode'])) : '';
				(empty( $_POST['order_comments'])) ? $_POST['order_comments'] = sprintf('%s', esc_html($other_fields['wlcfc_order_comments'])) : '';
			}
		}
		return $fields;
	}
}