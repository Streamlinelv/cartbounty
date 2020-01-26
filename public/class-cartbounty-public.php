<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/public
 * @author     Streamline.lv
 */
 
class CartBounty_Public{
	
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
	 * Register the stylesheets for the public area.
	 *
	 * @since    3.0
	 */
	public function enqueue_styles(){
		if($this->exit_intent_enabled()){ //If Exit Intent Enabled
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartbounty-public.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the javascripts for the public area.
	 *
	 * @since    3.0
	 */
	public function enqueue_scripts(){
		if($this->exit_intent_enabled()){ //If Exit Intent Enabled
			if(get_option('cartbounty_exit_intent_test_mode')){ //If Exit Intent Test mode is on
				$data = array(
				    'hours' => 0, //For Exit Intent Testing purposes
				    'product_count' => WC()->cart->get_cart_contents_count(),
				    'ajaxurl' => admin_url( 'admin-ajax.php' )
				);
			}else{
				$data = array(
				    'hours' => 1,
				    'product_count' => WC()->cart->get_cart_contents_count(),
				    'ajaxurl' => admin_url( 'admin-ajax.php' )
				);
			}
			wp_enqueue_script( $this->plugin_name . 'exit_intent', plugin_dir_url( __FILE__ ) . 'js/cartbounty-public-exit-intent.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name . 'exit_intent', 'public_data', $data); //Sending variable over to JS file
		}
	}
	
	/**
	 * Function to add aditional JS file to the checkout field and read data from inputs
	 *
	 * @since    1.0
	 */
	function add_additional_scripts_on_checkout(){
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartbounty-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'ajaxLink', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	}
	
	/**
	 * Function to receive data from Checkout input fields or Exit Intent form, sanitize it and save to Database
	 *
	 * @since    1.4.1
	 */
	function save_user_data(){
		//First check if data is being sent and that it is the data we want
		if ( isset( $_POST["cartbounty_email"] ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix

			//Retrieving cart array consisting of currency, cart toal, time, session id and products and their quantities
			$cart_data = $this->read_cart();
			$cart_total = $cart_data['cart_total'];
			$cart_currency = $cart_data['cart_currency'];
			$current_time = $cart_data['current_time'];
			$session_id = $cart_data['session_id'];
			$product_array = $cart_data['product_array'];
			$cartbounty_session_id = WC()->session->get('cartbounty_session_id');

			//In case if the cart has no items in it, we need to delete the abandoned cart
			if(empty($product_array)){
				$this->clear_cart_data();
				return;
			}
			
			//Checking if we have values coming from the input fields
			(isset($_POST['cartbounty_name'])) ? $name = $_POST['cartbounty_name'] : $name = ''; //If/Else shorthand (condition) ? True : False
			(isset($_POST['cartbounty_surname'])) ? $surname = $_POST['cartbounty_surname'] : $surname = '';
			(isset($_POST['cartbounty_phone'])) ? $phone = $_POST['cartbounty_phone'] : $phone = '';
			(isset($_POST['cartbounty_country'])) ? $country = $_POST['cartbounty_country'] : $country = '';
			(isset($_POST['cartbounty_city']) && $_POST['cartbounty_city'] != '') ? $city = ", ". $_POST['cartbounty_city'] : $city = '';
			(isset($_POST['cartbounty_billing_company'])) ? $company = $_POST['cartbounty_billing_company'] : $company = '';
			(isset($_POST['cartbounty_billing_address_1'])) ? $address_1 = $_POST['cartbounty_billing_address_1'] : $address_1 = '';
			(isset($_POST['cartbounty_billing_address_2'])) ? $address_2 = $_POST['cartbounty_billing_address_2'] : $address_2 = '';
			(isset($_POST['cartbounty_billing_state'])) ? $state = $_POST['cartbounty_billing_state'] : $state = '';
			(isset($_POST['cartbounty_billing_postcode'])) ? $postcode = $_POST['cartbounty_billing_postcode'] : $postcode = '';
			(isset($_POST['cartbounty_shipping_first_name'])) ? $shipping_name = $_POST['cartbounty_shipping_first_name'] : $shipping_name = '';
			(isset($_POST['cartbounty_shipping_last_name'])) ? $shipping_surname = $_POST['cartbounty_shipping_last_name'] : $shipping_surname = '';
			(isset($_POST['cartbounty_shipping_company'])) ? $shipping_company = $_POST['cartbounty_shipping_company'] : $shipping_company = '';
			(isset($_POST['cartbounty_shipping_country'])) ? $shipping_country = $_POST['cartbounty_shipping_country'] : $shipping_country = '';
			(isset($_POST['cartbounty_shipping_address_1'])) ? $shipping_address_1 = $_POST['cartbounty_shipping_address_1'] : $shipping_address_1 = '';
			(isset($_POST['cartbounty_shipping_address_2'])) ? $shipping_address_2 = $_POST['cartbounty_shipping_address_2'] : $shipping_address_2 = '';
			(isset($_POST['cartbounty_shipping_city'])) ? $shipping_city = $_POST['cartbounty_shipping_city'] : $shipping_city = '';
			(isset($_POST['cartbounty_shipping_state'])) ? $shipping_state = $_POST['cartbounty_shipping_state'] : $shipping_state = '';
			(isset($_POST['cartbounty_shipping_postcode'])) ? $shipping_postcode = $_POST['cartbounty_shipping_postcode'] : $shipping_postcode = '';
			(isset($_POST['cartbounty_order_comments'])) ? $comments = $_POST['cartbounty_order_comments'] : $comments = '';
			(isset($_POST['cartbounty_create_account'])) ? $create_account = $_POST['cartbounty_create_account'] : $create_account = '';
			(isset($_POST['cartbounty_ship_elsewhere'])) ? $ship_elsewhere = $_POST['cartbounty_ship_elsewhere'] : $ship_elsewhere = '';
			
			$other_fields = array(
				'cartbounty_billing_company' 		=> $company,
				'cartbounty_billing_address_1' 		=> $address_1,
				'cartbounty_billing_address_2' 		=> $address_2,
				'cartbounty_billing_state' 			=> $state,
				'cartbounty_billing_postcode' 		=> $postcode,
				'cartbounty_shipping_first_name' 	=> $shipping_name,
				'cartbounty_shipping_last_name' 	=> $shipping_surname,
				'cartbounty_shipping_company' 		=> $shipping_company,
				'cartbounty_shipping_country' 		=> $shipping_country,
				'cartbounty_shipping_address_1' 	=> $shipping_address_1,
				'cartbounty_shipping_address_2' 	=> $shipping_address_2,
				'cartbounty_shipping_city' 			=> $shipping_city,
				'cartbounty_shipping_state' 		=> $shipping_state,
				'cartbounty_shipping_postcode' 		=> $shipping_postcode,
				'cartbounty_order_comments' 		=> $comments,
				'cartbounty_create_account' 		=> $create_account,
				'cartbounty_ship_elsewhere' 		=> $ship_elsewhere
			);
			
			$location = $country . $city;

			$current_session_exist_in_db = $this->current_session_exist_in_db($cartbounty_session_id);
			//If we have already inserted the Users session ID in Session variable and it is not NULL and Current session ID exists in Database we update the abandoned cart row
			if( $current_session_exist_in_db && $cartbounty_session_id !== NULL ){

				//Updating row in the Database where users Session id = same as prevously saved in Session
				$updated_rows = $wpdb->prepare('%s',
					$wpdb->update(
						$table_name,
						array(
							'name'			=>	sanitize_text_field( $name ),
							'surname'		=>	sanitize_text_field( $surname ),
							'email'			=>	sanitize_email( $_POST['cartbounty_email'] ),
							'phone'			=>	filter_var( $phone, FILTER_SANITIZE_NUMBER_INT),
							'location'		=>	sanitize_text_field( $location ),
							'cart_contents'	=>	serialize($product_array),
							'cart_total'	=>	sanitize_text_field( $cart_total ),
							'currency'		=>	sanitize_text_field( $cart_currency ),
							'time'			=>	sanitize_text_field( $current_time ),
							'other_fields'	=>	sanitize_text_field( serialize($other_fields) )
						),
						array('session_id' => $cartbounty_session_id),
						array('%s', '%s', '%s', '%s', '%s', '%s', '%0.2f', '%s', '%s', '%s'),
						array('%s')
					)
				);

				if($updated_rows){ //If we have updated at least one row
					$updated_rows = str_replace("'", "", $updated_rows); //Removing quotes from the number of updated rows

					if($updated_rows > 1){ //Checking if we have updated more than a single row to know if there were duplicates
						$this->delete_duplicate_carts($cartbounty_session_id, $updated_rows);
					}
				}

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
							sanitize_email( $_POST['cartbounty_email'] ),
							filter_var($phone, FILTER_SANITIZE_NUMBER_INT),
							sanitize_text_field( $location ),
							serialize($product_array),
							sanitize_text_field( $cart_total ),
							sanitize_text_field( $cart_currency ),
							sanitize_text_field( $current_time ),
							sanitize_text_field( $session_id ),
							sanitize_text_field( serialize($other_fields) )
						) 
					)
				);
				
				//Storing session_id in WooCommerce session
				WC()->session->set('cartbounty_session_id', $session_id);
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
			$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix

			//Retrieving cart array consisting of currency, cart toal, time, session id and products and their quantities
			$cart_data = $this->read_cart();
			$cart_total = $cart_data['cart_total'];
			$cart_currency = $cart_data['cart_currency'];
			$current_time = $cart_data['current_time'];
			$session_id = $cart_data['session_id'];
			$product_array = $cart_data['product_array'];
			$cartbounty_session_id = WC()->session->get('cartbounty_session_id');

			//In case if the user updates the cart and takes out all items from the cart
			if(empty($product_array)){
				$this->clear_cart_data();
				return;
			}

			$abandoned_cart = '';

			//If we haven't set cartbounty_session_id, then need to check in the database if the current user has got an abandoned cart already
			if( $cartbounty_session_id === NULL ){
				$main_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
				$abandoned_cart = $wpdb->get_row($wpdb->prepare(
					"SELECT session_id FROM ". $main_table ."
					WHERE session_id = %d", get_current_user_id())
				);
			}

			$current_session_exist_in_db = $this->current_session_exist_in_db($cartbounty_session_id);
			//If the current user has got an abandoned cart already or if we have already inserted the Users session ID in Session variable and it is not NULL and already inserted the Users session ID in Session variable we update the abandoned cart row
			if( $current_session_exist_in_db && (!empty($abandoned_cart) || $cartbounty_session_id !== NULL )){

				//If the user has got an abandoned cart previously, we set session ID back
				if(!empty($abandoned_cart)){
					$session_id = $abandoned_cart->session_id;
					//Storing session_id in WooCommerce session
					WC()->session->set('cartbounty_session_id', $session_id);

				}else{
					$session_id = $cartbounty_session_id;
				}
				
				//Updating row in the Database where users Session id = same as prevously saved in Session
				//Updating only Cart related data since the user can change his data only in the Checkout form
				$updated_rows = $wpdb->prepare('%s',
					$wpdb->update(
						$table_name,
						array(
							'cart_contents'	=>	serialize($product_array),
							'cart_total'	=>	sanitize_text_field( $cart_total ),
							'currency'		=>	sanitize_text_field( $cart_currency ),
							'time'			=>	sanitize_text_field( $current_time )
						),
						array('session_id' => $session_id),
						array('%s', '%0.2f', '%s', '%s'),
						array('%s')
					)
				);

				if($updated_rows){ //If we have updated at least one row
					$updated_rows = str_replace("'", "", $updated_rows); //Removing quotes from the number of updated rows

					if($updated_rows > 1){ //Checking if we have updated more than a single row to know if there were duplicates
						$this->delete_duplicate_carts($cartbounty_session_id, $updated_rows);
					}
				}
				
			}else{

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
					if($current_user->billing_city){ //checking if the city was entered
						$city = ", ". $current_user->billing_city;
					}else{
						$city = '';
					}
					$location = $country . $city; 
				}else{
					$location = WC_Geolocation::geolocate_ip(); //Getting users country from his IP address
					$location = $location['country'];
				}

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
							serialize($product_array),
							sanitize_text_field( $cart_total ),
							sanitize_text_field( $cart_currency ),
							sanitize_text_field( $current_time ),
							sanitize_text_field( $session_id )
						) 
					)
				);
				//Storing session_id in WooCommerce session
				WC()->session->set('cartbounty_session_id', $session_id);

				$this->increase_captured_abandoned_cart_count(); //Increasing total count of captured abandoned carts
			}
		}
	}

	/**
	 * Function updates cart data for users who are not signed in
	 *
	 * @since    3.0
	 */
	function update_cart_data(){
		if(!is_user_logged_in()){ //If a user is not logged in

			$cartbounty_session_id = WC()->session->get('cartbounty_session_id');
			if( $cartbounty_session_id !== NULL ){
				
				global $wpdb;
				$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
				$cart_data = $this->read_cart();
				$product_array = $cart_data['product_array'];
				$cart_total = $cart_data['cart_total'];
				$cart_currency = $cart_data['cart_currency'];
				$current_time = $cart_data['current_time'];

				//In case if the cart has no items in it, we need to delete the abandoned cart
				if(empty($product_array)){
					$this->clear_cart_data();
					return;
				}

				//Updating row in the Database where users Session id = same as prevously saved in Session
				$wpdb->prepare('%s',
					$wpdb->update(
						$table_name,
						array(
							'cart_contents'	=>	serialize($product_array),
							'cart_total'	=>	sanitize_text_field( $cart_total ),
							'currency'		=>	sanitize_text_field( $cart_currency ),
							'time'			=>	sanitize_text_field( $current_time )
						),
						array('session_id' => $cartbounty_session_id),
						array('%s', '%0.2f', '%s', '%s'),
						array('%s')
					)
				);
			}
		}
	}

	/**
	 * Function checks if current user session ID also exists in the database
	 *
	 * @since    3.0
	 * @return  boolean
	 */
	function current_session_exist_in_db($cartbounty_session_id){
		//If we have saved the abandoned cart in session variable
		if( $cartbounty_session_id !== NULL ){
			global $wpdb;
			$main_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

			//Checking if we have this abandoned cart in our database already
			return $result = $wpdb->get_var($wpdb->prepare(
				"SELECT session_id
				FROM ". $main_table ."
				WHERE session_id = %s",
				$cartbounty_session_id
			));

		}else{
			return false;
		}
	}

	/**
	 * Function updates abandoned cart session from unknown session customer_id to known one in case if the user logs in
	 *
	 * @since    4.4
	 */
	function update_logged_customer_id(){

		if(is_user_logged_in()){ //If a user is logged in
			$session_id = WC()->session->get_customer_id();

			if( WC()->session->get('cartbounty_session_id') !== NULL && WC()->session->get('cartbounty_session_id') !== $session_id){ //If session is set and it is different from the one that currently is assigned to the customer

				global $wpdb;
				$main_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

				//Updating session ID to match the one of a logged in user
				$wpdb->prepare('%s',
					$wpdb->update(
						$main_table,
						array('session_id' => $session_id),
						array('session_id' => WC()->session->get('cartbounty_session_id'))
					)
				);

				WC()->session->set('cartbounty_session_id', $session_id);

			}else{
				return;
			}

		}else{
			return;
		}
	}

	/**
	 * Function to clear cart data from row
	 *
	 * @since    3.0
	 */
	function clear_cart_data(){
		
		global $wpdb;
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix
		
		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if(isset(WC()->session)){

			$cartbounty_session_id = WC()->session->get('cartbounty_session_id');
			if(isset($cartbounty_session_id)){

				$cart_data = $this->read_cart();
				$cart_currency = $cart_data['cart_currency'];
				$current_time = $cart_data['current_time'];
				
				//Cleaning Cart data
				$wpdb->prepare('%s',
					$wpdb->update(
						$table_name,
						array(
							'cart_contents'	=>	'',
							'cart_total'	=>	0,
							'currency'		=>	sanitize_text_field( $cart_currency ),
							'time'			=>	sanitize_text_field( $current_time )
						),
						array('session_id' => $cartbounty_session_id),
						array('%s', '%s'),
						array('%s')
					)
				);
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
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix

		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if(isset(WC()->session)){

			$cartbounty_session_id = WC()->session->get('cartbounty_session_id');
			if(isset($cartbounty_session_id)){
				
				//Deleting row from database
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM ". $table_name ."
						 WHERE session_id = %s",
						sanitize_key($cartbounty_session_id)
					)
				);
				$this->decrease_captured_abandoned_cart_count( $count = false ); //Decreasing total count of captured abandoned carts
			}
			
			$this->unset_cartbounty_session_id();
		}
	}

	/**
	 * Function deletes duplicate abandoned carts from the database
	 *
	 * @since    4.4
	 */
	private function delete_duplicate_carts($cartbounty_session_id, $duplicate_count){
		global $wpdb;
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix

		$duplicate_rows = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name
				WHERE session_id = %s
				ORDER BY %s DESC
				LIMIT %d",
				$cartbounty_session_id,
				'id',
				$duplicate_count - 1
			)
		);
	}

	/**
	 * Function builds and returns an array of cart products and their quantities, car total value, currency, time, session id
	 *
	 * @since    3.0
	 * @return   Array
	 */
	function read_cart(){
		global $woocommerce;

		//Retrieving cart total value and currency
		$cart_total = WC()->cart->total;
		$cart_currency = get_woocommerce_currency();
		$current_time = current_time( 'mysql', false ); //Retrieving current time

		//Retrieving customer ID from WooCommerce sessions variable in order to use it as a session_id value	
		$session_id = WC()->session->get_customer_id();


		//Retrieving cart
		$products = $woocommerce->cart->cart_contents;
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
	 * Function restores previous Checkout form data for users who are not registered
	 *
	 * @since    2.0
	 * Return: Input field values
	 */
	public function restore_input_data( $fields = array() ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$cartbounty_session_id = WC()->session->get('cartbounty_session_id'); //Retrieving current session ID from WooCommerce Session
		$current_customer_id = WC()->session->get_customer_id(); //Retrieving current customer ID
		
		//Checking if current cartbounty sesion ID matches current customer ID. If they do not match, this means that the user has either logged in/signed out or switched his account. In this case we must match both session ID values	
		if($cartbounty_session_id != $current_customer_id){
			WC()->session->set('cartbounty_session_id', $current_customer_id);
			$cartbounty_session_id = WC()->session->get('cartbounty_session_id');
		}
		
		//Retrieve a single row with current customer ID
		$row = $wpdb->get_row($wpdb->prepare(
			"SELECT *
			FROM ". $table_name ."
			WHERE session_id = %s",
			$cartbounty_session_id)
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
			(empty( $_POST['billing_first_name'])) ? $_POST['billing_first_name'] = sprintf('%s', esc_html($row->name)) : '';
			(empty( $_POST['billing_last_name'])) ? $_POST['billing_last_name'] = sprintf('%s', esc_html($row->surname)) : '';
			(empty( $_POST['billing_company'])) ? $_POST['billing_company'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_company'])) : '';
			(empty( $_POST['billing_country'])) ? $_POST['billing_country'] = sprintf('%s', esc_html($country)) : '';
			(empty( $_POST['billing_address_1'])) ? $_POST['billing_address_1'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_address_1'])) : '';
			(empty( $_POST['billing_address_2'])) ? $_POST['billing_address_2'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_address_2'])) : '';
			(empty( $_POST['billing_city'])) ? $_POST['billing_city'] = sprintf('%s', esc_html($city)) : '';
			(empty( $_POST['billing_state'])) ? $_POST['billing_state'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_state'])) : '';
			(empty( $_POST['billing_postcode'])) ? $_POST['billing_postcode'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_postcode'])) : '';
			(empty( $_POST['billing_phone'])) ? $_POST['billing_phone'] = sprintf('%s', esc_html($row->phone)) : '';
			(empty( $_POST['billing_email'])) ? $_POST['billing_email'] = sprintf('%s', esc_html($row->email)) : '';
			
			(empty( $_POST['shipping_first_name'])) ? $_POST['shipping_first_name'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_first_name'])) : '';
			(empty( $_POST['shipping_last_name'])) ? $_POST['shipping_last_name'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_last_name'])) : '';
			(empty( $_POST['shipping_company'])) ? $_POST['shipping_company'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_company'])) : '';
			(empty( $_POST['shipping_country'])) ? $_POST['shipping_country'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_country'])) : '';
			(empty( $_POST['shipping_address_1'])) ? $_POST['shipping_address_1'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_address_1'])) : '';
			(empty( $_POST['shipping_address_2'])) ? $_POST['shipping_address_2'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_address_2'])) : '';
			(empty( $_POST['shipping_city'])) ? $_POST['shipping_city'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_city'])) : '';
			(empty( $_POST['shipping_state'])) ? $_POST['shipping_state'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_state'])) : '';
			(empty( $_POST['shipping_postcode'])) ? $_POST['shipping_postcode'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_postcode'])) : '';
			(empty( $_POST['order_comments'])) ? $_POST['order_comments'] = sprintf('%s', esc_html($other_fields['cartbounty_order_comments'])) : '';
			
			//Checking if Create account should be checked or not
			if(isset($other_fields['cartbounty_create_account'])){
				if($other_fields['cartbounty_create_account']){
					add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
				}
			}

			//Checking if Ship to a different location must be checked or not
			if(isset($other_fields['cartbounty_ship_elsewhere'])){
				if($other_fields['cartbounty_ship_elsewhere']){
					add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );
				}
			}
		}
		return $fields;
	}

	/**
	 * Function unsets session variable
	 *
	 * @since    3.0
	 */
	function unset_cartbounty_session_id(){
		//Removing stored ID value from WooCommerce Session
		WC()->session->__unset('cartbounty_session_id');
	}

	/**
	 * Function saves and updates total count of captured abandoned carts
	 *
	 * @since    2.1
	 */
	function increase_captured_abandoned_cart_count(){
		$previously_captured_abandoned_cart_count = get_option('cartbounty_captured_abandoned_cart_count');
		update_option('cartbounty_captured_abandoned_cart_count', $previously_captured_abandoned_cart_count + 1); //Updating the count by one abandoned cart
	}

	/**
	 * Function decreases the total count of captured abandoned carts
	 *
	 * @since    3.0
	 */
	function decrease_captured_abandoned_cart_count($count){
		if(!$count){
			$count = 1;
		}
		$previously_captured_abandoned_cart_count = get_option('cartbounty_captured_abandoned_cart_count');
		update_option('cartbounty_captured_abandoned_cart_count', $previously_captured_abandoned_cart_count - $count); //Decreasing the count by one abandoned cart
	}

	/**
	 * Outputing email form if a user who is not logged in wants to leave with a full shopping cart
	 *
	 * @since    3.0
	 */
	function display_exit_intent_form(){
		if(!$this->exit_intent_enabled()){ //If Exit Intent disabled
			return;
		}
		
		if( WC()->cart->get_cart_contents_count() > 0 ){ //If the cart is not empty
			$current_user_is_admin = current_user_can( 'manage_options' );
			$output = $this->build_exit_intent_output($current_user_is_admin); //Creating the Exit Intent output
			if (isset( $_POST["cartbounty_insert"])) { //In case function triggered using Ajax Add to Cart
				return wp_send_json_success($output); //Sending Output to Javascript function
			}
			else{ //Outputing in case of page reload
				echo $output;
			}
		}
	}

	/**
	 * Checking if cart is empty and sending result to Ajax function
	 *
	 * @since    3.0
	 * @return   boolean
	 */
	function remove_exit_intent_form(){
		if( WC()->cart->get_cart_contents_count() == 0 ){ //If the cart is empty
			return wp_send_json_success('true'); //Sending successful output to Javascript function
		}else{
			return wp_send_json_success('false');
		}
	}

	/**
	 * Building the Exit Intent output
	 *
	 * @since    3.0
	 * @return   string
	 */
	function build_exit_intent_output($current_user_is_admin){
		global $wpdb;
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$cartbounty_session_id = WC()->session->get('cartbounty_session_id'); //Retrieving current session ID from WooCommerce Session
		$main_color = esc_attr( get_option('cartbounty_exit_intent_main_color'));
		$inverse_color = esc_attr( get_option('cartbounty_exit_intent_inverse_color'));
		if(!$main_color){
			$main_color = '#e3e3e3';
		}
		if(!$inverse_color){
			$inverse_color = $this->invert_color($main_color);
		}

		//Retrieve a single row with current customer ID
		$row = $wpdb->get_row($wpdb->prepare(
			"SELECT *
			FROM ". $table_name ."
			WHERE session_id = %s",
			$cartbounty_session_id)
		);

		if($row && !$current_user_is_admin){ //Exit if Abandoned Cart already saved and the current user is not admin
			return;
		}

		//In case the function is called via Ajax Add to Cart button
		//We must add wp_die() or otherwise the function does not return anything
		if (isset( $_POST["cartbounty_insert"])){ 
			$output = $this->get_template( 'cartbounty-exit-intent.php', array('main_color' => $main_color, 'inverse_color' => $inverse_color));
			die();
		}else{
			return $this->get_template( 'cartbounty-exit-intent.php', array('main_color' => $main_color, 'inverse_color' => $inverse_color));
		}
	}

	/**
	 * Checking if we Exit Intent is enabled
	 *
	 * @since    3.0
	 * @return   boolean
	 */
	function exit_intent_enabled(){
		$exit_intent_on = get_option('cartbounty_exit_intent_status');
		$test_mode_on = get_option('cartbounty_exit_intent_test_mode');
		$current_user_is_admin = current_user_can( 'manage_options' );

		if($test_mode_on && $current_user_is_admin){
			//Outputing Exit Intent for Testing purposes for Administrators
			return true;
		}elseif($exit_intent_on && !$test_mode_on && !is_user_logged_in()){
			//Outputing Exit Intent for all users who are not logged in
			return true;
		}else{
			//Do not Output Exit Intent
			return false;
		}
		
	}

	/**
	 * Getting inverse color from the given one
	 *
	 * @since    3.0
	 * @return   string
	 */
	function invert_color($color){
	    $color = str_replace('#', '', $color);
	    if (strlen($color) != 6){ return '000000'; }
	    $rgb = '';
	    for ($x=0;$x<3;$x++){
	        $c = 255 - hexdec(substr($color,(2*$x),2));
	        $c = ($c < 0) ? 0 : dechex($c);
	        $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
	    }
	    return '#'.$rgb;
	}

	/**
	 * Get the plugin url.
	 *
	 * @since    3.0
	 * @return   string
	 */
	public function get_plugin_url() {
		return plugins_url( '/', dirname(__FILE__) );
	}

	/**
	 * Locating Exit Intent template file.
	 * Function returns the path to the template
	 *
	 * Search Order:
	 * 1. /themes/theme/cartbounty-templates/$template_name
	 * 2. /themes/theme/$template_name
	 * 3. /plugins/woocommerce-plugin-templates/templates/$template_name.
	 *
	 * @since    3.0
	 * @return   string
	 * @param 	 string     $template_name - template to load.
	 * @param 	 string     $string $template_path - path to templates.
	 * @param    string     $default_path - default path to template files.
	 */
	function get_exit_intent_template_path($template_name, $template_path = '', $default_path = ''){
		// Set variable to search in woocommerce-plugin-templates folder of theme.
		if ( ! $template_path ) :
			$template_path = 'templates/';
		endif;

		// Set default plugin templates path.
		if ( ! $default_path ) :
			$default_path = plugin_dir_path( __FILE__ ) . '../templates/'; // Path to the template folder
		endif;

		// Search template file in theme folder.
		$template = locate_template( array(
			$template_path . $template_name,
			$template_name
		));

		// Get plugins template file.
		if ( ! $template ) :
			$template = $default_path . $template_name;
		endif;
		return apply_filters( 'get_exit_intent_template_path', $template, $template_name, $template_path, $default_path );
	}

	/**
	 * Get the template.
	 *
	 * @since 3.0
	 *
	 * @param string 	$template_name - template to load.
	 * @param array 	$args - args passed for the template file.
	 * @param string 	$string $template_path - path to templates.
	 * @param string	$default_path - default path to template files.
	 */
	function get_template($template_name, $args = array(), $tempate_path = '', $default_path = '') {
		if ( is_array( $args ) && isset( $args ) ){
			extract( $args );
		}
		$template_file = $this->get_exit_intent_template_path($template_name, $tempate_path, $default_path);
		if ( ! file_exists( $template_file ) ){ //Handling error output in case template file does not exist
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '3.0' );
			return;
		}
		include $template_file;
	}

}