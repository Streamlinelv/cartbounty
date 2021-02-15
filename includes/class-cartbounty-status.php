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
		if ( check_ajax_referer( 'get_system_status', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing the import
	        wp_send_json_error(__( 'Looks like you are not allowed to do this.', CARTBOUNTY_TEXT_DOMAIN ));
	    }

		global $wpdb;
		$carts = array();
		$all_plugins = array();
		$exit_intent_options = array();
		$settings = array();
		$missing_hooks = array();
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		
		if(get_option('cartbounty_recoverable_cart_count')){
			$carts[] = esc_html( __('Recoverable', CARTBOUNTY_TEXT_DOMAIN ) .': '. get_option('cartbounty_recoverable_cart_count') );
		}
		if(get_option('cartbounty_ghost_cart_count')){
			$carts[] = esc_html( __('Ghost', CARTBOUNTY_TEXT_DOMAIN ) .': '. get_option('cartbounty_ghost_cart_count') );
		}

		if(get_option('cartbounty_exit_intent_status')){
			$exit_intent_options[] = esc_html( __('Enabled', CARTBOUNTY_TEXT_DOMAIN ) );
		}
		if(get_option('cartbounty_exit_intent_test_mode')){
			$exit_intent_options[] = esc_html( __('Test mode', CARTBOUNTY_TEXT_DOMAIN ) );
		}

		if(get_option('cartbounty_exclude_ghost_carts')){
			$settings[] = esc_html( __('Exclude ghost carts', CARTBOUNTY_TEXT_DOMAIN ) );
		}
		if(get_option('cartbounty_notification_frequency')){
			$frequency = get_option('cartbounty_notification_frequency');
			if(isset($frequency['hours'])){
				$settings[] = esc_html( __('Notification frequency', CARTBOUNTY_TEXT_DOMAIN ) .': '. $frequency['hours'] );
			}
		}
		if(get_option('cartbounty_notification_email')){
			$settings[] = esc_html( __('Email', CARTBOUNTY_TEXT_DOMAIN ) .' ('. get_option('cartbounty_notification_email') .')' );
		}
		if(get_option('cartbounty_lift_email')){
			$settings[] = esc_html( __('Lift email field', CARTBOUNTY_TEXT_DOMAIN ) );
		}

		if(wp_next_scheduled('cartbounty_notification_sendout_hook') === false){
			$missing_hooks[] = 'cartbounty_notification_sendout_hook';
		}
		if (wp_next_scheduled ( 'cartbounty_remove_empty_carts_hook') === false) {
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
				$all_plugins[] = $plugin_name . ' ' . esc_html( __( 'by', CARTBOUNTY_TEXT_DOMAIN ) ) . ' ' . $plugin_data['Author'] . ' ' . esc_html( __( 'version', CARTBOUNTY_TEXT_DOMAIN ) ) . ' ' . $plugin_data['Version'] . $version_string;
			}
		}

		if ( sizeof( $all_plugins ) == 0 ) {
			$site_wide_plugins = '-';
		} else {
			$site_wide_plugins = implode( ', <br/>', $all_plugins );
		}

		$environment = array(
			esc_html( __('WordPress address (URL)', CARTBOUNTY_TEXT_DOMAIN ) ) => home_url(),
			esc_html( __('Site address (URL)', CARTBOUNTY_TEXT_DOMAIN ) ) => site_url(),
			esc_html( __('WordPress version', CARTBOUNTY_TEXT_DOMAIN ) ) => get_bloginfo( 'version' ),
			esc_html( __('WordPress multisite', CARTBOUNTY_TEXT_DOMAIN ) ) => (is_multisite()) ? esc_html( __('Yes', CARTBOUNTY_TEXT_DOMAIN ) ) : '-',
			esc_html( __('WooCommerce version', CARTBOUNTY_TEXT_DOMAIN ) ) => class_exists( 'WooCommerce' ) ? esc_html( WC_VERSION ) : '-',
			esc_html( __('Server info', CARTBOUNTY_TEXT_DOMAIN ) ) => isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '',
			esc_html( __('PHP version', CARTBOUNTY_TEXT_DOMAIN ) ) => phpversion(),
			esc_html( __('MySQL Version', CARTBOUNTY_TEXT_DOMAIN ) ) => $wpdb->db_version(),
			esc_html( __('WordPress debug mode', CARTBOUNTY_TEXT_DOMAIN ) ) => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? esc_html( __('On', CARTBOUNTY_TEXT_DOMAIN ) ) : '-',
			esc_html( __('WordPress cron', CARTBOUNTY_TEXT_DOMAIN ) ) => !( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? esc_html( __('On', CARTBOUNTY_TEXT_DOMAIN ) ) : '-',
			esc_html( __('Language', CARTBOUNTY_TEXT_DOMAIN ) ) => get_locale(),
			esc_html( __('Default server timezone', CARTBOUNTY_TEXT_DOMAIN ) ) => date_default_timezone_get()
		);

		$cartbounty_settings = array(
			esc_html( __('CartBounty version', CARTBOUNTY_TEXT_DOMAIN ) ) => esc_html( $this->version ),
			esc_html( __('Saved carts', CARTBOUNTY_TEXT_DOMAIN ) ) => ($carts) ? implode(", ", $carts) : '-',
			esc_html( __('Exit Intent', CARTBOUNTY_TEXT_DOMAIN ) ) => ($exit_intent_options) ? implode(", ", $exit_intent_options) : '-',
			esc_html( __('Settings', CARTBOUNTY_TEXT_DOMAIN ) ) => ($settings) ? implode(", ", $settings) : '-',
			esc_html( __('Missing hooks', CARTBOUNTY_TEXT_DOMAIN ) ) => ($missing_hooks) ? implode(", ", $missing_hooks) : '-'
		);

		$output = '<table id="cartbounty-system-report-table">';
		$output .= '<tbody>
						<tr>
							<td class="section-title">###'. esc_html__( 'Environment', CARTBOUNTY_TEXT_DOMAIN ) .'###</td>
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
							<td class="section-title">###'. esc_html__( CARTBOUNTY_ABREVIATION, CARTBOUNTY_TEXT_DOMAIN ) .'###</td>
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
							<td class="section-title">###'. esc_html__( 'Plugins', CARTBOUNTY_TEXT_DOMAIN ) .'###</td>
						</tr>
						<tr>
							<td>'. esc_html( __('Active plugins', CARTBOUNTY_TEXT_DOMAIN ) ) .':</td>
							<td>'. $site_wide_plugins .'</td>
						</tr>
					</tbody>';
		$output .= '</table>';

		wp_send_json_success( $output );
	}
}