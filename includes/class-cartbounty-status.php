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
		$interval_output = 0;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$wordpress = new CartBounty_WordPress();
		$overrides = $admin->get_template_overrides();
		$notification_frequency = $admin->get_interval_data( 'cartbounty_notification_frequency' );
		$review_data = array();
		
		if(get_option('cartbounty_recoverable_cart_count')){
			$carts[] = 'Recoverable: '. esc_html( get_option('cartbounty_recoverable_cart_count') );
		}
		if(get_option('cartbounty_anonymous_cart_count')){
			$carts[] = 'Anonymous: '. esc_html( get_option('cartbounty_anonymous_cart_count') );
		}
		if(get_option('cartbounty_recovered_cart_count')){
			$carts[] = 'Recovered: '. esc_html( get_option('cartbounty_recovered_cart_count') );
		}

		if(get_option('cartbounty_times_review_declined')){
			$review_data[] = 'Times declined: '. esc_html( get_option('cartbounty_times_review_declined') );
		}
		if($admin->is_notice_submitted('review')){
			$review_data[] = 'Submitted: True';
		}


		if($wordpress->automation_enabled()){
			$active_recovery[] = 'WordPress';
			$active_recovery[] = 'Total emails sent: '. esc_html( $wordpress->get_stats() );
		}

		if(get_option('cartbounty_exit_intent_status')){
			$exit_intent_options[] = 'Enabled';
		}
		if(get_option('cartbounty_exit_intent_test_mode')){
			$exit_intent_options[] = 'Test mode';
		}

		if(get_option('cartbounty_exclude_anonymous_carts')){
			$settings[] = 'Exclude anonymous carts';
		}
		if( isset( $notification_frequency['selected'] ) ){

			if( $notification_frequency['selected'] != 0 ){
				$interval_output = $admin->get_interval_data( 'cartbounty_notification_frequency', false, $just_selected_value = true ) . ' ('. esc_html( $admin->convert_miliseconds_to_minutes( $notification_frequency['selected'] ) ) . ')';
			}

			$settings[] = 'Notification frequency: ' . $interval_output;
		}
		if(get_option('cartbounty_notification_email')){
			$settings[] = 'Notification emails: '. esc_html( get_option('cartbounty_notification_email') );
		}
		if(get_option('cartbounty_lift_email')){
			$settings[] = 'Lift email field';
		}

		if( !$admin->action_scheduler_enabled() ){ //If WooCommerce Action scheduler library does not exist
			if(wp_next_scheduled('cartbounty_notification_sendout_hook') === false){
				$missing_hooks[] = 'cartbounty_notification_sendout_hook';
			}
			if(wp_next_scheduled('cartbounty_sync_hook') === false){
				$missing_hooks[] = 'cartbounty_sync_hook';
			}
			if(wp_next_scheduled('cartbounty_remove_empty_carts_hook') === false) {
				$missing_hooks[] = 'cartbounty_remove_empty_carts_hook';
			}
		}

		$active_theme = '-';
		$active_theme_data = wp_get_theme();

		if( $active_theme_data ){
			$active_theme = $active_theme_data->get( 'Name' ) . ' by ' . $active_theme_data->get( 'Author' ) . ' version ' . $active_theme_data->get( 'Version' ) . ' (' . $active_theme_data->get( 'ThemeURI' ) . ')';
		}

		$active_plugins = (array) get_option( 'active_plugins', array() ); //Check for active plugins
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		
		foreach ( $active_plugins as $plugin ) {
			$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$dirname        = dirname( $plugin );

			if ( ! empty( $plugin_data['Name'] ) ) {
				// link the plugin name to the plugin url if available
				$plugin_name = $plugin_data['Name'];
				$all_plugins[] = $plugin_name . ' by ' . $plugin_data['Author'] . ' version ' . $plugin_data['Version'];
			}
		}

		$site_wide_plugins = '-';
		if ( sizeof( $all_plugins ) != 0 ) {
			$site_wide_plugins = implode( ', <br/>', $all_plugins );
		}

		$environment = array(
			'WordPress address (URL)' 	=> home_url(),
			'Site address (URL)' 		=> site_url(),
			'WordPress version' 		=> get_bloginfo( 'version' ),
			'WordPress multisite' 		=> (is_multisite()) ? 'Yes' : '-',
			'WooCommerce version' 		=> class_exists( 'WooCommerce' ) ? esc_html( WC_VERSION ) : '-',
			'Server info' 				=> isset( $_SERVER['SERVER_SOFTWARE'] ) ? esc_html( $_SERVER['SERVER_SOFTWARE'] ) : '',
			'PHP version' 				=> phpversion(),
			'MySQL Version' 			=> $wpdb->db_version(),
			'WordPress debug mode' 		=> ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'On' : '-',
			'Action scheduler' 			=> ( $admin->action_scheduler_enabled() ) ? 'On' : '-',
			'WordPress cron' 			=> !( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? 'On' : '-',
			'Language' 					=> get_locale(),
			'Default server timezone' 	=> date_default_timezone_get()
		);

		$cartbounty_settings = array(
			'CartBounty version' 	=> esc_html( $this->version ),
			'Saved carts' 			=> ($carts) ? implode(", ", $carts) : '-',
			'Review' 				=> ($review_data) ? implode(", ", $review_data) : '-',
			'Recovery' 				=> ($active_recovery) ? implode(", ", $active_recovery) : '-',
			'Exit Intent' 			=> ($exit_intent_options) ? implode(", ", $exit_intent_options) : '-',
			'Settings' 				=> ($settings) ? implode(", ", $settings) : '-',
			'Missing hooks'			=> ($missing_hooks) ? implode(", ", $missing_hooks) : '-',
			'Template overrides' 	=> ($overrides) ? implode(", ", $overrides) : '-'
		);

		$output = '<table id="cartbounty-system-report-table">';
		$output .= '<tbody>
						<tr>
							<td class="section-title">### Environment ###</td>
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
							<td class="section-title">### '. CARTBOUNTY_ABREVIATION .' ###</td>
						</tr>';
					foreach( $cartbounty_settings as $key => $value ){
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
							<td class="section-title">### Themes ###</td>
						</tr>
						<tr>
							<td>Active theme:</td>
							<td>'. $active_theme .'</td>
						</tr>';
		$output .= '</tbody>';
		$output .= '<tbody>
						<tr>
							<td class="section-title"></td>
						</tr>
						<tr>
							<td class="section-title">### Plugins ###</td>
						</tr>
						<tr>
							<td>Active plugins:</td>
							<td>'. $site_wide_plugins .'</td>
						</tr>
					</tbody>';
		$output .= '</table>';

		wp_send_json_success( $output );
	}
}