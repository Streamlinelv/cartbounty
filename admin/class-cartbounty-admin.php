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

		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();
		
		//Do not continue if we are not on CARTBOUNTY plugin page
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
		
		//Do not continue if we are not on CARTBOUNTY plugin page
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
			$cartbounty_admin_menu_page = add_submenu_page( 'woocommerce', CARTBOUNTY_PLUGIN_NAME, __('CartBounty Abandoned carts', CARTBOUNTY_TEXT_DOMAIN), 'list_users', CARTBOUNTY_TEXT_DOMAIN, array($this,'display_page'));
		}else{
			$cartbounty_admin_menu_page = add_menu_page( CARTBOUNTY_PLUGIN_NAME, __('CartBounty Abandoned carts', CARTBOUNTY_TEXT_DOMAIN), 'list_users', CARTBOUNTY_TEXT_DOMAIN, array($this,'display_page'), 'dashicons-archive' );
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
			
			//Counting newly abandoned carts
			$order_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id) FROM $table_name
					WHERE
					cart_contents != '' AND time < (NOW() - INTERVAL %d MINUTE) AND 
					time > (NOW() - INTERVAL %d MINUTE)"
				, CARTBOUNTY_STILL_SHOPPING, CARTBOUNTY_NEW_NOTICE )
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
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', CARTBOUNTY_TEXT_DOMAIN ), $deleted_row_count ) . '</p></div>';
		}
		?>

		<div id="cartbounty-page-wrapper" class="wrap">

			<?php if ( isset ( $_GET['tab'] ) ){
				$this->create_admin_tabs($_GET['tab']);
			}else{
				$this->create_admin_tabs('carts');
			}

			if ( $pagenow == 'admin.php' && $_GET['page'] == CARTBOUNTY_TEXT_DOMAIN ){
				if (isset($_GET['tab'])){
					$tab = $_GET['tab'];
				}else{
					$tab = 'carts';
				}

				if($tab == 'exit_intent'): //Exit intent output ?>
					<h1><?php echo CARTBOUNTY_ABREVIATION; ?> <?php echo __('Exit Intent', CARTBOUNTY_TEXT_DOMAIN); ?></h1>
					<p class="cartbounty-description"><?php echo __('With the help of Exit Intent you can capture even more abandoned carts by displaying a message including an e-mail field that the customer can fill to save his shopping cart. You can even offer to send a discount code.', CARTBOUNTY_TEXT_DOMAIN); ?></p>
					<p class="cartbounty-description"><?php echo __('Please note that the Exit Intent will only be showed to unregistered users once per hour after they have added an item to their cart and try to leave your shop.', CARTBOUNTY_TEXT_DOMAIN); ?></p>
					<p class="cartbounty-description"><?php echo sprintf(__('If you would like to customize the content of your Exit Intent, please see <a href="%s" target="_blank">How to change the content and image of the Exit Intent</a>.', CARTBOUNTY_TEXT_DOMAIN), 'https://www.cartbounty.com/#modify-exit-intent-content'); ?></p>
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
	 */
	function create_admin_tabs( $current = 'carts' ){
		$tabs = array( 'carts' => __('Abandoned carts', CARTBOUNTY_TEXT_DOMAIN), 'exit_intent' => __('Exit Intent', CARTBOUNTY_TEXT_DOMAIN));
		echo '<h2 class="nav-tab-wrapper">';
		$icon_image = NULL;
		
		foreach( $tabs as $tab => $name ){
			if($name == 'Exit Intent'){
				//$icon_image = '';
				$icon_class = 'cartbounty-exit-intent-icon';
				$icon_image = "<img src='data:image/svg+xml;base64," . $this->exit_intent_svg_icon() . "' alt=''  />";
			}
			else{
				$icon_class = 'dashicons-cart';
			}
			
			$class = ( $tab == $current ) ? ' nav-tab-active' : ''; //if the tab is open, an additional class, nav-tab-active, is added
			echo "<a class='nav-tab$class' href='?page=". CARTBOUNTY_TEXT_DOMAIN ."&tab=$tab'><span class='cartbounty-tab-icon dashicons $icon_class' >$icon_image</span><span class='cartbounty-tab-name'>$name</span></a>";
		}
		echo '</h2>';
	}

	/**
	 * Function adds additional intervals to default Wordpress cron intervals (hourly, twicedaily, daily). Interval provided in minutes
	 *
	 * @since    3.0
	 */
	function additional_cron_intervals(){
		$interval['cartbounty_remove_empty_carts_interval'] = array( //Defining cron Interval for removing abandoned carts that do not have products
			'interval' => 12 * 60 * 60,
			'display' => 'Twice a day'
		);
		return $interval;
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
	 * @return array
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
	 * @since     3.0
	 */
	function disableField($options = array()){
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
						<?php $expression = $this->get_expressions(); ?>
						<h2><?php echo sprintf(__('%s You have already captured %d abandoned carts!', CARTBOUNTY_TEXT_DOMAIN ), $expression['exclamation'], $this->total_captured_abandoned_cart_count()); ?></h2>
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
					<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=bubble&utm_campaign=cartbounty" title="<?php __('Get CartBounty - Save and recover abandoned carts for WooCommerce Pro', CARTBOUNTY_TEXT_DOMAIN); ?>" target="_blank">
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
	 * @since 	2.1
	 * @return 	number
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
	 * @since 	2.1
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

		//Deleting row from database
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM ". $table_name ."
				WHERE cart_contents = '' AND
				time < (NOW() - INTERVAL %d DAY)",
				1
			)
		);

		if($count){
			$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
			$public->decrease_captured_abandoned_cart_count( $count );
		}
		
	}

	/**
	 * Function returns different expressions depending on the amount of captured carts
	 *
	 * @since    3.2.1
	 * return: 	 String
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
	 * return: 	 String
	 */
	public function exit_intent_svg_icon(){
		return base64_encode('<?xml version="1.0" encoding="UTF-8"?>
			<svg height="18px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 61.75 63.11"><defs><style>.cls-1{fill:#1B1A19;}</style></defs><title>Untitled-2</title><path class="cls-1" d="M26.32,6.24A6.24,6.24,0,1,1,20.07,0a6.24,6.24,0,0,1,6.24,6.24h0Z"/><path class="cls-1" d="M55.43,39.26C48.88,43.09,45,37.47,42,32.07c-0.13-.52-5.27-10.44-7.77-14.79,4.89-1.56,9.35-.13,12.86,4.79,2.85,4,9.53.16,6.64-3.88C46.94,8.67,36.8,6.3,26.66,12.32c-0.42.25-2.33,1.3-2.76,1.56-6.31,3.75-12.17,3-16.54-3.1-2.86-4-9.54-.16-6.65,3.89,5.59,7.82,13.43,10.8,21.67,8.27,2.59,4.45,5,9,7.41,13.54-3.49,1.79-10,5.39-11.71,8.71C16,49.32,14,53.53,12,57.7c-2.17,4.48,4.8,7.73,7,3.27,1.92-4,6.28-12.22,6.53-12.43,3.48-3,12.25-7.18,12.44-7.28,5.35,6.79,12.81,10.52,21.75,5.3,4.71-2.75.45-10.07-4.27-7.31h0Z"/></svg>
		');
    }

}