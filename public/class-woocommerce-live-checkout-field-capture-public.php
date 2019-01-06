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
	 * Function to add aditional JS file to the checkout field and read data from inputs
	 *
	 * @since    1.0
	 */
	function add_additional_scripts_on_checkout(){
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-live-checkout-field-capture-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'ajaxLink', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	}
	
	/**
	 * Function to receive data from Checkout input fields, sanitize it and save to Database
	 *
	 * @since    1.4.1
	 */
	function save_user_data(){
		// first check if data is being sent and that it is the data we want
		if ( isset( $_POST["wlcfc_email"] ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix

			//Retrieving cart array consisting of currency, cart toal, time, session id and products and their quantities
			$cart_data = $this->read_cart();
			$cart_total = $cart_data['cart_total'];
			$cart_currency = $cart_data['cart_currency'];
			$current_time = $cart_data['current_time'];
			$session_id = $cart_data['session_id'];
			$product_array = $cart_data['product_array'];

			//In case if the cart has no items in it, we need to delete the abandoned cart
			if(empty($product_array)){
				$this->delete_user_data();
				return;
			}
			
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
							'currency'		=>	sanitize_text_field( $cart_currency ),
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
							sanitize_text_field( $cart_currency ),
							sanitize_text_field( $current_time ),
							sanitize_text_field( $session_id ),
							sanitize_text_field( serialize($other_fields) )
						) 
					)
				);
				
				//Storing session_id in WooCommerce session
				WC()->session->set('wclcfc_session_id', $session_id);
				$this->increase_captured_abandoned_cart_count(); //Updating total count of captured abandoned carts
			}
			
			die();
		}
	}

	/**
	 * Function automatically saves a cart if a logged in user adds something to his shopping cart, removes something or updates his cart
	 * If we are updating the cart, we do not change users data, but just the Cart data since user can only change his data in the Checkout form
	 *
	 * @since    3.0
	 */
	function save_looged_in_user_data(){
		if(is_user_logged_in()){ //If a user is logged in
			global $wpdb;
			$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix

			//Retrieving cart array consisting of currency, cart toal, time, session id and products and their quantities
			$cart_data = $this->read_cart();
			$cart_total = $cart_data['cart_total'];
			$cart_currency = $cart_data['cart_currency'];
			$current_time = $cart_data['current_time'];
			$session_id = $cart_data['session_id'];
			$product_array = $cart_data['product_array'];

			//In case if the user updates the cart and takes out all items, we need to delete the abandoned cart
			if(empty($product_array)){
				$this->delete_user_data();
				return;
			}
			
			//Looking if a user has previously made an order
			//If not, using default WordPress assigned data
			//Handling users name
			$current_user = wp_get_current_user(); //Retrieving users data
			if($current_user->billing_first_name){
				$name = $current_user->billing_first_name; 
			}else{
				$name = $current_user->user_firstname; //Users name
			}

			//Handling users surname
			if($current_user->billing_last_name){
				$surname = $current_user->billing_last_name;
			}else{
				$surname = $current_user->user_lastname;
			}
			
			//Handling users email address
			if($current_user->billing_email){
				$email = $current_user->billing_email;
			}else{
				$email = $current_user->user_email;
			}

			//Handling users phone
			$phone = $current_user->billing_phone;

			//Handling users address
			if($current_user->billing_country){
				$country = $current_user->billing_country;
				if($current_user->billing_state){ //checking if the state was entered
					$city = ", ". $current_user->billing_state;
				}
				$location = $country . $city; 
			}else{
				$location = WC_Geolocation::geolocate_ip(); //Getting users country from his IP address
				$location = $location['country'];
			}

			$abandoned_cart = '';

			//If we haven't set wclcfc_session_id, then need to check in the database if the current user has got an abandoned cart already
			if( WC()->session->get('wclcfc_session_id') === NULL ){
				$main_table = $wpdb->prefix . WCLCFC_TABLE_NAME;
				$abandoned_cart = $wpdb->get_row($wpdb->prepare(
					"SELECT session_id FROM ". $main_table ."
					WHERE session_id = %d", get_current_user_id())
				);
			}

			//If the current user has got an abandoned cart already or if we have already inserted the Users session ID in Session variable and it is not NULL we update the abandoned cart row
			if( !empty($abandoned_cart) || WC()->session->get('wclcfc_session_id') !== NULL ){
				//If the user has got an abandoned cart previously, we set session ID back
				if(!empty($abandoned_cart)){
					$session_id = $abandoned_cart->session_id;
					//Storing session_id in WooCommerce session
					WC()->session->set('wclcfc_session_id', $session_id);

				}else{
					$session_id = WC()->session->get('wclcfc_session_id');
				}
				
				//Updating row in the Database where users Session id = same as prevously saved in Session
				//Updating only Cart related data since the user can change his data only in the Checkout form
				$wpdb->prepare('%s',
					$wpdb->update(
						$table_name,
						array(
							'cart_contents'	=>	sanitize_text_field( serialize($product_array) ),
							'cart_total'	=>	sanitize_text_field( $cart_total ),
							'currency'		=>	sanitize_text_field( $cart_currency ),
							'time'			=>	sanitize_text_field( $current_time )
						),
						array('session_id' => $session_id),
						array('%s', '%0.2f', '%s', '%s'),
						array('%s')
					)
				);
				
			}else{
				//Inserting row into Database
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO ". $table_name ."
						( name, surname, email, phone, location, cart_contents, cart_total, currency, time, session_id)
						VALUES ( %s, %s, %s, %s, %s, %s, %0.2f, %s, %s, %s)",
						array(
							sanitize_text_field( $name ),
							sanitize_text_field( $surname ),
							sanitize_email( $email ),
							filter_var($phone, FILTER_SANITIZE_NUMBER_INT),
							sanitize_text_field( $location ),
							sanitize_text_field( serialize($product_array) ),
							sanitize_text_field( $cart_total ),
							sanitize_text_field( $cart_currency ),
							sanitize_text_field( $current_time ),
							sanitize_text_field( $session_id )
						) 
					)
				);
				//Storing session_id in WooCommerce session
				WC()->session->set('wclcfc_session_id', $session_id);

				$this->increase_captured_abandoned_cart_count(); //Increasing total count of captured abandoned carts
			}
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
				$this->decrease_captured_abandoned_cart_count(); //Decreasing total count of captured abandoned carts
			}
			
			$this->unset_wclcfc_session_id();
		}
	}

	/**
	 * Function builds and returns an array of cart products and their quantities, car total value, currency, time, session id
	 *
	 * @since    3.0
	 * @return   Array
	 */
	function read_cart(){

		//Retrieving cart total value and currency
		$cart_total = WC()->cart->total;
		$cart_currency = get_woocommerce_currency();
		$current_time = current_time( 'mysql', false ); //Retrieving current time

		//Retrieving customer ID from WooCommerce sessions variable in order to use it as a session_id value	
		$session_id = WC()->session->get_customer_id();

		//Retrieving cart
		$products = WC()->cart->get_cart();
		$product_array = array();
				
		foreach($products as $product => $values){
			$item = wc_get_product( $values['data']->get_id());

			$product_title = $item->get_title();
			$product_quantity = $values['quantity'];
			$product_variation_price = $values['line_total'];
			
			// Handling product variations
			if($values['variation_id']){ //If user has chosen a variation
				$single_variation = new WC_Product_Variation($values['variation_id']);
		
				//Handling variable product title output with attributes
				$product_attributes = $this->attribute_slug_to_title($single_variation->get_variation_attributes());
				$product_variation_id = $values['variation_id'];
			}else{
				$product_attributes = false;
				$product_variation_id = '';
			}

			//Inserting Product title, Variation and Quantity into array
			$product_array[] = array(
				'product_title' => $product_title . $product_attributes,
				'quantity' => $product_quantity,
				'product_id' => $values['product_id'],
				'product_variation_id' => $product_variation_id,
				'product_variation_price' => $product_variation_price
			);
		}

		return $results_array = array('cart_total' => $cart_total, 'cart_currency' => $cart_currency, 'current_time' => $current_time, 'session_id' => $session_id, 'product_array' => $product_array);
	}

	/**
	 * Function returns product attributes
	 *
	 * @since    1.4.1
	 * Return: String
	 */
	public function attribute_slug_to_title( $product_variations ) {
		global $woocommerce;
		$attribute_array = array();
		
		if($product_variations){

			foreach($product_variations as $product_variation_key => $product_variation_name){

				$value = '';
				if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $product_variation_key )))){
					$term = get_term_by( 'slug', $product_variation_name, esc_attr( str_replace( 'attribute_', '', $product_variation_key )));
					if (!is_wp_error($term) && !empty($term->name)){
						$value = $term->name;
						if(!empty($value)){
							$attribute_array[] = $value;
						}
					}
				}else{
					$value = apply_filters( 'woocommerce_variation_option_name', $product_variation_name );
					if(!empty($value)){
						$attribute_array[] = $value;
					}
				}
			}
			
			//Generating attribute output			
			$total_variations = count($attribute_array);
			$increment = 0;
			$product_attribute = '';
			foreach($attribute_array as $attribute){
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
				$product_attribute .= $colon . $attribute . $comma;
				$increment++;
			}
			return $product_attribute;
		}
		else{
			return;
		}
	}
	
	/**
	 * Function restores previous Checkout form data for users that are not registered
	 *
	 * @since    2.0
	 * Return: Input field values
	 */
	public function restore_input_data( $fields = array() ) {
		global $wpdb;
		
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
			if(is_user_logged_in()){ //If the user is logged in, we want to only automatically fill his first name and last name previously saved abandoned cart during "Add to Cart" action.
			//Restoring Just Name and surname since the rest of the fields are restored automatically by WooCommerce if the user has previously purchased anything

				//Looking if a user has previously made an order
				//If not, using previously captured Abandoned cart data
				//Handling users name
				$current_user = wp_get_current_user(); //Retrieving users data
				if($current_user->billing_first_name){
					$row->name = $current_user->billing_first_name; 
				}

				//Handling users surname
				if($current_user->billing_last_name){
					$row->surname = $current_user->billing_last_name;
				}
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
		
		return $fields;
	}

	/**
	 * Function unsets session variable
	 *
	 * @since    3.0
	 */
	function unset_wclcfc_session_id(){
		//Removing stored ID value from WooCommerce Session
		WC()->session->__unset('wclcfc_session_id');
	}

	/**
	 * Function saves and updates total count of captured abandoned carts
	 *
	 * @since    2.1
	 */
	function increase_captured_abandoned_cart_count(){
		$previously_captured_abandoned_cart_count = get_option('wclcfc_captured_abandoned_cart_count');
		update_option('wclcfc_captured_abandoned_cart_count', $previously_captured_abandoned_cart_count + 1); //Updating the count by one abandoned cart
	}

	/**
	 * Function decreases the total count of captured abandoned carts
	 *
	 * @since    3.0
	 */
	function decrease_captured_abandoned_cart_count(){
		$previously_captured_abandoned_cart_count = get_option('wclcfc_captured_abandoned_cart_count');
		update_option('wclcfc_captured_abandoned_cart_count', $previously_captured_abandoned_cart_count - 1); //Decreasing the count by one abandoned cart
	}

}