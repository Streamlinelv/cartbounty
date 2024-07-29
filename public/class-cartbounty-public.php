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

		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$admin_ajax = admin_url( 'admin-ajax.php' );

		if( $this->tool_enabled( 'exit_intent' ) ){ //If Exit Intent Enabled
			$ei_settings = $admin->get_settings( 'exit_intent' );
			$hours = 1;

			if( $ei_settings['test_mode'] ){ //If test mode is enabled
				$hours = 0;
			}

			$data_ei = array(
			    'hours' 		=> $hours,
			    'product_count' => $this->get_cart_product_count(),
			    'ajaxurl' 		=> $admin_ajax
			);

			wp_enqueue_script( $this->plugin_name . '-exit-intent', plugin_dir_url( __FILE__ ) . 'js/cartbounty-public-exit-intent.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name . '-exit-intent', 'cartbounty_ei', $data_ei ); //Sending variable over to JS file
		}

		$data_co = array(
			'save_custom_email' 		=> apply_filters( 'cartbounty_save_custom_email', true ),
			'custom_email_selectors' 	=> $this->get_custom_email_selectors(),
			'selector_timeout' 			=> apply_filters( 'cartbounty_custom_email_selector_timeout', 2000 ), //Default timout 2 seconds - required for plugins that load the HTML form later
		    'ajaxurl' => $admin_ajax
		);

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartbounty-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'cartbounty_co', $data_co );
	}

	/**
	 * Method that takes care of saving and updating carts
	 *
	 * @since    5.0
	 */
	function save_cart(){
		$anonymous = true;

		if( isset( $_GET['cartbounty'] ) ) return;

		if( !WC()->cart ) return;

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );

		if( $this->cart_recoverable() ){ //If cart is recoverable
			$anonymous = false;
			$this->update_logged_customer_id(); //If current user had an abandoned cart before - restore session ID (in case of user switching)
		}

		if( $admin->get_settings( 'settings', 'exclude_anonymous_carts' ) && $anonymous ) return;

		$cart = $this->read_cart();
		$cart_saved = $this->cart_saved( $cart['session_id'] );
		$user_data = $this->get_user_data();

		if( $cart_saved ){ //If cart has already been saved
			$result = $this->update_cart( $cart, $user_data, $anonymous );

		}else{
			$result = $this->create_new_cart( $cart, $user_data, $anonymous );
		}

		if( isset( $_POST["action"] ) ){ //In case we are saving cart data via Ajax coming from CartBounty tools - try and return the result
			if( $_POST["action"] == 'cartbounty_save' ){
				if( $result ){
					wp_send_json_success();

				}else{
					wp_send_json_error();
				}
			}
		}
	}

	/**
	 * Method creates a new cart
	 *
	 * @since    5.0
	 * @return   boolean
	 * @param    array      $cart     		Cart contents
	 * @param    array      $user_data      User's data
	 * @param    boolean    $anonymous    	If the cart is anonymous or not
	 */
	function create_new_cart( $cart = array(), $user_data = array(), $anonymous = false ){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		//In case if the cart has no items in it, we must delete the cart
		if( empty( $cart['cart_contents']['products'] ) ){
			$admin->clear_cart_data();
			return;
		}

		if( $anonymous ){ //If dealing with anonymous cart
			//Inserting row into database
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $cart_table
					( location, cart_contents, cart_total, currency, time, session_id )
					VALUES ( %s, %s, %0.2f, %s, %s, %s )",
					array(
						'location'		=> sanitize_text_field( serialize( $user_data['location'] ) ),
						'cart_contents'	=> serialize( $cart['cart_contents'] ),
						'total'			=> sanitize_text_field( $cart['cart_total'] ),
						'currency'		=> sanitize_text_field( $cart['cart_currency'] ),
						'time'			=> sanitize_text_field( $cart['current_time'] ),
						'session_id'	=> sanitize_text_field( $cart['session_id'] )
					)
				)
			);
			$this->increase_anonymous_cart_count();

		}else{
			$other_fields = NULL;
			$cart_source = $this->get_cart_source();
			$source = $cart_source['source'];

			if( empty( $source ) ){ //If source not coming from Tools
				$source = 'NULL';
			}

			if( !empty( $user_data['other_fields'] ) ){
				$other_fields = sanitize_text_field( serialize( $user_data['other_fields'] ) );
			}
			//Inserting row into Database
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $cart_table
					( name, surname, email, phone, location, cart_contents, cart_total, currency, time, session_id, other_fields, saved_via )
					VALUES ( %s, %s, %s, %s, %s, %s, %0.2f, %s, %s, %s, %s, %s )",
					array(
						'name'			=> sanitize_text_field( $user_data['name'] ),
						'surname'		=> sanitize_text_field( $user_data['surname'] ),
						'email'			=> sanitize_email( $user_data['email'] ),
						'phone'			=> filter_var( $user_data['phone'], FILTER_SANITIZE_NUMBER_INT),
						'location'		=> serialize( $user_data['location'] ),
						'cart_contents'	=> serialize( $cart['cart_contents'] ),
						'total'			=> sanitize_text_field( $cart['cart_total'] ),
						'currency'		=> sanitize_text_field( $cart['cart_currency'] ),
						'time'			=> sanitize_text_field( $cart['current_time'] ),
						'session_id'	=> sanitize_text_field( $cart['session_id'] ),
						'other_fields'	=> $other_fields,
						'saved_via'		=> $source
					)
				)
			);
			$this->increase_recoverable_cart_count();
		}
		$this->set_cartbounty_session( $cart['session_id'] );

		return true;
	}

	/**
	 * Method updates a cart
	 *
	 * @since    5.0
	 * @return   boolean
	 * @param    array      $cart     		Cart contents
	 * @param    array      $user_data      User's data
	 * @param    boolean    $anonymous   	If the cart is anonymous or not
	 */
	function update_cart( $cart = array(), $user_data = array(), $anonymous = false ){
		//In case if the cart has no items in it, we must delete the cart
		if( empty( $cart['cart_contents']['products'] ) ){
			$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
			$admin->clear_cart_data();
			return;
		}

		if( $anonymous ){ //In case of anonymous cart
			$this->update_cart_data($cart);

		}else{

			if( isset( $_POST["action"] ) ){

				if( $_POST["action"] == 'cartbounty_save' ){
					$this->update_cart_and_user_data( $cart, $user_data );

				}else{
					$this->update_cart_data( $cart );
				}

			}else{
				$this->update_cart_data( $cart );
			}
		}

		$this->set_cartbounty_session( $cart['session_id'] );
		return true;
	}

	/**
	 * Method updates only cart related data excluding customer details
	 *
	 * @since    5.0
	 * @param    Array    $cart    Cart contents inclding cart session ID
	 */
	function update_cart_data( $cart ){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
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
				serialize( $cart['cart_contents'] ),
				sanitize_text_field( $cart['cart_total'] ),
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$cart['session_id'],
				$admin->get_cart_type( 'abandoned' )
			)
		);

		$this->delete_duplicate_carts( $cart['session_id'], $updated_rows );
	}

	/**
	 * Method updates both customer data and contents of the cart
	 *
	 * @since    5.0
	 * @param    Array    $cart    		Cart contents inclding cart session ID
	 * @param    Array    $user_data    User's data
	 */
	function update_cart_and_user_data( $cart, $user_data ){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$other_fields = NULL;
		$cart_source = $this->get_cart_source();
		$source = $cart_source['source'];

		if( empty( $source ) ){ //If source not coming from Tools
			$source = 'NULL';
		}

		if( !empty( $user_data['other_fields'] ) ){
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
				other_fields = %s,
				saved_via = $source
				WHERE session_id = %s AND
				type = %d",
				sanitize_text_field( $user_data['name'] ),
				sanitize_text_field( $user_data['surname'] ),
				sanitize_email( $user_data['email'] ),
				filter_var( $user_data['phone'], FILTER_SANITIZE_NUMBER_INT),
				sanitize_text_field( serialize( $user_data['location'] ) ),
				serialize( $cart['cart_contents'] ),
				sanitize_text_field( $cart['cart_total'] ),
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$other_fields,
				$cart['session_id'],
				$admin->get_cart_type( 'abandoned' )
			)
		);

		$this->delete_duplicate_carts( $cart['session_id'], $updated_rows );
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
	 * Method returns True in case the current user has got a cart that contains a phone or email
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
	 * Method checks if current shopping cart has been saved in the past 2 hours (by default) basing upon session ID
	 * Cooldown period introduced to prevent creating new abandoned carts during the same session after user has already placed a new order
	 * New cart for the same user in the same session will be created once the cooldown period has ended
	 *
	 * @since    3.0
	 * @return   boolean
	 * @param    $session_id    Session ID
	 */
	function cart_saved( $session_id ){
		$saved = false;

		if( $session_id !== NULL ){
			global $wpdb;
			$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
			$time = $admin->get_time_intervals();
			$cooldown_period = apply_filters( 'cartbounty_cart_cooldown_period', $time['two_hours'] );

			//Checking if we have this abandoned cart in our database already
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT session_id
					FROM $cart_table
					WHERE session_id = %s AND
					time > %s",
					$session_id,
					$cooldown_period
				)
			);

			if( $result ){
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
				$this->update_cart_and_user_data( $cart, $user_data ); //Updating logged in user cart data so we do not have anything that was entered in the checkout form prior the user signed in

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
				$where_sentence = $admin->get_where_sentence('anonymous');
				//First delete all duplicate anonymous carts
				$deleted_duplicate_anonymous_carts = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $cart_table
						WHERE session_id = %s
						$where_sentence",
						$session_id
					)
				);

				$limit = $duplicate_count - $deleted_duplicate_anonymous_carts - 1;
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

		if( !WC()->cart ) return; //Exit if Woocommerce cart has not been initialized

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
			$product_title = strip_tags( $item->get_title() );
			$product_quantity = $product['quantity'];
			$product_variation_price = '';
			$product_tax = '';
			$variation_attributes = array();
			$product_variation_id = '';
			$product_attributes = '';

			if( isset( $product['line_total'] ) ){
				$product_variation_price = $product['line_total'];
			}

			if( isset( $product['line_tax'] ) ){ //If we have taxes, add them to the price
				$product_tax = $product['line_tax'];
			}

			//Handling product variations
			if( isset( $product['variation'] ) ){
				
				if( is_array( $product['variation'] ) ){
					foreach( $product['variation'] as $key => $variation ){
						$variation_attributes[$key] = $variation;
					}
				}
			}

			if( isset( $product['variation_id'] ) ){ //If user has chosen a variation
				$product_variation_id = $product['variation_id'];
				$product_attributes = $this->get_attribute_names( $variation_attributes, $product['product_id'] );
			}

			$product_data = array(
				'product_title' 				=> $product_title . $product_attributes,
				'quantity' 						=> $product_quantity,
				'product_id'					=> $product['product_id'],
				'product_variation_id' 			=> $product_variation_id,
				'product_variation_price' 		=> $product_variation_price,
				'product_variation_attributes' 	=> $variation_attributes,
				'product_tax' 					=> $product_tax
			);

			$product_array[] = $product_data;
		}

		$cart_contents = array(
			'products' => $product_array,
			'cart_data' => WC()->cart->get_cart_contents()
		);

		return $results_array = array(
			'cart_total' 	=> $cart_total,
			'cart_currency' => $cart_currency,
			'current_time' 	=> $current_time,
			'session_id' 	=> $session_id,
			'cart_contents' => $cart_contents
		);
	}

	/**
	 * Retrieve product attribute names as comma sepparated string
	 *
	 * @since    8.1
	 * @return   string
	 * @param    array    	$variation_attributes   Product variation attributes
	 * @param    integer    $product_id   			Product number
	 */
	public function get_attribute_names( $variation_attributes, $product_id = '' ){
		$attribute_names = '';
		$labels = array();
		
		if( is_array( $variation_attributes ) ){
			foreach( $variation_attributes as $attribute_name => $attribute_value ){

				$attribute_name = str_replace( 'attribute_', '', $attribute_name );
				$product_terms = wc_get_product_terms( $product_id, $attribute_name );

				if( is_array( $product_terms ) ){
					foreach( $product_terms as $key => $term ){

						if( $term->slug == $attribute_value ){
							$labels[] = $term->name;
							break;
						}
					}
				}
			}
		}

		if( !empty( $labels ) ){
			$attribute_names = ': ' . implode( ', ', $labels );
		}

		return $attribute_names;
	}
	
	/**
	 * Method restores previous Checkout form data
	 *
	 * @since    2.0
	 * @return   Input field values
	 * @param    $fields    Checkout fields - array
	 */
	public function restore_input_data( $fields = array() ) {
		
		if( !is_checkout() ) return;//In case this is no WooCommerce checkout page - do not restore data. Added since some plugins change the My account form and may trigger this function unnecessarily

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$saved_cart = $this->get_saved_cart();

		if( $saved_cart ){
			$other_fields = maybe_unserialize( $saved_cart->other_fields );
			$location_data = $admin->get_cart_location( $saved_cart->location );
            $country = $location_data['country'];
            $city = $location_data['city'];
            $postcode = $location_data['postcode'];

            ( empty( $_POST['billing_first_name'] ) ) ? $_POST['billing_first_name'] = sprintf( '%s', esc_html( $saved_cart->name ) ) : '';
			( empty( $_POST['billing_last_name'] ) ) ? $_POST['billing_last_name'] = sprintf( '%s', esc_html( $saved_cart->surname ) ) : '';
			( empty( $_POST['billing_country'] ) ) ? $_POST['billing_country'] = sprintf( '%s', esc_html( $country ) ) : '';
			( empty( $_POST['billing_city'] ) ) ? $_POST['billing_city'] = sprintf( '%s', esc_html( $city ) ) : '';
			( empty( $_POST['billing_phone'] ) ) ? $_POST['billing_phone'] = sprintf( '%s', esc_html( $saved_cart->phone ) ) : '';
			( empty( $_POST['billing_email'] ) ) ? $_POST['billing_email'] = sprintf( '%s', esc_html( $saved_cart->email ) ) : '';
			( empty( $_POST['billing_postcode'] ) ) ? $_POST['billing_postcode'] = sprintf( '%s', esc_html( $postcode ) ) : '';

			$otherFieldDefaults = array(
				'cartbounty_billing_company' => '',
				'cartbounty_billing_address_1' => '',
				'cartbounty_billing_address_2' => '',
				'cartbounty_billing_state' => '',
				'cartbounty_shipping_first_name' => '',
				'cartbounty_shipping_last_name' => '',
				'cartbounty_shipping_company' => '',
				'cartbounty_shipping_country' => '',
				'cartbounty_shipping_address_1' => '',
				'cartbounty_shipping_address_2' => '',
				'cartbounty_shipping_city' => '',
				'cartbounty_shipping_state' => '',
				'cartbounty_shipping_postcode' => '',
				'cartbounty_order_comments' => '',
				'cartbounty_create_account' => '',
				'cartbounty_ship_elsewhere' => ''
			);

			$other_fields = array_merge( $otherFieldDefaults, ( array )$other_fields ); //Making sure that other fields do not throw warnings even if unable to unserialize and restore them

			if( is_array( $other_fields ) ){
				( empty( $_POST['billing_company'] ) ) ? $_POST['billing_company'] = sprintf( '%s', esc_html( $other_fields['cartbounty_billing_company'] ) ) : '';
				( empty( $_POST['billing_address_1'] ) ) ? $_POST['billing_address_1'] = sprintf( '%s', esc_html( $other_fields['cartbounty_billing_address_1'] ) ) : '';
				( empty( $_POST['billing_address_2'] ) ) ? $_POST['billing_address_2'] = sprintf( '%s', esc_html( $other_fields['cartbounty_billing_address_2'] ) ) : '';
				( empty( $_POST['billing_state'] ) ) ? $_POST['billing_state'] = sprintf( '%s', esc_html( $other_fields['cartbounty_billing_state'] ) ) : '';
				( empty( $_POST['shipping_first_name'] ) ) ? $_POST['shipping_first_name'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_first_name'] ) ) : '';
				( empty( $_POST['shipping_last_name'] ) ) ? $_POST['shipping_last_name'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_last_name'] ) ) : '';
				( empty( $_POST['shipping_company'] ) ) ? $_POST['shipping_company'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_company'] ) ) : '';
				( empty( $_POST['shipping_country'] ) ) ? $_POST['shipping_country'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_country'] ) ) : '';
				( empty( $_POST['shipping_address_1'] ) ) ? $_POST['shipping_address_1'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_address_1'] ) ) : '';
				( empty( $_POST['shipping_address_2'] ) ) ? $_POST['shipping_address_2'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_address_2'] ) ) : '';
				( empty( $_POST['shipping_city'] ) ) ? $_POST['shipping_city'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_city'] ) ) : '';
				( empty( $_POST['shipping_state'] ) ) ? $_POST['shipping_state'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_state'] ) ) : '';
				( empty( $_POST['shipping_postcode'] ) ) ? $_POST['shipping_postcode'] = sprintf( '%s', esc_html( $other_fields['cartbounty_shipping_postcode'] ) ) : '';
				( empty( $_POST['order_comments'] ) ) ? $_POST['order_comments'] = sprintf( '%s', esc_html( $other_fields['cartbounty_order_comments'] ) ) : '';
			}
			
			//Checking if Create account should be checked or not
			if( isset( $other_fields['cartbounty_create_account'] ) ){
				
				if( $other_fields['cartbounty_create_account'] ){
					add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
				}
			}

			//Checking if Ship to a different location must be checked or not
			if( isset( $other_fields['cartbounty_ship_elsewhere'] ) ){
				
				if( $other_fields['cartbounty_ship_elsewhere'] ){
					add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );
				}
			}
		}

		return $fields;
	}

	/**
	 * Retrieving current user's saved abandoned cart
	 *
	 * @since    8.1
	 * @return   array
	 */
	function get_saved_cart(){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$cart = $this->read_cart();
		
		$this->update_logged_customer_id(); //If current user had an abandoned cart before - restore session ID (in case of user switching)

		//Retrieve a single, latest edited abandoned cart with current customer ID
		$saved_cart = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM $cart_table
				WHERE session_id = %s
				ORDER BY time DESC",
				$cart['session_id']
			)
		);

		return $saved_cart;
	}


	/**
	 * Method saves and updates total count of captured recoverable abandoned carts
	 *
	 * @since    5.0
	 */
	function increase_recoverable_cart_count(){

		if( !WC()->session ) return; //If session does not exist, exit

		if( WC()->session->get( 'cartbounty_recoverable_count_increased' ) ) return; //Exit function in case we already have run this once

		WC()->session->set( 'cartbounty_recoverable_count_increased', true );

		if( WC()->session->get( 'cartbounty_from_link' ) ) return; //Exit if user returned from link - it means we have increased this before

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['recoverable_carts'] = $misc_settings['recoverable_carts'] + 1;
		update_option( 'cartbounty_misc_settings', $misc_settings );

		if( WC()->session->get( 'cartbounty_anonymous_count_increased' ) ){ //In case we previously increased anonymous cart count, we must now reduce it as it has been turned to recoverable
			$this->decrease_anonymous_cart_count( 1 );
		}
	}

	/**
	 * Method saves and updates total count of captured anonymous carts
	 *
	 * @since    5.0
	 */
	function increase_anonymous_cart_count(){

		if( !WC()->session ) return; //If session does not exist, exit

		if( WC()->session->get( 'cartbounty_anonymous_count_increased' ) ) return; //Exit in case we already have run this once

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['anonymous_carts'] = $misc_settings['anonymous_carts'] + 1;
		update_option( 'cartbounty_misc_settings', $misc_settings );

		WC()->session->set( 'cartbounty_anonymous_count_increased', 1 );
	}

	/**
	 * Method saves and updates total count of recovered carts
	 *
	 * @since    7.0
	 */
	function increase_recovered_cart_count(){

		if( !WC()->session ) return; //If session does not exist, exit function

		if( WC()->session->get( 'cartbounty_recovered_count_increased' ) ) return; //Exit fnction in case we already have run this once

		WC()->session->set( 'cartbounty_recovered_count_increased', 1 );

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['recovered_carts'] = $misc_settings['recovered_carts'] + 1;
		update_option( 'cartbounty_misc_settings', $misc_settings );
	}

	/**
	 * Method decreases the total count of captured abandoned carts
	 *
	 * @since    5.0
	 * @param    integer 	$count    			Cart count
	 */
	function decrease_recoverable_cart_count( $count ){
		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['recoverable_carts'] = $misc_settings['recoverable_carts'] - $count;
		update_option( 'cartbounty_misc_settings', $misc_settings ); //Decreasing the count by one abandoned cart

		if( WC()->session ) {
			WC()->session->__unset( 'cartbounty_recoverable_count_increased' );
			$this->increase_anonymous_cart_count(); //Since this is no longer a recoverable cart - must make sure we update anonymous cart count
		}
	}

	/**
	 * Method decreases the total count of captured anonymous carts
	 *
	 * @since    5.0
	 * @param    $count    Cart number - integer 
	 */
	function decrease_anonymous_cart_count( $count ){
		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['anonymous_carts'] = $misc_settings['anonymous_carts'] - $count;

		update_option( 'cartbounty_misc_settings', $misc_settings );

		if( WC()->session ){
			WC()->session->__unset( 'cartbounty_anonymous_count_increased' );
		}
	}

	/**
	 * Outputing email form if anonymous user who is not logged in wants to leave with a full shopping cart
	 *
	 * @since    3.0
	 */
	function display_exit_intent_form(){
		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist
		
		if( !$this->tool_enabled( 'exit_intent' ) || !WC()->cart ) return; //If Exit Intent disabled or WooCommerce cart does not exist
		
		$current_user_is_admin = current_user_can( 'manage_options' );
		$output = $this->build_exit_intent_output($current_user_is_admin); //Creating the Exit Intent output
		echo $output;
		echo "<script>localStorage.setItem( 'cartbounty_product_count', " . $this->get_cart_product_count() . " )</script>";
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
		$ei_settings = $admin->get_settings( 'exit_intent' );
		$main_color = $ei_settings['main_color'];
		$inverse_color = $ei_settings['inverse_color'];
		$where_sentence = $admin->get_where_sentence('recoverable');

		if( trim( $ei_settings['heading'] ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$heading = $admin->sanitize_field( $ei_settings['heading'] );
		}

		if( trim( $ei_settings['content'] ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$content = $admin->sanitize_field( $ei_settings['content'] );
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
		$image_id = $ei_settings['image'];
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

		return $this->get_template( 'cartbounty-exit-intent.php', $args );
	}

	/**
	 * Checking if we Exit Intent is enabled
	 *
	 * @since    3.0
	 * @return   boolean
	 * @param    string    $tool    Tool that is being checked
	 */
	function tool_enabled( $tool ){
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

		switch ( $tool ) {
			case 'exit_intent':
				$ei_settings = $admin->get_settings( 'exit_intent' );
				$tool_enabled = $ei_settings['status'];
				$test_mode_on = $ei_settings['test_mode'];
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
		if( !$template_path ){
			$template_path = 'templates/';
		}

		//Add paths to look for template files
		$search_array[] = $template_path . 'emails/' . $template_name; 
		$search_array[] = $template_path . $template_name;
		$search_array[] = $template_name;

		// Search template file in theme folder.
		$template = locate_template( $search_array );

		// Set default plugin templates path.
		if( !$default_path ){
			$default_path = plugin_dir_path( __FILE__ ) . '../templates/'; // Path to the template folder
		}

		// Get plugins template file.
		if( !$template ){
			$template = $default_path . $template_name;
		}

		return apply_filters( 'cartbounty_get_template_path', $template, $template_name, $template_path, $default_path );
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
		
		if ( !file_exists( $template_file ) ){ //Handling error output in case template file does not exist
			_doing_it_wrong( __FUNCTION__, sprintf( 'Template file %s does not exist.', esc_html( $template_file ) ), '6.1' );

			return;
		}
		include $template_file;
	}

	/**
	 * Check what is the source of the saved abandoned cart and return it
	 * Source types NULL = checkout, 1 = Exit Intent, 3 = Custom email field
	 *
	 * @since    7.3
	 * @return 	 Array
	 */
	function get_cart_source(){
		$source = '';

		if(WC()->session){ //If session exists
			if(!WC()->session->get('cartbounty_saved_via')){ //If we do not know how the cart was initially saved
				if(isset($_POST['source'])){ //Check if data coming from a source or not
					switch ( $_POST['source'] ) {
						case 'cartbounty_exit_intent':
							$source = 1;
							break;

						case 'cartbounty_custom_email':
							$source = 3;
							break;
					}

					if(!empty($source)){
						WC()->session->set('cartbounty_saved_via', $source);
					}
				}
				
			}else{ //In case we already know how the cart was initially saved - retrieve the information from session
				$source = WC()->session->get('cartbounty_saved_via');
			}
		}
		return array(
			'source' 	=> $source
		);
	}

	/**
	 * Get product count isnide shopping cart
	 *
	 * @since    7.1.3
	 * @return   integer
	 */
	function get_cart_product_count(){
		$count = 0;

		if( WC()->cart ){
			$count = WC()->cart->get_cart_contents_count();
		}

		return $count;
	}

	/**
	 * Get custom email field selectors (required for adding email to abandoned cart data input field data from 3rd party plugins)
	 * Aility to use a filter to edit this list
	 *
	 * Supporting these plugins by default:
	 * - CartBounty custom email field option									[cartbounty]
	 * - WooCommerce MyAccount login form										[woocommerce]
	 * - WPForms Lite by by WPForms												[wpforms]
	 * - Popup Builder by Looking Forward Software Incorporated					[sgpb]
	 * - Popup Maker by Popup Maker												[pum]
	 * - Ninja Forms by Saturday Drive											[ninja]
	 * - Contact Form 7 by Takayuki Miyoshi										[wpcf7]
	 * - Fluent Forms by Contact Form - WPManageNinja LLC						[fluentform]
	 * - Newsletter, SMTP, Email marketing and Subscribe forms by Brevo			[sib]
	 * - MailPoet by MailPoet													[mailpoet]
	 * - Newsletter by Stefano Lissa & The Newsletter Team						[tnp]
	 * - OptinMonster by OptinMonster Popup Builder Team						[optinmonster]
	 * - OptiMonk: Popups, Personalization & A/B Testing by OptiMonk			[optimonk]
	 * - Poptin by Poptin														[poptin]
	 * - Gravity Forms by Gravity Forms											[gform]
	 * - Popup Anything by WP OnlineSupport, Essential Plugin					[paoc]
	 * - Popup Box by Popup Box Team											[ays]
	 * - Hustle by WPMU DEV														[hustle]
	 * - Popups for Divi by divimode.com										[popdivi]
	 * - Brave Conversion Engine by Brave										[brave]
	 * - Popup by Supsystic by supsystic.com									[ppspopup]
	 * - Login/Signup Popup by XootiX											[xoologin]
	 *
	 * @since    7.3
	 * @return   string
	 */
	function get_custom_email_selectors(){
		$selectors = apply_filters( 'cartbounty_custom_email_selectors',
			array(
				'cartbounty' 	=> '.cartbounty-custom-email-field',
				'woocommerce' 	=> '.login #username',
				'wpforms' 		=> '.wpforms-container input[type="email"]',
				'sgpb' 			=> '.sgpb-form input[type="email"]',
				'pum' 			=> '.pum-container input[type="email"]',
				'ninja'			=> '.nf-form-cont input[type="email"]',
				'wpcf7'			=> '.wpcf7 input[type="email"]',
				'fluentform'	=> '.fluentform input[type="email"]',
				'sib'			=> '.sib_signup_form input[type="email"]',
				'mailpoet'		=> '.mailpoet_form input[type="email"]',
				'tnp'			=> '.tnp input[type="email"]',
				'optinmonster'	=> '.om-element input[type="email"]',
				'optimonk'		=> '.om-holder input[type="email"]',
				'poptin'		=> '.poptin-popup input[type="email"]',
				'gform'			=> '.gform_wrapper input[type="email"]',
				'paoc'			=> '.paoc-popup input[type="email"]',
				'ays'			=> '.ays-pb-form input[type="email"]',
				'hustle'		=> '.hustle-form input[type="email"]',
				'popdivi'		=> '.et_pb_section input[type="email"]',
				'brave'			=> '.brave_form_form input[type="email"]',
				'ppspopup'		=> '.ppsPopupShell input[type="email"]',
				'xoologin'		=> '.xoo-el-container input[name="xoo-el-username"]',
			)
		);

		return implode( ', ', $selectors );
	}
}