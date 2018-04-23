<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 * @author     Your Name <email@example.com>
 */
class Woocommerce_Live_Checkout_Field_Capture_Admin {

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
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0
	 */
	public function enqueue_styles() {

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
	 * Register the menu under Woocommerce admin menu.
	 *
	 */
	function woocommerce_live_checkout_field_capture_menu() {
		//Check if Woocommerce plugin is active
		//If the plugin is active - output menu under Woocommerce
		//Else output the menu as a Page
		if(class_exists('WooCommerce')){
			add_submenu_page( 'woocommerce', 'Woocommerce Live Checkout Field Capture', 'Checkout Field Capture', 'manage_options', 'wclcfc', array($this,'woocommerce_live_checkout_field_capture_menu_options'));			
		}else{
			add_menu_page( 'Woocommerce Live Checkout Field Capture', 'Checkout Field Capture', 'manage_options', 'wclcfc', array($this,'woocommerce_live_checkout_field_capture_menu_options'), 'dashicons-archive' );
		}
	}
	
	
	
	/**
	 * Register the menu options for admin area.
	 *
	 * @since    1.3
	 */
	function woocommerce_live_checkout_field_capture_menu_options() {
		global $wpdb;
		$table_name = $wpdb->prefix . WCLCFC_TABLE_NAME;
		
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		//Our class extends the WP_List_Table class, so we need to make sure that it's there
		//Prepare Table of elements
		require_once plugin_dir_path( __FILE__ ) . 'class-woocommerce-live-checkout-field-capture-admin-table.php';
		$wp_list_table = new Woocommerce_Live_Checkout_Field_Capture_Table();
		$wp_list_table->prepare_items();
		
		//Output table contents
		 $message = '';
		if ('delete' === $wp_list_table->current_action()) {
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'custom_table_example'), esc_html(count($_REQUEST['id']))) . '</p></div>';
		}
		?>
		<div class="wrap">
			<div id="woocommerce-live-checkout-field-capture-bubbles">
				<div id="woocommerce-live-checkout-field-capture-review" class="woocommerce-live-checkout-field-capture-bubble">
					<div class="woocommerce-live-checkout-field-capture-header-image">
						<a href="<?php echo WCLCFC_REVIEW_LINK; ?>" title="Get Woocommerce Live Checkout Field Capture Pro" target="_blank">
							<img src="<?php echo plugins_url( 'assets/review-notification.svg', __FILE__ ) ; ?>" title=""/>
						</a>
					</div>
					<div id="woocommerce-live-checkout-field-capture-review-content">
						<form method="post" action="options.php">
							<?php settings_fields( 'wclcfc-settings-group' ); ?>
							<h2>Would you mind leaving us a positive review?</h2>
							<p>Your review is the simplest way to help us continue providing a great product, improve it, and help others to make confident decisions.</p>
							<div class="woocommerce-live-checkout-field-capture-button-row">
								<a href="<?php echo WCLCFC_REVIEW_LINK; ?>" class="button" target="_blank">Sure, I'd love to</a>
								<?php submit_button('Done that'); ?>
								<span id="woocommerce-live-checkout-field-capture-close-review" class="woocommerce-live-checkout-field-capture-close">Close</span>
							</div>
							<input id="wclcfc_review_submitted" type="hidden" name="wclcfc_review_submitted" value="1" />
						</form>
					</div>
				</div>
				<div id="woocommerce-live-checkout-field-capture-go-pro" class="woocommerce-live-checkout-field-capture-bubble">
					<div class="woocommerce-live-checkout-field-capture-header-image">
						<a href="<?php echo WCLCFC_LICENSE_SERVER_URL; ?>" title="Get Woocommerce Live Checkout Field Capture Pro" target="_blank">
							<img src="<?php echo plugins_url( 'assets/e-mail-notification.svg', __FILE__ ) ; ?>" title=""/>
						</a>
					</div>
					<div id="woocommerce-live-checkout-field-capture-go-pro-content">
						<h2>Would you like to receive e-mail notifications about Abandoned carts?</h2>
						<p>Save your time by enabling e-mail notifications and work on your business instead.</p>
						<p class="woocommerce-live-checkout-field-capture-button-row">
							<a href="<?php echo WCLCFC_LICENSE_SERVER_URL; ?>" class="button" target="_blank">Get Pro</a>
							<span id="woocommerce-live-checkout-field-capture-close-go-pro" class="woocommerce-live-checkout-field-capture-close">Not now</span>
						</p>
					</div>
				</div>
				<?php echo $this->draw_bubble(); ?>
			</div>
			<h1 id="woocommerce-live-checkout-field-capture-title">Wooommerce Live Checkout Field Capture</h1>
			<?php echo $message; 
			if ($this->abandoned_cart_count() == 0): //If no abandoned carts, then output this note?>
				<p>Well, well, well, looks like you don’t have any saved Abandoned carts yet.<br/>But don’t worry, as soon as someone will fill the <strong>Email field</strong> of your WooCommerce Checkout form and abandon the cart, it will automatically appear here.</p>
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
	 * Show bubble slide-out window
	 *
	 * @since    1.3
	 */
	function draw_bubble(){
		//Checking if we should display the Review bubble or Get Pro bubble
		if(($this->abandoned_cart_count() > 7) && woocommerce_live_checkout_field_capture_days_have_passed('wclcfc_plugin_activation_time', 30) && (!get_option('wclcfc_review_submitted'))){ //If 30 days since plugin activation have passed and we have at least 8 abandoned carts captured
			$bubble_type = '#woocommerce-live-checkout-field-capture-review';
			update_option('wclcfc_plugin_activation_time', current_time('mysql')); // Reset time when we last displayed the bubble (sets current time)
			$display_bubble = true; //Let us show the bubble
		}elseif(($this->abandoned_cart_count() > 5) && (woocommerce_live_checkout_field_capture_days_have_passed('wclcfc_last_time_bubble_displayed', 18))){ //If we have more than 5 abandoned carts and the last time bubble was displayed was 18 days ago, display the bubble info about Pro version
			$bubble_type = '#woocommerce-live-checkout-field-capture-go-pro';
			update_option('wclcfc_last_time_bubble_displayed', current_time('mysql')); // Reset time when we last displayed the bubble (sets current time)
			$display_bubble = true; //Let us show the bubble
		}else{
			$display_bubble = false; //Don't show the bubble just yet
		}
		
		if($display_bubble){ //Check ff we should display the bubble ?>
			<script>
				jQuery(document).ready(function($) {
					var bubble = $(<?php echo "'". $bubble_type ."'"; ?>);
					var close = $('#woocommerce-live-checkout-field-capture-close-go-pro, #woocommerce-live-checkout-field-capture-review form p.submit .button, #woocommerce-live-checkout-field-capture-close-review');
					
					//Function loads the bubble after a given time period in seconds	
					setTimeout(function() {
						bubble.css({top:"60px", right: "50px"});
					}, 3000);
						
					//Handles close button action
					close.click(function(){
						bubble.css({top:"-500px", right: "50px"});
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
}