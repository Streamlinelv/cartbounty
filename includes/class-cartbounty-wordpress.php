<?php
/**
 * The WordPress class
 *
 * Used to define WordPress email related functions
 *
 *
 * @since      7.0
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/includes
 * @author     Streamline.lv
 */

class CartBounty_WordPress{
	
	/**
	 * Starting WordPress email automation workflow
	 *
	 * @since    7.0
	 */
	public function auto_send(){
		if(!class_exists('WooCommerce')){ //Exit if license key not valid or if WooCommerce is not activated
			return;
		}else{
			if( $this->automation_enabled() ){ //If WordPress email automation workflow enabled
				//Activating automation workflow
				$this->recover_carts();
			}
		}
	}

	/**
	 * Checking if WordPress automation enabled. If at least one email template enabled, return true
	 *
	 * @since    7.0
	 * @return   boolean
	 */
	public function automation_enabled() {
		$enabled = false;
		$active_steps = $this->get_active_steps();
		if(count($active_steps) > 0){
			$enabled = true;
		}
		return $enabled;
	}

	/**
	 * Checking if WordPress automation enabled. At least one email template should be enabled
	 *
	 * @since    7.0
	 * @return   string
     * @param    boolean    $enabled    		  Wheather automation has been enabled or not
	 */
	public function display_automation_status( $enabled ) {
		if($enabled){
			$status = sprintf('<span class="status active">%s</span>', esc_html__('Active', 'woo-save-abandoned-carts'));
		}else{
			$status = sprintf('<span class="status inactive">%s</span>', esc_html__('Disabled', 'woo-save-abandoned-carts'));
		}
		echo $status;
	}

