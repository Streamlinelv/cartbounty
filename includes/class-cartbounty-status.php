<?php
/**
 * The System status class.
 *
 * Used to define privacy related information
 *
 *
 * @since      6.1.2
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/includes
 * @author     Streamline.lv
 */
class CartBounty_System_Status{

	/**
	 * The ID of this plugin.
	 *
	 * @since    6.1.2
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    6.1.2
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    6.1.2
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    		  The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ){
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Output system status information
	 *
	 * @since    6.1.2
	 * @return   HTML
	 */
	public function get_system_status(){
		if ( check_ajax_referer( 'get_system_status', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing the function
	        wp_send_json_error(esc_html__( 'Looks like you are not allowed to do this.', 'woo-save-abandoned-carts' ));
	    }

		global $wpdb;
		$carts = array();
		$all_plugins = array();
		$active_recovery = array();
		$exit_intent_options = array();
		$settings = array();
		$missing_hooks = array();
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$wordpress = new CartBounty_WordPress();
		$overrides = $admin->get_template_overrides();
		
		if(get_option('cartbounty_recoverable_cart_count')){
			$carts[] = esc_html__('Recoverable', 'woo-save-abandoned-carts' ) .': '. esc_html( get_option('cartbounty_recoverable_cart_count') );
		}
		if(get_option('cartbounty_ghost_cart_count')){
			$carts[] = esc_html__('Ghost', 'woo-save-abandoned-carts' ) .': '. esc_html( get_option('cartbounty_ghost_cart_count') );
		}
		if(get_option('cartbounty_recovered_cart_count')){
			$carts[] = esc_html__('Recovered', 'woo-save-abandoned-carts' ) .': '. esc_html( get_option('cartbounty_recovered_cart_count') );
		}

		if($wordpress->automation_enabled()){
			$active_recovery[] = 'WordPress';
			$active_recovery[] = esc_html__('Total emails sent', 'woo-save-abandoned-carts' ) .': '. esc_html( $wordpress->get_stats() );
		}

		if(get_option('cartbounty_exit_intent_status')){
			$exit_intent_options[] = esc_html__('Enabled', 'woo-save-abandoned-carts' );
		}
		if(get_option('cartbounty_exit_intent_test_mode')){
			$exit_intent_options[] = esc_html__('Test mode', 'woo-save-abandoned-carts' );
		}

		if(get_option('cartbounty_exclude_ghost_carts')){
			$settings[] = esc_html__('Exclude ghost carts', 'woo-save-abandoned-carts' );
		}
		if(get_option('cartbounty_notification_frequency')){
			$frequency = get_option('cartbounty_notification_frequency');
			if(isset($frequency['hours'])){
				$settings[] = esc_html__('Notification frequency', 'woo-save-abandoned-carts' ) .': '. esc_html( $frequency['hours'] );
			}
		}
		if(get_option('cartbounty_notification_email')){
			$settings[] = esc_html__('Email', 'woo-save-abandoned-carts' ) .' ('. esc_html( get_option('cartbounty_notification_email') ) .')';
		}
		if(get_option('cartbounty_lift_email')){
			$settings[] = esc_html__('Lift email field', 'woo-save-abandoned-carts' );
		}

		if(wp_next_scheduled('cartbounty_notification_sendout_hook') === false){
			$missing_hooks[] = 'cartbounty_notification_sendout_hook';
		}
		if(wp_next_scheduled('cartbounty_sync_hook') === false){
			$missing_hooks[] = 'cartbounty_sync_hook';
		}
		if(wp_next_scheduled('cartbounty_remove_empty_carts_hook') === false) {
			$missing_hooks[] = 'cartbounty_remove_empty_carts_hook';
		}

		$active_plugins = (array) get_option( 'active_plugins', array() ); //Check for active plugins
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		
		foreach ( $active_plugins as $plugin ) {
			$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$dirname        = dirname( $plugin );
			$version_string = '';
			if ( ! empty( $plugin_data['Name'] ) ) {
				// link the plugin name to the plugin url if available
				$plugin_name = $plugin_data['Name'];
				$all_plugins[] = $plugin_name . ' ' . esc_html__( 'by', 'woo-save-abandoned-carts' ) . ' ' . $plugin_data['Author'] . ' ' . esc_html__( 'version', 'woo-save-abandoned-carts' ) . ' ' . $plugin_data['Version'] . $version_string;
			}
		}

		if ( sizeof( $all_plugins ) == 0 ) {
			$site_wide_plugins = '-';
		} else {
			$site_wide_plugins = implode( ', <br/>', $all_plugins );
		}

		$environment = array(
			esc_html__('WordPress address (URL)', 'woo-save-abandoned-carts' ) => home_url(),
			esc_html__('Site address (URL)', 'woo-save-abandoned-carts' ) => site_url(),
			esc_html__('WordPress version', 'woo-save-abandoned-carts' ) => get_bloginfo( 'version' ),
			esc_html__('WordPress multisite', 'woo-save-abandoned-carts' ) => (is_multisite()) ? esc_html__('Yes', 'woo-save-abandoned-carts' ) : '-',
			esc_html__('WooCommerce version', 'woo-save-abandoned-carts' ) => class_exists( 'WooCommerce' ) ? esc_html( WC_VERSION ) : '-',
			esc_html__('Server info', 'woo-save-abandoned-carts' ) => isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '',
			esc_html__('PHP version', 'woo-save-abandoned-carts' ) => phpversion(),
			esc_html__('MySQL Version', 'woo-save-abandoned-carts' ) => $wpdb->db_version(),
			esc_html__('WordPress debug mode', 'woo-save-abandoned-carts' ) => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? esc_html__('On', 'woo-save-abandoned-carts' ) : '-',
			esc_html__('WordPress cron', 'woo-save-abandoned-carts' ) => !( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? esc_html__('On', 'woo-save-abandoned-carts' ) : '-',
			esc_html__('Language', 'woo-save-abandoned-carts' ) => get_locale(),
			esc_html__('Default server timezone', 'woo-save-abandoned-carts' ) => date_default_timezone_get()
		);

		$cartbounty_settings = array(
			esc_html__('CartBounty version', 'woo-save-abandoned-carts' ) => esc_html( $this->version ),
			esc_html__('Saved carts', 'woo-save-abandoned-carts' ) => ($carts) ? implode(", ", $carts) : '-',
			esc_html__('Automation', 'woo-save-abandoned-carts' ) => ($active_recovery) ? implode(", ", $active_recovery) : '-',
			esc_html__('Exit Intent', 'woo-save-abandoned-carts' ) => ($exit_intent_options) ? implode(", ", $exit_intent_options) : '-',
			esc_html__('Settings', 'woo-save-abandoned-carts' ) => ($settings) ? implode(", ", $settings) : '-',
			esc_html__('Missing hooks', 'woo-save-abandoned-carts' ) => ($missing_hooks) ? implode(", ", $missing_hooks) : '-',
			esc_html__('Template overrides', 'woo-save-abandoned-carts' ) => ($overrides) ? implode(", ", $overrides) : '-'
		);

		$output = '<table id="cartbounty-system-report-table">';
		$output .= '<tbody>
						<tr>
							<td class="section-title">###'. esc_html__( 'Environment', 'woo-save-abandoned-carts' ) .'###</td>
						</tr>';
					foreach( $environment as $key => $value ){
						$output .= '
						<tr>
							<td>'. esc_html( $key ) . ':' . '</td>
							<td>'. esc_html( $value ) .'</td>
						</tr>';
					}
		$output .= '</tbody>';
		$output .= '<tbody>
						<tr>
							<td class="section-title"></td>
						</tr>
						<tr>
							<td class="section-title">###'. esc_html__( CARTBOUNTY_ABREVIATION, 'woo-save-abandoned-carts' ) .'###</td>
						</tr>';
					foreach( $cartbounty_settings as $key => $value ){
						$output .= '
						<tr>
							<td>'. esc_html( $key ) . ':' . '</td>
							<td>'. esc_html( $value ) .'</td>
						</tr>';
					}
		$output .= '</tbody>
					<tbody>
						<tr>
							<td class="section-title"></td>
						</tr>
						<tr>
							<td class="section-title">###'. esc_html__( 'Plugins', 'woo-save-abandoned-carts' ) .'###</td>
						</tr>
						<tr>
							<td>'. esc_html__('Active plugins', 'woo-save-abandoned-carts' ) .':</td>
							<td>'. $site_wide_plugins .'</td>
						</tr>
					</tbody>';
		$output .= '</table>';

		wp_send_json_success( $output );
	}
}