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
	 * @param    string    $plugin_name       The name of the plugin.
	 * @param    string    $version    The version of this plugin.
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
		if($this->tool_enabled( 'exit_intent' )){ //If Exit Intent Enabled
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartbounty-public.css', array(), $this->version, 'all' );
			wp_style_add_data( $this->plugin_name, 'rtl', 'replace' );
		}
	}

	/**
	 * Register the javascripts for the public area.
	 *
	 * @since    3.0
	 */
	public function enqueue_scripts(){
		if (!class_exists('WooCommerce')) { //If WooCommerce does not exist
			return;
		}

		if($this->tool_enabled( 'exit_intent' )){ //If Exit Intent Enabled
			$cart_content_count = 0;

			if(WC()->cart){
				$cart_content_count = WC()->cart->get_cart_contents_count();
			}

			$hours = 1;
			if(get_option('cartbounty_exit_intent_test_mode')){ //If test mode is enabled
				$hours = 0;
			}

			$data = array(
			    'hours' => $hours,
			    'product_count' => $cart_content_count,
			    'ajaxurl' => admin_url( 'admin-ajax.php' )
			);
			wp_enqueue_script( $this->plugin_name . '-exit-intent', plugin_dir_url( __FILE__ ) . 'js/cartbounty-public-exit-intent.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name . '-exit-intent', 'cartbounty_ei', $data); //Sending variable over to JS file
		}
	}
	
	/**
	 * Method to add aditional JS file to the checkout field and read data from inputs
	 *
	 * @since    1.0
	 */
	function add_additional_scripts_on_checkout(){
		$data = array(
		    'ajaxurl' => admin_url( 'admin-ajax.php' )
		);
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartbounty-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'cartbounty_co', $data);
	}

	/**
	 * Method that takes care of saving and updating carts
	 *
	 * @since    5.0
	 */
	function save_cart(){
		if (isset( $_GET['cartbounty'])){
			return;
		}
		if(!WC()->cart){ //Exit if Woocommerce cart has not been initialized
			return;
		}
		$ghost = true;

		if($this->cart_recoverable()){ //If cart is recoverable
			$ghost = false;
			$this->update_logged_customer_id(); //If current user had an abandoned cart before - restore session ID (in case of user switching)
		}

		if(get_option('cartbounty_exclude_ghost_carts') && $ghost){ //If Ghost carts are disabled and current cart is a ghost cart - do not save it
			return;
		}

		$cart = $this->read_cart();
		$cart_saved = $this->cart_saved($cart['session_id']);

		if( $cart_saved ){ //If cart has already been saved
			$this->update_cart( $cart, $ghost );

		}else{
			$this->create_new_cart( $cart, $ghost );
		}
	}

	/**
	 * Method creates a new cart
	 *
	 * @since    5.0
	 * @param    array      $cart     Cart contents
	 * @param    boolean    $ghost    If the cart is a ghost cart or not
	 */
	function create_new_cart( $cart = array(), $ghost = false ){
		global $wpdb;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$user_data = $this->get_user_data();

		//In case if the cart has no items in it, we must delete the cart
		if(empty( $cart['product_array'] )){
			$admin->clear_cart_data();
			return;
		}

		if($ghost){ //If dealing with a ghost cart
			//Inserting row into database
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $cart_table
					( location, cart_contents, cart_total, currency, time, session_id )
					VALUES ( %s, %s, %0.2f, %s, %s, %s )",
					array(
						'location'		=> sanitize_text_field( serialize( $user_data['location'] ) ),
						'products'		=> serialize( $cart['product_array'] ),
						'total'			=> sanitize_text_field( $cart['cart_total'] ),
						'currency'		=> sanitize_text_field( $cart['cart_currency'] ),
						'time'			=> sanitize_text_field( $cart['current_time'] ),
						'session_id'	=> sanitize_text_field( $cart['session_id'] )
					)
				)
			);
			$this->increase_ghost_cart_count();

		}else{
			$other_fields = NULL;
			if(!empty($user_data['other_fields'])){
				$other_fields = sanitize_text_field( serialize( $user_data['other_fields'] ) );
			}
			//Inserting row into Database
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $cart_table
					( name, surname, email, phone, location, cart_contents, cart_total, currency, time, session_id, other_fields)
					VALUES ( %s, %s, %s, %s, %s, %s, %0.2f, %s, %s, %s, %s)",
					array(
						'name'			=> sanitize_text_field( $user_data['name'] ),
						'surname'		=> sanitize_text_field( $user_data['surname'] ),
						'email'			=> sanitize_email( $user_data['email'] ),
						'phone'			=> filter_var( $user_data['phone'], FILTER_SANITIZE_NUMBER_INT),
						'location'		=> sanitize_text_field( serialize( $user_data['location'] ) ),
						'products'		=> serialize( $cart['product_array'] ),
						'total'			=> sanitize_text_field( $cart['cart_total'] ),
						'currency'		=> sanitize_text_field( $cart['cart_currency'] ),
						'time'			=> sanitize_text_field( $cart['current_time'] ),
						'session_id'	=> sanitize_text_field( $cart['session_id'] ),
						'other_fields'	=> $other_fields
					)
				)
			);
			$this->increase_recoverable_cart_count();
		}
		$this->set_cartbounty_session($cart['session_id']);
	}

	/**
	 * Method updates a cart
	 *
	 * @since    5.0
	 * @param    array      $cart     Cart contents
	 * @param    boolean    $ghost    If the cart is a ghost cart or not
	 */
	function update_cart( $cart = array(), $ghost = false ){
		$user_data = $this->get_user_data();

		//In case if the cart has no items in it, we must delete the cart
		if(empty( $cart['product_array'] )){
			$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
			$admin->clear_cart_data();
			return;
		}

		if($ghost){ //In case of a ghost cart
			$this->update_cart_data($cart);

		}else{
			if(isset($_POST["action"])){
				if($_POST["action"] == 'cartbounty_save'){
					$this->update_cart_and_user_data($cart, $user_data);
				}else{
					$this->update_cart_data($cart);
				}
			}else{
				$this->update_cart_data($cart);
			}
		}

		$this->set_cartbounty_session($cart['session_id']);
	}

	/**
	 * Method updates only cart related data excluding customer details
	 *
	 * @since    5.0
	 * @param    Array    $cart    Cart contents inclding cart session ID
	 */
	function update_cart_data($cart){
		global $wpdb;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		
		$updated_rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET cart_contents = %s,
				cart_total = %0.2f,
				currency = %s,
				time = %s
				WHERE session_id = %s AND
				type = %d",
				serialize( $cart['product_array'] ),
				sanitize_text_field( $cart['cart_total'] ),
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$cart['session_id'],
				$admin->get_cart_type('abandoned')
			)
		);

		$this->delete_duplicate_carts( $cart['session_id'], $updated_rows);
	}

	/**
	 * Method updates both customer data and contents of the cart
	 *
	 * @since    5.0
	 * @param    Array    $cart    		Cart contents inclding cart session ID
	 * @param    Array    $user_data    User's data
	 */
	function update_cart_and_user_data($cart, $user_data){
		global $wpdb;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$other_fields = NULL;
		if(!empty($user_data['other_fields'])){
			$other_fields = sanitize_text_field( serialize( $user_data['other_fields'] ) );
		}

		$updated_rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET name = %s,
				surname = %s,
				email = %s,
				phone = %s,
				location = %s,
				cart_contents = %s,
				cart_total = %0.2f,
				currency = %s,
				time = %s,
				other_fields = '$other_fields'
				WHERE session_id = %s AND
				type = %d",
				sanitize_text_field( $user_data['name'] ),
				sanitize_text_field( $user_data['surname'] ),
				sanitize_email( $user_data['email'] ),
				filter_var( $user_data['phone'], FILTER_SANITIZE_NUMBER_INT),
				sanitize_text_field( serialize( $user_data['location'] ) ),
				serialize( $cart['product_array'] ),
				sanitize_text_field( $cart['cart_total'] ),
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$cart['session_id'],
				$admin->get_cart_type('abandoned')
			)
		);

		$this->delete_duplicate_carts( $cart['session_id'], $updated_rows);
		$this->increase_recoverable_cart_count();
	}

	/**
	 * Method shows if the current user can be identifiable and the cart can be recovered later
	 *
	 * @since    5.0
	 * @return   Boolean
	 */
	function cart_recoverable(){
		$recoverable = false;
		if( is_user_logged_in() || isset( $_POST["action"]) || $this->cart_identifiable() ){
			$recoverable = true;
		}
		return $recoverable;
	}

	/**
	 * Method returns True in case the current user has got a cart that consists phone or email
	 *
	 * @since    5.0
	 * @return   Boolean
	 */
	function cart_identifiable(){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$identifiable = false;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$where_sentence = $admin->get_where_sentence('recoverable');
		$cart = $this->read_cart();

		//Checking if we have this abandoned cart in our database already
		$result = $wpdb->get_var($wpdb->prepare(
			"SELECT id
			FROM $cart_table
			WHERE session_id = %s
			$where_sentence",
			$cart['session_id']
		));

		if($result){
			$identifiable = true;
		}

		return $identifiable;
	}

	/**
	 * Method returns available user's data
	 *
	 * @since    5.0
	 * @return   Array
	 */
	function get_user_data(){
		$user_data = array();

		if ( is_user_logged_in() && !isset( $_POST["action"] )){ //If user has signed in and the request is not triggered by checkout fields or Exit Intent
			$current_user = wp_get_current_user(); //Retrieving users data
			//Looking if a user has previously made an order. If not, using default WordPress assigned data
			(isset($current_user->billing_first_name)) ? $name = $current_user->billing_first_name : $name = $current_user->user_firstname; //If/Else shorthand (condition) ? True : False
			(isset($current_user->billing_last_name)) ? $surname = $current_user->billing_last_name : $surname = $current_user->user_lastname;
			(isset($current_user->billing_email)) ? $email = $current_user->billing_email : $email = $current_user->user_email;
			(isset($current_user->billing_phone)) ? $phone = $current_user->billing_phone : $phone = '';
			(isset($current_user->billing_country)) ? $country = $current_user->billing_country : $country = '';
			(isset($current_user->billing_city)) ? $city = $current_user->billing_city : $city = '';
			(isset($current_user->billing_postcode)) ? $postcode = $current_user->billing_postcode : $postcode = '';

			if($country == ''){ //Trying to Geolocate user's country in case it was not found
				$country = WC_Geolocation::geolocate_ip(); //Getting users country from his IP address
				$country = $country['country'];
			}

			$location = array(
				'country' 	=> $country,
				'city' 		=> $city,
				'postcode' 	=> $postcode
			);

			$user_data = array(
				'name'			=> $name,
				'surname'		=> $surname,
				'email'			=> $email,
				'phone'			=> $phone,
				'location'		=> $location,
				'other_fields'	=> ''
			);

		}else{ //Checking if we have values coming from the input fields
			(isset($_POST['cartbounty_name'])) ? $name = $_POST['cartbounty_name'] : $name = ''; //If/Else shorthand (condition) ? True : False
			(isset($_POST['cartbounty_surname'])) ? $surname = $_POST['cartbounty_surname'] : $surname = '';
			(isset($_POST['cartbounty_email'])) ? $email = $_POST['cartbounty_email'] : $email = '';
			(isset($_POST['cartbounty_phone'])) ? $phone = $_POST['cartbounty_phone'] : $phone = '';
			(isset($_POST['cartbounty_country'])) ? $country = $_POST['cartbounty_country'] : $country = '';
			(isset($_POST['cartbounty_city']) && $_POST['cartbounty_city'] != '') ? $city = $_POST['cartbounty_city'] : $city = '';
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

			if($country == ''){ //Trying to Geolocate user's country in case it was not found
				$country = WC_Geolocation::geolocate_ip(); //Getting users country from his IP address
				$country = $country['country'];
			}
			
			$location = array(
				'country' 	=> $country,
				'city' 		=> $city,
				'postcode' 	=> $postcode
			);

			$user_data = array(
				'name'			=> $name,
				'surname'		=> $surname,
				'email'			=> $email,
				'phone'			=> $phone,
				'location'		=> $location,
				'other_fields'	=> $other_fields
			);
		}

		return $user_data;
	}

	/**
	 * Method checks if current user session ID also exists in the database and the cart has not been paid for (type = abandoned or recovered_pending)
	 *
	 * @since    3.0
	 * @return   boolean
	 * @param    $session_id    Session ID
	 */
	function cart_saved( $session_id ){
		$saved = false;
		if( $session_id !== NULL ){
			global $wpdb;
			$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

			//Checking if we have this abandoned cart in our database already
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT session_id
					FROM $cart_table
					WHERE session_id = %s AND
					type = %d",
					$session_id,
					$admin->get_cart_type('abandoned')
				)
			);

			if($result){
				$saved = true;
			}
		}

		return $saved;
	}

	/**
	 * Method sets CartBounty session id value
	 *
	 * @since    5.0
	 */
	function set_cartbounty_session($session_id){
		if(!WC()->session->get('cartbounty_session_id')){ //In case browser session is not set, we make sure it gets set
			WC()->session->set('cartbounty_session_id', $session_id); //Storing session_id in WooCommerce session
		}
	}

	/**
	 * Method updates abandoned cart session from unknown session customer_id to known one in case if the user logs in
	 *
	 * @since    4.4
	 */
	function update_logged_customer_id(){
		if(is_user_logged_in()){ //If a user is logged in
			if(!WC()->session){ //If session does not exist, exit function
				return;
			}
			$customer_id = WC()->session->get_customer_id();

			if( WC()->session->get('cartbounty_session_id') !== NULL && WC()->session->get('cartbounty_session_id') !== $customer_id){ //If session is set and it is different from the one that currently is assigned to the customer
				global $wpdb;
				$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

				//Updating session ID to match the one of a logged in user
				$wpdb->update(
					$cart_table,
					array('session_id' => $customer_id),
					array('session_id' => WC()->session->get('cartbounty_session_id')),
					array('%s'),
					array('%s')
				);

				WC()->session->set('cartbounty_session_id', $customer_id);
				$cart = $this->read_cart();
				$user_data = $this->get_user_data();
				$this->update_cart_and_user_data($cart, $user_data); //Updating logged in user cart data so we do not have anything that was entered in the checkout form prior the user signed in

			}else{
				return;
			}

		}else{
			return;
		}
	}

	/**
	 * Method deletes duplicate abandoned carts from the database
	 *
	 * @since    4.4
	 * @param    $session_id			Session ID
	 * @param    $duplicate_count		Number of duplicate carts
	 */
	private function delete_duplicate_carts( $session_id, $duplicate_count ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		if($duplicate_count){ //If we have updated at least one row
			if($duplicate_count > 1){ //Checking if we have updated more than a single row to know if there were duplicates
				$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
				$where_sentence = $admin->get_where_sentence('ghost');
				//First delete all duplicate ghost carts
				$deleted_duplicate_ghost_carts = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $cart_table
						WHERE session_id = %s
						$where_sentence",
						$session_id
					)
				);

				$limit = $duplicate_count - $deleted_duplicate_ghost_carts - 1;
				if($limit < 1){
					$limit = 0;
				}

				$wpdb->query( //Leaving one cart remaining that can be identified
					$wpdb->prepare(
						"DELETE FROM $cart_table
						WHERE session_id = %s AND
						type != %d
						ORDER BY id DESC
						LIMIT %d",
						$session_id,
						$admin->get_cart_type('recovered'),
						$limit
					)
				);
			}
		}
	}

	/**
	 * Method builds and returns an array of cart products and their quantities, car total value, currency, time, session id
	 *
	 * @since    3.0
	 * @return   Array
	 */
	function read_cart(){

		if( !WC()->cart ){ //Exit if Woocommerce cart has not been initialized
			return;
		}

		//Retrieving cart total value and currency
		$cart_total = WC()->cart->total;
		$cart_currency = get_woocommerce_currency();
		$current_time = current_time( 'mysql', false ); //Retrieving current time
		$session_id = WC()->session->get( 'cartbounty_session_id' ); //Check if the session is already set
		
		if( empty( $session_id ) ){ //If session value does not exist - set one now
			$session_id = WC()->session->get_customer_id(); //Retrieving customer ID from WooCommerce sessions variable
		}

		if( WC()->session->get( 'cartbounty_from_link' ) && WC()->session->get( 'cartbounty_session_id' ) ){
			$session_id = WC()->session->get( 'cartbounty_session_id' );
		}

		//Retrieving cart
		$products = WC()->cart->get_cart_contents();
		$product_array = array();
				
		foreach( $products as $key => $product ){
			$item = wc_get_product( $product['data']->get_id() );
			$product_title = $item->get_title();
			$product_quantity = $product['quantity'];
			$product_variation_price = '';
			$product_tax = '';

			if( isset( $product['line_total'] ) ){
				$product_variation_price = $product['line_total'];
			}

			if( isset( $product['line_tax'] ) ){ //If we have taxes, add them to the price
				$product_tax = $product['line_tax'];
			}
			
			// Handling product variations
			if( $product['variation_id'] ){ //If user has chosen a variation
				$single_variation = new WC_Product_Variation( $product['variation_id'] );
		
				//Handling variable product title output with attributes
				$product_attributes = $this->attribute_slug_to_title( $single_variation->get_variation_attributes() );
				$product_variation_id = $product['variation_id'];

			}else{
				$product_attributes = false;
				$product_variation_id = '';
			}

			$product_data = array(
				'product_title' => $product_title . $product_attributes,
				'quantity' => $product_quantity,
				'product_id' => $product['product_id'],
				'product_variation_id' => $product_variation_id,
				'product_variation_price' => $product_variation_price,
				'product_tax' => $product_tax
			);

			$product_array[] = $product_data;
		}

		return $results_array = array(
			'cart_total' 	=> $cart_total,
			'cart_currency' => $cart_currency,
			'current_time' 	=> $current_time,
			'session_id' 	=> $session_id,
			'product_array' => $product_array
		);
	}

	/**
	 * Method returns product attributes
	 *
	 * @since    1.4.1
	 * @return   String
	 * @param    $product_variations    Product variations - array
	 */
	public function attribute_slug_to_title( $product_variations ) {
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
	 * Method restores previous Checkout form data
	 *
	 * @since    2.0
	 * @return   Input field values
	 * @param    $fields    Checkout fields - array
	 */
	public function restore_input_data( $fields = array() ) {
		
		if(is_account_page()){ //In case this is user's account page, do not restore data. Added since some plugins change the My account form and may trigger this function unnecessarily
			return $fields;
		}

		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$cart = $this->read_cart();
		
		$this->update_logged_customer_id(); //If current user had an abandoned cart before - restore session ID (in case of user switching)

		//Retrieve a single row with current customer ID
		$row = $wpdb->get_row($wpdb->prepare(
			"SELECT *
			FROM  $cart_table
			WHERE session_id = %s",
			$cart['session_id'])
		);

		if($row){ //If we have a user with such session ID in the database
			$other_fields = @unserialize($row->other_fields);

			if(is_serialized($row->location)){ //Since version 4.6
	            $location_data = unserialize($row->location);
	            $country = $location_data['country'];
	            $city = $location_data['city'];
	            $postcode = $location_data['postcode'];

	        }else{ //Prior version 4.6. Will be removed in future releases
	        	$parts = explode(',', $row->location); //Splits the Location field into parts where there are commas
	            if (count($parts) > 1) {
	                $country = $parts[0];
	                $city = trim($parts[1]); //Trim removes white space before and after the string
	            }
	            else{
	                $country = $parts[0];
	                $city = '';
	            }

	            $postcode = '';
                if(isset($other_fields['cartbounty_billing_postcode'])){
                    $postcode = $other_fields['cartbounty_billing_postcode'];
                }
	        }

	        (empty( $_POST['billing_first_name'])) ? $_POST['billing_first_name'] = sprintf('%s', esc_html($row->name)) : '';
			(empty( $_POST['billing_last_name'])) ? $_POST['billing_last_name'] = sprintf('%s', esc_html($row->surname)) : '';
			(empty( $_POST['billing_country'])) ? $_POST['billing_country'] = sprintf('%s', esc_html($country)) : '';
			(empty( $_POST['billing_city'])) ? $_POST['billing_city'] = sprintf('%s', esc_html($city)) : '';
			(empty( $_POST['billing_phone'])) ? $_POST['billing_phone'] = sprintf('%s', esc_html($row->phone)) : '';
			(empty( $_POST['billing_email'])) ? $_POST['billing_email'] = sprintf('%s', esc_html($row->email)) : '';
			(empty( $_POST['billing_postcode'])) ? $_POST['billing_postcode'] = sprintf('%s', esc_html($postcode)) : '';

			if($other_fields){
				(empty( $_POST['billing_company'])) ? $_POST['billing_company'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_company'])) : '';
				(empty( $_POST['billing_address_1'])) ? $_POST['billing_address_1'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_address_1'])) : '';
				(empty( $_POST['billing_address_2'])) ? $_POST['billing_address_2'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_address_2'])) : '';
				(empty( $_POST['billing_state'])) ? $_POST['billing_state'] = sprintf('%s', esc_html($other_fields['cartbounty_billing_state'])) : '';
				(empty( $_POST['shipping_first_name'])) ? $_POST['shipping_first_name'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_first_name'])) : '';
				(empty( $_POST['shipping_last_name'])) ? $_POST['shipping_last_name'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_last_name'])) : '';
				(empty( $_POST['shipping_company'])) ? $_POST['shipping_company'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_company'])) : '';
				(empty( $_POST['shipping_country'])) ? $_POST['shipping_country'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_country'])) : '';
				(empty( $_POST['shipping_address_1'])) ? $_POST['shipping_address_1'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_address_1'])) : '';
				(empty( $_POST['shipping_address_2'])) ? $_POST['shipping_address_2'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_address_2'])) : '';
				(empty( $_POST['shipping_city'])) ? $_POST['shipping_city'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_city'])) : '';
				(empty( $_POST['shipping_state'])) ? $_POST['shipping_state'] = sprintf('%s', esc_html($other_fields['cartbounty_shipping_state'])) : '';
				(empty( $_POST['order_comments'])) ? $_POST['order_comments'] = sprintf('%s', esc_html($other_fields['cartbounty_order_comments'])) : '';
			}

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
	 * Method saves and updates total count of captured recoverable abandoned carts
	 *
	 * @since    5.0
	 */
	function increase_recoverable_cart_count(){
		if(!WC()->session){ //If session does not exist, exit function 
			return;
		}
		if(WC()->session->get('cartbounty_recoverable_count_increased') || WC()->session->get('cartbounty_from_link')){//Exit function in case we already have run this once or user has returned form a recovery link
			return;
		}
		update_option('cartbounty_recoverable_cart_count', get_option('cartbounty_recoverable_cart_count') + 1);
		WC()->session->set('cartbounty_recoverable_count_increased', 1);

		if(WC()->session->get('cartbounty_ghost_count_increased')){ //In case we previously increased ghost cart count, we must now reduce it as it has been turned to recoverable
			$this->decrease_ghost_cart_count( 1 );
		}
	}

	/**
	 * Method saves and updates total count of captured ghost carts
	 *
	 * @since    5.0
	 */
	function increase_ghost_cart_count(){
		if(!WC()->session){ //If session does not exist, exit function
			return;
		}
		if(WC()->session->get('cartbounty_ghost_count_increased')){ //Exit fnction in case we already have run this once
			return;
		}
		update_option('cartbounty_ghost_cart_count', get_option('cartbounty_ghost_cart_count') + 1);
		WC()->session->set('cartbounty_ghost_count_increased', 1);
	}

	/**
	 * Method saves and updates total count of recovered carts
	 *
	 * @since    7.0
	 */
	function increase_recovered_cart_count(){
		if(!WC()->session){ //If session does not exist, exit function
			return;
		}

		if(WC()->session->get('cartbounty_recovered_count_increased')){ //Exit fnction in case we already have run this once
			return;
		}
		update_option('cartbounty_recovered_cart_count', get_option('cartbounty_recovered_cart_count') + 1);
		WC()->session->set('cartbounty_recovered_count_increased', 1);
	}

	/**
	 * Method decreases the total count of captured abandoned carts
	 *
	 * @since    5.0
	 * @param    $count    Abandoned cart number - integer 
	 */
	function decrease_recoverable_cart_count( $count ){
		update_option('cartbounty_recoverable_cart_count', get_option('cartbounty_recoverable_cart_count') - $count);
		delete_transient( 'cartbounty_recoverable_cart_count' );
	}

	/**
	 * Method decreases the total count of captured ghost carts
	 *
	 * @since    5.0
	 * @param    $count    Cart number - integer 
	 */
	function decrease_ghost_cart_count( $count ){
		update_option('cartbounty_ghost_cart_count', get_option('cartbounty_ghost_cart_count') - $count);
	}

	/**
	 * Outputing email form if a ghost user who is not logged in wants to leave with a full shopping cart
	 *
	 * @since    3.0
	 */
	function display_exit_intent_form(){
		if (!class_exists('WooCommerce')) { //If WooCommerce does not exist
			return;
		}
		
		if(!$this->tool_enabled( 'exit_intent' ) || !WC()->cart){ //If Exit Intent disabled or WooCommerce cart does not exist
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
		if(!WC()->cart){
			return;
		}
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
	 * @param    $current_user_is_admin    If the current user has Admin rights or not - boolean
	 */
	function build_exit_intent_output( $current_user_is_admin ){
		global $wpdb;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$cart = $this->read_cart();
		$heading = $admin->get_tools_defaults('heading', 'exit_intent');
		$content = $admin->get_tools_defaults('content', 'exit_intent');
		$main_color = get_option('cartbounty_exit_intent_main_color');
		$inverse_color = get_option('cartbounty_exit_intent_inverse_color');
		$where_sentence = $admin->get_where_sentence('recoverable');

		if( trim( get_option( 'cartbounty_exit_intent_heading' ) ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$heading = $admin->sanitize_field(get_option('cartbounty_exit_intent_heading'));
		}

		if( trim( get_option( 'cartbounty_exit_intent_content' ) ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$content = $admin->sanitize_field(get_option('cartbounty_exit_intent_content'));
		}

		if(!$main_color){
			$main_color = '#e3e3e3';
		}
		if(!$inverse_color){
			$inverse_color = $this->invert_color($main_color);
		}

		//Retrieve a single row with current customer ID
		$row = $wpdb->get_row($wpdb->prepare(
			"SELECT *
			FROM $cart_table
			WHERE session_id = %s
			$where_sentence",
			$cart['session_id'])
		);

		if($row && !$current_user_is_admin){ //Exit if cart already saved and the current user is not admin
			return;
		}
		
		//Prepare Exit Intent image
		$image_id = get_option('cartbounty_exit_intent_image');
		$image_url = $this->get_plugin_url() . '/public/assets/abandoned-shopping-cart.gif';
		if($image_id){
			$image = wp_get_attachment_image_src( $image_id, 'full' );
			if(is_array($image)){
				$image_url = $image[0];
			}
		}

		$args = array(
			'image_url' => $image_url,
			'heading' => $heading,
			'content' => $content,
			'main_color' => $main_color,
			'inverse_color' => $inverse_color
		);

		//In case the function is called via Ajax Add to Cart button
		//We must add wp_die() or otherwise the function does not return anything
		if (isset( $_POST["cartbounty_insert"])){ 
			$output = $this->get_template( 'cartbounty-exit-intent.php', $args );
			die();
		}else{
			return $this->get_template( 'cartbounty-exit-intent.php', $args );
		}
	}

	/**
	 * Checking if we Exit Intent is enabled
	 *
	 * @since    3.0
	 * @return   boolean
	 * @param    string    $tool    Tool that is being checked
	 */
	function tool_enabled( $tool ){
		switch ( $tool ) {
			case 'exit_intent':
				$tool_enabled = get_option('cartbounty_exit_intent_status');
				$test_mode_on = get_option('cartbounty_exit_intent_test_mode');
				break;
		}

		$current_user_is_admin = current_user_can( 'manage_options' );

		if($test_mode_on && $current_user_is_admin){
			//Outputing tool for Testing purposes to Administrators
			return true;
		}elseif($tool_enabled && !$test_mode_on && !is_user_logged_in()){
			//Outputing tool for all users who are not logged in
			return true;
		}else{
			//Tool is not enabled
			return false;
		}
	}

	/**
	 * Getting inverse color from the given one
	 *
	 * @since    3.0
	 * @return   string
	 * @param    $color    Color code - string
	 */
	function invert_color( $color ){
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
	 * Locating template file.
	 * Method returns the path to the template
	 *
	 * Search Order:
	 * 1. /themes/theme/templates/emails/$template_name
	 * 2. /themes/theme/templates/$template_name
	 * 3. /themes/theme/$template_name
	 * 4. /plugins/woo-save-abandoned-carts/templates/$template_name
	 *
	 * @since    3.0
	 * @return   string
	 * @param 	 string    $template_name - template to load.
	 * @param 	 string    $string $template_path - path to templates.
	 * @param    string    $default_path - default path to template files.
	 */
	function get_template_path( $template_name, $template_path = '', $default_path = '' ){
		$search_array = array();

		// Set variable to search folder of theme.
		if ( ! $template_path ) :
			$template_path = 'templates/';
		endif;

		//Add paths to look for template files
		$search_array[] = $template_path . 'emails/' . $template_name; 
		$search_array[] = $template_path . $template_name;
		$search_array[] = $template_name;

		// Search template file in theme folder.
		$template = locate_template( $search_array );

		// Set default plugin templates path.
		if ( ! $default_path ) :
			$default_path = plugin_dir_path( __FILE__ ) . '../templates/'; // Path to the template folder
		endif;

		// Get plugins template file.
		if ( ! $template ) :
			$template = $default_path . $template_name;
		endif;
		return apply_filters( 'get_template_path', $template, $template_name, $template_path, $default_path );
	}

	/**
	 * Get the template.
	 *
	 * @since    3.0
	 *
	 * @param    string    $template_name - template to load.
	 * @param    array     $args - args passed for the template file.
	 * @param    string    $string $template_path - path to templates.
	 * @param    string	   $default_path - default path to template files.
	 */
	function get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {
		if ( is_array( $args ) && isset( $args ) ){
			extract( $args );
		}
		$template_file = $this->get_template_path($template_name, $tempate_path, $default_path);
		if ( ! file_exists( $template_file ) ){ //Handling error output in case template file does not exist
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', esc_html( $template_file ) ), '6.1' );
			return;
		}
		include $template_file;
	}
}