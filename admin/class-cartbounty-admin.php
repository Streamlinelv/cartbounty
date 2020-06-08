<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/admin
 * @author     Streamline.lv
 */
class CartBounty_Admin{

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
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    	      The version of this plugin.
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

		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();
		
		//Do not continue if we are not on CartBounty plugin page
		if(!is_object($screen)){
			return;
		}

		if($screen->id == $cartbounty_admin_menu_page || $screen->id == 'plugins'){
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartbounty-admin.css', array('wp-color-picker'), $this->version, 'all' );
		}
	}

	/**
	 * Register the javascripts for the admin area.
	 *
	 * @since    3.0
	 */
	public function enqueue_scripts(){
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();
		
		//Do not continue if we are not on CartBounty plugin page
		if(!is_object($screen) || $screen->id != $cartbounty_admin_menu_page){
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartbounty-admin.js', array( 'wp-color-picker', 'jquery' ), $this->version, false );
	}
	
	/**
	 * Register the menu under WooCommerce admin menu.
	 *
	 * @since    1.0
	 */
	function cartbounty_menu(){
		global $cartbounty_admin_menu_page;
		if(class_exists('WooCommerce')){
			$cartbounty_admin_menu_page = add_submenu_page( 'woocommerce', CARTBOUNTY_PLUGIN_NAME, __('CartBounty Abandoned carts', CARTBOUNTY_TEXT_DOMAIN), 'list_users', CARTBOUNTY, array($this,'display_page'));
		}else{
			$cartbounty_admin_menu_page = add_menu_page( CARTBOUNTY_PLUGIN_NAME, __('CartBounty Abandoned carts', CARTBOUNTY_TEXT_DOMAIN), 'list_users', CARTBOUNTY, array($this,'display_page'), 'dashicons-archive' );
		}
	}

	/**
	 * Adds newly abandoned cart count to the menu
	 *
	 * @since    1.4
	 */
	function menu_abandoned_count(){
		global $wpdb, $submenu;
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		
		if ( isset( $submenu['woocommerce'] ) ) { //If WooCommerce Menu exists
			$time = $this->get_time_intervals();
			// Retrieve from database rows that have not been e-mailed and are older than 60 minutes
			$order_count = $wpdb->get_var( //Counting newly abandoned carts
				$wpdb->prepare(
					"SELECT COUNT(id)
					FROM $table_name
					WHERE
					cart_contents != '' AND
					time < %s AND 
					time > %s ",
					$time['cart_abandoned'],
					$time['old_cart']
				)
			);
			
			foreach ( $submenu['woocommerce'] as $key => $menu_item ) { //Go through all Sumenu sections of WooCommerce and look for CartBounty Abandoned carts
				if ( 0 === strpos( $menu_item[0], __('CartBounty Abandoned carts', CARTBOUNTY_TEXT_DOMAIN))) {
					$submenu['woocommerce'][$key][0] .= ' <span class="new-abandoned update-plugins count-' . $order_count . '">' .  $order_count .'</span>';
				}
			}
		}
	}
	
	/**
	 * Display the abandoned carts and settings under admin page
	 *
	 * @since    1.3
	 */
	function display_page(){
		global $wpdb, $pagenow;
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		
		if ( !current_user_can( 'list_users' )){
			wp_die( __( 'You do not have sufficient permissions to access this page.', CARTBOUNTY_TEXT_DOMAIN ) );
		}
		
		//Our class extends the WP_List_Table class, so we need to make sure that it's there
		//Prepare Table of elements
		require_once plugin_dir_path( __FILE__ ) . 'class-cartbounty-admin-table.php';
		$wp_list_table = new CartBounty_Table();
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
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf(
				/* translators: %d - Item count */
				__('Items deleted: %d', CARTBOUNTY_TEXT_DOMAIN ), $deleted_row_count
			) . '</p></div>';
		}
		?>

		<div id="cartbounty-page-wrapper" class="wrap<?php if(get_option('cartbounty_hide_images')) {echo " cartbounty-without-thumbnails";}?>">

			<?php if ( isset ( $_GET['tab'] ) ){
				$this->create_admin_tabs($_GET['tab']);
			}else{
				$this->create_admin_tabs('carts');
			}

			if ( $pagenow == 'admin.php' && $_GET['page'] == CARTBOUNTY ){
				if (isset($_GET['tab'])){
					$tab = $_GET['tab'];
				}else{
					$tab = 'carts';
				}

				if($tab == 'settings'): //Settings tab output ?>

					<h1><?php echo CARTBOUNTY_ABREVIATION; ?> <?php echo __('Settings', CARTBOUNTY_TEXT_DOMAIN); ?></h1>
					<form method="post" action="options.php">
						<?php
							settings_fields( 'cartbounty-settings' );
							do_settings_sections( 'cartbounty-settings' );
							$lift_email_on = esc_attr( get_option('cartbounty_lift_email') );
							$hide_images_on = esc_attr( get_option('cartbounty_hide_images') );
						?>
						<table id="cartbounty-settings-table" class="form-table">
							<tr>
								<th scope="row">
									<label for="cartbounty_notification_email"><?php echo __('Send notifications about abandoned carts to this email:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="cartbounty_notification_email" type="email" name="cartbounty_notification_email" value="<?php echo esc_attr( get_option('cartbounty_notification_email') ); ?>" <?php echo $this->disableField(); ?> />
									<p><small><?php echo sprintf(
										/* translators: %s - Email address */
										__('By default, notifications will be sent to WordPress admin email - %s.', CARTBOUNTY_TEXT_DOMAIN), get_option( 'admin_email' )); ?>
									</small></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cartbounty_notification_frequency[hours]"><?php echo __('How often to check and send emails about newly abandoned carts?', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<!-- Using selected() instead -->
									<?php $options = get_option( 'cartbounty_notification_frequency' );
										if(!$options){
											$options = array('hours' => 60);
										}
									?>
									 <select id="cartbounty_notification_frequency[hours]" name='cartbounty_notification_frequency[hours]' <?php echo $this->disableField(); ?>>
										<option value='10' <?php selected( $options['hours'], 10 ); ?>><?php echo 		__('Every 10 minutes', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='20' <?php selected( $options['hours'], 20 ); ?>><?php echo 		__('Every 20 minutes', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='30' <?php selected( $options['hours'], 30 ); ?>><?php echo 		__('Every 30 minutes', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='60' <?php selected( $options['hours'], 60 ); ?>><?php echo 		__('Every hour', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='120' <?php selected( $options['hours'], 120 ); ?>><?php echo 	__('Every 2 hours', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='180' <?php selected( $options['hours'], 180 ); ?>><?php echo 	__('Every 3 hours', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='240' <?php selected( $options['hours'], 240 ); ?>><?php echo 	__('Every 4 hours', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='300' <?php selected( $options['hours'], 300 ); ?>><?php echo 	__('Every 5 hours', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='360' <?php selected( $options['hours'], 360 ); ?>><?php echo 	__('Every 6 hours', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='720' <?php selected( $options['hours'], 720 ); ?>><?php echo 	__('Twice a day', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='1440' <?php selected( $options['hours'], 1440 ); ?>><?php echo 	__('Once a day', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='2880' <?php selected( $options['hours'], 2880 ); ?>><?php echo 	__('Once every 2 days', CARTBOUNTY_TEXT_DOMAIN); ?></option>
										<option value='0' <?php selected( $options['hours'], 0 ); ?>><?php echo 		__('Disable notifications', CARTBOUNTY_TEXT_DOMAIN); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cartbounty-lift-email"><?php echo __('Lift email field:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="cartbounty-lift-email" class="cartbounty-checkbox" type="checkbox" name="cartbounty_lift_email" value="1" <?php echo $this->disableField(); ?> <?php echo checked( 1, $lift_email_on, false ); ?> />
									<p><small>
										<?php if($lift_email_on){
											echo __('Please test the checkout after enabling this, as sometimes it can cause <br/>issues or not raise the field if you have a custom checkout.', CARTBOUNTY_TEXT_DOMAIN);
										}else{
											echo __('You could increase the chances of capturing abandoned carts by <br/>moving the email field to the top of your checkout form.', CARTBOUNTY_TEXT_DOMAIN);
										}?>
										</small>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cartbounty-hide-images"><?php echo __('Display abandoned cart contents in a list:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="cartbounty-hide-images" class="cartbounty-checkbox" type="checkbox" name="cartbounty_hide_images" value="1" <?php echo $this->disableField(); ?> <?php echo checked( 1, $hide_images_on, false ); ?> />
								</td>
							</tr>
						</table>
						
						<?php
						if(current_user_can( 'manage_options' )){
							submit_button(__('Save settings', CARTBOUNTY_TEXT_DOMAIN));
						}?>
					</form>

				<?php elseif($tab == 'exit_intent'): //Exit intent output ?>

					<h1><?php echo CARTBOUNTY_ABREVIATION; ?> <?php echo __('Exit Intent', CARTBOUNTY_TEXT_DOMAIN); ?></h1>
					<p class="cartbounty-description"><?php echo __('With the help of Exit Intent you can capture even more abandoned carts by displaying a message including an e-mail field that the customer can fill to save his shopping cart. You can even offer to send a discount code.', CARTBOUNTY_TEXT_DOMAIN); ?></p>
					<p class="cartbounty-description"><?php echo __('Please note that the Exit Intent will only be showed to unregistered users once per hour after they have added an item to their cart and try to leave your shop.', CARTBOUNTY_TEXT_DOMAIN); ?></p>
					<p class="cartbounty-description"><?php echo sprintf(
						/* translators: %s - Link */
						__('If you would like to customize the content of your Exit Intent, please see <a href="%s" target="_blank">How to change the content and image of the Exit Intent</a>.', CARTBOUNTY_TEXT_DOMAIN), 'https://www.cartbounty.com/#modify-exit-intent-content'); ?>
					</p>
					<form method="post" action="options.php">
						<?php
							settings_fields( 'cartbounty-settings-exit-intent' );
							do_settings_sections( 'cartbounty-settings-exit-intent' );
							$exit_intent_on = esc_attr( get_option('cartbounty_exit_intent_status'));
							$test_mode_on = esc_attr( get_option('cartbounty_exit_intent_test_mode'));
							$exit_intent_type = esc_attr( get_option('cartbounty_exit_intent_type'));
							$main_color = esc_attr( get_option('cartbounty_exit_intent_main_color'));
							$inverse_color = esc_attr( get_option('cartbounty_exit_intent_inverse_color'));
						?>
						
						<table id="cartbounty-exit-intent-table" class="form-table">
							<tr>
								<th scope="row">
									<label for="cartbounty-exit-intent-status"><?php echo __('Enable Exit Intent:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="cartbounty-exit-intent-status" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_status" value="1" <?php echo $this->disableField(); ?> <?php echo checked( 1, $exit_intent_on, false ); ?> />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cartbounty-exit-intent-test-mode"><?php echo __('Enable test mode:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="cartbounty-exit-intent-test-mode" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_test_mode" value="1" <?php echo $this->disableField(); ?> <?php echo checked( 1, $test_mode_on, false ); ?> />
									<p><small>
										<?php if($test_mode_on){
										echo __('Now go to your store and add a product to your shopping cart. Please note that only <br/>users with Admin rights will be able to see the Exit Intent and appearance limits <br/>have been removed - it will be shown each time you try to leave your shop.', CARTBOUNTY_TEXT_DOMAIN);
										}?>
										</small>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php echo __('Choose style:', CARTBOUNTY_TEXT_DOMAIN); ?>
								</th>
								<td>
									<div id="cartbounty-exit-intent-center" class="cartbounty-exit-intent-type <?php if($exit_intent_type == 1){ echo "cartbounty-radio-active";} ?>">
										<label class="cartbounty-exit-intent-image" for="cartbounty-radiobutton-center">
											<em>
												<i>
													<img src="<?php echo plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ; ?>" title="" alt=""/>
												</i>
											</em>
											<input id="cartbounty-radiobutton-center" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_type" value="1" <?php echo $this->disableField(); ?> <?php echo checked( 1, $exit_intent_type, false ); ?> />
											<?php echo __('Appear In Center', CARTBOUNTY_TEXT_DOMAIN); ?>
										</label>
									</div>
									<div id="cartbounty-exit-intent-left" class="cartbounty-exit-intent-type">
										<label class="cartbounty-exit-intent-image" for="cartbounty-radiobutton-left">
											<em>
												<i>
													<img src="<?php echo plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ; ?>" title="" alt=""/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php echo __('Upgrade to enable this style', CARTBOUNTY_TEXT_DOMAIN); ?>
													<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=ei_style&utm_campaign=cartbounty" class="button" target="_blank"><?php echo __('Get Pro', CARTBOUNTY_TEXT_DOMAIN); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-left" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_type" value="1" <?php echo $this->disableField(array('forced' => true )); ?> />
											<?php echo __('Slide In From Left', CARTBOUNTY_TEXT_DOMAIN); ?>
										</label>
									</div>
									<div id="cartbounty-exit-intent-fullscreen" class="cartbounty-exit-intent-type">
										<label class="cartbounty-exit-intent-image" for="cartbounty-radiobutton-fullscreen">
											<em>
												<i>
													<img src="<?php echo plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ; ?>" title="" alt=""/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php echo __('Upgrade to enable this style', CARTBOUNTY_TEXT_DOMAIN); ?>
													<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=ei_style&utm_campaign=cartbounty" class="button" target="_blank"><?php echo __('Get Pro', CARTBOUNTY_TEXT_DOMAIN); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-fullscreen" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_type" value="1" <?php echo $this->disableField(array('forced' => true )); ?> />
											<?php echo __('Fullscreen', CARTBOUNTY_TEXT_DOMAIN); ?>
										</label>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php echo __('Exit Intent colors:', CARTBOUNTY_TEXT_DOMAIN); ?>
								</th>
								<td>
									<div class="cartbounty-exit-intent-colors">
										<label for="cartbounty-exit-intent-main-color"><?php echo __('Main:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
										<input id="cartbounty-exit-intent-main-color" type="text" name="cartbounty_exit_intent_main_color" class="cartbounty-exit-intent-color-picker" value="<?php echo $main_color; ?>" <?php echo $this->disableField(); ?> />
									</div>
									<div class="cartbounty-exit-intent-colors">
										<label for="cartbounty-exit-intent-inverse-color"><?php echo __('Inverse:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
										<input id="cartbounty-exit-intent-inverse-color" type="text" name="cartbounty_exit_intent_inverse_color" class="cartbounty-exit-intent-color-picker" value="<?php echo $inverse_color; ?>" <?php echo $this->disableField(); ?> />
									</div>
									<p class="clear"><small>
										<?php echo __('If you leave the Inverse color empty, it will automatically use the inverse color of <br/>the main color you have picked. Clear both colors to use the default colors.', CARTBOUNTY_TEXT_DOMAIN);
										?>
										</small>
									</p>
								</td>
							</tr>
						</table>
						<?php
						if(current_user_can( 'manage_options' )){
							submit_button(__('Save settings', CARTBOUNTY_TEXT_DOMAIN));
						}?>
					</form>

				<?php else: //Table output ?>

					<h1><?php echo CARTBOUNTY_ABREVIATION; ?> <?php echo __('Abandoned carts', CARTBOUNTY_TEXT_DOMAIN); ?></h1>
					<?php do_action('cartbounty_after_page_title'); ?>
					<?php echo $message; 
					if ($this->abandoned_cart_count() == 0): //If no abandoned carts, then output this note ?>
						<p>
							<?php echo __( 'Looks like you do not have any saved Abandoned carts yet.<br/>But do not worry, as soon as someone fills the <strong>Email</strong> or <strong>Phone number</strong> fields of your WooCommerce Checkout form and abandons the cart, it will automatically appear here.', CARTBOUNTY_TEXT_DOMAIN); ?>
						</p>
					<?php else: ?>
						<form id="cartbounty-table" method="GET">
							<input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
							<?php $wp_list_table->display(); ?>
						</form>
					<?php endif; ?>
				<?php endif;
			}?>
		</div>
	<?php
	}

	/**
	 * Function creates tabs on plugin page
	 *
	 * @since    3.0
	 * @param    $current    Currently open tab - string
	 */
	function create_admin_tabs( $current = 'carts' ){
		$tabs = array( 'carts' => __('Abandoned carts', CARTBOUNTY_TEXT_DOMAIN), 'settings' => __('Settings', CARTBOUNTY_TEXT_DOMAIN), 'exit_intent' => __('Exit Intent', CARTBOUNTY_TEXT_DOMAIN));
		echo '<h2 class="nav-tab-wrapper">';
		$icon_image = NULL;
		
		foreach( $tabs as $tab => $name ){
			if($name == 'Settings'){
				$icon_class = 'dashicons-admin-generic';
				$icon_image = '';
			}
			elseif($name == 'Exit Intent'){
				//$icon_image = '';
				$icon_class = 'cartbounty-exit-intent-icon';
				$icon_image = "<img src='data:image/svg+xml;base64," . $this->exit_intent_svg_icon($current) . "' alt=''  />";
			}
			else{
				$icon_class = 'cartbounty-logo';
				$icon_image = "<img src='data:image/svg+xml;base64," . $this->cartbounty_svg_icon($current) . "' alt=''  />";
			}
			
			$class = ( $tab == $current ) ? ' nav-tab-active' : ''; //if the tab is open, an additional class, nav-tab-active, is added
			echo "<a class='nav-tab$class' href='?page=". CARTBOUNTY ."&tab=$tab'><span class='cartbounty-tab-icon dashicons $icon_class' >$icon_image</span><span class='cartbounty-tab-name'>$name</span></a>";
		}
		echo '</h2>';
	}

	/**
	 * Function adds additional intervals to default Wordpress cron intervals (hourly, twicedaily, daily). Interval provided in minutes
	 *
	 * @since    3.0
	 */
	function additional_cron_intervals(){
		$interval['cartbounty_notification_sendout_interval'] = array( //Defining cron Interval for sending out email notifications about abandoned carts
			'interval' => CARTBOUNTY_EMAIL_INTERVAL * 60,
			'display' => 'Every '. CARTBOUNTY_EMAIL_INTERVAL .' minutes'
		);
		$interval['cartbounty_remove_empty_carts_interval'] = array( //Defining cron Interval for removing abandoned carts that do not have products
			'interval' => 12 * 60 * 60,
			'display' => 'Twice a day'
		);
		return $interval;
	}

	/**
	 * Function resets Wordpress cron function after user sets other notification frequency
	 * wp_schedule_event() Schedules a hook which will be executed by the WordPress actions core on a specific interval, specified by you. 
	 * The action will trigger when someone visits your WordPress site, if the scheduled time has passed.
	 *
	 * @since    4.3
	 */
	function notification_sendout_interval_update(){
		$user_settings_notification_frequency = get_option('cartbounty_notification_frequency');
		if(intval($user_settings_notification_frequency['hours']) == 0){ //If Email notifications have been disabled, we disable cron job
			wp_clear_scheduled_hook( 'cartbounty_notification_sendout_hook' );
		}else{
			if (wp_next_scheduled ( 'cartbounty_notification_sendout_hook' )) {
				wp_clear_scheduled_hook( 'cartbounty_notification_sendout_hook' );
			}
			wp_schedule_event(time(), 'cartbounty_notification_sendout_interval', 'cartbounty_notification_sendout_hook');
		}
	}

	/**
	 * Function shows warnings if any of the WP Cron events required for MailChimp or ActiveCampaign are not scheduled (either sending notifications or pushing carts) or if the WP Cron has been disabled
	 *
	 * @since    4.3
	 */
	function display_wp_cron_warnings(){
		global $pagenow;

		//Checking if we are on open plugin page
		if ($pagenow == 'admin.php' && $_GET['page'] == CARTBOUNTY){
				//Checking if WP Cron hooks are scheduled
				$missing_hooks = array();
				$user_settings_notification_frequency = get_option('cartbounty_notification_frequency');

				if(wp_next_scheduled('cartbounty_notification_sendout_hook') === false && intval($user_settings_notification_frequency['hours']) != 0){ //If we havent scheduled email notifications and notifications have not been disabled
					$missing_hooks[] = 'cartbounty_notification_sendout_hook';
				}
				if (!empty($missing_hooks)) { //If we have hooks that are not scheduled
					$hooks = '';
					$current = 1;
					$total = count($missing_hooks);
					foreach($missing_hooks as $missing_hook){
						$hooks .= $missing_hook;
						if ($current != $total){
							$hooks .= ', ';
						}
						$current++;
					}
					?>
					<div id="cartbounty-cron-schedules" class="cartbounty-notification warning update-nag">
						<p class="left-part">
							<?php
								echo sprintf(
									/* translators: %s - Cron event name */
									_n("It seems that WP Cron event <strong>%s</strong> required for plugin automation is not scheduled.", "It seems that WP Cron events <strong>%s</strong> required for plugin automations are not scheduled.", $total, CARTBOUNTY_TEXT_DOMAIN ), $hooks); ?> <?php echo sprintf(
									/* translators: %s - Plugin name */
									__("Please try disabling and enabling %s plugin. If this notice does not go away after that, please get in touch with your hosting provider and make sure to enable WP Cron. Without it you will not be able to receive automated email notifications about newly abandoned shopping carts.", CARTBOUNTY_TEXT_DOMAIN ), CARTBOUNTY_ABREVIATION ); ?>
						</p>
					</div>
					<?php 
				}

				//Checking if WP Cron is enabled
				if(defined('DISABLE_WP_CRON')){
					if(DISABLE_WP_CRON == true){ ?>
						<div id="cartbounty-cron-schedules" class="cartbounty-notification warning update-nag">
							<p class="left-part"><?php echo __("WP Cron has been disabled. Several WordPress core features, such as checking for updates or sending notifications utilize this function. Please enable it or contact your system administrator to help you with this.", CARTBOUNTY_TEXT_DOMAIN ); ?></p>
						</div>
					<?php
					}
				}


		}
	}

	/**
	 * Function to send out e-mail notification in order to notify about new abandoned carts
	 *
	 * @since    4.3
	 */
	function send_email(){
		global $wpdb;
		
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time = $this->get_time_intervals();

		// Retrieve from database rows that have not been e-mailed and are older than 60 minutes
		$rows_to_email = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id) FROM ". $table_name ."
				WHERE mail_sent = 0 AND cart_contents != '' AND time < %s", $time['cart_abandoned']
			)
		);
		
		if ($rows_to_email){ //If we have new rows in the database
			$user_settings_email = get_option('cartbounty_notification_email'); //Retrieving email address if the user has entered one
			if($user_settings_email == '' || $user_settings_email == NULL){
				$to = get_option( 'admin_email' );
			}else{
				$to = $user_settings_email;
			}
			
			$sender = 'WordPress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
			$from = "From: WordPress <$sender>";
			$blog_name = get_option( 'blogname' );
			$admin_link = get_admin_url() .'admin.php?page='. CARTBOUNTY;
			$subject = '['.$blog_name.'] '. _n('New abandoned cart saved', 'New abandoned carts saved', $rows_to_email, CARTBOUNTY_TEXT_DOMAIN);
			$message = sprintf(
				/* translators: %d - Abandoned cart count, %s - Plugin name, %s - Link, %s - Link */
				_n('Great! You have saved %d new abandoned cart using %s. <br/>View your abandoned carts here: <a href="%s">%s</a>', 'Congratulations, you have saved %d new abandoned carts using %s. <br/>View your abandoned carts here: <a href="%s">%s</a>', $rows_to_email, CARTBOUNTY_TEXT_DOMAIN), esc_html($rows_to_email), CARTBOUNTY_ABREVIATION, esc_html($admin_link), esc_html($admin_link));
			$headers 	= "$from\n" . "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
			
			//Sending out e-mail
			wp_mail( esc_html($to), esc_html($subject), $message, $headers );
			
			//Update mail_sent status to true with mail_status = 0 and are older than 60 minutes
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $table_name
					SET mail_sent = %d 
					WHERE mail_sent = %d AND
					cart_contents != '' AND
					time < %s",
					1,
					0,
					$time['cart_abandoned']
				)
			);
		}
	}

	/**
	 * Count abandoned carts
	 *
	 * @since    1.1
	 */
	function abandoned_cart_count(){
		global $wpdb;
        $table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        return $total_items;
	}

	/**
	 * Adds custom action link on Plugin page under plugin name
	 *
	 * @since    1.2
	 * @param    $actions        Action
	 * @param    $plugin_file    Location of the plugin
	 */
	function add_plugin_action_links( $actions, $plugin_file ){
		if ( ! is_array( $actions ) ) {
			return $actions;
		}
		
		$action_links = array();
		$action_links['cartbounty_get_pro'] = array(
			'label' => __('Get Pro', CARTBOUNTY_TEXT_DOMAIN),
			'url'   => CARTBOUNTY_LICENSE_SERVER_URL . '?utm_source=' . urlencode(get_bloginfo('url')) . '&utm_medium=plugin_link&utm_campaign=cartbounty'
		);

		return $this->add_display_plugin_action_links( $actions, $plugin_file, $action_links, 'before' );
	}

	/**
	 * Function that merges the links on Plugin page under plugin name
	 *
	 * @since    1.2
	 * @return   array
	 * @param    $actions        Action
	 * @param    $plugin_file    Location of the plugin
	 * @param    $action_links   Action links - array
	 * @param    $position       Postition
	 */
	function add_display_plugin_action_links( $actions, $plugin_file, $action_links = array(), $position = 'after' ){
		static $plugin;
		if ( ! isset( $plugin ) ) {
			$plugin = CARTBOUNTY_BASENAME;
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
	 * @since    1.3
	 * @return   Boolean
	 * @param    $option    Option from WordPress database
	 * @param    $days      Number of days
	 */
	function days_have_passed( $option, $days ){
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
		$plugin = new CartBounty();
		$current_version = $plugin->get_version();
		
		if ($current_version == get_option('cartbounty_version_number')){ //If database version is equal to plugin version. Not updating database
			return;
		}else{ //Versions are different and we must update the database
			update_option('cartbounty_version_number', $current_version);
			activate_cartbounty(); //Function that updates the database
			return;
		}
	}

	/**
	 * Checks if we have to disable input field or not because of the users access right to save data
	 *
	 * @since    3.0
	 * @param    $options    Options
	 */
	function disableField( $options = array() ){
		if($options){
			if($options['forced'] == true){
				return 'disabled=""';
			}
		}
		elseif(!current_user_can( 'manage_options' )){
			return 'disabled=""';
		}
	}

	/**
	 * Function outputs bubble content
	 *
	 * @since    1.4.2
	 */
	function output_bubble_content(){ ?>
		<div id="cartbounty-bubbles">
			<?php if(!get_option('cartbounty_review_submitted')): //Don't output Review bubble if review has been left ?>
				<div id="cartbounty-review" class="cartbounty-bubble">
					<div class="cartbounty-header-image">
						<a href="<?php echo CARTBOUNTY_REVIEW_LINK; ?>" title="<?php echo __('Leave CartBounty - Save and recover abandoned carts for WooCommerce a 5-star rating', CARTBOUNTY_TEXT_DOMAIN ); ?>" target="_blank">
							<img src="<?php echo plugins_url( 'assets/review-notification.gif', __FILE__ ) ; ?>" alt="" title=""/>
						</a>
					</div>
					<div id="cartbounty-review-content">
						<?php
							$expression = $this->get_expressions();
							$saved_cart_count = $this->total_captured_abandoned_cart_count();
						?>
						<h2><?php echo sprintf(
							/* translators: %s - Gets replaced by an excitement word e.g. Awesome!, %d - Abandoned cart count */
							_n('%s You have already captured %d abandoned cart!', '%s You have already captured %d abandoned carts!', $saved_cart_count , CARTBOUNTY_TEXT_DOMAIN ), $expression['exclamation'], $saved_cart_count ); ?></h2>
						<p><?php echo __('If you like our plugin, please leave us a 5-star rating. It is the easiest way to help us grow and keep evolving further.', CARTBOUNTY_TEXT_DOMAIN ); ?></p>
						<div class="cartbounty-button-row">
							<form method="post" action="options.php" class="cartbounty_inline">
								<?php settings_fields( 'cartbounty-settings-review' ); ?>
								<a href="<?php echo CARTBOUNTY_REVIEW_LINK; ?>" class="button" target="_blank"><?php echo __("Leave a 5-star rating", CARTBOUNTY_TEXT_DOMAIN ); ?></a>
								<?php submit_button(__('Done that', CARTBOUNTY_TEXT_DOMAIN), 'cartbounty-review-submitted', false, false); ?>
								<input id="cartbounty_review_submitted" type="hidden" name="cartbounty_review_submitted" value="1" />
							</form>
							<form method="post" action="options.php" class="cartbounty_inline">
								<?php settings_fields( 'cartbounty-settings-declined' ); ?>
								<?php submit_button(__('Close', CARTBOUNTY_TEXT_DOMAIN), 'cartbounty-close', false, false); ?>
								<input id="cartbounty_times_review_declined" type="hidden" name="cartbounty_times_review_declined" value="<?php echo get_option('cartbounty_times_review_declined') + 1; // Retrieving how many times review has been declined and updates the count in database by one ?>" />
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<div id="cartbounty-go-pro" class="cartbounty-bubble">
				<div class="cartbounty-header-image">
					<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=bubble&utm_campaign=cartbounty" title="<?php __('Get CartBounty Pro - Save and recover abandoned carts for WooCommerce', CARTBOUNTY_TEXT_DOMAIN); ?>" target="_blank">
						<img src="<?php echo plugins_url( 'assets/notification-email.gif', __FILE__ ) ; ?>" alt="" title=""/>
					</a>
				</div>
				<div id="cartbounty-go-pro-content">
					<form method="post" action="options.php">
						<?php settings_fields( 'cartbounty-settings-time' ); ?>
						<h2><?php echo __('Automate your abandoned cart recovery workflow and get back to those lovely cat videos (:', CARTBOUNTY_TEXT_DOMAIN ); ?></h2>
						<p><?php echo __('Use your time wisely by enabling Pro features and increase your sales.', CARTBOUNTY_TEXT_DOMAIN ); ?></p>
						<p class="cartbounty-button-row">
							<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=bubble&utm_campaign=cartbounty" class="button" target="_blank"><?php echo __('Get Pro', CARTBOUNTY_TEXT_DOMAIN); ?></a>
							<?php submit_button(__('Not now', CARTBOUNTY_TEXT_DOMAIN), 'cartbounty-close', false, false); ?>
						</p>
						<input id="cartbounty_last_time_bubble_displayed" type="hidden" name="cartbounty_last_time_bubble_displayed" value="<?php echo current_time('mysql'); //Set activation time when we last displayed the bubble to current time so that next time it would display after a specified period of time ?>" />
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
			($this->total_captured_abandoned_cart_count() > 9 && get_option('cartbounty_times_review_declined') < 1 && !get_option('cartbounty_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 29 && get_option('cartbounty_times_review_declined') < 2 && !get_option('cartbounty_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 99 && get_option('cartbounty_times_review_declined') < 3 && !get_option('cartbounty_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 299 && get_option('cartbounty_times_review_declined') < 4 && !get_option('cartbounty_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 499 && get_option('cartbounty_times_review_declined') < 5 && !get_option('cartbounty_review_submitted')) ||
			($this->total_captured_abandoned_cart_count() > 999 && get_option('cartbounty_times_review_declined') < 6 && !get_option('cartbounty_review_submitted'))
		){
			$bubble_type = '#cartbounty-review';
			$display_bubble = true; //Show the bubble
		}elseif($this->total_captured_abandoned_cart_count() > 5 && $this->days_have_passed('cartbounty_last_time_bubble_displayed', 18 )){ //If we have more than 5 abandoned carts or the user has deleted more than 10 abandoned carts the last time bubble was displayed was 18 days ago, display the bubble info about Pro version
			$bubble_type = '#cartbounty-go-pro';
			$display_bubble = true; //Show the bubble
		}else{
			$display_bubble = false; //Don't show the bubble just yet
		}
		
		if($display_bubble){ //Check ff we should display the bubble ?>
			<script>
				jQuery(document).ready(function($) {
					var bubble = $(<?php echo "'". $bubble_type ."'"; ?>);
					var close = $('.cartbounty-close, .cartbounty-review-submitted');
					
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
	 * Returns the count of total captured abandoned carts
	 *
	 * @since    2.1
	 * @return 	 number
	 */
	function total_captured_abandoned_cart_count(){
		if ( false === ( $captured_abandoned_cart_count = get_transient( 'cartbounty_captured_abandoned_cart_count' ))){ //If value is not cached or has expired
			$captured_abandoned_cart_count = get_option('cartbounty_captured_abandoned_cart_count');
			set_transient( 'cartbounty_captured_abandoned_cart_count', $captured_abandoned_cart_count, 60 * 10 ); //Temporary cache will expire in 10 minutes
		}
		
		return $captured_abandoned_cart_count;
	}

	/**
	 * Sets the path to language folder for internationalization
	 *
	 * @since    2.1
	 */
	function cartbounty_text_domain(){
		return load_plugin_textdomain( CARTBOUNTY_TEXT_DOMAIN, false, basename( plugin_dir_path( __DIR__ ) ) . '/languages' );
	}

	/**
	 * Function removes empty abandoned carts that do not have any products and are older than 1 day
	 *
	 * @since    3.0
	 */
	function delete_empty_carts(){
		
		global $wpdb;
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix
		$time = $this->get_time_intervals();

		//Deleting row from database
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM ". $table_name ."
				WHERE cart_contents = '' AND
				time < %s",
				$time['day']
			)
		);

		if($count){
			$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
			$public->decrease_captured_abandoned_cart_count( $count );
		}
		
	}

	/**
	 * Function to clear cart data from row
	 *
	 * @since    3.0
	 */
	function clear_cart_data(){
		
		global $wpdb;
		$table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix
		
		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if(isset(WC()->session)){

			$cartbounty_session_id = WC()->session->get('cartbounty_session_id');
			if(isset($cartbounty_session_id)){
				$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
				$cart_data = $public->read_cart();
				$cart_currency = $cart_data['cart_currency'];
				$current_time = $cart_data['current_time'];
				
				//Cleaning Cart data
				$wpdb->prepare('%s',
					$wpdb->update(
						$table_name,
						array(
							'cart_contents'	=>	'',
							'cart_total'	=>	0,
							'currency'		=>	sanitize_text_field( $cart_currency ),
							'time'			=>	sanitize_text_field( $current_time )
						),
						array('session_id' => $cartbounty_session_id),
						array('%s', '%s'),
						array('%s')
					)
				);
			}
		}
	}

	/**
	 * Function returns different expressions depending on the amount of captured carts
	 *
	 * @since    3.2.1
	 * @return 	 String
	 */
	function get_expressions(){

		if($this->total_captured_abandoned_cart_count() <= 10){
			$expressions = array(
				'exclamation' => __('Congrats!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_captured_abandoned_cart_count() <= 30){
			$expressions = array(
				'exclamation' => __('Awesome!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_captured_abandoned_cart_count() <= 100){
			$expressions = array(
				'exclamation' => __('Amazing!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_captured_abandoned_cart_count() <= 300){
			$expressions = array(
				'exclamation' => __('Incredible!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_captured_abandoned_cart_count() <= 500){
			$expressions = array(
				'exclamation' => __('Crazy!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_captured_abandoned_cart_count() <= 1000){
			$expressions = array(
				'exclamation' => __('Fantastic!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}else{
			$expressions = array(
				'exclamation' => __('Insane!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}

		return $expressions;
	}

	/**
	 * Function returns Exit Intent icon as SVG code
	 *
	 * @since    3.0
	 * @return 	 String
	 * @param    $current    Current active tab - string
	 */
	public function exit_intent_svg_icon( $current ){
		$color = '#555';
		if($current == 'exit_intent'){
			$color = '#000';
		}
		return base64_encode('<svg height="18px" style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 61.75 63.11"><path d="M26.32,6.24A6.24,6.24,0,1,1,20.07,0a6.24,6.24,0,0,1,6.24,6.24h0Z"/><path d="M55.43,39.26C48.88,43.09,45,37.47,42,32.07c-0.13-.52-5.27-10.44-7.77-14.79,4.89-1.56,9.35-.13,12.86,4.79,2.85,4,9.53.16,6.64-3.88C46.94,8.67,36.8,6.3,26.66,12.32c-0.42.25-2.33,1.3-2.76,1.56-6.31,3.75-12.17,3-16.54-3.1-2.86-4-9.54-.16-6.65,3.89,5.59,7.82,13.43,10.8,21.67,8.27,2.59,4.45,5,9,7.41,13.54-3.49,1.79-10,5.39-11.71,8.71C16,49.32,14,53.53,12,57.7c-2.17,4.48,4.8,7.73,7,3.27,1.92-4,6.28-12.22,6.53-12.43,3.48-3,12.25-7.18,12.44-7.28,5.35,6.79,12.81,10.52,21.75,5.3,4.71-2.75.45-10.07-4.27-7.31h0Z"/></svg>
		');
    }

    /**
	 * Function returns CartBounty icon as SVG code
	 *
	 * @since    4.6
	 * @return 	 String
	 * @param    $current    Current active tab - string
	 */
	public function cartbounty_svg_icon( $current ){
		$color = '#555';
		if($current == 'carts'){
			$color = '#000';
		}
		return base64_encode('<svg height="18px" style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26.34 29.48"><path d="M7.65,24c-2.43,0-3.54-1.51-3.54-2.91V3.44C3.77,3.34,3,3.15,2.48,3L.9,2.59A1.28,1.28,0,0,1,0,1.15,1.32,1.32,0,0,1,1.34,0a1.52,1.52,0,0,1,.42.06l.68.2c1.38.41,2.89.85,3.25,1A1.72,1.72,0,0,1,6.79,2.8V5.16L24.67,7.53a1.75,1.75,0,0,1,1.67,2v6.1a3.45,3.45,0,0,1-3.59,3.62h-16v1.68c0,.14,0,.47,1.07.47H21.13a1.32,1.32,0,0,1,1.29,1.38,1.35,1.35,0,0,1-.25.79,1.18,1.18,0,0,1-1,.5Zm-.86-7.5,15.76,0c.41,0,1.11,0,1.11-1.45V10c-3-.41-13.49-1.69-16.87-2.11Z"/><path d="M21.78,29.48a4,4,0,1,1,4-4A4,4,0,0,1,21.78,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.35,1.35,0,0,0,21.78,24.11ZM10.14,29.48a4,4,0,1,1,4-4A4,4,0,0,1,10.14,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.34,1.34,0,0,0,10.14,24.11Z"/><path d="M18.61,18.91a1.34,1.34,0,0,1-1.34-1.34v-9a1.34,1.34,0,1,1,2.67,0v9A1.34,1.34,0,0,1,18.61,18.91Z"/><path d="M12.05,18.87a1.32,1.32,0,0,1-1.34-1.29v-10a1.34,1.34,0,0,1,2.68,0v10A1.32,1.32,0,0,1,12.05,18.87Z"/></svg>
		');
    }

    /**
	 * Function tries to move the email field higher in the checkout form
	 *
	 * @since    4.5
	 * @return 	 Array
	 * @param 	 $fields    Checkout form fields
	 */ 
	public function lift_checkout_email_field( $fields ) {
		$lift_email_on = esc_attr( get_option('cartbounty_lift_email'));
		if($lift_email_on){ //Changing the priority and moving the email higher
			$fields['billing_email']['priority'] = 5;
		}
		return $fields;
	}

	/**
	 * Function prepares and returns an array of different time intervals used for calulating time substractions
	 *
	 * @since    4.6
	 * @return 	 Array
	 */
	public function get_time_intervals(){
		//Calculating time intervals
		$datetime = current_time( 'mysql' );
		return array(
			'cart_abandoned' 	=> date( 'Y-m-d H:i:s', strtotime( '-' . CARTBOUNTY_STILL_SHOPPING . ' minutes', strtotime( $datetime ) ) ),
			'old_cart' 			=> date( 'Y-m-d H:i:s', strtotime( '-' . CARTBOUNTY_NEW_NOTICE . ' minutes', strtotime( $datetime ) ) ),
			'day' 				=> date( 'Y-m-d H:i:s', strtotime( '-1 day', strtotime( $datetime ) ) )
		);
	}
}