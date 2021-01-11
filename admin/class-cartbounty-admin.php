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
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		if ( isset( $submenu['woocommerce'] ) ) { //If WooCommerce Menu exists
			$time = $this->get_time_intervals();
			$where_sentence = $this->get_where_sentence( 'all' );
			// Retrieve from database rows that have not been emailed and are older than 60 minutes
			$cart_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id)
					FROM $cart_table
					WHERE
					cart_contents != ''
					$where_sentence AND
					time < %s AND 
					time > %s ",
					$time['cart_abandoned'],
					$time['old_cart']
				)
			);

			foreach ( $submenu['woocommerce'] as $key => $menu_item ) { //Go through all Sumenu sections of WooCommerce and look for CartBounty Abandoned carts
				if ( 0 === strpos( $menu_item[0], __('CartBounty Abandoned carts', CARTBOUNTY_TEXT_DOMAIN))) {
					$submenu['woocommerce'][$key][0] .= ' <span class="new-abandoned update-plugins count-' . $cart_count . '">' .  $cart_count .'</span>';
				}
			}
		}
	}

	/**
	 * Adds Screen options tab
	 *
	 * @since    5.0
	 */
	function register_admin_screen_options_tab(){
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();

		// Check if we are on CartBounty page
		if(!is_object($screen) || $screen->id != $cartbounty_admin_menu_page){
			return;

		}else{
			//Outputing how many rows we would like to see on the page
			$option = 'per_page';
			$args = array(
				'label' => __('Carts per page: ', CARTBOUNTY_TEXT_DOMAIN),
				'default' => 10,
				'option' => 'cartbounty_carts_per_page'
			);

			//Adding screen options only on Abandoned cart page
			if(isset($_GET['tab'])){
				if($_GET['tab'] == 'carts'){
					add_screen_option( $option, $args );
				}
			}elseif(!isset($_GET['tab'])){
				add_screen_option( $option, $args );
			}
		}
	}

	/**
	 * Saves settings displayed under Screen options
	 *
	 * @since    1.0
	 */
	function save_page_options(){
		if ( isset( $_POST['wp_screen_options'] ) && is_array( $_POST['wp_screen_options'] ) ) {
			check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

			global $cartbounty_admin_menu_page;
			$screen = get_current_screen();

			//Do not continue if we are not on CartBounty Pro plugin page
			if(!is_object($screen) || $screen->id != $cartbounty_admin_menu_page){
				return;
			}

			$user = wp_get_current_user();
	        if ( ! $user ) {
	            return;
	        }

	        $option = $_POST['wp_screen_options']['option'];
	        $value  = $_POST['wp_screen_options']['value'];
	 
	        if ( sanitize_key( $option ) != $option ) {
	            return;
	        }

	        update_user_meta( $user->ID, $option, $value );
	    }
	}
	
	/**
	 * Display the abandoned carts and settings under admin page
	 *
	 * @since    1.3
	 */
	function display_page(){
		global $pagenow;
		
		if ( !current_user_can( 'list_users' )){
			wp_die( __( 'You do not have sufficient permissions to access this page.', CARTBOUNTY_TEXT_DOMAIN ) );
		}?>

		<div id="cartbounty-page-wrapper" class="wrap<?php if(get_option('cartbounty_hide_images')) {echo " cartbounty-without-thumbnails";}?>">
			<h1><?php echo CARTBOUNTY_ABREVIATION; ?></h1>

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

				if (isset($_GET['section'])){
					$current_section = $_GET['section'];
				}else{
					$current_section = '';
				}?>

				<?php if($current_section): //If one of the sections has been opened

					$sections = $this->get_sections( $tab );
					foreach( $sections as $key => $section ){
						if($current_section == $key): //Determine which section is open to output correct contents ?>
							<div id="cartbounty-content-container">
								<div class="cartbounty-row">
									<div class="cartbounty-sidebar cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3"><?php $this->display_sections( $current_section, $tab ); ?></div>
									<div class="cartbounty-content cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-container">
											<h2 class="cartbounty-section-title"><?php echo $section['name']; ?></h2>
											<?php $this->section_contents( $key ); ?>
										</div>
									</div>
								</div>
							</div>
						<?php endif;
					}?>

				<?php elseif($tab == 'recovery'): //Recovery tab ?>

					<div id="cartbounty-content-container">
						<div class="cartbounty-row">
							<div class="cartbounty-sidebar cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3"><?php $this->display_sections( $current_section, $tab ); ?></div>
							<div class="cartbounty-content cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
								<h2 class="cartbounty-section-title"><?php echo __('Recovery', CARTBOUNTY_TEXT_DOMAIN); ?></h2>
								<div class="cartbounty-section-intro"><?php echo sprintf(
									/* translators: %s - URL link tags */
									__('Automate your abandoned cart recovery by sending automated recovery emails to your visitors.<br/>Please consider upgrading to %sCartBounty Pro%s to connect one of the professional automation services listed below.', CARTBOUNTY_TEXT_DOMAIN),
										'<a href="'. CARTBOUNTY_LICENSE_SERVER_URL.'?utm_source='. urlencode(get_bloginfo('url')) .'&utm_medium=recovery&utm_campaign=cartbounty" target="_blank">',
										'</a>'
									); ?>
									
								</div>
								<div class="cartbounty-row cartbounty-flex">
									<?php
									$recovery_items = $this->get_sections( $tab );
									foreach( $recovery_items as $key => $item ): ?>
										<?php $button = __('Connect', CARTBOUNTY_TEXT_DOMAIN); 
											if($item['connected']){
												$button = __('Edit', CARTBOUNTY_TEXT_DOMAIN);
											}elseif(!$item['availability']){
												$button = __('Get Pro to connect', CARTBOUNTY_TEXT_DOMAIN);
											}
										?>
										<div class="cartbounty-section-item-container cartbounty-col-sm-6 cartbounty-col-lg-4">
											<div class="cartbounty-section-item<?php if($item['connected']){echo ' cartbounty-connected'; }?><?php if(!$item['availability']){echo ' cartbounty-unavailable'; }?>">
												<?php if($item['availability']){
													$link = '?page='. CARTBOUNTY .'&tab='. $tab .'&section='. $key;
												}else{
													$link = CARTBOUNTY_LICENSE_SERVER_URL;
												}?>
												<div class="cartbounty-section-image">
													<?php echo $this->get_connection( $item['connected'], true, $tab ); ?>
													<a href="<?php echo $link; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=recovery_<?php echo "$key"; ?>&utm_campaign=cartbounty" title="<?php echo $item['name']; ?>"><?php echo $this->get_icon( $key, false, false, true ); ?></a>
												</div>
												<div class="cartbounty-section-content">
													<h3><a href="<?php echo $item['info_link']; ?>" title="<?php echo $item['name']; ?>" target="_blank"><?php echo $item['name']; ?></a></h3>
													<?php echo $item['description']; ?>
													<a class="button cartbounty-button<?php if(!$item['availability']){echo " button-primary";}?>" href="<?php echo $link; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=recovery_<?php echo "$key"; ?>&utm_campaign=cartbounty"<?php if(!$item['availability']){echo ' target="_blank"'; }?>><?php echo $button; ?></a>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>

				<?php elseif($tab == 'tools'): //Tools tab ?>

					<div id="cartbounty-content-container">
						<div class="cartbounty-row">
							<div class="cartbounty-sidebar cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3"><?php $this->display_sections( $current_section, $tab ); ?></div>
							<div class="cartbounty-content cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
								<h2 class="cartbounty-section-title"><?php echo __('Tools', CARTBOUNTY_TEXT_DOMAIN); ?></h2>
								<div class="cartbounty-section-intro"><?php echo __('Here you will find some special tools that will enable you to discover even more bounty. Increase your chances of getting more recoverable abandoned carts. You can enable one or all of them, just make sure not to overwhelm your visitors with information :)', CARTBOUNTY_TEXT_DOMAIN); ?></div>
								<div class="cartbounty-row cartbounty-flex">
									<?php
									$tools_items = $this->get_sections( $tab );
									foreach( $tools_items as $key => $item ): ?>
										<?php $button = __('Enable', CARTBOUNTY_TEXT_DOMAIN); 
											if($item['connected']){
												$button = __('Edit', CARTBOUNTY_TEXT_DOMAIN);
											}
										?>
										<div class="cartbounty-section-item-container cartbounty-col-sm-6 cartbounty-col-lg-4">
											<div class="cartbounty-section-item<?php if($item['connected']){echo ' cartbounty-connected'; }?>">
												<?php $link = '?page='. CARTBOUNTY .'&tab='. $tab .'&section='. $key; ?>
												<div class="cartbounty-section-image">
													<?php echo $this->get_connection( $item['connected'], true, $tab ); ?>
													<a href="<?php echo $link; ?>" title="<?php echo $item['name']; ?>"><?php echo $this->get_icon( $key, false, false, true ); ?></a>
												</div>
												<div class="cartbounty-section-content">
													<h3><a href="<?php echo $link; ?>" title="<?php echo $item['name']; ?>"><?php echo $item['name']; ?></a></h3>
													<?php echo $item['description']; ?>
													<a class="button cartbounty-button" href="<?php echo $link; ?>"><?php echo $button; ?></a>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>

				<?php elseif($tab == 'settings'): //Settings tab ?>

					<div id="cartbounty-content-container">
						<div class="cartbounty-settings-container">
							<form method="post" action="options.php">
								<?php
									$lift_email_on = esc_attr( get_option('cartbounty_lift_email') );
									$hide_images_on = esc_attr( get_option('cartbounty_hide_images') );
									$exclude_ghost_carts = esc_attr( get_option('cartbounty_exclude_ghost_carts') );
								?>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php echo __('Ghost carts', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php echo __('Ghost carts are carts that can’t be identified since the visitor has not provided neither email nor phone number.', CARTBOUNTY_TEXT_DOMAIN); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-exclude-ghost-carts" class="cartbounty-switch">
												<input id="cartbounty-exclude-ghost-carts" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exclude_ghost_carts" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exclude_ghost_carts, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-exclude-ghost-carts">
												<?php echo __('Exclude ghost carts', CARTBOUNTY_TEXT_DOMAIN); ?>
											</label>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php echo __('Notifications', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php echo __('Receive email notifications about newly abandoned carts. Please note, that you will not get emails about ghost carts.', CARTBOUNTY_TEXT_DOMAIN); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group">
											<label for="cartbounty_notification_email"><?php echo __('Admin email', CARTBOUNTY_TEXT_DOMAIN); ?></label>
											<input id="cartbounty_notification_email" class="cartbounty-text" type="email" name="cartbounty_notification_email" value="<?php echo esc_attr( get_option('cartbounty_notification_email') ); ?>" placeholder="<?php echo get_option( 'admin_email' );?>" <?php echo $this->disable_field(); ?> multiple />
											<p class='cartbounty-additional-information'>
												<?php echo __('You can add multiple emails separated by a comma.', CARTBOUNTY_TEXT_DOMAIN); ?>
											</p>
										</div>
										<div class="cartbounty-settings-group">
											<label for="cartbounty_notification_frequency"><?php echo __('Check for new abandoned carts', CARTBOUNTY_TEXT_DOMAIN); ?></label>
											<?php $this->display_frequencies(); ?>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php echo __('Protection', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php echo sprintf(
											/* translators: %s - URL link */ __('In case you feel that bots might be leaving recoverable abandoned carts. Please <a href="%s" title="Prevent bots from leaving ghost carts" target="_blank">see this</a> to learn how to prevent bots from leaving ghost carts.', CARTBOUNTY_TEXT_DOMAIN), BOTS_FAQ_LINK ); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty_recaptcha" class="cartbounty-switch cartbounty-unavailable">
												<input id="cartbounty_recaptcha" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty_recaptcha" class="cartbounty-unavailable"><?php echo __('Enable reCAPTCHA v3', CARTBOUNTY_TEXT_DOMAIN); ?></label>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'recaptcha' ); ?></i>
											</p>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php echo __('Miscellaneous', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php echo __('Other settings that may be useful.', CARTBOUNTY_TEXT_DOMAIN);?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle<?php if($lift_email_on){ echo ' cartbounty-checked'; }?>">
											<label for="cartbounty-lift-email" class="cartbounty-switch cartbounty-control-visibility">
												<input id="cartbounty-lift-email" class="cartbounty-checkbox" type="checkbox" name="cartbounty_lift_email" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $lift_email_on, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-lift-email" class="cartbounty-control-visibility"><?php echo __('Lift email field', CARTBOUNTY_TEXT_DOMAIN); ?></label>
											<p class='cartbounty-additional-information'>
												<?php echo __('You could increase the chances of saving recoverable abandoned carts by moving the email field to the top of your checkout form.', CARTBOUNTY_TEXT_DOMAIN);
												echo " <i class='cartbounty-hidden'>". __('Please test the checkout after enabling this, as sometimes it can cause issues or not raise the field if you have a custom checkout.', CARTBOUNTY_TEXT_DOMAIN) ."</i>";
												 ?>
											</p>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-hide-images" class="cartbounty-switch">
												<input id="cartbounty-hide-images" class="cartbounty-checkbox" type="checkbox" name="cartbounty_hide_images" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $hide_images_on, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-hide-images"><?php echo __('Display abandoned cart contents in a list', CARTBOUNTY_TEXT_DOMAIN); ?></label>
											<p class='cartbounty-additional-information'>
												<?php echo __('This will only affect how abandoned cart contents are displayed in the Abandoned carts tab.', CARTBOUNTY_TEXT_DOMAIN); ?>
											</p>
										</div>
									</div>
								</div>
								<div class='cartbounty-button-row'>
									<?php
									settings_fields( 'cartbounty-settings' );
									do_settings_sections( 'cartbounty-settings' );
									if(current_user_can( 'manage_options' )){
										echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". __('Save settings', CARTBOUNTY_TEXT_DOMAIN) ."</button>";
									}?>
								</div>
							</form>
						</div>
					</div>

				<?php else: //Abandoned cart tab ?>

					<?php
						require_once plugin_dir_path( __FILE__ ) . 'class-cartbounty-admin-table.php';
						$table = new CartBounty_Table();
						$table->prepare_items();
						
						//Output table contents
						$message = '';
						if ('delete' === $table->current_action()) {
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

						$cart_status = 'all';
				        if (isset($_GET['cart-status'])){
				            $cart_status = $_GET['cart-status'];
				        }
					?>
					<?php do_action('cartbounty_after_page_title'); ?>
					<?php echo $message; 
					if ($this->get_cart_count( 'all' ) == 0): //If no abandoned carts, then output this note ?>
						<p id="cartbounty-no-carts-message">
							<?php echo __( 'Looks like you do not have any saved Abandoned carts yet.<br/>But do not worry, as soon as someone fills the <strong>Email</strong> or <strong>Phone number</strong> fields of your WooCommerce Checkout form and abandons the cart, it will automatically appear here.', CARTBOUNTY_TEXT_DOMAIN); ?>
						</p>
					<?php else: ?>
						<form id="cartbounty-table" method="GET">
							<div class="cartbounty-row">
								<div class="cartbounty-col-sm-12">
									<?php $this->display_cart_statuses( $cart_status, $tab);?>
								</div>
							</div>
							<input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
							<?php $table->display(); ?>
						</form>
					<?php endif; ?>
				<?php endif;
			}?>
		</div>
	<?php
	}

	/**
	 * Method creates tabs on plugin page
	 *
	 * @since    3.0
	 * @param    $current    Currently open tab - string
	 */
	function create_admin_tabs( $current = 'carts' ){
		$tabs = array(
			'carts' => __('Abandoned carts', CARTBOUNTY_TEXT_DOMAIN),
			'recovery' => __('Recovery', CARTBOUNTY_TEXT_DOMAIN),
			'tools' => __('Tools', CARTBOUNTY_TEXT_DOMAIN),
			'settings' => __('Settings', CARTBOUNTY_TEXT_DOMAIN)
		);
		echo '<h2 class="nav-tab-wrapper">';
		
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : ''; //if the tab is open, an additional class, nav-tab-active, is added
			echo "<a class='cartbounty-tab nav-tab$class' href='?page=". CARTBOUNTY ."&tab=$tab'>". $this->get_icon( $tab, $current, false, false ) ."<span class='cartbounty-tab-name'>$name</span></a>";
		}
		echo '</h2>';
	}

	/**
     * Method displays available sections
     *
     * @since    6.0
     * @return   string
     * @param 	 string    $active_section    	Currently open section item
     * @param    string    $tab    		  		Tab name
     */
    function display_sections( $active_section, $tab ){
    	$sections = $this->get_sections( $tab );
    	$all_title = __('All integrations', CARTBOUNTY_TEXT_DOMAIN);

    	if($tab == 'tools'){
    		$all_title = __('All tools', CARTBOUNTY_TEXT_DOMAIN);
    	}

    	//Generating sections for large screens
    	echo '<ul id="cartbounty-sections" class="cartbounty-hidden-xs">';
    	foreach( $sections as $key => $section ){
    		$class = ( $key == $active_section ) ? 'class="current"' : '';
    		$availability_class = (!$section['availability']) ? 'class="cartbounty-unavailable"' : '';
    		$link = "?page=". CARTBOUNTY ."&tab=$tab&section=$key";
    		if(!$section['availability']){
    			$link = '#';
    		}

	    	echo "<li $availability_class><a href='$link' title='". $section['name']. "' $class>". $this->get_icon( $key, $active_section, true, false) ." <span class='cartbounty-section-name'>". $section['name']. "</span>". $this->get_connection( $section['connected'], false, false ) ."</a></li>";
    	}
    	echo '</ul>';

    	//Generating sections for small screens
    	echo '<select id="cartbounty-mobile-sections" class="cartbounty-select cartbounty-visible-xs" onchange="window.location.href=this.value" style="display: none;">';
    		echo "<option value='?page=". CARTBOUNTY ."&tab=$tab'>". $all_title ."</option>";
	    	foreach( $sections as $key => $section ){
	    		$link = "?page=". CARTBOUNTY ."&tab=$tab&section=$key";
	    		if($section['availability']){
	    			echo "<option value='$link' ". selected( $active_section, $key, false ) .">". $section['name']. "</option>";
	    		}
	    	}
    	echo '</select>';
    }

	/**
     * Method displays available time intervals at which we are checking for notifications
     *
     * @since    6.0
     * @return   string
     */
    function display_frequencies(){
    	$active_frequency = get_option( 'cartbounty_notification_frequency' );
    	if(!$active_frequency){
			$active_frequency = array('hours' => 60);
		}

    	$intervals = array(
    		'10' 	=> __('Every 10 minutes', CARTBOUNTY_TEXT_DOMAIN),
    		'20' 	=> __('Every 20 minutes', CARTBOUNTY_TEXT_DOMAIN),
    		'30' 	=> __('Every 30 minutes', CARTBOUNTY_TEXT_DOMAIN),
    		'60' 	=> __('Every hour', CARTBOUNTY_TEXT_DOMAIN),
    		'120' 	=> __('Every 2 hours', CARTBOUNTY_TEXT_DOMAIN),
    		'180' 	=> __('Every 3 hours', CARTBOUNTY_TEXT_DOMAIN),
    		'240' 	=> __('Every 4 hours', CARTBOUNTY_TEXT_DOMAIN),
    		'300' 	=> __('Every 5 hours', CARTBOUNTY_TEXT_DOMAIN),
    		'360' 	=> __('Every 6 hours', CARTBOUNTY_TEXT_DOMAIN),
    		'720' 	=> __('Twice a day', CARTBOUNTY_TEXT_DOMAIN),
    		'1440' 	=> __('Once a day', CARTBOUNTY_TEXT_DOMAIN),
    		'2880' 	=> __('Once every 2 days', CARTBOUNTY_TEXT_DOMAIN),
    		'0' 	=> __('Disable notifications', CARTBOUNTY_TEXT_DOMAIN)
    	);

    	echo '<select id="cartbounty_notification_frequency" class="cartbounty-select" name="cartbounty_notification_frequency[hours]">';
	    	foreach( $intervals as $key => $interval ){
		    	echo "<option value='$key' ". selected( $active_frequency['hours'], $key, false ) .">$interval</option>";
	    	}
    	echo '</select>';
    }

    /**
	 * Method returns sections
	 *
	 * @since    6.0
	 * @return   array
	 * @param    string    $tab    		  Tab name
	 */
	function get_sections( $tab ){
		$sections = array();

		if($tab == 'recovery'){

			$sections = array(
				'activecampaign'	=> array(
					'name'				=> __('ActiveCampaign', CARTBOUNTY_TEXT_DOMAIN),
					'connected'			=> false,
					'availability'		=> false,
					'info_link'			=> CARTBOUNTY_ACTIVECAMPAIGN_TRIAL_LINK,
					'description'		=> __("<p>ActiveCampaign is awesome and allows creating different If/Else statements to setup elaborate rules for sending abandoned cart recovery emails.</p><p>In contrast to MailChimp, it allows sending reminder email series without the requirement to subscribe.</p>", CARTBOUNTY_TEXT_DOMAIN)
				),
				'getresponse'	=> array(
					'name'				=> __('GetResponse', CARTBOUNTY_TEXT_DOMAIN),
					'connected'			=> false,
					'availability'		=> false,
					'info_link'			=> CARTBOUNTY_GETRESPONSE_TRIAL_LINK,
					'description'		=> __("<p>GetResponse offers efficient and beautifully designed email marketing platform to recover abandoned carts. It is a professional email marketing system with awesome email design options and beautifully pre-designed email templates.</p>", CARTBOUNTY_TEXT_DOMAIN)
				),
				'mailchimp'	=> array(
					'name'				=> __('MailChimp', CARTBOUNTY_TEXT_DOMAIN),
					'connected'			=> false,
					'availability'		=> false,
					'info_link'			=> CARTBOUNTY_MAILCHIMP_LINK,
					'description'		=> __("<p>MailChimp offers a forever free plan and allows to create both single and series of reminder emails (e.g. first email in the 1st hour of cart abandonment, 2nd after 24 hours etc.).</p><p>MailChimp will only send the 1st email in the series unless a user becomes a subscriber.</p>", CARTBOUNTY_TEXT_DOMAIN)
				)
			);
		}

		if($tab == 'tools'){
			$sections = array(
				'exit_intent'	=> array(
					'name'				=> __('Exit Intent', CARTBOUNTY_TEXT_DOMAIN),
					'connected'			=> esc_attr( get_option('cartbounty_exit_intent_status')) ? true : false,
					'availability'		=> true,
					'description'		=> __("<p>Save more recoverable abandoned carts by showcasing a popup message right before your customer tries to leave and offer an option to save his shopping cart by entering his email.</p>", CARTBOUNTY_TEXT_DOMAIN)
				)
			);
		}
		
		return $sections;
	}

	/**
	 * Method returns section contents
	 *
	 * @since    6.0
	 * @return   array
	 * @param    string    $active_section    	Currently open section item
	 */
	function section_contents( $active_section ){
		switch ( $active_section ) {

			case 'exit_intent':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo sprintf(
						/* translators: %s - Link */
						 __('With the help of Exit Intent you can capture even more abandoned carts by displaying a message including an email field that the customer can fill to save his shopping cart. You can even offer to send a discount code. Please note that the Exit Intent will only be showed to unregistered users once per hour after they have added an item to their cart and try to leave your shop. Learn <a href="%s" target="_blank" title="How to customize the contents of Exit Intent">how to customize the contents</a> of Exit Intent popup.', CARTBOUNTY_TEXT_DOMAIN), 'https://www.cartbounty.com/#modify-exit-intent-content');
					?>
				</div>
				<form method="post" action="options.php">
					<?php 
						settings_fields( 'cartbounty-settings-exit-intent' );
						do_settings_sections( 'cartbounty-settings-exit-intent' );
						$exit_intent_on = esc_attr( get_option('cartbounty_exit_intent_status'));
						$test_mode_on = esc_attr( get_option('cartbounty_exit_intent_test_mode'));
						$exit_intent_type = esc_attr( get_option('cartbounty_exit_intent_type'));
						$main_color = esc_attr( get_option('cartbounty_exit_intent_main_color'));
						$inverse_color = esc_attr( get_option('cartbounty_exit_intent_inverse_color'));
						$main_image = esc_attr( get_option('cartbounty_exit_intent_image'));
					?>

					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php echo __('General', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9<?php if($exit_intent_on){ echo ' cartbounty-checked-parent'; }?>">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-exit-intent-status" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-exit-intent-status" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_status" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exit_intent_on, false ); ?> autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-status" class="cartbounty-control-visibility"><?php echo __('Enable Exit Intent', CARTBOUNTY_TEXT_DOMAIN); ?></label>
							</div>
							<div class="cartbounty-settings-group cartbounty-toggle cartbounty-hidden">
								<label for="cartbounty-exit-intent-mobile-status" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-exit-intent-mobile-status" class="cartbounty-checkbox" type="checkbox" value="0" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-mobile-status" class="cartbounty-unavailable"><?php echo __('Enable mobile Exit Intent', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'mobile_exit_intent' ); ?></i>
								</p>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php echo __('Appearance', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php echo __('Adjust the visual appearance of your Exit Intent popup.', CARTBOUNTY_TEXT_DOMAIN); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<h4><?php echo __('Style', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
								<div id="cartbounty-exit-intent-center" class="cartbounty-exit-intent-type <?php if($exit_intent_type == 1){ echo "cartbounty-radio-active";} ?>">
									<label class="cartbounty-exit-intent-image" for="cartbounty-radiobutton-center">
										<em>
											<i>
												<img src="<?php echo plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ; ?>" title="" alt=""/>
											</i>
										</em>
										<input id="cartbounty-radiobutton-center" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_type" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exit_intent_type, false ); ?> autocomplete="off" />
										<?php echo __('Appear in center', CARTBOUNTY_TEXT_DOMAIN); ?>
									</label>
								</div>
								<div id="cartbounty-exit-intent-left" class="cartbounty-exit-intent-type <?php if($exit_intent_type == 2){ echo "cartbounty-radio-active";} ?>">
									<label class="cartbounty-exit-intent-image" for="cartbounty-radiobutton-left">
										<em>
											<i>
												<img src="<?php echo plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ; ?>" title="" alt=""/>
											</i>
											<span class="cartbounty-exit-intent-additional-style"><?php echo __('Upgrade to enable this style', CARTBOUNTY_TEXT_DOMAIN); ?>
												<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=ei_style&utm_campaign=cartbounty" class="button cartbounty-button" target="_blank"><?php echo __('Get Pro', CARTBOUNTY_TEXT_DOMAIN); ?></a>
											</span>
										</em>
										<input id="cartbounty-radiobutton-left" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_type" disabled autocomplete="off" />
										<?php echo __('Slide in from left', CARTBOUNTY_TEXT_DOMAIN); ?>
									</label>
								</div>
								<div id="cartbounty-exit-intent-fullscreen" class="cartbounty-exit-intent-type <?php if($exit_intent_type == 3){ echo "cartbounty-radio-active";} ?>">
									<label class="cartbounty-exit-intent-image" for="cartbounty-radiobutton-fullscreen">
										<em>
											<i>
												<img src="<?php echo plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ; ?>" title="" alt=""/>
											</i>
											<span class="cartbounty-exit-intent-additional-style"><?php echo __('Upgrade to enable this style', CARTBOUNTY_TEXT_DOMAIN); ?>
												<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=ei_style&utm_campaign=cartbounty" class="button cartbounty-button" target="_blank"><?php echo __('Get Pro', CARTBOUNTY_TEXT_DOMAIN); ?></a>
											</span>
										</em>
										<input id="cartbounty-radiobutton-fullscreen" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_type" disabled autocomplete="off" />
										<?php echo __('Fullscreen', CARTBOUNTY_TEXT_DOMAIN); ?>
									</label>
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php echo __('Colors', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php echo __('If you leave the Inverse color empty, it will automatically use the inverse color of the main color you have picked. Clear both colors to use the default colors.', CARTBOUNTY_TEXT_DOMAIN); ?>
								</p>
								<div class="cartbounty-exit-intent-colors">
									<label for="cartbounty-exit-intent-main-color"><?php echo __('Main:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
									<input id="cartbounty-exit-intent-main-color" type="text" name="cartbounty_exit_intent_main_color" class="cartbounty-exit-intent-color-picker cartbounty-text" value="<?php echo $main_color; ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
								</div>
								<div class="cartbounty-exit-intent-colors">
									<label for="cartbounty-exit-intent-inverse-color"><?php echo __('Inverse:', CARTBOUNTY_TEXT_DOMAIN); ?></label>
									<input id="cartbounty-exit-intent-inverse-color" type="text" name="cartbounty_exit_intent_inverse_color" class="cartbounty-exit-intent-color-picker cartbounty-text" value="<?php echo $inverse_color; ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<?php
									if(!did_action('wp_enqueue_media')){
										wp_enqueue_media();
									}
									$image = wp_get_attachment_image_src( $main_image );
								?>
								<h4><?php echo __('Custom image', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php echo __('Recommended dimensions:', CARTBOUNTY_TEXT_DOMAIN); ?> 1024 x 600 px.
								</p>
								<div id="cartbounty-exit-intent-image-container">
									<p id="cartbounty-upload-image">
										<?php if($image):?>
											<img src="<?php echo $image[0]; ?>" />
										<?php else: ?>
											<input type="button" value="<?php echo __('Add a custom image', CARTBOUNTY_TEXT_DOMAIN); ?>" class="cartbounty-button button-secondary button" <?php echo $this->disable_field(); ?> />
										<?php endif;?>
									</p>
									<a href="#" id="cartbounty-remove-image" <?php if(!$image){echo 'style="display:none"';}?>></a>
								</div>
								<input id="cartbounty_exit_intent_image" type="hidden" name="cartbounty_exit_intent_image" value="<?php if($main_image){echo $main_image;}?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php echo __('Miscellaneous', CARTBOUNTY_TEXT_DOMAIN); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php echo __('Enable test mode to see how the Exit Intent popup looks like.', CARTBOUNTY_TEXT_DOMAIN);?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle<?php if($test_mode_on){ echo ' cartbounty-checked'; }?>">
								<label for="cartbounty-exit-intent-test-mode" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-exit-intent-test-mode" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_test_mode" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $test_mode_on, false ); ?> autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-test-mode" class="cartbounty-control-visibility"><?php echo __('Enable test mode', CARTBOUNTY_TEXT_DOMAIN); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden'>
										<?php echo __('Now open up your store, add a product to the cart and try leaving it. Please note that while this is enabled, only users with Admin rights will be able to see the Exit Intent and appearance limitations have been removed which means that you will see the popup each time you try to leave your store. Disable this after you have finished testing.', CARTBOUNTY_TEXT_DOMAIN); ?>
									</i>
								</p>
							</div>
						</div>
					</div>
					<div class='cartbounty-button-row'>
						<?php
						if(current_user_can( 'manage_options' )){
							echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". __('Save settings', CARTBOUNTY_TEXT_DOMAIN) ."</button>";
						}?>
					</div>
				</form>
				<?php
				break;
		}
	}

	/**
	 * Method displays notice of a mising WooCommerce plugin
	 *
	 * @since    6.0
	 * @param    string    $current_section    	Currently open section
	 */
	function missing_woocommerce_notice( $current_section ){
		$sections = $this->get_sections( 'recovery' );
		$name = '';
		foreach ($sections as $key => $section) { //Getting integration name from na array
			if($key == $current_section){
				$name = $section['name'];
			}
		}
		echo '<p class="cartbounty-description">' . sprintf(
			/* translators: %s - Gets replaced by an integration name, e.g. MailChimp */
			__('Not so fast, sailor! You must enable WooCommerce before we can set sail or this %s boat gets us nowhere.', CARTBOUNTY_TEXT_DOMAIN), $name ) . '</p>';
	}

	/**
	 * Method returns visual status indiciator if the item has been connected
	 *
	 * @since    6.0
	 * @return   string
	 * @param    boolean    $connected    	Wheather the item is connected or not
	 * @param    boolean    $text    		Should the text be displayed or just the green status
	 * @param    string    	$tab    		Tab section, used to determine which type of text should be returned
	 */
	function get_connection( $connected, $text, $tab ){
		$status = '';
		if($connected){
			if($text){
				$text = __('Enabled', CARTBOUNTY_TEXT_DOMAIN);

				if($tab == 'recovery'){
					$text = __('Connected', CARTBOUNTY_TEXT_DOMAIN);
				}
				
			}
			$status = '<em class="cartbounty-connection">'. $text .'</em>';
		}
		return $status;
	}

	/**
	 * Method adds additional intervals to default Wordpress cron intervals (hourly, twicedaily, daily). Interval provided in minutes
	 *
	 * @since    3.0
	 * @param    array    $intervals    Existing interval array
	 */
	function additional_cron_intervals( $intervals ){
		$intervals['cartbounty_notification_sendout_interval'] = array( //Defining cron Interval for sending out email notifications about abandoned carts
			'interval' => CARTBOUNTY_EMAIL_INTERVAL * 60,
			'display' => 'Every '. CARTBOUNTY_EMAIL_INTERVAL .' minutes'
		);
		$intervals['cartbounty_remove_empty_carts_interval'] = array( //Defining cron Interval for removing abandoned carts that do not have products
			'interval' => 12 * 60 * 60,
			'display' => 'Twice a day'
		);
		return $intervals;
	}

	/**
	 * Method resets Wordpress cron function after user sets other notification frequency
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
	 * Method shows warnings if any of the WP Cron events required for MailChimp or ActiveCampaign are not scheduled (either sending notifications or pushing carts) or if the WP Cron has been disabled
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
				<div id="cartbounty-cron-schedules" class="cartbounty-notification warning notice updated">
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
					<div id="cartbounty-cron-schedules" class="cartbounty-notification warning notice updated">
						<p class="left-part"><?php echo __("WP Cron has been disabled. Several WordPress core features, such as checking for updates or sending notifications utilize this function. Please enable it or contact your system administrator to help you with this.", CARTBOUNTY_TEXT_DOMAIN ); ?></p>
					</div>
				<?php
				}
			}
		}
	}

	/**
	 * Method for sending out email notification in order to notify about new abandoned carts
	 *
	 * @since    4.3
	 */
	function send_email(){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time = $this->get_time_intervals();
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$where_sentence = $this->get_where_sentence( 'recoverable' );
		$to = get_option( 'admin_email' );

		// Retrieve from database rows that have not been emailed and are older than 60 minutes
		$cart_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id)
				FROM $cart_table
				WHERE mail_sent = %d
				$where_sentence AND
				cart_contents != '' AND
				time < %s",
				0,
				$time['cart_abandoned']
			)
		);
		
		if ($cart_count){ //If we have new rows in the database
			$user_settings_email = get_option('cartbounty_notification_email'); //Retrieving email address if the user has entered one
			if(!empty($user_settings_email)){
				$to = $user_settings_email;
			}
			
			$sender = 'WordPress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
			$from = "From: WordPress <" . apply_filters( 'cartbounty_from_email', $sender ) . ">";
			$blog_name = get_option( 'blogname' );
			$admin_link = get_admin_url() .'admin.php?page='. CARTBOUNTY;
			$subject = '['.$blog_name.'] '. _n('New abandoned cart saved', 'New abandoned carts saved', $cart_count, CARTBOUNTY_TEXT_DOMAIN);
			$message = sprintf(
				/* translators: %1$d - Abandoned cart count, %2$s - Plugin name, %3$s - Link, %4$s - Link */
				_n('Great! You have saved %1$d new recoverable abandoned cart using %2$s. <br/>View them here: <a href="%3$s">%4$s</a>', 'Congratulations, you have saved %1$d new recoverable abandoned carts using %2$s. <br/>View them here: <a href="%3$s">%4$s</a>', $cart_count, CARTBOUNTY_TEXT_DOMAIN), esc_html($cart_count), CARTBOUNTY_ABREVIATION, esc_html($admin_link), esc_html($admin_link));
			$headers 	= "$from\n" . "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
			
			//Sending out email
			wp_mail( esc_html($to), esc_html($subject), $message, $headers );
			
			//Update mail_sent status to true with mail_status = 0 and are older than 60 minutes
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $cart_table
					SET mail_sent = %d
					WHERE mail_sent = %d 
					$where_sentence AND
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
	 * Method that merges the links on Plugin page under plugin name
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
	 * Method calculates if time has passed since the given time period (In days)
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
	 * Method checks the current plugin version with the one saved in database
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
	function disable_field( $options = array() ){
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
	 * Method outputs bubble content
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
							$saved_cart_count = $this->total_cartbounty_recoverable_cart_count();
						?>
						<h2><?php echo sprintf(
							/* translators: %s - Gets replaced by an excitement word e.g. Awesome!, %d - Abandoned cart count */
							_n('%s You have already captured %d abandoned cart!', '%s You have already captured %d abandoned carts!', $saved_cart_count , CARTBOUNTY_TEXT_DOMAIN ), $expression['exclamation'], $saved_cart_count ); ?></h2>
						<p><?php echo __('If you like our plugin, please leave us a 5-star rating. It is the easiest way to help us grow and keep evolving further.', CARTBOUNTY_TEXT_DOMAIN ); ?></p>
						<div class="cartbounty-button-row">
							<form method="post" action="options.php">
								<?php settings_fields( 'cartbounty-settings-review' ); ?>
								<a href="<?php echo CARTBOUNTY_REVIEW_LINK; ?>" class="button" target="_blank"><?php echo __("Leave a 5-star rating", CARTBOUNTY_TEXT_DOMAIN ); ?></a>
								<?php submit_button(__('Done that', CARTBOUNTY_TEXT_DOMAIN), 'cartbounty-review-submitted', false, false); ?>
								<input id="cartbounty_review_submitted" type="hidden" name="cartbounty_review_submitted" value="1" />
							</form>
							<form method="post" action="options.php">
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
						<div class="cartbounty-button-row">
							<a href="<?php echo CARTBOUNTY_LICENSE_SERVER_URL; ?>?utm_source=<?php echo urlencode(get_bloginfo('url')); ?>&utm_medium=bubble&utm_campaign=cartbounty" class="button" target="_blank"><?php echo __('Get Pro', CARTBOUNTY_TEXT_DOMAIN); ?></a>
							<?php submit_button(__('Not now', CARTBOUNTY_TEXT_DOMAIN), 'cartbounty-close', false, false); ?>
						</div>
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
			($this->total_cartbounty_recoverable_cart_count() > 9 && get_option('cartbounty_times_review_declined') < 1 && !get_option('cartbounty_review_submitted')) ||
			($this->total_cartbounty_recoverable_cart_count() > 29 && get_option('cartbounty_times_review_declined') < 2 && !get_option('cartbounty_review_submitted')) ||
			($this->total_cartbounty_recoverable_cart_count() > 99 && get_option('cartbounty_times_review_declined') < 3 && !get_option('cartbounty_review_submitted')) ||
			($this->total_cartbounty_recoverable_cart_count() > 299 && get_option('cartbounty_times_review_declined') < 4 && !get_option('cartbounty_review_submitted')) ||
			($this->total_cartbounty_recoverable_cart_count() > 499 && get_option('cartbounty_times_review_declined') < 5 && !get_option('cartbounty_review_submitted')) ||
			($this->total_cartbounty_recoverable_cart_count() > 999 && get_option('cartbounty_times_review_declined') < 6 && !get_option('cartbounty_review_submitted'))
		){
			$bubble_type = '#cartbounty-review';
			$display_bubble = true; //Show the bubble
		}elseif($this->total_cartbounty_recoverable_cart_count() > 5 && $this->days_have_passed('cartbounty_last_time_bubble_displayed', 18 )){ //If we have more than 5 abandoned carts or the user has deleted more than 10 abandoned carts the last time bubble was displayed was 18 days ago, display the bubble info about Pro version
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
	function total_cartbounty_recoverable_cart_count(){
		if ( false === ( $captured_abandoned_cart_count = get_transient( 'cartbounty_recoverable_cart_count' ))){ //If value is not cached or has expired
			$captured_abandoned_cart_count = get_option('cartbounty_recoverable_cart_count');
			set_transient( 'cartbounty_recoverable_cart_count', $captured_abandoned_cart_count, 60 * 10 ); //Temporary cache will expire in 10 minutes
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
	 * Method removes empty abandoned carts that do not have any products and are older than 1 day
	 *
	 * @since    3.0
	 */
	function delete_empty_carts(){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time = $this->get_time_intervals();
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$where_sentence = $this->get_where_sentence( 'ghost' );

		//Deleting ghost rows from database first
		$ghost_row_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE cart_contents = ''
				$where_sentence AND
				time < %s",
				$time['day']
			)
		);
		$public->decrease_ghost_cart_count( $ghost_row_count );

		//Deleting rest of the abandoned carts without products
		$rest_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE cart_contents = '' AND
				time < %s",
				$time['day']
			)
		);
		$public->decrease_recoverable_cart_count( $rest_count );
	}

	/**
	 * Method to clear cart data from row
	 *
	 * @since    3.0
	 */
	function clear_cart_data(){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		
		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if(isset(WC()->session)){
			$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
			$cart = $public->read_cart();

			if(isset($cart['session_id'])){
				//Cleaning Cart data
				$wpdb->prepare('%s',
					$wpdb->update(
						$cart_table,
						array(
							'cart_contents'	=>	'',
							'cart_total'	=>	0,
							'currency'		=>	sanitize_text_field( $cart['cart_currency'] ),
							'time'			=>	sanitize_text_field( $cart['current_time'] )
						),
						array('session_id' => $cart['session_id']),
						array('%s', '%s'),
						array('%s')
					)
				);
			}
		}
	}

	/**
	 * Reseting abandoned cart data in case if a registered user has an existing abandoned cart and updates his data on his Account page
	 *
	 * @since    5.0.3
	 */
	public function reset_abandoned_cart(){
		if(!is_user_logged_in()){ //Exit in case the user is not logged in
			return;
		}

		global $wpdb;
		$user_id = 0;
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

		if (!empty($_POST['user_id']) && is_numeric($_POST['user_id']) ) { //In case the user's data is updated from WordPress admin dashboard "Edit profile page"
			$user_id = $_POST['user_id'];

		}elseif(!empty($_POST['action'])){ //This check is to prevent profile update to be fired after a new Order is created since no "action" is provided and the user's ID remians 0 and we exit resetting of the abandoned cart
			$user_id = get_current_user_id();
		}

		if(!$user_id){ //Exit in case we do not have user's ID value
			return;
		}
		
		if($public->cart_saved($user_id)){ //If we have saved an abandoned cart for the user - go ahead and reset it
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
			$updated_rows = $wpdb->prepare('%s',
				$wpdb->update(
					$cart_table,
					array(
						'name'			=>	'',
						'surname'		=>	'',
						'email'			=>	'',
						'phone'			=>	'',
						'location'		=>	'',
						'cart_contents'	=>	'',
						'cart_total'	=>	'',
						'currency'		=>	'',
						'time'			=>	'',
						'other_fields'	=>	''
					),
					array('session_id' => $user_id),
					array(),
					array('%s')
				)
			);
		}
	}

	/**
	 * Method returns different expressions depending on the amount of captured carts
	 *
	 * @since    3.2.1
	 * @return 	 String
	 */
	function get_expressions(){
		if($this->total_cartbounty_recoverable_cart_count() <= 10){
			$expressions = array(
				'exclamation' => __('Congrats!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 30){
			$expressions = array(
				'exclamation' => __('Awesome!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 100){
			$expressions = array(
				'exclamation' => __('Amazing!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 300){
			$expressions = array(
				'exclamation' => __('Incredible!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 500){
			$expressions = array(
				'exclamation' => __('Crazy!', CARTBOUNTY_TEXT_DOMAIN)
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 1000){
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
	 * Method returns icons as SVG code
	 *
	 * @since    6.0
	 * @return 	 String
	 * @param    $icon 		Icon to get - string
	 * @param    $current 	Current active tab - string
	 * @param    $section 	Wheather the icon is located in sections - boolean
	 * @param    $grid 		Wheather the icon is located section items grid
	 */
	public function get_icon( $icon, $current, $section, $grid ){

		$color = '#555'; //Default icon color
		$svg = '';
		
		if( $current == $icon ){ //If the icon is active in tabs
			$color = '#000';
		}

		if( $grid ){ //If the icon is in the item grid
			$color = '#000';
		}

		if( $current == $icon && $section ){ //If the icon is active in sections
			$color = '#976dfb';
		}

		if( $icon == 'carts' ){
			$svg = '<svg style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26.34 29.48"><path d="M7.65,24c-2.43,0-3.54-1.51-3.54-2.91V3.44C3.77,3.34,3,3.15,2.48,3L.9,2.59A1.28,1.28,0,0,1,0,1.15,1.32,1.32,0,0,1,1.34,0a1.52,1.52,0,0,1,.42.06l.68.2c1.38.41,2.89.85,3.25,1A1.72,1.72,0,0,1,6.79,2.8V5.16L24.67,7.53a1.75,1.75,0,0,1,1.67,2v6.1a3.45,3.45,0,0,1-3.59,3.62h-16v1.68c0,.14,0,.47,1.07.47H21.13a1.32,1.32,0,0,1,1.29,1.38,1.35,1.35,0,0,1-.25.79,1.18,1.18,0,0,1-1,.5Zm-.86-7.5,15.76,0c.41,0,1.11,0,1.11-1.45V10c-3-.41-13.49-1.69-16.87-2.11Z"/><path d="M21.78,29.48a4,4,0,1,1,4-4A4,4,0,0,1,21.78,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.35,1.35,0,0,0,21.78,24.11ZM10.14,29.48a4,4,0,1,1,4-4A4,4,0,0,1,10.14,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.34,1.34,0,0,0,10.14,24.11Z"/><path d="M18.61,18.91a1.34,1.34,0,0,1-1.34-1.34v-9a1.34,1.34,0,1,1,2.67,0v9A1.34,1.34,0,0,1,18.61,18.91Z"/><path d="M12.05,18.87a1.32,1.32,0,0,1-1.34-1.29v-10a1.34,1.34,0,0,1,2.68,0v10A1.32,1.32,0,0,1,12.05,18.87Z"/></svg>';
		}

		elseif( $icon == 'recovery' ){
			$svg = '<svg style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 66.07 49.48"><path d="M28.91,26.67A7.62,7.62,0,0,0,33,28a7,7,0,0,0,4.16-1.37c.77-.46,23.19-17.66,26.05-20s1.06-4.9,1.06-4.9S63.51,0,60.64,0H6.08c-3.83,0-4.5,2-4.5,2S0,4.49,3.28,7Z"/><path d="M40.84,32.14A13.26,13.26,0,0,1,33,34.9a13,13,0,0,1-7.77-2.76C24.33,31.55,1.11,14.49,0,13.25V43a6.52,6.52,0,0,0,6.5,6.5H59.57a6.51,6.51,0,0,0,6.5-6.5V13.25C65,14.46,41.74,31.55,40.84,32.14Z"/></svg>';
		}

		elseif( $icon == 'settings' ){
			$svg = '<svg style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 70"><path d="M58.9,23.28a23.37,23.37,0,0,1,1.41,3.1c.12.49.49.68,1.33.88s6.43,1.55,6.72,1.64c1,.32,1.64.85,1.62,2.42,0,2.65,0,5,0,7.63a2.08,2.08,0,0,1-1.67,2.16c-.7.2-5.77,1.44-7.61,1.87-.27.06-.36.25-.45.49A25.48,25.48,0,0,1,58.78,47a.65.65,0,0,0,0,.78c.24.33,2.33,3.88,3.19,5.29a2.89,2.89,0,0,1-.63,3.58c-1,1-3.55,3.59-4.61,4.61a3,3,0,0,1-3.53.64C51.8,61,49,59.48,48.37,59.12s-.9-.62-1.44-.3-2.2,1-3.49,1.45a.6.6,0,0,0-.47.45c-.42,1.84-1.66,6.91-1.86,7.6A2.08,2.08,0,0,1,39,70c-2.62,0-5.24,0-7.86,0a2.1,2.1,0,0,1-2.21-1.7c-.08-.28-1.5-6-1.86-7.58-.07-.31-.31-.38-.56-.47-.84-.32-2.79-1.11-3.34-1.4a.86.86,0,0,0-1.08,0l-5.16,3a2.2,2.2,0,0,1-3.19-.4c-.63-.61-4.52-4.5-5.21-5.2a2.17,2.17,0,0,1-.4-3.08c.88-1.5,2.54-4.35,2.87-4.94a1.36,1.36,0,0,0,0-1.79,19.48,19.48,0,0,1-1.2-2.75c-.24-.78-.46-.68-1.47-.9S2.1,41.24,1.81,41.15A2.15,2.15,0,0,1,0,38.85Q0,35,0,31.14a2.11,2.11,0,0,1,1.8-2.28c.75-.2,5.68-1.42,7.39-1.82a.74.74,0,0,0,.56-.56,23.39,23.39,0,0,1,1.29-3.09,1.12,1.12,0,0,0,0-1.36s-3.35-5-3.82-5.84a2,2,0,0,1,.4-2.54l5.51-5.51a2.24,2.24,0,0,1,2.55-.43c.71.41,5.42,2.92,6.05,3.28s.9.44,1.47.15a21.56,21.56,0,0,1,3.25-1.38c.28-.1.49-.21.56-.55.4-1.72,1.62-6.7,1.84-7.48A2.08,2.08,0,0,1,31.08,0h7.81a2.1,2.1,0,0,1,2.24,1.76c.09.3,1.49,5.93,1.83,7.43a.68.68,0,0,0,.49.53c.83.29,3.12,1.27,3.46,1.44s.52.31.85.08c.54-.38,4.18-2.53,5.43-3.27,1.5-.88,2.18-.79,3.44.45.64.64,4.5,4.49,5.15,5.16a2.13,2.13,0,0,1,.37,2.95L59,21.9A1,1,0,0,0,58.9,23.28ZM35,20.68A14.32,14.32,0,1,0,49.32,35,14.32,14.32,0,0,0,35,20.68Z"/></svg>';
		}

		elseif( $icon == 'exit_intent' || $icon == 'tools' ){
			$svg = '<svg style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.5 70"><path d="M29,7.23A7.23,7.23,0,1,1,21.75,0,7.23,7.23,0,0,1,29,7.23Z"/><path d="M17.32,70a5.73,5.73,0,0,1-4.78-2.6,4.85,4.85,0,0,1-.18-4.84q1-2.12,2-4.25c1.33-2.8,2.71-5.68,4.14-8.5,1.33-2.6,5-5.49,11.29-8.81-2.17-4.18-4.25-8-6.35-11.61a21.16,21.16,0,0,1-5.12.66C11.6,30.05,5.59,26.63,1,20.18a4.58,4.58,0,0,1-.48-4.86,5.76,5.76,0,0,1,5.06-3,5.28,5.28,0,0,1,4.39,2.29c2.32,3.26,5.1,4.92,8.26,4.92A13.46,13.46,0,0,0,25,17.43c.18-.12.63-.36,1.12-.64l.31-.17,1.36-.78a23.44,23.44,0,0,1,12-3.55c6.76,0,12.77,3.42,17.39,9.89A4.56,4.56,0,0,1,57.58,27,5.76,5.76,0,0,1,52.52,30a5.26,5.26,0,0,1-4.38-2.28c-2.33-3.26-5.11-4.91-8.27-4.91a10.63,10.63,0,0,0-1.66.14c2.44,4.4,6.53,12.22,7.08,13.58,2.23,4.07,4.78,7.82,8.25,7.82A7,7,0,0,0,57,43.23a5.68,5.68,0,0,1,2.85-.81,5.85,5.85,0,0,1,5.41,4.43A5.27,5.27,0,0,1,62.74,53a18,18,0,0,1-9.08,2.68c-5,0-9.91-2.61-14.08-7.55-2.93,1.44-8.65,4.38-11.3,6.65-.53.87-4.4,8.16-6.4,12.29A5,5,0,0,1,17.32,70Z"/></svg>';
		}

		elseif( $icon == 'activecampaign' ){
			$svg = '<svg style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 13.24 20"><path d="M12.52,8.69C12.23,8.45.76,0.44,0.24,0.12L0.08,0V2A1.32,1.32,0,0,0,.8,3.14l0.08,0L10.7,10c-1.09.76-9.38,6.52-9.9,6.84a1.16,1.16,0,0,0-.68,1.25V20s12.19-8.49,12.43-8.69h0a1.52,1.52,0,0,0,.68-1.25V9.82A1.4,1.4,0,0,0,12.52,8.69Z"/><path d="M5.35,10.91a1.61,1.61,0,0,0,1-.36L7.08,10,7.2,9.94,7.08,9.86s-5.39-3.74-6-4.1A0.7,0.7,0,0,0,.36,5.63,0.71,0.71,0,0,0,0,6.28V7.53l0,0s3.7,2.58,4.43,3.06A1.63,1.63,0,0,0,5.35,10.91Z"/></svg>';
		}

		elseif( $icon == 'getresponse' ){
			$svg = '<svg style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 44.54"><path d="M35,35a29,29,0,0,1-17.22-5.84A28.38,28.38,0,0,1,6.89,11.35c-.07-.47-.15-.94-.21-1.33A3.2,3.2,0,0,1,9.86,6.36h1.48a23.94,23.94,0,0,0,8.4,13.76A24.74,24.74,0,0,0,34.91,25.5C48.16,25.78,61.05,14,69.31,1.15A3,3,0,0,0,67,0H3A3,3,0,0,0,0,3V41.55a3,3,0,0,0,3,3H67a3,3,0,0,0,3-3V8.5C59.14,27.59,46.65,35,35,35Z"/></svg>';
		}

		elseif( $icon == 'mailchimp' ){
			$svg = '<svg style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.96 70"><path d="M49.61,33.08a5.41,5.41,0,0,1,1.45,0,4.92,4.92,0,0,0,.07-2.75c-.34-1.66-.82-2.67-1.79-2.52s-1,1.37-.66,3a5.7,5.7,0,0,0,.93,2.24Z"/><path d="M41.26,34.4c.69.3,1.12.5,1.29.33s.07-.32-.09-.59a4,4,0,0,0-1.8-1.45,4.88,4.88,0,0,0-4.78.57c-.47.34-.91.81-.84,1.1,0,.09.09.16.25.19a27.75,27.75,0,0,1,3.27-.73,5.65,5.65,0,0,1,2.7.58Z"/><path d="M39.85,35.2a3.23,3.23,0,0,0-1.72.72,1.1,1.1,0,0,0-.45.69.19.19,0,0,0,.07.16.2.2,0,0,0,.15.06,2.81,2.81,0,0,0,.67-.18,5.74,5.74,0,0,1,2.92-.31c.45,0,.67.08.77-.07a.26.26,0,0,0,0-.29,2.62,2.62,0,0,0-2.38-.78Z"/><path d="M46.79,38.13a1.13,1.13,0,0,0,1.52-.26c.22-.45-.1-1.06-.72-1.37a1.13,1.13,0,0,0-1.52.27,1.11,1.11,0,0,0,.72,1.36Z"/><path d="M50.75,34.67c-.5,0-.92.54-.93,1.23s.39,1.25.89,1.26.91-.55.93-1.23-.39-1.25-.89-1.26Z"/><path d="M17.14,47c-.12-.15-.33-.1-.53-.06a2.11,2.11,0,0,1-.46.07,1,1,0,0,1-.86-.44,1.59,1.59,0,0,1,0-1.47,2,2,0,0,1,.12-.26c.4-.9,1.07-2.41.31-3.85a3.38,3.38,0,0,0-2.6-1.89,3.34,3.34,0,0,0-2.87,1,4.14,4.14,0,0,0-1.07,3.47c.08.22.2.28.29.29s.47-.11.64-.58a.86.86,0,0,0,0-.15,5,5,0,0,1,.46-1.08,2,2,0,0,1,1.26-.87,2,2,0,0,1,1.53.29,2,2,0,0,1,.74,2.36A5.58,5.58,0,0,0,13.8,46a2.11,2.11,0,0,0,1.87,2.16,1.59,1.59,0,0,0,1.5-.75.31.31,0,0,0,0-.37Z"/><path d="M24.76,19.66a31,31,0,0,1,8.71-7.12.11.11,0,0,1,.15.15,8.56,8.56,0,0,0-.81,2,.12.12,0,0,0,.18.12,17,17,0,0,1,7.65-2.7.13.13,0,0,1,.08.22,6.6,6.6,0,0,0-1.21,1.21.12.12,0,0,0,.1.18A15.09,15.09,0,0,1,46,15.38c.12.06,0,.3-.1.27a25.86,25.86,0,0,0-11.58,0,26.57,26.57,0,0,0-9.41,4.15.11.11,0,0,1-.15-.17Zm13,29.25Zm10.78,1.27a.21.21,0,0,0,.12-.2.2.2,0,0,0-.22-.18,24.86,24.86,0,0,1-10.84-1.1c.57-1.87,2.1-1.19,4.4-1a32.17,32.17,0,0,0,10.64-1.15,24.28,24.28,0,0,0,8-3.95,16,16,0,0,1,1.11,3.78,1.86,1.86,0,0,1,1.17.22c.5.31.87.95.62,2.61a14.39,14.39,0,0,1-4,7.93,16.67,16.67,0,0,1-4.86,3.63,20,20,0,0,1-3.17,1.34c-8.35,2.73-16.9-.27-19.65-6.71a9.46,9.46,0,0,1-.55-1.52,13.36,13.36,0,0,1,2.93-12.54h0a1.09,1.09,0,0,0,.39-.75,1.27,1.27,0,0,0-.3-.7c-1.09-1.59-4.86-4.28-4.11-9.49C30.68,26.64,34,24,37,24.16l.77,0c1.33.08,2.48.25,3.57.3a7.19,7.19,0,0,0,5.41-1.81,4.13,4.13,0,0,1,2.07-1.17,2.71,2.71,0,0,1,.79-.08,2.68,2.68,0,0,1,1.33.43c1.56,1,1.78,3.55,1.86,5.38,0,1.05.17,3.58.21,4.31.1,1.67.54,1.9,1.42,2.19.5.17,1,.29,1.65.48a9.31,9.31,0,0,1,4,1.92,2.56,2.56,0,0,1,.74,1.45c.24,1.77-1.38,4-5.67,5.95a28.69,28.69,0,0,1-14.3,2.29l-1.37-.15c-3.15-.43-4.94,3.63-3,6.42,1.21,1.79,4.52,3,7.84,3,7.59,0,13.43-3.24,15.6-6l.17-.24c.11-.16,0-.25-.11-.16-1.77,1.21-9.66,6-18.08,4.58a11.38,11.38,0,0,1-2-.53c-.75-.29-2.3-1-2.49-2.6,6.8,2.1,11.09.11,11.09.11ZM11.18,34a9.06,9.06,0,0,0-5.72,3.65A15.45,15.45,0,0,1,3,35.33C1,31.46,5.24,24,8.22,19.7c7.35-10.49,18.86-18.43,24.19-17,.86.25,3.73,3.58,3.73,3.58a74.88,74.88,0,0,0-10.26,7.07A46.63,46.63,0,0,0,11.18,34Zm4,17.73a5,5,0,0,1-1.09.08c-3.56-.09-7.41-3.3-7.79-7.1-.42-4.2,1.72-7.43,5.52-8.2a6.67,6.67,0,0,1,1.6-.11c2.13.11,5.27,1.75,6,6.39.64,4.11-.37,8.29-4.22,8.94Zm48.22-7.43c0-.11-.23-.84-.51-1.71a13.28,13.28,0,0,0-.55-1.49,5.47,5.47,0,0,0,1-3.94,5,5,0,0,0-1.45-2.81,11.64,11.64,0,0,0-5.11-2.53l-1.3-.36c0-.06-.07-3.07-.13-4.37a15,15,0,0,0-.57-3.84,10.35,10.35,0,0,0-2.66-4.74c3.24-3.36,5.27-7.06,5.26-10.24,0-6.11-7.51-8-16.76-4.13l-2,.83L35,1.47c-10.54-9.2-43.51,27.44-33,36.34l2.31,1.95A11.32,11.32,0,0,0,3.71,45,10.3,10.3,0,0,0,7.27,51.6a10.86,10.86,0,0,0,7,2.81C18.35,63.86,27.72,69.66,38.71,70c11.78.35,21.68-5.18,25.82-15.11A20.84,20.84,0,0,0,66,48.26c0-2.79-1.58-3.94-2.58-3.94Z"/></svg>';
		}

		return "<span class='cartbounty-icon-container cartbounty-icon-$icon'><img src='data:image/svg+xml;base64," . base64_encode($svg) . "' alt='" . $icon . "' /></span>";
    }

    /**
	 * Method tries to move the email field higher in the checkout form
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
	 * Method prepares and returns an array of different time intervals used for calulating time substractions
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

	/**
     * Method counts carts in the selected category
     *
     * @since    5.0
     * @return   number
     */
    function get_cart_count( $cart_status ){
        global $wpdb;
        $cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
        $total_items = 0;
        $where_sentence = $this->get_where_sentence($cart_status);

        $total_items = $wpdb->get_var("
            SELECT COUNT(id)
            FROM $cart_table
            WHERE cart_contents != ''
            $where_sentence
        ");

        return $total_items;
    }

    /**
     * Method displays available cart type filters
     *
     * @since    5.0
     * @return   string
     * @param 	 $cart_status    Currently filtered cart status
     * @param 	 $tab    		 Currently open tab
     */
    function display_cart_statuses( $cart_status, $tab ){
    	$exclude = false;
    	$divider = '<em>|</em>';
    	if(get_option('cartbounty_exclude_ghost_carts' )){
			$exclude = true;
		}
    	$cart_types = array(
    		'all' 			=> __('All', CARTBOUNTY_TEXT_DOMAIN),
    		'recoverable' 	=> __('Recoverable', CARTBOUNTY_TEXT_DOMAIN),
    		'ghost' 		=> __('Ghost', CARTBOUNTY_TEXT_DOMAIN)
    	);
    	$total_items = count($cart_types);
    	if(count($cart_types) <= 3 && $exclude){ //Do not output the filter if we are excluding Ghost carts and we have only 3 cart types
    		return;
    	}
    	echo '<ul id="cartbounty-cart-statuses" class="subsubsub">';
    	$counter = 0;
    	foreach( $cart_types as $key => $type ){
    		$counter++;
    		if($counter == $total_items){
    			$divider = '';
    		}
    		$class = ( $key == $cart_status ) ? 'current' : '';
    		$count = $this->get_cart_count($key);
    		if (!($key == 'ghost' && $exclude)){ //If we are not processing Ghost carts and they have not been excluded
	    		echo "<li><a href='?page=". CARTBOUNTY ."&tab=$tab&cart-status=$key' title='$type' class='$class'>$type <span class='count'>($count)</span></a>$divider</li>";
	    	}
    	}
    	echo '</ul>';
    }

    /**
     * Method for creating SQL query depending on different post types
     *
     * @since    5.0
     * @return   string
     */
    function get_where_sentence( $cart_status ){
		$where_sentence = '';

		if($cart_status == 'recoverable'){
			$where_sentence = "AND (email != '' OR phone != '')";

		}elseif($cart_status == 'ghost'){
			$where_sentence = "AND ((email IS NULL OR email = '') AND (phone IS NULL OR phone = ''))";

		}elseif(get_option('cartbounty_exclude_ghost_carts')){ //In case Ghost carts have been excluded
			$where_sentence = "AND (email != '' OR phone != '')";
		}

		return $where_sentence;
    }

    /**
	 * Handling abandoned carts in case of a new order is placed
	 *
	 * @since    5.0.2
	 * @param    Integer    $order_id - ID of the order created by WooCommerce
	 */
	function handle_order( $order_id ){
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$public->update_logged_customer_id(); //In case a user chooses to create an account during checkout process, the session id changes to a new one so we must update it
		$this->clear_cart_data(); //Clearing abandoned cart after it has been synced
	}

	/**
	 * Adds one or more classes to the body tag in the dashboard
	 *
	 * @since    6.0
	 * @param    String    $classes    Current body classes
	 * @return   String    Altered body classes
	 */
	function add_cartbounty_body_class( $classes ) {
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();
		// Check if we are on CartBounty page
		if(!is_object($screen) || $screen->id != $cartbounty_admin_menu_page){
			return;
		}

	    return "$classes cartbounty";
	}

	/**
	 * Outputs message about upgrade to Pro
	 *
	 * @since    6.0
	 * @param    String    $medium    Determines where the button was clicked from
	 * @return   String    Message
	 */
	function display_unavailable_notice( $medium = false ) {
		$message = __('Please upgrade to enable this feature.', CARTBOUNTY_TEXT_DOMAIN);
		$message .= " <a href='". CARTBOUNTY_LICENSE_SERVER_URL ."?utm_source=". urlencode(get_bloginfo('url')) ."&utm_medium=". $medium ."&utm_campaign=cartbounty' target='_blank'>". __('Get Pro', CARTBOUNTY_TEXT_DOMAIN) ."</a>";
		return $message;
	}
}