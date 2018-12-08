<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce Live Checkout Field Capture
 * @subpackage WooCommerce Live Checkout Field Capture/admin
 * @author     Streamline.lv
 */
class WooCommerce_Live_Checkout_Field_Capture_Admin{

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ){

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0
	 */
	public function enqueue_styles(){

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-live-checkout-field-capture-admin-admin.css', array(), $this->version, 'all' );
	}	
	
	/**
	 * Register the menu under WooCommerce admin menu.
	 *
	 */
	function register_menu(){
		//Check if WooCommerce plugin is active
		//If the plugin is active - output menu under WooCommerce
		//Else output the menu as a Page
		if(class_exists('WooCommerce')){
			add_submenu_page( 'woocommerce', __('WooCommerce Live Checkout Field Capture', WCLCFC_TEXT_DOMAIN), __('Checkout Field Capture', WCLCFC_TEXT_DOMAIN), 'list_users', 'wclcfc', array($this,'register_menu_options'));			
		}else{
			add_menu_page( __('WooCommerce Live Checkout Field Capture', WCLCFC_TEXT_DOMAIN), __('Checkout Field Capture', WCLCFC_TEXT_DOMAIN), 'list_users', 'wclcfc', array($this,'register_menu_options'), 'dashicons-archive' );
		}
	}

	/**
	 * Adds newly abandoned cart count to the menu
	 *
	 * @since    1.4
	 */
	function menu_abandoned_count(){
		global $wpdb, $submenu;
		$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME;
		
		if ( isset( $submenu['woocommerce'] ) ) { //If WooCommerce Menu exists
			
			//Counting newly abandoned carts
			$order_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id) FROM ". $table_name ."
					WHERE 
					time < (NOW() - INTERVAL %d MINUTE) AND 
					time > (NOW() - INTERVAL %d MINUTE)"
				, 0, 120 )
			);
			
			foreach ( $submenu['woocommerce'] as $key => $menu_item ) { //Go through all Sumenu sections of WooCommerce and look for Checkout Field Capture Pro
				if ( 0 === strpos( $menu_item[0], __('Checkout Field Capture', WCLCFC_TEXT_DOMAIN))) {
					$submenu['woocommerce'][$key][0] .= ' <span class="new-abandoned update-plugins count-' . $order_count . '">' .  $order_count .'</span>';
				}
			}
		}
	}
	
	/**
	 * Register the menu options for admin area.
	 *
	 * @since    1.3
	 */
	function register_menu_options(){
		global $wpdb;
		$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME;
		
		if ( !current_user_can( 'list_users' )){
			wp_die( __( 'You do not have sufficient permissions to access this page.', WCLCFC_TEXT_DOMAIN ) );
		}
		
		//Our class extends the WP_List_Table class, so we need to make sure that it's there
		//Prepare Table of elements
		require_once plugin_dir_path( __FILE__ ) . 'class-woocommerce-live-checkout-field-capture-admin-table.php';
		$wp_list_table = new WooCommerce_Live_Checkout_Field_Capture_Table();
		$wp_list_table->prepare_items();
		
		//Output table contents
		 $message = '';
		if ('delete' === $wp_list_table->current_action()) {
			if(is_array($_REQUEST['id'])){ //If deleting multiple lines from table
				$deleted_row_count = esc_html(count($_REQUEST['id']));
			}
			else{ //If a single row is deleted
				$deleted_row_count = 1;
			}
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', WCLCFC_TEXT_DOMAIN ), $deleted_row_count ) . '</p></div>';

		}
		?>
		<div class="wrap">
			<h1 id="woocommerce-live-checkout-field-capture-title"><?php echo __('WooCommerce Live Checkout Field Capture', WCLCFC_TEXT_DOMAIN); ?></h1>
			<?php do_action('wclcfc_after_page_title'); ?>
			<?php echo $message;
			if ($this->abandoned_cart_count() == 0): //If no abandoned carts, then output this note?>
				<p>
					<?php echo __( 'Looks like you do not have any saved Abandoned carts yet.<br/>But do not worry, as soon as someone fills the <strong>Email</strong> or <strong>Phone number</strong> fields of your WooCommerce Checkout form and abandons the cart, it will automatically appear here.', WCLCFC_TEXT_DOMAIN); ?>
				</p>
			<?php else: ?>
				<form id="wclcfc-table" method="GET">
					<input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
					<?php $wp_list_table->display() ?>
				</form>
			<?php endif; ?>
		</div>
	<?php
	}

	/**
	 * Count abandoned carts
	 *
	 * @since    1.1
	 */
	function abandoned_cart_count(){
		global $wpdb;
        $table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        return $total_items;
	}

	/**
	 * Adds custom action link on Plugin page under plugin name
	 *
	 * @since    1.2
	 */
	function add_plugin_action_links( $actions, $plugin_file ){
		if ( ! is_array( $actions ) ) {
			return $actions;
		}
		
		$action_links = array();
		$action_links['wlcfc_get_pro'] = array(
			'label' => __('Get Pro', WCLCFC_TEXT_DOMAIN),
			'url'   => WCLCFC_LICENSE_SERVER_URL . '?utm_source=' . urlencode(get_bloginfo('url')) . '&utm_medium=plugin_link&utm_campaign=wclcfc'
		);

		return $this->add_display_plugin_action_links( $actions, $plugin_file, $action_links, 'before' );
	}

	/**
	 * Function that merges the links on Plugin page under plugin name
	 *
	 * @since    1.2
	 * @return array
	 */
	function add_display_plugin_action_links( $actions, $plugin_file, $action_links = array(), $position = 'after' ){
		static $plugin;
		if ( ! isset( $plugin ) ) {
			$plugin = WCLCFC_BASENAME;
		}
		if ( $plugin === $plugin_file && ! empty( $action_links ) ) {
			foreach ( $action_links as $key => $value ) {
				$link = array( $key => '<a href="' . $value['url'] . '">' . $value['label'] . '</a>' );
				if ( 'after' === $position ) {
					$actions = array_merge( $actions, $link );
				} else {
					$actions = array_merge( $link, $actions );
				}
			}
		}
		return $actions;
	}
	
	/**
	 * Function calculates if time has passed since the given time period (In days)
	 *
	 * $option	= Option from WordPress database
	 * $days	= Number that defines days
	 *
	 * @since    1.3
	 * @return Boolean
	 */
	 
	function days_have_passed($option, $days){
		$last_time = esc_attr(get_option($option)); //Get time value from the database
		$last_time = strtotime($last_time); //Convert time from text to Unix timestamp
		
		$date = date_create(current_time( 'mysql', false ));
		$current_time = strtotime(date_format($date, 'Y-m-d H:i:s'));
		$days = $days; //Defines the time interval that should be checked against in days
		
		if($last_time < $current_time - $days * 24 * 60 * 60 ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Function checks the current plugin version with the one saved in database
	 *
	 * @since    1.4.1
	 */
	function check_current_plugin_version(){
		$plugin = new WooCommerce_Live_Checkout_Field_Capture();
		$current_version = $plugin->get_version();
		
		if ($current_version == get_option('wclcfc_version_number')){ //If database version is equal to plugin version. Not updating database
			return;
		}else{ //Versions are different and we must update the database
			update_option('wclcfc_version_number', $current_version);
			activate_woocommerce_live_checkout_field_capture(); //Function that updates the database
			return;
		}
	}

	/**
	 * Function outputs bubble content
	 *
	 * @since    1.4.2
	 */
	function output_bubble_content(){ ?>
		<div id="woocommerce-live-checkout-field-capture-bubbles">
			<?php if(!get_option('wclcfc_review_submitted')): //Don't output Review bubble if review has been left ?>
				<div id="woocommerce-live-checkout-field-capture-review" class="woocommerce-live-checkout-field-capture-bubble">
					<div class="woocommerce-live-checkout-field-capture-header-image">
						<a href="<?php echo WCLCFC_REVIEW_LINK; ?>" title="<?php echo __('Leave WooCommerce Live Checkout Field Capture a 5-star rating', WCLCFC_TEXT_DOMAIN ); ?>" target="_blank">
							<img src="<?php echo plugins_url( 'assets/review-notification.gif', __FILE__ ) ; ?>" alt="" title=""/>
						</a>
					</div>
					<div id="woocommerce-live-checkout-field-capture-review-content">
						<?php
							//Outputing different expressions depending on the amount of captured carts
							if($this->total_captured_abandoned_cart_count() <= 10){
								$expression = __('Congrats!', WCLCFC_TEXT_DOMAIN);
							}elseif($this->total_captured_abandoned_cart_count() <= 30){
								$expression = __('Awesome!', WCLCFC_TEXT_DOMAIN);
							}elseif($this->total_captured_abandoned_cart_count() <= 100){
								$expression = __('Amazing!', WCLCFC_TEXT_DOMAIN);
							}elseif($this->total_captured_abandoned_cart_count() <= 300){
								$expression = __('Incredible!', WCLCFC_TEXT_DOMAIN);
							}elseif($this->total_captured_abandoned_cart_count() <= 500){
								$expression = __('Crazy!', WCLCFC_TEXT_DOMAIN);
							}
							else{
								$expression = __('Fantastic!', WCLCFC_TEXT_DOMAIN);
							}
						?>
						<h2><?php echo sprintf(__('%s You have already captured %d abandoned carts!', WCLCFC_TEXT_DOMAIN ), $expression, $this->total_captured_abandoned_cart_count()); ?></h2>
						<p><?php echo __('If you like our plugin, please leave us a 5-star rating. It is the fastest way to help us grow and keep improving it further.', WCLCFC_TEXT_DOMAIN ); ?></p>
						<div class="woocommerce-live-checkout-field-capture-button-row">
							<form method="post" action="options.php" class="wclcfc_inline">
								<?php settings_fields( 'wclcfc-settings-review' ); ?>
								<a href="<?php echo WCLCFC_REVIEW_LINK; ?>" class="button" target="_blank"><?php echo __("Let's do this", WCLCFC_TEXT_DOMAIN ); ?></a>
								<?php submit_button(__('Done that', WCLCFC_TEXT_DOMAIN), 'woocommerce-live-checkout-field-capture-review-submitted', false, false); ?>
								<input id="wclcfc_review_submitted" type="hidden" name="wclcfc_review_submitted" value="1" />
							</form>
							<form method="post" action="options.php" class="wclcfc_inline">
								<?php settings_fields( 'wclcfc-settings-declined' ); ?>
								<?php submit_button(__('Close', WCLCFC_TEXT_DOMAIN), 'woocommerce-live-checkout-field-capture-close', false, false); ?>
								<input id="wclcfc_times_review_declined" type="hidden" name="wclcfc_times_review_declined" value="<?php echo get_option('wclcfc_times_review_declined') + 1; // Retrieving how many times review has been declined and updates the count in database by one ?>" />
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<div id="woocommerce-live-checkout-field-capture-go-pro" class="woocommerce-live-checkout-field-capture-bubble">
				<div class="woocommerce-live-checkout-field-capture-header-image">
					<a href="<?php echo WCLCFC_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=bubble&utm_campaign=wclcfc" title="<?php __('Get WooCommerce Live Checkout Field Capture Pro', WCLCFC_TEXT_DOMAIN); ?>" target="_blank">
						<img src="<?php echo plugins_url( 'assets/notification-email.gif', __FILE__ ) ; ?>" alt="" title=""/>
					</a>
				</div>
				<div id="woocommerce-live-checkout-field-capture-go-pro-content">
					<form method="post" action="options.php">
						<?php settings_fields( 'wclcfc-settings-time' ); ?>
						<h2><?php echo __('Would you like to get notified about abandoned carts and send automated cart recovery emails?', WCLCFC_TEXT_DOMAIN ); ?></h2>
						<p><?php echo __('Save your time by enabling Pro features and focus on your business instead.', WCLCFC_TEXT_DOMAIN ); ?></p>
						<p class="woocommerce-live-checkout-field-capture-button-row">
							<a href="<?php echo WCLCFC_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=bubble&utm_campaign=wclcfc" class="button" target="_blank"><?php echo __('Get Pro', WCLCFC_TEXT_DOMAIN); ?></a>
							<?php submit_button(__('Not now', WCLCFC_TEXT_DOMAIN), 'woocommerce-live-checkout-field-capture-close', false, false); ?>
						</p>
						<input id="wclcfc_last_time_bubble_displayed" type="hidden" name="wclcfc_last_time_bubble_displayed" value="<?php echo current_time('mysql'); //Set activation time when we last displayed the bubble to current time so that next time it would display after a specified period of time ?>" />
					</form>
				</div>
			</div>
			<?php echo $this->draw_bubble(); ?>
		</div>
		<?php
	}

	/**
	 * Show bubble slide-out window
	 *
	 * @since 	1.3
	 */
	function draw_bubble(){

		//Checking if we should display the Review bubble or Get Pro bubble
		//Displaying review bubble after 10, 30, 100, 300, 500 and 1000 abandoned carts have been captured and if the review has not been submitted
		if(
			($this->total_captured_abandoned_cart_count() > 9 && get_option('wclcfc_times_review_declined') < 1 && !get_option('wclcfc_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 29 && get_option('wclcfc_times_review_declined') < 2 && !get_option('wclcfc_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 99 && get_option('wclcfc_times_review_declined') < 3 && !get_option('wclcfc_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 299 && get_option('wclcfc_times_review_declined') < 4 && !get_option('wclcfc_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 499 && get_option('wclcfc_times_review_declined') < 5 && !get_option('wclcfc_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 999 && get_option('wclcfc_times_review_declined') < 6 && !get_option('wclcfc_review_submitted'))
		){
			$bubble_type = '#woocommerce-live-checkout-field-capture-review';
			$display_bubble = true; //Show the bubble
		}elseif($this->total_captured_abandoned_cart_count() > 5 && $this->days_have_passed('wclcfc_last_time_bubble_displayed', 18 )){ //If we have more than 5 abandoned carts or the user has deleted more than 10 abandoned carts the last time bubble was displayed was 18 days ago, display the bubble info about Pro version
			$bubble_type = '#woocommerce-live-checkout-field-capture-go-pro';
			$display_bubble = true; //Show the bubble
		}else{
			$display_bubble = false; //Don't show the bubble just yet
		}
		
		if($display_bubble){ //Check ff we should display the bubble ?>
			<script>
				jQuery(document).ready(function($) {
					var bubble = $(<?php echo "'". $bubble_type ."'"; ?>);
					var close = $('.woocommerce-live-checkout-field-capture-close, .woocommerce-live-checkout-field-capture-review-submitted');
					
					//Function loads the bubble after a given time period in seconds	
					setTimeout(function() {
						bubble.css({top:"60px", right: "50px"});
					}, 2500);
						
					//Handles close button action
					close.click(function(){
						bubble.css({top:"-600px", right: "50px"});
					});
				});
			</script>
			<?php
		}else{
			//Do nothing
			return;
		}
	}

	/**
	 * Returns count of total captired abandoned carts
	 *
	 * @since 	2.1
	 * @return 	number
	 */
	function total_captured_abandoned_cart_count(){
		if ( false === ( $captured_abandoned_cart_count = get_transient( 'wclcfc_captured_abandoned_cart_count' ))){ //If value is not cached or has expired
			$captured_abandoned_cart_count = get_option('wclcfc_captured_abandoned_cart_count');
			set_transient( 'wclcfc_captured_abandoned_cart_count', $captured_abandoned_cart_count, 60 * 10 ); //Temporary cache will expire in 10 minutes
		}
		
		return $captured_abandoned_cart_count;
	}

	/**
	 * Sets the path to language folder for internationalization
	 *
	 * @since 	2.1
	 */
	function wclcfc_text_domain(){
		return load_plugin_textdomain( WCLCFC_TEXT_DOMAIN, false, basename( plugin_dir_path( __DIR__ ) ) . '/languages' );
	}

}