	/**
	 * Starting abandoned cart recovery
	 *
	 * @since    7.0
	 */
	private function recover_carts() {
		global $wpdb;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time = $admin->get_time_intervals();

		//Retrieving all abandoned carts that are eligible for email recovery
		//Excluding finished automations, unsubscried carts
		$carts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, email, session_id, time, wp_steps_completed
				FROM {$cart_table}
				WHERE type = %d AND
				email != '' AND
				cart_contents != '' AND
				wp_unsubscribed != 1 AND
				wp_complete != 1 AND
				time < %s AND
				time > %s",
				$admin->get_cart_type('abandoned'),
				$time['cart_abandoned'],
				$time['maximum_sync_period']
			)
		);

		$active_steps = $this->get_active_steps();
		$automation_steps = get_option('cartbounty_automation_steps');

		foreach ($active_steps as $key => $step) { //Looping through active steps
			$automation_step = $automation_steps[$step];
			foreach ($carts as $cart_key => $cart) {
				if($cart->wp_steps_completed == $key){ //If current step must be complete
					$first_step = true;
					$time = $cart->time;

					if(isset($automation_step['interval'])){
						$interval = $automation_step['interval'];
					}else{ //If custom interval is not set, fallback to default interval
						$interval = $this->get_defaults( 'interval', $step );
					}
					$step_wait_time = $admin->get_time_intervals( $interval, $first_step ); //Get time interval for the current step

					if ($time < $step_wait_time['wp_step_send_period']){ //Check if time has passed for current step
						$this->send_reminder( $cart ); //Time has passed - must prepare and send out reminder email
						unset($carts[$cart_key]); //Remove array element so the next step loop runs faster
					}
				}
			}
		}
	}

	/**
	 * Send email reminder
	 * Send out email reminder
	 *
	 * @since    7.0
	 * @param    object     $cart    		  	  Cart data
	 * @param    boolean    $test                 Wheather this is a test email or not
	 * @param    string     $email                Email that is used for sending a test email
	 * @param    array    	$preview_data         Automation step input data passed from frontend to allow template preview
	 */
	public function send_reminder( $cart, $test = false, $email = false, $preview_data = array() ){
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

		if($test){
			$to = $email;

		}else{
			$to = $cart->email;
		}

		$subject = $this->get_defaults( 'subject', 0 );
		$automation_steps = get_option('cartbounty_automation_steps');
		$step = $automation_steps[0];

		if($test){
			$step = $preview_data;
		}

		if(isset($step['subject'])){ //In case we have a custom subject set, use it
			if( !empty($step['subject']) ) {
				$subject = $step['subject'];
			}
		}

		$message = $this->get_reminder_contents( $cart, $test, $preview_data );
		$from_name = ( !empty(get_option('cartbounty_automation_from_name')) ) ? get_option('cartbounty_automation_from_name') : get_option( 'blogname' );
		$from_email = ( !empty(get_option('cartbounty_automation_from_email')) ) ? get_option('cartbounty_automation_from_email') : get_option( 'admin_email' );
		$reply_to = ( !empty(get_option('cartbounty_automation_reply_email')) ) ? get_option('cartbounty_automation_reply_email') : false;

		$headers = array(
			'Content-Type: text/html',
			'charset='. get_option('blog_charset')
		);
		$headers[] = "From: ". $admin->sanitize_field($from_name) ." <". sanitize_email($from_email)  .">";

		if($reply_to){
			$headers[] = "Reply-To: <". sanitize_email($reply_to)  .">";
		}
		
		$result = wp_mail( sanitize_email($to), $admin->sanitize_field($subject), $message, $headers );

		if($result){ //In case if the email was successfuly sent out
			if(!$test){ //If this is not a test email
				$current_time = current_time( 'mysql', false );
				$template = ( isset($step['template']) ) ? $step['template'] : $this->get_defaults( 'template', 0 );
				$this->update_cart( $cart ); //Update cart information
				$this->add_email( $cart->id, $current_time ); //Create a new row in the emails table
			}
		}
		restore_previous_locale();
	}

	/**
	* Send a test email. 
	* If email field is empty, send email to default Administrator email
	*
	* @since    7.0
	* @return   HTML
	*/
	public function send_test(){
		if ( check_ajax_referer( 'test_email', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing function
			wp_send_json_error(esc_html__( 'Looks like you are not allowed to do this.', 'woo-save-abandoned-carts' ));
		}
		$step_nr = false;
		if(isset($_POST['step'])){
			$step_nr = $_POST['step'];
		}
		$preview_data = array();
		if(isset($_POST)){
			$preview_data = $this->get_preview_data($_POST);
		}

		$email = false;
		$direction = is_rtl() ? 'rtl' : 'ltr';

		if(isset($_POST['email'])){ //If we have received email field
			if(!empty($_POST['email'])){ //If email field is not empty
				if(is_email($_POST['email'])){ //If email is valid
					$email = $_POST['email'];
				}else{ //If email is invalid
					wp_send_json_error('<span class="license-status license-inactive fadeOutSlow" dir="'. $direction .'"><i class="license-status-icon"><img src="'. esc_url( plugin_dir_url( __DIR__ ) ) . 'admin/assets/invalid-icon.svg" /></i>'. esc_html__( 'Please enter a valid email', 'woo-save-abandoned-carts' ) .'</span>');
				}
			}else{ //If email input field is empty, sending it to default Administrator email
				$email = get_option( 'admin_email' );
			}
		}
		$this->send_reminder( $cart = false, $test = true, $email, $preview_data );
		wp_send_json_success('<span class="license-status license-active fadeOutSlow" dir="'. $direction .'"><i class="license-status-icon"><img src="'. esc_url( plugin_dir_url( __DIR__ ) ) . 'admin/assets/active-icon.svg" /></i>'. esc_html__( 'Email successfully sent', 'woo-save-abandoned-carts' ) .'</span>');
	}

	/**
	* Return email preview contents
	*
	* @since    7.0
	* @return   HTML
	*/
	public function email_preview(){
		if ( check_ajax_referer( 'preview_email', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing function
			wp_send_json_error(esc_html__( 'Looks like you are not allowed to do this.', 'woo-save-abandoned-carts' ));
		}

		$step_nr = false;

		if(isset($_POST['step'])){
			$step_nr = $_POST['step'];
		}

		$preview_data = array();

		if(isset($_POST)){
			$preview_data = $this->get_preview_data($_POST);
		}

		$contents = $this->get_reminder_contents( $cart = false, $test = true, $preview_data );
		wp_send_json_success( $contents );
	}

	/**
	* Retrieving input data that is used during email Preview and Testing
	*
	* @since    7.0
	* @return   array
	*/
	public function get_preview_data( $data ){
		$preview_data = array(
			'subject' 			=> isset($data['subject']) ? $data['subject'] : '',
			'heading' 			=> isset($data['main_title']) ? $data['main_title'] : '',
			'content' 			=> isset($data['content']) ? $data['content'] : '',
			'main_color' 		=> isset($data['main_color']) ? $data['main_color'] : '',
			'button_color'		=> isset($data['button_color']) ? $data['button_color'] : '',
			'text_color' 		=> isset($data['text_color']) ? $data['text_color'] : '',
			'background_color' 	=> isset($data['background_color']) ? $data['background_color'] : ''
		);

		return $preview_data;
	}

	/**
     * Building email reminder contents
     *
     * @since    7.0
     * @return   html
     * @param    object     $cart    		  	  Cart data. If false, then we must output
     * @param    boolean    $test                 Weather request triggered by Email test or preview function
     * @param    array    	$preview_data         Automation step input data passed from frontend to allow template preview
     */
	private function get_reminder_contents( $cart, $test = false, $preview_data = array() ){
		ob_start();
		echo $this->get_selected_template( $cart, $test, $preview_data );
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
     * Retrieve appropriate template for the automation
     *
     * @since    7.0
     * @return   html
     * @param    object     $cart    		  	  Cart data. If false, then we must output
     * @param    boolean    $test                 Weather request triggered by Email test or preview function
     * @param    array    	$preview_data         Automation step input data passed from frontend to allow template preview
     */
	private function get_selected_template( $cart, $test = false, $preview_data = array() ){
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$automation_steps = get_option('cartbounty_automation_steps');
		$step = $automation_steps[0];
		if($test){
			$step = $preview_data;
		}

		//Setting defaults
		$main_color = '#ffffff';
		$button_color = '#ff5e2d';
		$text_color = '#000000';
		$background_color = '#f2f2f2';
		$footer_color = '#353535';
		$border_color = '#e9e8e8';
		$recovery_link = wc_get_checkout_url();
		$unsubscribe_link = '';
		$heading = $this->get_defaults('heading', 0);
		$content = $this->get_defaults('content', 0);

		if(isset($step['main_color'])){
			if(!empty($step['main_color'])){
				$main_color = $step['main_color'];
				$border_color = $main_color;
			}
		}
		if(isset($step['button_color'])){
			if(!empty($step['button_color'])){
				$button_color = $step['button_color'];
			}
		}
		if(isset($step['text_color'])){
			if(!empty($step['text_color'])){
				$text_color = $step['text_color'];
			}
		}
		if(isset($step['background_color'])){
			if(!empty($step['background_color'])){
				$background_color = $step['background_color'];
				$footer_color = $public->invert_color($background_color);
			}
		}
		if(isset($step['heading'])){
			if( trim($step['heading']) != '' ){ //If the value is not empty and does not contain only whitespaces
				$heading = $admin->sanitize_field($step['heading']);
			}
		}
		if(isset($step['content'])){
			if( trim($step['content']) != '' ){ //If the value is not empty and does not contain only whitespaces
				$content = $admin->sanitize_field($step['content']);
			}
		}

		$store_location = new WC_Countries();
		$address_1 = array();
		$address_2 = array();
		//Support for WooCommerce versions prior 3.1.1 where method get_base_address didn't exist
		if(method_exists($store_location, 'get_base_address')){
			if($store_location->get_base_address()){
				$address_1[] = $store_location->get_base_address();
			}
			if($store_location->get_base_address_2()){
				$address_1[] = $store_location->get_base_address_2();
			}
		}
		if($store_location->get_base_city()){
			$address_1[] = $store_location->get_base_city();
		}
		if($store_location->get_base_state()){
			$address_2[] = $store_location->get_base_state();
		}
		if($store_location->get_base_postcode()){
			$address_2[] = $store_location->get_base_postcode();
		}
		if(WC()->countries->countries[$store_location->get_base_country()]){
			$address_2[] = WC()->countries->countries[$store_location->get_base_country()];
		}

		$store_address = array(
			'address_1' => implode(', ', $address_1),
			'address_2' => implode(', ', $address_2)
		);

		if(isset($cart)){ //If we have a cart
			if(!empty($cart)){
				$unsubscribe_link = $this->get_unsubscribe_url( $cart->email, $cart->session_id, $cart->id );
				$recovery_link = $admin->create_cart_url( $cart->email, $cart->session_id, $cart->id );
			}
		}

		$template_name = 'cartbounty-email-light.php';

		$args = array(
			'main_color' => $main_color,
			'button_color' => $button_color,
			'text_color' => $text_color,
			'background_color' => $background_color,
			'footer_color' => $footer_color,
			'border_color' => $border_color,
			'heading' => $heading,
			'content' => $content,
			'store_address' => $store_address,
			'recovery_link' => $recovery_link,
			'unsubscribe_link' => $unsubscribe_link,
			'current_year' => date('Y'),
			'test' => $test
		);

		ob_start();
		echo $public->get_template( $template_name, $args, false, plugin_dir_path( __FILE__ ) . '../templates/emails/');
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}

	/**
     * Generate Unsubscribe URL
     *
     * @since    7.0
     * @return   string
     * @param    string     $email   		    Cart email
     * @param    string     $session_id   		Cart Session ID
     * @param    integer    $cart_id   			Cart ID
     */
	public function get_unsubscribe_url( $email, $session_id, $cart_id ){
		$store_url = get_site_url();
		$hash = hash_hmac('sha256', $email . $session_id, CARTBOUNTY_ENCRYPTION_KEY) . '-' . $cart_id; //Creating encrypted hash with abandoned cart row ID in the end
		return $unsubscribe_url = $store_url . '?cartbounty=' . $hash .'&cartbounty-unsubscribe=1';
	}

	/**
     * Unsubscribe user from further WordPress emails about abandoned cart
     *
     * @since    7.0
     * @param    integer    $cart_id   			Cart ID
     */
	public function unsubscribe_user( $cart_id ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$cart_table}
				SET wp_unsubscribed = %d
				WHERE id = %d",
				1,
				$cart_id
			)
		);
	}

	/**
     * Update abandoned cart last sent time, completed steps and if the automation is completed or not
     *
     * @since    7.0
     * @param    object     $cart   		    Cart data
     */
	public function update_cart( $cart ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$active_steps = $this->get_active_steps();
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$cart_table}
				SET wp_steps_completed = %d,
				wp_complete = %s
				WHERE id = %d",
				$cart->wp_steps_completed + 1,
				true,
				$cart->id
			)
		);
	}

	/**
     * Add new email to sent emails table
     *
     * @since    7.0
     * @param    integer    $cart_id   			Cart ID
     * @param    string     $current_time       Current time
     */
	public function add_email( $cart_id, $current_time ){
		global $wpdb;

		//Making sure that the email table exists or is created
		if(!$this->email_table_exist()){
			$this->create_email_table();
		}

		$email_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME_EMAILS;

		$data = array(
			'cart' 				=> $cart_id,
			'time' 				=> $current_time,
		);
		$format = array(
			'%d',
			'%s',
		);
		$wpdb->insert($email_table, $data, $format);
		$this->increase_sent_emails();
	}

	/**
     * Increase the count of sent emails for a given automation step
     *
     * @since    7.0
     */
	public function increase_sent_emails(){
		$sent_emails = get_option('cartbounty_automation_sends');
		if(isset($sent_emails)){ //If we already have previous sent emails for the current step
			$sent_emails = $sent_emails + 1;
		}else{ //If this is the first email that is sent
			$sent_emails = 1;
		}
		update_option('cartbounty_automation_sends', $sent_emails);
	}

	/**
     * Retrieve sent email history of a given abandoned cart
     *
     * @since    7.0
     * @return   array
     * @param    integer    $cart_id   			Cart ID
     */
	public function get_email_history( $cart_id = false ){
		global $wpdb;
		if(!$this->email_table_exist()){
			$this->create_email_table();
		}
		$email_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME_EMAILS;
		$emails = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$email_table}
				WHERE cart = %s",
				$cart_id
			)
		);
		return $emails;
	}

	/**
     * Output a list of sent emails for a given cart
     *
     * @since    7.0
     * @return   array
     * @param    integer    $cart_id   			Cart ID
     */
	public function display_email_history( $cart_id = false ){
		$emails = $this->get_email_history( $cart_id );
		$output = '';
		if($emails){
			$output .= '<em class="cartbounty-email-history-list">';
			foreach ($emails as $key => $email){
				$time = new DateTime($email->time);
				$output .= '<i class="cartbounty-email-history-item"><i class="cartbounty-automation-number">1</i>' . esc_html( $time->format('M d, Y H:i') ) . '</i>';
			}
			$output .= '</em>';
		}
		return $output;
	}

	/**
	* Return email preview modal container
	*
	* @since    7.0
	* @return   HTML
	*/
	public function output_modal_container(){
		$output = '';
		$output .= '<div class="cartbounty-modal" id="cartbounty-modal" aria-hidden="true">';
			$output .= '<div class="cartbounty-modal-overlay" tabindex="-1" data-micromodal-close>';
				$output .= '<div class="cartbounty-modal-content-container" role="dialog" aria-modal="true">';
					$output .= '<button type="button" class="cartbounty-close-modal" aria-label="'. esc_html__("Close", 'woo-save-abandoned-carts') .'" data-micromodal-close></button>';
					$output .= '<div class="cartbounty-modal-content" id="cartbounty-modal-content"></div>';
				$output .= '</div>';
			$output .= '</div>';
		$output .= '</div>';
		return $output;
	}

	/**
	 * Return abandoned carts waiting in the given automation step queue.
	 *
	 * @since    7.0
	 * @return   string
	 */
	public function get_queue() {
		global $wpdb;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time = $admin->get_time_intervals();
		$count = 0;
		
		$carts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, email, session_id, time, wp_steps_completed
				FROM {$cart_table}
				WHERE type = %d AND
				email != '' AND
				cart_contents != '' AND
				wp_unsubscribed != 1 AND
				wp_complete != 1 AND
				wp_steps_completed = %d AND
				time < %s AND
				time > %s",
				$admin->get_cart_type('abandoned'),
				0,
				$time['cart_abandoned'],
				$time['maximum_sync_period']
			)
		);

		$automation_steps = get_option('cartbounty_automation_steps');
		foreach ($automation_steps as $key => $step) { //Looping through automation steps
			if(0 == $key){ //If current step must be complete
				$count = count($carts);
			}
		}
		return $count;
	}

	/**
     * Return sent reminder email count
     *
     * @since    7.0
     */
	public function get_stats(){
		$count = 0;
		$sent_emails = get_option('cartbounty_automation_sends');
		if($sent_emails > 0){
			$count = $sent_emails;
		}
		return $count;
	}

	/**
     * Returning WordPress automation defaults
     *
     * @since    7.0
     * @return   array or string
     * @param    string     $value    		  	  Value to return
     * @param    integer    $automation    		  Automation number
     */
	public function get_defaults( $value = false, $automation = false ){
		$defaults = array();
		switch ( $automation ) {
			case 0:

				$defaults = array(
					'name'			=> esc_html__( 'First email', 'woo-save-abandoned-carts' ),
					'subject'		=> esc_attr__( 'Forgot something? 🙈', 'woo-save-abandoned-carts' ),
					'heading'		=> esc_attr__( 'Forgot to complete your purchase? 🙈', 'woo-save-abandoned-carts' ),
					'content'		=> esc_attr__( 'We noticed that you placed some nice items in your cart. Would you like to complete your order?', 'woo-save-abandoned-carts' ),
					'interval'		=> 5
				);

				break;

			case 1:

				$defaults = array(
					'name'			=> esc_html__( 'Second email', 'woo-save-abandoned-carts' ),
					'subject'		=> esc_attr__( 'Return to complete your checkout!', 'woo-save-abandoned-carts' ),
					'heading'		=> esc_attr__( 'Still thinking it over?', 'woo-save-abandoned-carts' ),
					'content'		=> esc_attr__( 'We are keeping items in your cart reserved for you, but do not wait too long or they will expire 😇.', 'woo-save-abandoned-carts' ),
					'interval'		=> 1440
				);
			
				break;

			case 2:

				$defaults = array(
					'name'			=> esc_html__( 'Third email', 'woo-save-abandoned-carts' ),
					'subject'		=> esc_attr__( 'Your cart is about to expire! 🛒', 'woo-save-abandoned-carts' ),
					'heading'		=> esc_attr__( 'Last chance to save your cart! 🛒', 'woo-save-abandoned-carts' ),
					'content'		=> esc_attr__( 'Goodbyes are never easy, but this is our last reminder. Products in your shopping cart will expire unless you take them with you.', 'woo-save-abandoned-carts' ),
					'interval'		=> 2880
				);
			
				break;
		}

		if($value){ //If a single value should be returned
			if(isset($defaults[$value])){ //Checking if value exists
				$defaults = $defaults[$value];
			}
		}

		return $defaults;
	}

	/**
     * Method returns available time intervals alongside a selected interval for a given automation or just the name of the selected interval if $selected_name set to true
     *
     * @since    7.0
     * @return   array or string
     * @param    integer    $automation    		  Automation number
     * @param    boolean    $selected_name    	  Should just the name of the selected Interval be returned
     */
	public function get_intervals( $automation = false, $selected_name = false ){
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$automation_steps = get_option('cartbounty_automation_steps');
		$minutes = array(5, 10, 15, 20, 25, 30, 40, 50, 60, 120, 180, 240, 300, 360, 420, 480, 540, 600, 660, 720, 1080, 1440, 2880, 4320, 5760, 7200, 8640, 10080); //Defining array of minutes

		if(isset($automation_steps[$automation])){
			$automation_step = $automation_steps[$automation];
		}

		if(!empty($automation_step['interval'])){ //If custom interval is not set, fallback to default
			$selected_interval = $automation_step['interval'];
		}else{
			$selected_interval = $this->get_defaults( 'interval', $automation );
		}

		$intervals = $admin->prepare_time_intervals( $minutes );
		
		if($selected_name){ //In case just the selected name should be returned
			return $intervals[$selected_interval];

		}else{
			return array(
				'intervals'  => $intervals,
				'selected'   => $selected_interval
			);
		}
	}

	/**
	 * Method displays available time intervals at which we are sending out abandoned carts
	 *
	 * @since    7.0
	 * @return   string
	 * @param    integer    $automation    		  Automation number
	 */
	public function display_intervals( $automation = false ){
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$array = $this->get_intervals( $automation );
		echo '<select id="cartbounty-automation-interval-'. esc_attr( $automation ) .'" class="cartbounty-select" name="cartbounty_automation_steps['. esc_attr( $automation ) .'][interval]" autocomplete="off" '. $admin->disable_field() .'>';
			foreach( $array['intervals'] as $key => $interval ){
				echo "<option value='". esc_attr( $key ) ."' ". selected( $array['selected'], $key, false ) .">". esc_html( $interval ) ."</option>";
			}
		echo '</select>';
	}

	/**
	 * Check if a given automation is enabled
	 *
	 * @since    7.0
	 * @return   array
	 * @param    integer      $automation    		      WordPress automation step. If not provided, an array of enabled automation steps returned
	 */
	public function get_active_steps( $automation = false ){
		$result = false;
		$option = 'cartbounty_automation_steps';
		$this->restore_steps( $option );
		$automation_steps = get_option( $option );
		$result = array();
		foreach ($automation_steps as $key => $step) {
			if(isset($step['enabled'])){
				$result[] = $key;
			}
		}
		return $result;
	}

	/**
	* Method validates WordPress automation step data
	*
	* @since    7.0.2
	*/
	public function validate_automation_steps(){
		if(!isset($_POST['cartbounty_automation_steps'])){ //Exit in case the automation step data is not present
			return;
		}
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$steps = $_POST['cartbounty_automation_steps'];

		foreach ($steps as $key => $step) {

			//Sanitizing Subject
			if(isset($step['subject'])){
				$steps[$key]['subject'] = $admin->sanitize_field($step['subject']);
			}
			//Sanitizing Heading
			if(isset($step['heading'])){
				$steps[$key]['heading'] = $admin->sanitize_field($step['heading']);
			}
			//Sanitizing Content
			if(isset($step['content'])){
				$steps[$key]['content'] = $admin->sanitize_field($step['content']);
			}
		}

		update_option('cartbounty_automation_steps', $steps);
	}

	/**
	 * Creating database table to save email history of emails delivered by WordPress automation
	 *
	 * @since    7.0
	 */
	public static function create_email_table(){
		global $wpdb;
		$email_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME_EMAILS;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $email_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			cart BIGINT(20) NOT NULL,
			time DATETIME DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		//Resets table Auto increment index to 1
		$sql = "ALTER TABLE $email_table AUTO_INCREMENT = 1";
		dbDelta( $sql );
		
		update_option('cartbounty_email_table_exists', 1); //Updating status and telling that table has been created
		return;
	}

	/**
	 * Method checks if WordPress email table has been created
	 *
	 * @since    7.0
	 */
	public function email_table_exist(){
		$email_table_exists = get_option('cartbounty_email_table_exists');
		if(empty($email_table_exists)){
			return false;
		}else{
			return true;
		}
	}

	/**
	* Method sanitizes "From" field
	*
	* @since    7.0.2
	*/
	public function sanitize_from_field(){
		if(!isset($_POST['cartbounty_automation_from_name'])){ //Exit in case the field is not present in the request
			return;
		}
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		update_option('cartbounty_automation_from_name', $admin->sanitize_field($_POST['cartbounty_automation_from_name']));
	}

	/**
	* Method restores automation steps in case they are cleared, deleted or do not exist
	*
	* @since    7.1.2.2
	* @param    string		$option   		    Option name
	*/
	private function restore_steps( $option ){
		$automation_steps = get_option( $option );
		if( empty( $automation_steps ) ){
			update_option( $option,
				array(
					array(
						'subject' => $this->get_defaults( 'subject', 0 ),
						'heading' => $this->get_defaults( 'heading', 0 ),
						'content' => $this->get_defaults( 'content', 0 )
					),
					1,
					1
				)
			);
		}
	}
}