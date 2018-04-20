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
	 * @since    1.0.0
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
	 * @since    1.0.0
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
			<div id="woocommerce-live-checkout-field-capture-go-pro">
				<div id="woocommerce-live-checkout-field-capture-go-pro-header-image">
					<a href="<?php echo WCLCFC_LICENSE_SERVER_URL; ?>" title="Get Woocommerce Live Checkout Field Capture Pro" target="_blank">
						<img src="<?php echo plugins_url( 'assets/e-mail-notification.svg', __FILE__ ) ; ?>" title=""/>
					</a>
				</div>
				<div id="woocommerce-live-checkout-field-capture-go-pro-content">
					<h2>Would you like to receive e-mail notifications about Abandoned carts?</h2>
					<p>Save your time by enabling e-mail notifications and work on your business instead.</p>
					<p id="woocommerce-live-checkout-field-capture-button-row">
						<a href="<?php echo WCLCFC_LICENSE_SERVER_URL; ?>" class="button" target="_blank">Get Pro</a>
						<span id="woocommerce-live-checkout-field-capture-close">Not now</span>
					</p>
				</div>
			</div>
			<?php echo $this->draw_bubble(); ?>
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
	 * Show Go Pro Tooltip bubble once every 15 days
	 *
	 * @since    1.1.0
	 */
	function draw_bubble(){
		if ($this->abandoned_cart_count() > 1): ?>
			<script>
				jQuery(document).ready(function($) {
					var days = 15; // How often to show the popup
					var now = new Date().getTime();
					var setupTime = localStorage.getItem('setupTime');
					var bubble = $('#woocommerce-live-checkout-field-capture-go-pro');
					var close = $('#woocommerce-live-checkout-field-capture-close');
					
					if (setupTime == null || setupTime == 0) { // Shows for the first time or when expired time
						//Function loads the bubble after a given time period in seconds	
						setTimeout(function() {
							bubble.css({top:"60px", right: "50px"});
						}, 3000);
					} else {
						if(now-setupTime > days * 24 * 60 * 60 * 1000) { // If the time has expired, clear the cookie
							localStorage.setItem('setupTime', 0);
						}
					}
					
					//Handles close button action
					close.click(function(){
						bubble.css({top:"-400px", right: "50px"});
						localStorage.setItem('setupTime', now); //Hides the Bubble after click and stops showing until cookies deleted or time expires
					});
				});
			</script>
	<?php endif;
	}


	/**
	 * Count abandoned carts
	 *
	 * @since    1.1.0
	 */
	function abandoned_cart_count(){
		global $wpdb;
        $table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        return $total_items;
	}


	
}