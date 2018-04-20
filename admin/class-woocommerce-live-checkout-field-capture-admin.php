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
			add_submenu_page( 'woocommerce', 'Woocommerce Live Checkout Field Capture', 'Checkout Field Capture', 'manage_options', 'wclcfc', 'woocommerce_live_checkout_field_capture_menu_options' );			
		}else{
			add_menu_page( 'Woocommerce Live Checkout Field Capture', 'Checkout Field Capture', 'manage_options', 'wclcfc', 'woocommerce_live_checkout_field_capture_menu_options', 'dashicons-archive' );
		}
	}
}

/**
 * Register the menu options for admin area.
 *
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
		<h1>Woocommerce Live Checkout Field Capture</h1>
		<?php echo $message; ?>
		<form id="wclcfc-table" method="GET">
			<input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
			<?php $wp_list_table->display() ?>
		</form>
	</div>
<?php
}

	