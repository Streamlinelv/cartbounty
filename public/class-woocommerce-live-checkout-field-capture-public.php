<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce Live Checkout Field Capture
 * @subpackage Woocommerce Live Checkout Field Capture/public
 * @author     Streamline.lv
 */
 
class Woocommerce_Live_Checkout_Field_Capture_Public {
	
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
	public function __construct( $plugin_name, $version ) {
		global $wpdb;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}
	
	
	
	
	/**
	 * Function in order to add aditional JS file to the checkout field and read data from inputs
	 *
	 * @since    1.0
	 */
	function add_additional_scripts_on_checkout() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-live-checkout-field-capture-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'ajaxLink', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	}
	
	
	
	
	/**
	 * Function in order to receive data from Checkout input fields, sanitize it and save to Database
	 *
	 * @since    1.4.1
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

				// Handling product variations
				$product_variations = $values['variation'];

				if($product_variations){ //If we have variations
					$total_variations = count($product_variations);
					$increment = 0;
					$product_attribute = '';

					foreach($product_variations as $product_variation){
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
						$product_attribute .= $colon . $product_variation . $comma;
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
			
			//Starting session in order to check if we have to insert or update database row with the data from input boxes
			if ( $this->session_has_started() === false ){
				session_start();
				$session_id = session_id();
			}

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

			if(isset($_POST['billing_country'])){
				$country = $_POST['billing_country'];
			}else{
				$country = '';
			}

			if(isset($_POST['billing_city']) && $_POST['billing_city'] != ''){
				$city = ", ". $_POST['billing_city'];
			}else{
				$city = '';
			}

			$location = $country . $city;
			
			//If we have already inserted the Users session ID in Session variable and it is not NULL we update the abandoned cart row
			if(isset($_SESSION['current_session_id']) && $_SESSION['current_session_id'] != NULL ) {
				
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
							'time'			=>	sanitize_text_field( $current_time )
						),
						array('session_id' => $_SESSION['current_session_id']),
						array('%s', '%s', '%s', '%s', '%s', '%s', '%0.2f', '%s', '%s'),
						array('%s')
					)
				);

			}else{
				
				//Inserting row into Database
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO ". $table_name ."
						( name, surname, email, phone, location, cart_contents, cart_total, currency, time, session_id )
						VALUES ( %s, %s, %s, %s, %s, %s, %0.2f, %s, %s, %s)",
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
							sanitize_text_field( $session_id )
						) 
					)
				);
				
				//Saving in session variable current Session ID
				$_SESSION['current_session_id'] = $session_id;
			}
			
			die();
		}
	}
	
	
	/**
	 * Function in order to delete row from table if the user completes the checkout
	 *
	 * @since    1.3
	 */
	function delete_user_data() {
		
		global $wpdb;
		$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix
		
		//Starting session in order to check if we have to insert or update database row with the data from input boxes 
		if ( $this->session_has_started() === false ){
			session_start();
		}
		
		if(isset($_SESSION['current_session_id'])) {
			
			//Deleting row from database
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM ". $table_name ."
					 WHERE session_id = %s",
					sanitize_key($_SESSION['current_session_id'])
				)
			);
		}
		
		//Removing stored ID value from Session
		unset($_SESSION['current_session_id']);
	}


	/**
	 * Function that checks if the session has started
	 *
	 * @since    1.4.1
	 £ Return: Boolean
	 */
	function session_has_started(){
	    if ( php_sapi_name() !== 'cli' ) {
	        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
	            return session_status() === PHP_SESSION_ACTIVE ? true : false;
	        } else {
	            return session_id() === '' ? false : true;
	        }
	    }
	    return false;
	}
}
