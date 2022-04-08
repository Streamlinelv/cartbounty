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

		if(!is_object($screen)){
			return;
		}

		wp_enqueue_style( $this->plugin_name . '-global', plugin_dir_url( __FILE__ ) . 'css/cartbounty-admin-global.css', $this->version ); //Global styles
		wp_style_add_data( $this->plugin_name . '-global', 'rtl', 'replace' );
		if($screen->id == $cartbounty_admin_menu_page ){
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartbounty-admin.css', array('wp-color-picker'), $this->version );
			wp_style_add_data( $this->plugin_name, 'rtl', 'replace' );
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

		$data = array(
		    'ajaxurl' => admin_url( 'admin-ajax.php' )
		);

		if (isset($_GET['section'])){ //Adding additional script on WordPress recovery page
			if($_GET['section'] == 'wordpress'){
				wp_enqueue_script( $this->plugin_name . '-micromodal', plugin_dir_url( __FILE__ ) . 'js/micromodal.min.js', array( 'jquery' ), $this->version, false );
			}
		}

		wp_enqueue_script( $this->plugin_name . '-selectize', plugin_dir_url( __FILE__ ) . 'js/selectize.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartbounty-admin.js', array( 'wp-color-picker', 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'cartbounty_admin_data', $data ); //Sending data over to JS file
	}
	
	/**
	 * Register the menu under WooCommerce admin menu.
	 *
	 * @since    1.0
	 */
	function cartbounty_menu(){
		global $cartbounty_admin_menu_page;
		if(class_exists('WooCommerce')){
			$cartbounty_admin_menu_page = add_submenu_page( 'woocommerce', CARTBOUNTY_PLUGIN_NAME, esc_html__('CartBounty Abandoned carts', 'woo-save-abandoned-carts'), 'list_users', CARTBOUNTY, array( $this,'display_page' ) );
		}else{
			$cartbounty_admin_menu_page = add_menu_page( CARTBOUNTY_PLUGIN_NAME, esc_html__('CartBounty Abandoned carts', 'woo-save-abandoned-carts'), 'list_users', CARTBOUNTY, array( $this,'display_page' ), 'dashicons-archive' );
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
			// Retrieve from database rows that have not been emailed and are older than 60 minutes
			$cart_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id)
					FROM $cart_table
					WHERE (email != '' OR phone != '') AND
					type != %d AND
					time < %s AND 
					time > %s",
					$this->get_cart_type('ordered'),
					$time['cart_recovered'],
					$time['old_cart']
				)
			);

			foreach ( $submenu['woocommerce'] as $key => $menu_item ) { //Go through all Sumenu sections of WooCommerce and look for CartBounty Abandoned carts
				if ( 0 === strpos( $menu_item[0], esc_html__('CartBounty Abandoned carts', 'woo-save-abandoned-carts'))) {
					$submenu['woocommerce'][$key][0] .= ' <span class="new-abandoned update-plugins count-' . esc_attr( $cart_count ) . '">' .  esc_html( $cart_count ) .'</span>';
				}
			}
		}
	}

	/**
	 * Adds Screen options tab and Help tab to plugin
	 *
	 * @since    5.0
	 */
	function register_admin_tabs(){
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();

		$wordpress = new CartBounty_WordPress();
		$status = new CartBounty_System_Status(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

		// Check if we are on CartBounty page
		if(!is_object($screen) || $screen->id != $cartbounty_admin_menu_page){
			return;

		}else{
			//Outputing how many rows we would like to see on the page
			$option = 'per_page';
			$args = array(
				'label' => esc_html__('Carts per page: ', 'woo-save-abandoned-carts'),
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

			$nonce = wp_create_nonce( 'get_system_status' );
			//Ads Help tab sections
			$cartbounty_carts_help_support_content = '
				<h2>'. esc_html__('Need help or support?', 'woo-save-abandoned-carts') .'</h2>
				<p>'. sprintf(
					/* translators: %1$s - Plugin name */
					esc_html__('%1$s saves all activity in the WooCommerce checkout form before it is submitted. The plugin allows to see who abandons your shopping carts and get in touch with them.', 'woo-save-abandoned-carts'), esc_html( CARTBOUNTY_ABREVIATION ) ) . '<br/>' .
					esc_html__('You will receive regular email notifications about newly abandoned shopping carts and will be able to remind about these carts either manually or automatically by sending automated abandoned cart recovery messages.', 'woo-save-abandoned-carts') .'</p>
				<p>'. sprintf(
					/* translators: %1$s - Link start, %2$s - Link end */
					esc_html__('If you have additional questions, please see the readme file, or look at %1$sfrequently asked questions%2$s.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( CARTBOUNTY_FAQ_LINK ) .'" target="_blank">', '</a>' ) .'</p>
				<div class="cartbounty-button-row"><a href="'. esc_url( CARTBOUNTY_FAQ_LINK ) .'" class="cartbounty-button" target="_blank">'. esc_html__('View FAQ', 'woo-save-abandoned-carts') .'</a><span class="cartbounty-tooltip-container"><a href="#" id="cartbounty-copy-system-status" class="cartbounty-button cartbounty-progress" data-nonce="' . esc_attr( $nonce ) . '">'. esc_html__('Copy system report', 'woo-save-abandoned-carts') .'</a><span class="cartbounty-tooltip">'. esc_html__('Copied to clipboard', 'woo-save-abandoned-carts') .'</span></span><a href="'. esc_attr( CARTBOUNTY_SUPPORT_LINK ) .'" class="cartbounty-button" target="_blank">'. esc_html__( 'Get support', 'woo-save-abandoned-carts' ) .'</a></div>';

			$screen->add_help_tab( array(
				'id'       =>	'cartbounty_carts_help_support',
				'title'    =>	esc_html__( 'Need help?', 'woo-save-abandoned-carts' ),
				'content'  =>	$cartbounty_carts_help_support_content
			));

			$cartbounty_carts_help_request_feature = '
				<h2>'. esc_html__("Have a new feature in mind? That's awesome!", 'woo-save-abandoned-carts') .'</h2>
				<p>'. sprintf(
					/* translators: %s - Plugin name */
					esc_html__('We always welcome suggestions from our users and will evaluate each new idea to improve %s. In fact, many of the features you are currently using have arrived from users like yourself.', 'woo-save-abandoned-carts'), esc_html( CARTBOUNTY_ABREVIATION ) ) .'</p>
				<div class="cartbounty-button-row"><a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_FEATURE_LINK, 'help_tab_suggest_feature') ) .'" class="cartbounty-button" target="_blank">'. esc_html__('Suggest a feature', 'woo-save-abandoned-carts') .'</a>
				</div>';
			$screen->add_help_tab( array(
				'id'       =>	'cartbounty_carts_request_feature',
				'title'    =>	esc_html__('Suggest a feature', 'woo-save-abandoned-carts'),
				'content'  =>	$cartbounty_carts_help_request_feature
			));
		}
	}

	/**
	 * Saves settings displayed under Screen options
	 *
	 * @since    1.0
	 */
	function save_page_options( $status, $option, $value ){
		return $value;
	}
	
	/**
	 * Display the abandoned carts and settings under admin page
	 *
	 * @since    1.3
	 */
	function display_page(){
		global $pagenow;
		
		if ( !current_user_can( 'list_users' )){
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-save-abandoned-carts' ) );
		}?>

		<div id="cartbounty-page-wrapper" class="wrap<?php if(get_option('cartbounty_hide_images')) {echo " cartbounty-without-thumbnails";}?>">
			<h1><?php esc_html_e( CARTBOUNTY_ABREVIATION ); ?></h1>
			<?php do_action('cartbounty_after_page_title'); ?>

			<?php if ( isset ( $_GET['tab'] ) ){
				$this->create_admin_tabs($_GET['tab']);
			}else{
				$this->create_admin_tabs('carts');
			}

			if ( $pagenow == 'admin.php' && $_GET['page'] == CARTBOUNTY ){
				$tab = $this->get_open_tab();
				$current_section = $this->get_open_section();

				if($current_section): //If one of the sections has been opened

					$sections = $this->get_sections( $tab );
					foreach( $sections as $key => $section ){
						if($current_section == $key): //Determine which section is open to output correct contents ?>
							<div id="cartbounty-content-container">
								<div class="cartbounty-row">
									<div class="cartbounty-sidebar cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3"><?php $this->display_sections( $current_section, $tab ); ?></div>
									<div class="cartbounty-content cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-container">
											<h2 class="cartbounty-section-title"><?php esc_html_e( $section['name'] ); ?></h2>
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
								<h2 class="cartbounty-section-title"><?php esc_html_e('Recovery', 'woo-save-abandoned-carts'); ?></h2>
								<div class="cartbounty-section-intro"><?php esc_html_e('Automate your abandoned cart recovery by sending automated recovery emails and SMS text messages to your visitors.', 'woo-save-abandoned-carts')?><br/> <?php echo sprintf(
									/* translators: %s - URL link tags */
									esc_html__('Please consider upgrading to %s%s Pro%s to connect one of the professional automation services listed below.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'recovery' ) ) .'" target="_blank">', esc_html( CARTBOUNTY_ABREVIATION ), '</a>'); ?></div>
								<div class="cartbounty-row cartbounty-flex">
									<?php
									$recovery_items = $this->get_sections( $tab );
									foreach( $recovery_items as $key => $item ): ?>
										<?php $button = esc_html__('Connect', 'woo-save-abandoned-carts');
											if($item['connected']){
												$button = esc_html__('Edit', 'woo-save-abandoned-carts');
											}elseif(!$item['availability']){
												$button = esc_html__('Get Pro to connect', 'woo-save-abandoned-carts');
											}
										?>
										<div class="cartbounty-section-item-container cartbounty-col-sm-6 cartbounty-col-lg-4">
											<div class="cartbounty-section-item<?php if($item['connected']){echo ' cartbounty-connected'; }?><?php if(!$item['availability']){echo ' cartbounty-unavailable'; }?>">
												<?php if($item['availability']){
													$link = '?page='. CARTBOUNTY .'&tab='. $tab .'&section='. $key;
													$item['info_link'] = $link;
												}else{
													$link = $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'recovery_'. $key );
												}?>
												<div class="cartbounty-section-image">
													<?php echo $this->get_connection( $item['connected'], true, $tab ); ?>
													<a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>"><?php echo $this->get_icon( $key, false, false, true ); ?></a>
												</div>
												<div class="cartbounty-section-content">
													<h3><a href="<?php echo esc_url( $item['info_link'] ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>" <?php if(!$item['availability']){echo ' target="_blank"'; }?>><?php echo esc_html( $item['name'] ); ?></a></h3>
													<?php echo wp_kses_post( $item['description'] ); ?>
													<a class="button cartbounty-button<?php if(!$item['availability']){echo " button-primary";}?>" href="<?php echo esc_url( $link ); ?>"<?php if(!$item['availability']){echo ' target="_blank"'; }?>><?php echo esc_html( $button ); ?></a>
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
								<h2 class="cartbounty-section-title"><?php esc_html_e('Tools', 'woo-save-abandoned-carts'); ?></h2>
								<div class="cartbounty-section-intro"><?php esc_html_e('Here you will find some special tools that will enable you to discover even more bounty. Increase your chances of getting more recoverable abandoned carts. You can enable one or all of them, just make sure not to overwhelm your visitors with information :)', 'woo-save-abandoned-carts'); ?></div>
								<div class="cartbounty-row cartbounty-flex">
									<?php
									$tools_items = $this->get_sections( $tab );
									foreach( $tools_items as $key => $item ): ?>
										<?php $button = esc_html__('Enable', 'woo-save-abandoned-carts'); 
											if($item['connected']){
												$button = esc_html__('Edit', 'woo-save-abandoned-carts');
											}
										?>
										<div class="cartbounty-section-item-container cartbounty-col-sm-6 cartbounty-col-lg-4">
											<div class="cartbounty-section-item<?php if($item['connected']){echo ' cartbounty-connected'; }?>">
												<?php $link = '?page='. CARTBOUNTY .'&tab='. $tab .'&section='. $key; ?>
												<div class="cartbounty-section-image">
													<?php echo $this->get_connection( $item['connected'], true, $tab ); ?>
													<a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>"><?php echo $this->get_icon( $key, false, false, true ); ?></a>
												</div>
												<div class="cartbounty-section-content">
													<h3><a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>"><?php echo esc_html( $item['name'] ); ?></a></h3>
													<?php echo wp_kses_post( $item['description'] ); ?>
													<a class="button cartbounty-button" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $button ); ?></a>
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
									$exclude_recovered_cart_notifications = esc_attr( get_option('cartbounty_exclude_recovered') );
								?>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Ghost carts', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('Ghost carts are carts that can’t be identified since the visitor has not provided neither email nor phone number.', 'woo-save-abandoned-carts'); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9<?php if($exclude_ghost_carts){ echo ' cartbounty-checked-parent'; }?>">
										<div class="cartbounty-settings-group cartbounty-toggle <?php if($exclude_ghost_carts){ echo ' cartbounty-checked'; }?>">
											<label for="cartbounty-exclude-ghost-carts" class="cartbounty-switch cartbounty-control-visibility">
												<input id="cartbounty-exclude-ghost-carts" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exclude_ghost_carts" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exclude_ghost_carts, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-exclude-ghost-carts" class="cartbounty-control-visibility">
												<?php esc_html_e('Exclude ghost carts', 'woo-save-abandoned-carts'); ?>
											</label>
										</div>
										<div class="cartbounty-settings-group cartbounty-hidden">
											<label for="cartbounty_allowed_countries" class="cartbounty-unavailable"><?php esc_html_e('Exclude from all countries except these', 'woo-save-abandoned-carts'); ?></label>
											<select id="cartbounty-allowed-countries" class="cartbounty-select cartbounty-unavailable disabled" placeholder="<?php echo esc_attr('Choose countries / regions…', 'woo-save-abandoned-carts'); ?>"></select>
											<button id="cartbounty-add-all-countries" class="cartbounty-button button button-secondary cartbounty-unavailable hidden" type="button"><?php esc_html_e('Select all', 'woo-save-abandoned-carts'); ?></button>
											<button id="cartbounty-remove-all-countries" class="cartbounty-button button button-secondary cartbounty-unavailable hidden" type="button"><?php esc_html_e('Select none', 'woo-save-abandoned-carts'); ?></button>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'ghost_countries' ); ?></i>
											</p>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Notifications', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('Receive email notifications about newly abandoned carts. Please note, that you will not get emails about ghost carts.', 'woo-save-abandoned-carts'); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group">
											<label for="cartbounty_notification_email"><?php esc_html_e('Admin email', 'woo-save-abandoned-carts'); ?></label>
											<input id="cartbounty_notification_email" class="cartbounty-text" type="email" name="cartbounty_notification_email" value="<?php echo esc_attr( get_option('cartbounty_notification_email') ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) );?>" <?php echo $this->disable_field(); ?> multiple />
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('You can add multiple emails separated by a comma.', 'woo-save-abandoned-carts'); ?>
											</p>
										</div>
										<div class="cartbounty-settings-group">
											<label for="cartbounty_notification_frequency"><?php esc_html_e('Check for new abandoned carts', 'woo-save-abandoned-carts'); ?></label>
											<?php $this->display_frequencies(); ?>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-exclude-recovered" class="cartbounty-switch">
												<input id="cartbounty-exclude-recovered" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exclude_recovered" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exclude_recovered_cart_notifications, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-exclude-recovered">
												<?php esc_html_e('Exclude recovered carts from notifications', 'woo-save-abandoned-carts'); ?>
											</label>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Protection', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php echo sprintf(
											/* translators: %s - URL link */
											esc_html__('In case you feel that bots might be leaving recoverable abandoned carts. Please %sview this%s to learn how to prevent bots from leaving ghost carts.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_LICENSE_SERVER_URL . '/abandoned-carts', 'ghost_bots', '#prevent-bots-from-leaving-abandoned-carts' ) ) .'" title="'. esc_attr__('Prevent bots from leaving ghost carts', 'woo-save-abandoned-carts') .'" target="_blank">', '</a>' ); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty_recaptcha" class="cartbounty-switch cartbounty-unavailable">
												<input id="cartbounty_recaptcha" class="cartbounty-checkbox" type="checkbox" disabled />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty_recaptcha" class="cartbounty-unavailable"><?php esc_html_e('Enable reCAPTCHA v3', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'recaptcha' ); ?></i>
											</p>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Unfinished orders', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('Send abandoned cart reminders about WooCommerce orders that have been abandoned.', 'woo-save-abandoned-carts'); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group-container">
											<div class="cartbounty-settings-group cartbounty-toggle">
												<label for="cartbounty-order-recovery" class="cartbounty-switch cartbounty-unavailable">
													<input id="cartbounty-order-recovery" class="cartbounty-checkbox" type="checkbox" disabled />
													<span class="cartbounty-slider round"></span>
												</label>
												<label for="cartbounty-order-recovery" class="cartbounty-unavailable"><?php esc_html_e('Enable unfinished order recovery', 'woo-save-abandoned-carts'); ?></label>
												<p class='cartbounty-additional-information'>
													<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'unfinished_orders' ); ?></i>
												</p>
											</div>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Text messages', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('General settings that may come in handy when sending abandoned cart SMS text messages.', 'woo-save-abandoned-carts'); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group-container">
											<div class="cartbounty-settings-group cartbounty-toggle">
												<label for="cartbounty-international-phone" class="cartbounty-switch cartbounty-unavailable">
													<input id="cartbounty-international-phone" class="cartbounty-checkbox" type="checkbox" disabled />
													<span class="cartbounty-slider round"></span>
												</label>
												<label for="cartbounty-international-phone" class="cartbounty-unavailable"><?php esc_html_e( 'Enable easy international phone input', 'woo-save-abandoned-carts' ); ?></label>
												<p class='cartbounty-additional-information'>
													<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'easy_phone_input' ); ?></i>
												</p>
											</div>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Miscellaneous', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('Other settings that may be useful.', 'woo-save-abandoned-carts');?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle<?php if($lift_email_on){ echo ' cartbounty-checked'; }?>">
											<label for="cartbounty-lift-email" class="cartbounty-switch cartbounty-control-visibility">
												<input id="cartbounty-lift-email" class="cartbounty-checkbox" type="checkbox" name="cartbounty_lift_email" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $lift_email_on, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-lift-email" class="cartbounty-control-visibility"><?php esc_html_e('Lift email field', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('You could increase the chances of saving recoverable abandoned carts by moving the email field to the top of your checkout form.', 'woo-save-abandoned-carts');
												echo " <i class='cartbounty-hidden'>". esc_html__('Please test the checkout after enabling this, as sometimes it can cause issues or not raise the field if you have a custom checkout.', 'woo-save-abandoned-carts') ."</i>";
												 ?>
											</p>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-lift-phone" class="cartbounty-switch cartbounty-unavailable">
												<input id="cartbounty-lift-phone" class="cartbounty-checkbox" type="checkbox" disabled />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-lift-phone" class="cartbounty-unavailable"><?php echo __('Lift phone field', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('You could increase the chances of saving recoverable abandoned carts by moving the phone field to the top of your checkout form.', 'woo-save-abandoned-carts');
												 ?>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><br/><?php echo $this->display_unavailable_notice( 'lift_phone' ); ?></i>
											</p>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-hide-images" class="cartbounty-switch">
												<input id="cartbounty-hide-images" class="cartbounty-checkbox" type="checkbox" name="cartbounty_hide_images" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $hide_images_on, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-hide-images"><?php esc_html_e('Display abandoned cart contents in a list', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('This will only affect how abandoned cart contents are displayed in the Abandoned carts tab.', 'woo-save-abandoned-carts'); ?>
											</p>
										</div>
									</div>
								</div>
								<div class='cartbounty-button-row'>
									<?php
									settings_fields( 'cartbounty-settings' );
									do_settings_sections( 'cartbounty-settings' );
									if(current_user_can( 'manage_options' )){
										echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". esc_html__('Save settings', 'woo-save-abandoned-carts') ."</button>";
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
						$footer_bulk_delete = false;

						if( isset( $_GET['action2'] ) && $_GET['action2'] == 'delete' ){ //Check if bottom Bulk delete action fired
							$footer_bulk_delete = true;
						}
						
						//Output table contents
						$message = '';
						if ('delete' === $table->current_action() || $footer_bulk_delete) {
							if(!empty($_REQUEST['id'])){ //In case we have a row selected for deletion, process the message otput
								if(is_array($_REQUEST['id'])){ //If deleting multiple lines from table
									$deleted_row_count = esc_html(count($_REQUEST['id']));
								}
								else{ //If a single row is deleted
									$deleted_row_count = 1;
								}
								$message = '<div class="updated below-h2" id="message"><p>' . sprintf(
									/* translators: %d - Item count */
									esc_html__('Items deleted: %d', 'woo-save-abandoned-carts' ), esc_html( $deleted_row_count )
								) . '</p></div>';
							}
						}

						$cart_status = 'all';
				        if (isset($_GET['cart-status'])){
				            $cart_status = $_GET['cart-status'];
				        }
					?>
					<?php echo $message; 
					if ($this->get_cart_count( 'all' ) == 0): //If no abandoned carts, then output this note ?>
						<p id="cartbounty-no-carts-message">
							<?php echo wp_kses_post( __( 'Looks like you do not have any saved Abandoned carts yet.<br/>But do not worry, as soon as someone fills the <strong>Email</strong> or <strong>Phone number</strong> fields of your WooCommerce Checkout form and abandons the cart, it will automatically appear here.', 'woo-save-abandoned-carts') ); ?>
						</p>
					<?php else: ?>
						<form id="cartbounty-table" method="GET">
							<div class="cartbounty-row">
								<div class="cartbounty-col-sm-6">
									<?php $this->display_cart_statuses( $cart_status, $tab);?>
								</div>
								<div class="cartbounty-col-sm-6">
									<div id="cartbounty-cart-search">
										<label class="screen-reader-text cartbounty-unavailable" for="post-search-input"><?php esc_html_e('Search carts', 'woo-save-abandoned-carts'); ?> :</label>
										<input type="search" id="cartbounty-cart-search-input" class="cartbounty-text cartbounty-unavailable" readonly><button type="button" id="cartbounty-search-submit" class="cartbounty-button button button-secondary cartbounty-unavailable"><i class="cartbounty-icon cartbounty-visible-xs"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70.06 70"><path d="M30,60a29.9,29.9,0,0,0,16.64-5.06L60.7,69a3.58,3.58,0,0,0,5,0L69,65.64a3.57,3.57,0,0,0,0-5l-14.13-14A30,30,0,1,0,30,60Zm0-48.21A18.23,18.23,0,1,1,11.76,30,18.24,18.24,0,0,1,30,11.76Zm0,0" transform="translate(0)"/></svg></i><i class='cartbounty-hidden-xs'><?php esc_html_e('Search carts', 'woo-save-abandoned-carts'); ?></i>
										</button>
										<p class='cartbounty-additional-information'>
											<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'enable_search' ); ?></i>
										</p>
									</div>
								</div>
							</div>
							<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>"/>
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
			'carts' 	=> esc_html__('Abandoned carts', 'woo-save-abandoned-carts'),
			'recovery' 	=> esc_html__('Recovery', 'woo-save-abandoned-carts'),
			'tools' 	=> esc_html__('Tools', 'woo-save-abandoned-carts'),
			'settings' 	=> esc_html__('Settings', 'woo-save-abandoned-carts')
		);
		echo '<h2 class="nav-tab-wrapper">';
		
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : ''; //if the tab is open, an additional class, nav-tab-active, is added
			echo "<a class='cartbounty-tab nav-tab$class' href='". esc_url( "?page=". esc_html( CARTBOUNTY ) ."&tab=$tab" ) ."'>". $this->get_icon( $tab, $current, false, false ) ."<span class='cartbounty-tab-name'>". esc_html( $name ) ."</span></a>";
		}
		echo '</h2>';
	}

	/**
	 * Method returns current open tab. Default tab - carts
	 *
	 * @since    7.0.4
	 */
	function get_open_tab(){
		$tab = 'carts';
		if (isset($_GET['tab'])){
			$tab = $_GET['tab'];
		}
		return $tab;
	}

	/**
	 * Method returns current open section. Default - empty section
	 *
	 * @since    7.0.4
	 */
	function get_open_section(){
		$section = '';
		if (isset($_GET['section'])){
			$section = $_GET['section'];
		}
		return $section;
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
    	$all_title = esc_html__('All integrations', 'woo-save-abandoned-carts');

    	if($tab == 'tools'){
    		$all_title = esc_html__('All tools', 'woo-save-abandoned-carts');
    	}

    	//Generating sections for large screens
    	echo '<ul id="cartbounty-sections" class="cartbounty-hidden-xs">';
    	foreach( $sections as $key => $section ){
    		$class = ( $key == $active_section ) ? 'current' : '';
    		$availability_class = (!$section['availability']) ? 'cartbounty-unavailable' : '';
    		$link = "?page=". CARTBOUNTY ."&tab=$tab&section=$key";
    		if(!$section['availability']){
    			$link = '#';
    		}

	    	echo "<li class='". esc_attr( $availability_class ) ."'><a href='". esc_url( $link ) ."' title='". esc_attr( $section['name'] ). "' class='". esc_attr( $class ) ."'>". $this->get_icon( $key, $active_section, true, false) ." <span class='cartbounty-section-name'>". esc_html( $section['name'] ). "</span>". $this->get_connection( $section['connected'], $text = false, false ) ."</a></li>";
    	}
    	echo '</ul>';

    	//Generating sections for small screens
    	echo '<select id="cartbounty-mobile-sections" class="cartbounty-select" onchange="window.location.href=this.value" style="display: none;">';
    		echo "<option value='". esc_url( "?page=". esc_html( CARTBOUNTY ) ."&tab=$tab" ) ."'>". esc_html( $all_title ) ."</option>";
	    	foreach( $sections as $key => $section ){
	    		$link = "?page=". CARTBOUNTY ."&tab=$tab&section=$key";
	    		if($section['availability']){
	    			echo "<option value='". esc_url( $link ) ."' ". selected( $active_section, $key, false ) .">". esc_html( $section['name'] ). "</option>";
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

		$intervals = array();
		$minutes = array(10, 20, 30, 60, 120, 180, 240, 300, 360); //Defining array of minutes
		foreach ($minutes as $minute) {
			if($minute < 60) { //Generate minutes
				$intervals[$minute] = sprintf(
					esc_html( _n( 'Every minute', 'Every %s minutes', esc_html( $minute ), 'woo-save-abandoned-carts' ) ), esc_html( $minute )
				);
			}

			elseif ($minute < 720) {
				$hours = $minute / 60; //Splitting with 60 minutes to get amount of hours
				$intervals[$minute] = sprintf(
					esc_html( _n( 'Every hour', 'Every %s hours', esc_html( $hours ), 'woo-save-abandoned-carts' ) ), esc_html( $hours )
				);
			}
		}

		//Add other intervals
		$intervals[720] = 	esc_html__('Twice a day', 'woo-save-abandoned-carts');
		$intervals[1440] = 	esc_html__('Once a day', 'woo-save-abandoned-carts');
		$intervals[2880] = 	esc_html__('Once every 2 days', 'woo-save-abandoned-carts');
		$intervals[0] = 	esc_html__('Disable notifications', 'woo-save-abandoned-carts');

    	echo '<select id="cartbounty_notification_frequency" class="cartbounty-select" name="cartbounty_notification_frequency[hours]" autocomplete="off" '. $this->disable_field() .'>';
	    	foreach( $intervals as $key => $interval ){
		    	echo "<option value='". esc_attr( $key ) ."' ". selected( $active_frequency['hours'], $key, false ) .">". esc_html( $interval ) ."</option>";
	    	}
    	echo '</select>';
    }

    /**
     * Prepare time intervals from minutes
     *
     * @since    7.0.5
     * @return   array
     * @param 	 array    $minutes    		Array of minutes
     * @param 	 string   $zero_value    	Content for zero value
     */
    function prepare_time_intervals( $minutes = array(), $zero_value = '' ){
    	$intervals = array();
		foreach ($minutes as $minute) {
			if($minute == 0) { //Generate minutes
				$intervals[$minute] = $zero_value;
			}
			elseif($minute < 60) { //Generate minutes
				$intervals[$minute] = sprintf(
					esc_html( _n( '%s minute', '%s minutes', esc_html( $minute ), 'woo-save-abandoned-carts' ) ), esc_html( $minute )
				);

			}elseif($minute < 1440) { //Generate hours
				$hours = $minute / 60; //Splitting with 60 minutes to get amount of hours
				$intervals[$minute] = sprintf(
					esc_html( _n( '%s hour', '%s hours', esc_html( $hours ), 'woo-save-abandoned-carts' ) ), esc_html( $hours )
				);

			}elseif($minute < 10080) { //Generate days
				$days = $minute / 1440; //Splitting with 1440 minutes to get amount of days
				$intervals[$minute] = sprintf(
					esc_html( _n( '%s day', '%s days', esc_html( $days ), 'woo-save-abandoned-carts' ) ), esc_html( $days )
				);

			}else{ //Generate weeks
				$weeks = $minute / 10080; //Splitting with 10080 minutes to get amount of weeks
				$intervals[$minute] = sprintf(
					esc_html( _n( '%s week', '%s weeks', esc_html( $weeks ), 'woo-save-abandoned-carts' ) ), esc_html( $weeks )
				);
			}
		}
		return $intervals;
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

			$wordpress = new CartBounty_WordPress();

			$sections = array(
				'activecampaign'	=> array(
					'name'				=> 'ActiveCampaign',
					'connected'			=> false,
					'availability'		=> false,
					'info_link'			=> CARTBOUNTY_ACTIVECAMPAIGN_TRIAL_LINK,
					'description'		=> '<p>' . esc_html__("ActiveCampaign is awesome and allows creating different If/Else statements to setup elaborate rules for sending abandoned cart recovery emails.", 'woo-save-abandoned-carts') . '</p><p>' . esc_html__("In contrast to MailChimp, it allows sending reminder email series without the requirement to subscribe.", 'woo-save-abandoned-carts') . '</p>'
				),
				'getresponse'	=> array(
					'name'				=> 'GetResponse',
					'connected'			=> false,
					'availability'		=> false,
					'info_link'			=> CARTBOUNTY_GETRESPONSE_TRIAL_LINK,
					'description'		=> '<p>' . esc_html__("GetResponse offers efficient and beautifully designed email marketing platform to recover abandoned carts. It is a professional email marketing system with awesome email design options and beautifully pre-designed email templates.", 'woo-save-abandoned-carts') . '</p>'
				),
				'mailchimp'	=> array(
					'name'				=> 'MailChimp',
					'connected'			=> false,
					'availability'		=> false,
					'info_link'			=> CARTBOUNTY_MAILCHIMP_LINK,
					'description'		=> '<p>' . esc_html__("MailChimp offers a forever free plan and allows to create both single and series of reminder emails (e.g., first email in the 1st hour of cart abandonment, 2nd after 24 hours etc.).", 'woo-save-abandoned-carts') . '</p><p>' . esc_html__("MailChimp will only send the 1st email in the series unless a user becomes a subscriber.", 'woo-save-abandoned-carts') . '</p>'
				),
				'wordpress'	=> array(
					'name'				=> 'WordPress',
					'connected'			=> $wordpress->automation_enabled() ? true : false,
					'availability'		=> true,
					'info_link'			=> '',
					'description'		=> '<p>' . esc_html__("A simple solution for sending abandoned cart recovery emails using the default WordPress mail server. This recovery option works best if you have a small to medium number of abandoned carts.", 'woo-save-abandoned-carts') . '</p><p>' . esc_html__("If you are looking for something more advanced and powerful, please consider connecting with ActiveCampaign, GetResponse or MailChimp.", 'woo-save-abandoned-carts') . '</p>'
				),
				'bulkgate'	=> array(
					'name'				=> 'BulkGate',
					'connected'			=> false,
					'availability'		=> false,
					'info_link'			=> CARTBOUNTY_BULKGATE_TRIAL_LINK,
					'description'		=> '<p>' . esc_html__("A perfect channel for sending personalized, time-sensitive SMS text messages like abandoned cart reminders.", 'woo-save-abandoned-carts') . '</p><p>' . esc_html__("Add an additional dimension to your existing abandoned cart email recovery workflow including a personal SMS about the abandoned cart.", 'woo-save-abandoned-carts') . '</p>'
				)
			);
		}

		if($tab == 'tools'){
			$sections = array(
				'exit_intent'	=> array(
					'name'				=> esc_html__('Exit Intent', 'woo-save-abandoned-carts'),
					'connected'			=> esc_attr( get_option('cartbounty_exit_intent_status')) ? true : false,
					'availability'		=> true,
					'description'		=> '<p>' . esc_html__("Save more recoverable abandoned carts by showcasing a popup message right before your customer tries to leave and offer an option to save his shopping cart by entering his email.", 'woo-save-abandoned-carts') . '</p>'
				),
				'early_capture'	=> array(
					'name'				=> esc_html__('Early capture', 'woo-save-abandoned-carts'),
					'connected'			=> false,
					'availability'		=> true,
					'description'		=> '<p>' . esc_html__('Try saving more recoverable abandoned carts by enabling Early capture to collect customer’s email or phone right after the "Add to cart" button is clicked.', 'woo-save-abandoned-carts') . '</p>'
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

			case 'wordpress':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo sprintf(
					/* translators: %s - URL link tags */
					esc_html__('A simple solution for sending abandoned cart recovery emails using the default WordPress mail server. This recovery option works best if you have a small to medium number of abandoned carts. If you are looking for something more advanced and powerful, please consider upgrading to %s%s Pro%s.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_section_intro' ) ) .'" target="_blank">', esc_html( CARTBOUNTY_ABREVIATION ), '</a>' );?>
				</div>

				<form method="post" action="options.php">
					<?php
						settings_fields( 'cartbounty-wordpress-settings' );
						do_settings_sections( 'cartbounty-wordpress-settings' );
						$wordpress = new CartBounty_WordPress();
						$automation_steps = get_option('cartbounty_automation_steps');
					?>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-full-width cartbounty-col-sm-12 cartbounty-col-md-12 cartbounty-col-lg-12">
							<h4><?php esc_html_e('Automation workflow', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Configure your abandoned cart reminder emails, how they look, when to send them, include coupons, etc. You can choose to enable just one or all of them creating a 3-step automation workflow. The countdown of the next step starts right after the previous one is finished.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-full-width cartbounty-col-sm-12 cartbounty-col-md-12 cartbounty-col-lg-12">
							<div class="cartbounty-settings-group">
								<div class="cartbounty-stairway">
									<?php if(!empty($automation_steps)){
											$step = $automation_steps[0];
											$step = (object)$step;
											$enabled = ( isset($step->enabled) ) ? $step->enabled : false;
											$subject = ( isset($step->subject) ) ? $step->subject : '';
											$heading = ( isset($step->heading) ) ? $step->heading : '';
											$content = ( isset($step->content) ) ? $step->content : '';
											$main_color = ( isset($step->main_color) ) ? $step->main_color : false;
											$button_color = ( isset($step->button_color) ) ? $step->button_color : false;
											$text_color = ( isset($step->text_color) ) ? $step->text_color : false;
											$background_color = ( isset($step->background_color) ) ? $step->background_color : false;
											$time_interval_name = $wordpress->get_intervals( 0, $selected_name = true );
											$preview_email_nonce = wp_create_nonce( 'preview_email' );
											$test_email_nonce = wp_create_nonce( 'test_email' ); ?>

											<div class="cartbounty-step">
												<div class="cartbounty-step-opener">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-lg-3">
															<div class="cartbounty-automation-number">1</div>
															<div class="cartbounty-automation-name">
																<h3><?php echo esc_html( $wordpress->get_defaults( 'name', 0 ) ); ?></h3>
																<p><?php echo sprintf(
																	/* translators: %s - Time, e.g. 10 minutes */
																	 esc_html__('Sends after %s', 'woo-save-abandoned-carts'), esc_html( $time_interval_name ) );?></p>
																<div class="cartbounty-step-trigger"></div>
															</div>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-lg-9">
															<div class="cartbounty-row">
																<div class="cartbounty-stats-container cartbounty-col-sm-12 cartbounty-col-lg-8">
																	<div class="cartbounty-stats">
																		<i><?php esc_html_e('Queue', 'woo-save-abandoned-carts'); ?></i>
																		<p><?php echo esc_html( $wordpress->get_queue() ); ?></p>
																	</div>
																	<div class="cartbounty-stats">
																		<i><?php esc_html_e('Sends', 'woo-save-abandoned-carts'); ?></i>
																		<p><?php echo esc_html( $wordpress->get_stats() ); ?></p>
																	</div>
																	<div class="cartbounty-stats cartbounty-percentage-switcher">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e('Open rate', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_enable_email_stats' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Upgrade to see stats', 'woo-save-abandoned-carts'); ?></a>
																			<i><?php esc_html_e('Opens', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																	<div class="cartbounty-stats cartbounty-percentage-switcher">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e('Click rate', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<i><?php esc_html_e('Clicks', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																</div>
																<div class="cartbounty-trigger-container cartbounty-col-sm-12 cartbounty-col-lg-4">
																	<div class="cartbounty-automation-status">
																		<?php $wordpress->display_automation_status( $enabled );?>
																	</div><div class="cartbounty-step-trigger"></div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="cartbounty-step-contents">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('General', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php esc_html_e('Enable email sending to start this automated abandoned cart recovery step.', 'woo-save-abandoned-carts');
																	echo ' ' . sprintf(
																	/* translators: %s - Link tags */
																	 esc_html__('Learn how to use %spersonalization tags%s.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . '/personalization-tags', 'wp_personalization' ) ) .'" target="_blank">', '</a>');?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9<?php if($enabled){ echo ' cartbounty-checked-parent'; }?>">
															<div class="cartbounty-settings-group cartbounty-toggle">
																<label for="cartbounty-automation-status" class="cartbounty-switch cartbounty-control-visibility cartbounty-step-controller">
																	<input id="cartbounty-automation-status" class="cartbounty-checkbox" type="checkbox" name="cartbounty_automation_steps[0][enabled]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $enabled, false ); ?> autocomplete="off" />
																	<span class="cartbounty-slider round"></span>
																</label>
																<label for="cartbounty-automation-status" class="cartbounty-control-visibility cartbounty-step-controller"><?php esc_html_e('Enable email', 'woo-save-abandoned-carts'); ?></label>
															</div>
															<div class="cartbounty-settings-group cartbounty-hidden">
																<label for="cartbounty-automation-interval"><?php esc_html_e('Send email after', 'woo-save-abandoned-carts'); ?></label>
																<?php $wordpress->display_intervals(0); ?>
																<p class='cartbounty-additional-information'>
																	<?php echo sprintf(
																	/* translators: %s - Link tags */
																	 esc_html__( 'Please %ssee this%s to learn how reminder sending works and when it will be delivered.', 'woo-save-abandoned-carts' ), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . '/abandoned-carts', 'wp_is_it_abandoned', '#when-is-the-cart-abandoned' ) ) .'" target="_blank">', '</a>'); ?>
																</p>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-subject"><?php esc_html_e('Email subject', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-content-creation cartbounty-flex">
																	<input id="cartbounty-automation-subject" class="cartbounty-text" type="text" name="cartbounty_automation_steps[0][subject]" value="<?php echo $this->sanitize_field($subject); ?>" placeholder="<?php echo esc_attr( $wordpress->get_defaults( 'subject', 0 ) ); ?>" /><?php $this->add_emojis(); ?><?php $this->add_tags(); ?>
																</div>
																<p class='cartbounty-additional-information'>
																	<?php esc_html_e('Subject line has a huge impact on email open rate.', 'woo-save-abandoned-carts'); ?>
																</p>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-heading"><?php esc_html_e('Main title', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-content-creation cartbounty-flex">
																	<input id="cartbounty-automation-heading" class="cartbounty-text" type="text" name="cartbounty_automation_steps[0][heading]" value="<?php echo $this->sanitize_field($heading); ?>" placeholder="<?php echo esc_attr( $wordpress->get_defaults( 'heading', 0 ) ); ?>" /><?php $this->add_emojis(); ?><?php $this->add_tags(); ?>
																</div>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-content"><?php esc_html_e('Content', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-content-creation cartbounty-flex">
																	<textarea id="cartbounty-automation-content" class="cartbounty-text" name="cartbounty_automation_steps[0][content]" placeholder="<?php echo esc_attr( $wordpress->get_defaults( 'content', 0 ) ); ?>" rows="4"><?php echo $this->sanitize_field($content); ?></textarea><?php $this->add_emojis(); ?><?php $this->add_tags(); ?>
																</div>
															</div>
														</div>
													</div>
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('Appearance', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php echo sprintf(
																	/* translators: %s - Link tags */
																	 esc_html__('Choose a template that will be used to display the abandoned cart reminder email. Look %shere%s to see advanced template customization options', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . '/templates', 'wp_template_customization' ) ) .'" target="_blank">', '</a>');?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
															<div class="cartbounty-settings-group">
																<h4><?php esc_html_e('Template', 'woo-save-abandoned-carts'); ?></h4>
																<div class="cartbounty-flex-container">
																	<div id="cartbounty-automation-template-light" class="cartbounty-type cartbounty-email-template cartbounty-email-template-light cartbounty-radio-active">
																		<label class="cartbounty-image" for="cartbounty-template-light">
																			<em>
																				<i>
																					<span class="cartbounty-template-top-image"></span>
																					<img src="<?php echo esc_url( plugins_url( 'assets/template-light.svg', __FILE__ ) ) ; ?>" title="<?php esc_attr_e('Light', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Light', 'woo-save-abandoned-carts'); ?>"/>
																				</i>
																			</em>
																			<input id="cartbounty-template-light" class="cartbounty-radiobutton" type="radio" name="cartbounty_automation_steps[0][template]" value="1" <?php echo $this->disable_field(); ?> checked="" autocomplete="off" />
																			<?php esc_html_e('Light', 'woo-save-abandoned-carts'); ?>
																		</label>
																	</div>
																	<div id="cartbounty-automation-template-rows" class="cartbounty-type cartbounty-email-template cartbounty-email-template-rows">
																		<label class="cartbounty-image" for="cartbounty-template-rows">
																			<em>
																				<i>
																					<span class="cartbounty-template-top-image"></span>
																					<img src="<?php echo esc_url( plugins_url( 'assets/template-rows.svg', __FILE__ ) ) ; ?>" title="<?php esc_attr_e('With cart contents', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('With cart contents', 'woo-save-abandoned-carts'); ?>"/>
																				</i>
																				<span class="cartbounty-wordpress-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
																					<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_style_rows' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
																				</span>
																			</em>
																			<input id="cartbounty-template-rows" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
																			<?php esc_html_e('With cart contents', 'woo-save-abandoned-carts'); ?>
																		</label>
																	</div>
																	<div id="cartbounty-automation-template-columns" class="cartbounty-type cartbounty-email-template cartbounty-email-template-columns">
																		<label class="cartbounty-image" for="cartbounty-template-columns">
																			<em>
																				<i>
																					<span class="cartbounty-template-top-image"></span>
																					<img src="<?php echo esc_url( plugins_url( 'assets/template-columns.svg', __FILE__ ) ) ; ?>" title="<?php esc_attr_e('With cart contents in columns', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('With cart contents in columns', 'woo-save-abandoned-carts'); ?>"/>
																				</i>
																				<span class="cartbounty-wordpress-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
																					<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_style_columns' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
																				</span>
																			</em>
																			<input id="cartbounty-template-columns" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
																			<?php esc_html_e('With cart contents in columns', 'woo-save-abandoned-carts'); ?>
																		</label>
																	</div>
																</div>
															</div>
															<div class="cartbounty-settings-group">
																<h4><?php esc_html_e('Colors', 'woo-save-abandoned-carts'); ?></h4>
																<p class='cartbounty-additional-information'>
																	<?php esc_html_e('Look at the default email colors and adjust them to fit your design requirements.', 'woo-save-abandoned-carts'); ?>
																</p>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-main-color"><?php esc_html_e('Main:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-main-color" type="text" name="cartbounty_automation_steps[0][main_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $main_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-inverse-color"><?php esc_html_e('Button:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-button-color" type="text" name="cartbounty_automation_steps[0][button_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $button_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-text-color"><?php esc_html_e('Text:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-text-color" type="text" name="cartbounty_automation_steps[0][text_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $text_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-background-color"><?php esc_html_e('Backdrop:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-background-color" type="text" name="cartbounty_automation_steps[0][background_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $background_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
															</div>
															<div class="cartbounty-settings-group cartbounty-toggle">
																<label for="cartbounty-automation-include-image" class="cartbounty-switch cartbounty-unavailable">
																	<input id="cartbounty-automation-include-image" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
																	<span class="cartbounty-slider round"></span>
																</label>
																<label for="cartbounty-automation-include-image" class="cartbounty-unavailable"><?php esc_html_e('Include image', 'woo-save-abandoned-carts'); ?></label>
																<p class='cartbounty-additional-information'>
																	<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'wp_include_image' ); ?></i>
																</p>
															</div>
														</div>
													</div>
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('Coupon', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php esc_html_e('Add a coupon code to your recovery email as it might help to increase recovery ratio.', 'woo-save-abandoned-carts'); ?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
															<div class="cartbounty-settings-group cartbounty-toggle">
																<label for="cartbounty-automation-generate-coupon" class="cartbounty-switch cartbounty-unavailable">
																	<input id="cartbounty-automation-generate-coupon" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
																	<span class="cartbounty-slider round"></span>
																</label>
																<label for="cartbounty-automation-generate-coupon" class="cartbounty-unavailable"><?php esc_html_e('Generate coupon', 'woo-save-abandoned-carts'); ?></label>
																<p class='cartbounty-additional-information'>
																	<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'generate_coupon' ); ?></i>
																</p>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-existing-coupon"><?php esc_html_e('Include an existing coupon', 'woo-save-abandoned-carts'); ?></label>
																<select id="cartbounty-automation-existing-coupon" class="cartbounty-select" placeholder="<?php esc_attr_e('Search coupon...', 'woo-save-abandoned-carts'); ?>" disabled autocomplete="off">
																</select>
															</div>
														</div>
													</div>
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('Test email', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php esc_html_e('Before activating this automation, you might want to test your email.', 'woo-save-abandoned-carts'); ?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
															<div class="cartbounty-settings-group">
																<h4><?php esc_html_e('Preview', 'woo-save-abandoned-carts'); ?></h4>
																<button type="button" class='cartbounty-button button-secondary cartbounty-progress cartbounty-preview-email' data-nonce='<?php echo esc_attr( $preview_email_nonce ); ?>' <?php echo $this->disable_field(); ?>><?php esc_html_e('Preview email', 'woo-save-abandoned-carts'); ?></button>
																<?php echo $wordpress->output_modal_container(); ?>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-send-test"><?php esc_html_e('Send a test email to', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-input-with-button">
																	<input id="cartbounty-send-test" class="cartbounty-text cartbounty-disable-submit" type="email"  placeholder="<?php echo esc_attr( get_option( 'admin_email' ) );?>" <?php echo $this->disable_field(); ?> />
																	<button type="button" class='cartbounty-button button-secondary cartbounty-progress cartbounty-send-email' data-nonce='<?php echo esc_attr( $test_email_nonce ); ?>' <?php echo $this->disable_field(); ?>><?php esc_html_e('Send', 'woo-save-abandoned-carts'); ?></button>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="cartbounty-step cartbounty-step-unavailable">
												<div class="cartbounty-step-opener">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-lg-3">
															<div class="cartbounty-automation-number">2</div>
															<div class="cartbounty-automation-name">
																<h3><?php echo esc_html( $wordpress->get_defaults( 'name', 1 ) ); ?></h3>
																<p><?php $time_interval_name = $wordpress->get_intervals( 1, $selected_name = true );
																	echo sprintf( esc_html__('Sends after %s', 'woo-save-abandoned-carts'), esc_html( $time_interval_name ) );?></p>
																<div class="cartbounty-step-trigger"></div>
															</div>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-lg-9">
															<div class="cartbounty-row">
																<div class="cartbounty-stats-container cartbounty-col-sm-12 cartbounty-col-lg-8">
																	<div class="cartbounty-stats">
																		<i><?php esc_html_e('Queue', 'woo-save-abandoned-carts'); ?></i>
																		<p>0</p>
																	</div>
																	<div class="cartbounty-stats">
																		<i><?php esc_html_e('Sends', 'woo-save-abandoned-carts'); ?></i>
																		<p>0</p>
																	</div>
																	<div class="cartbounty-stats cartbounty-percentage-switcher">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e('Open rate', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_enable_email_stats' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Upgrade to see stats', 'woo-save-abandoned-carts'); ?></a>
																			<i><?php esc_html_e('Opens', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																	<div class="cartbounty-stats cartbounty-percentage-switcher">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e('Click rate', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<i><?php esc_html_e('Clicks', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																</div>
																<div class="cartbounty-trigger-container cartbounty-col-sm-12 cartbounty-col-lg-4">
																	<div class="cartbounty-automation-status">
																		<span class="status inactive"><?php esc_html_e('Disabled', 'woo-save-abandoned-carts'); ?></span>
																	</div><div class="cartbounty-step-trigger"></div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="cartbounty-wordpress-get-additional-step">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-lg-3">
															<div class="cartbounty-automation-name">
																<h3><?php esc_html_e('Upgrade to enable this email', 'woo-save-abandoned-carts'); ?></h3>
															</div>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-lg-9">
															<div class="cartbounty-stats">
																<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_add_automation_step' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="cartbounty-step cartbounty-step-unavailable">
												<div class="cartbounty-step-opener">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-lg-3">
															<div class="cartbounty-automation-number">3</div>
															<div class="cartbounty-automation-name">
																<h3><?php echo esc_html( $wordpress->get_defaults( 'name', 2 ) ); ?></h3>
																<p><?php $time_interval_name = $wordpress->get_intervals( 2, $selected_name = true );
																	echo sprintf( esc_html__('Sends after %s', 'woo-save-abandoned-carts'), esc_html( $time_interval_name ) );?></p>
																<div class="cartbounty-step-trigger"></div>
															</div>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-lg-9">
															<div class="cartbounty-row">
																<div class="cartbounty-stats-container cartbounty-col-sm-12 cartbounty-col-lg-8">
																	<div class="cartbounty-stats">
																		<i><?php esc_html_e('Queue', 'woo-save-abandoned-carts'); ?></i>
																		<p>0</p>
																	</div>
																	<div class="cartbounty-stats">
																		<i><?php esc_html_e('Sends', 'woo-save-abandoned-carts'); ?></i>
																		<p>0</p>
																	</div>
																	<div class="cartbounty-stats cartbounty-percentage-switcher">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e('Open rate', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_enable_email_stats' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Upgrade to see stats', 'woo-save-abandoned-carts'); ?></a>
																			<i><?php esc_html_e('Opens', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																	<div class="cartbounty-stats cartbounty-percentage-switcher">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e('Click rate', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<i><?php esc_html_e('Clicks', 'woo-save-abandoned-carts'); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																</div>
																<div class="cartbounty-trigger-container cartbounty-col-sm-12 cartbounty-col-lg-4">
																	<div class="cartbounty-automation-status">
																		<span class="status inactive"><?php esc_html_e('Disabled', 'woo-save-abandoned-carts'); ?></span>
																	</div><div class="cartbounty-step-trigger"></div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="cartbounty-wordpress-get-additional-step">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-lg-3">
															<div class="cartbounty-automation-name">
																<h3><?php esc_html_e('Upgrade to enable this email', 'woo-save-abandoned-carts'); ?></h3>
															</div>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-lg-9">
															<div class="cartbounty-stats">
																<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_add_automation_step' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
															</div>
														</div>
													</div>
												</div>
											</div>
										<?php } ?>
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('General email settings', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('"From" name tells your recipients who sent them the message. It is just as important as your subject line and can impact whether your email is opened, or not.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<label for="cartbounty-automation-from-name"><?php esc_html_e('"From" name', 'woo-save-abandoned-carts'); ?></label>
								<input id="cartbounty-automation-from-name" class="cartbounty-text" type="text" name="cartbounty_automation_from_name" value="<?php echo $this->sanitize_field(get_option('cartbounty_automation_from_name')); ?>" placeholder="<?php echo esc_attr( get_option( 'blogname' ) );?>" <?php echo $this->disable_field(); ?> />
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-automation-from-email"><?php esc_html_e('"From" email', 'woo-save-abandoned-carts'); ?></label>
								<input id="cartbounty-automation-from-email" class="cartbounty-text" type="email" name="cartbounty_automation_from_email" value="<?php echo sanitize_email( get_option('cartbounty_automation_from_email') ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) );?>" <?php echo $this->disable_field(); ?> />
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-automation-reply-email"><?php esc_html_e('Reply-to address', 'woo-save-abandoned-carts'); ?></label>
								<input id="cartbounty-automation-reply-email" class="cartbounty-text" type="email" name="cartbounty_automation_reply_email" value="<?php echo sanitize_email( get_option('cartbounty_automation_reply_email') ); ?>" <?php echo $this->disable_field(); ?> />
							</div>
						</div>
					</div>
					<div class='cartbounty-button-row'>
						<?php
						if(current_user_can( 'manage_options' )){
							echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". esc_html__('Save settings', 'woo-save-abandoned-carts') ."</button>";
						}
						if($wordpress->automation_enabled()){
							echo $this->display_force_sync_button( $active_section );
						}
						?>
					</div>
				</form>

				<?php
				break;

			case 'exit_intent':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo sprintf(
						/* translators: %s - Link */
						 esc_html__('With the help of Exit Intent, you can capture even more abandoned carts by displaying a message including an email or phone field that the customer can fill to save his shopping cart. You can even offer to send a discount code. Please note that the Exit Intent will only be showed to unregistered users once every 60 minutes after they have added an item to their cart and try to leave your store. Learn how to %scustomize contents%s of Exit Intent popup.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . '/exit-intent-popup-technology', 'ei_modify_content' ) ) .'" target="_blank" title="'. esc_html__( 'How to customize contents of Exit Intent', 'woo-save-abandoned-carts' ) .'">', '</a>' );
					?>
				</div>
				<form method="post" action="options.php">
					<?php 
						settings_fields( 'cartbounty-settings-exit-intent' );
						do_settings_sections( 'cartbounty-settings-exit-intent' );
						$exit_intent_on = esc_attr( get_option('cartbounty_exit_intent_status'));
						$test_mode_on = esc_attr( get_option('cartbounty_exit_intent_test_mode'));
						$exit_intent_type = esc_attr( get_option('cartbounty_exit_intent_type'));
						$exit_intent_heading = esc_attr( get_option('cartbounty_exit_intent_heading'));
						$exit_intent_content = esc_attr( get_option('cartbounty_exit_intent_content'));
						$main_color = esc_attr( get_option('cartbounty_exit_intent_main_color'));
						$inverse_color = esc_attr( get_option('cartbounty_exit_intent_inverse_color'));
						$main_image = esc_attr( get_option('cartbounty_exit_intent_image'));
					?>

					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('General', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable email or phone request before leaving your store.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9<?php if($exit_intent_on){ echo ' cartbounty-checked-parent'; }?>">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-exit-intent-status" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-exit-intent-status" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_status" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exit_intent_on, false ); ?> autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-status" class="cartbounty-control-visibility"><?php esc_html_e('Enable Exit Intent', 'woo-save-abandoned-carts'); ?></label>
							</div>
							<div class="cartbounty-settings-group cartbounty-toggle cartbounty-hidden">
								<label for="cartbounty-exit-intent-mobile-status" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-exit-intent-mobile-status" class="cartbounty-checkbox" type="checkbox" value="0" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-mobile-status" class="cartbounty-unavailable"><?php esc_html_e('Enable mobile Exit Intent', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'exit_intent_mobile' ); ?></i>
								</p>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Field type', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('Choose which input field should be collected in the popup.', 'woo-save-abandoned-carts'); ?>
								</p>
								<label for="cartbounty-exit-intent-field-type-email" class="cartbounty-radiobutton-label">
									<input id="cartbounty-exit-intent-field-type-email" class="cartbounty-radiobutton" type="radio" checked autocomplete="off" />
										<?php esc_html_e('Email', 'woo-save-abandoned-carts'); ?>
								</label>
								<label for="cartbounty-exit-intent-field-type-phone" class="cartbounty-radiobutton-label cartbounty-unavailable">
									<input id="cartbounty-exit-intent-field-type-phone" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
										<?php esc_html_e('Phone', 'woo-save-abandoned-carts'); ?>
								</label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'exit_intent_phone' ); ?></i>
								</p>
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-exit-intent-heading"><?php esc_html_e('Main title', 'woo-save-abandoned-carts'); ?></label>
								<div class="cartbounty-content-creation cartbounty-flex">
									<input id="cartbounty-exit-intent-heading" class="cartbounty-text" type="text" name="cartbounty_exit_intent_heading" value="<?php echo esc_attr( $this->sanitize_field($exit_intent_heading) ); ?>" placeholder="<?php echo esc_attr( $this->get_tools_defaults('heading', 'exit_intent') ); ?>" /><?php $this->add_emojis(); ?>
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-exit-intent-content"><?php esc_html_e('Content', 'woo-save-abandoned-carts'); ?></label>
								<div class="cartbounty-content-creation cartbounty-flex">
									<textarea id="cartbounty-exit-intent-content" class="cartbounty-text" name="cartbounty_exit_intent_content" placeholder="<?php echo esc_attr( $this->get_tools_defaults('content', 'exit_intent') ); ?>" rows="4"><?php echo $this->sanitize_field($exit_intent_content); ?></textarea><?php $this->add_emojis(); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Appearance', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Adjust the visual appearance of your Exit Intent popup.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Style', 'woo-save-abandoned-carts'); ?></h4>
								<div class="cartbounty-flex-container">
									<div id="cartbounty-exit-intent-center" class="cartbounty-type <?php if($exit_intent_type == 1){ echo "cartbounty-radio-active";} ?>">
										<label class="cartbounty-image" for="cartbounty-radiobutton-center">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Appear in center', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Appear in center', 'woo-save-abandoned-carts'); ?>"/>
												</i>
											</em>
											<input id="cartbounty-radiobutton-center" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_type" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exit_intent_type, false ); ?> autocomplete="off" />
											<?php esc_html_e('Appear in center', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
									<div id="cartbounty-exit-intent-left" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-left">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Slide in from left', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Slide in from left', 'woo-save-abandoned-carts'); ?>"/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ei_style_left' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-left" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Slide in from left', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
									<div id="cartbounty-exit-intent-fullscreen" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-fullscreen">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Fullscreen', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Fullscreen', 'woo-save-abandoned-carts'); ?>"/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ei_style_fullscreen' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-fullscreen" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Fullscreen', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Colors', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('If you leave the Inverse color empty, it will automatically use the inverse color of the main color you have picked. Clear both colors to use the default colors.', 'woo-save-abandoned-carts'); ?>
								</p>
								<div class="cartbounty-colors">
									<label for="cartbounty-exit-intent-main-color"><?php esc_html_e('Main:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-exit-intent-main-color" type="text" name="cartbounty_exit_intent_main_color" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $main_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
								</div>
								<div class="cartbounty-colors">
									<label for="cartbounty-exit-intent-inverse-color"><?php esc_html_e('Inverse:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-exit-intent-inverse-color" type="text" name="cartbounty_exit_intent_inverse_color" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $inverse_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<?php
									if(!did_action('wp_enqueue_media')){
										wp_enqueue_media();
									}
									$image = wp_get_attachment_image_src( $main_image );
								?>
								<h4><?php esc_html_e('Custom image', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('Recommended dimensions:', 'woo-save-abandoned-carts'); ?> 1024 x 600 px.
								</p>
								<div class="cartbounty-action-container">
									<p id="cartbounty-upload-custom-image" class="cartbounty-upload-image">
										<?php if($image):?>
											<img src="<?php echo esc_url( $image[0] ); ?>" />
										<?php else: ?>
											<input type="button" value="<?php esc_attr_e('Add a custom image', 'woo-save-abandoned-carts'); ?>" class="cartbounty-button button-secondary button" <?php echo $this->disable_field(); ?> />
										<?php endif;?>
									</p>
									<a href="#" id="cartbounty-remove-custom-image" class="cartbounty-remove-image" <?php if(!$image){echo 'style="display:none"';}?>></a>
								</div>
								<input id="cartbounty-custom-image" type="hidden" name="cartbounty_exit_intent_image" value="<?php if($main_image){echo esc_attr( $main_image );}?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Miscellaneous', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable test mode to see how the Exit Intent popup looks like.', 'woo-save-abandoned-carts');?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle<?php if($test_mode_on){ echo ' cartbounty-checked'; }?>">
								<label for="cartbounty-exit-intent-test-mode" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-exit-intent-test-mode" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_test_mode" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $test_mode_on, false ); ?> autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-test-mode" class="cartbounty-control-visibility"><?php esc_html_e('Enable test mode', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden'>
										<?php esc_html_e('Now open your store, add a product to the cart and try leaving it. Please note that while this is enabled, only users with Admin rights will be able to see the Exit Intent and appearance limitations have been removed which means that you will see the popup each time you try to leave your store. Don’t forget to disable this after you have done testing.', 'woo-save-abandoned-carts'); ?>
									</i>
								</p>
							</div>
						</div>
					</div>
					<div class='cartbounty-button-row'>
						<?php
						if(current_user_can( 'manage_options' )){
							echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". esc_html__('Save settings', 'woo-save-abandoned-carts') ."</button>";
						}?>
					</div>
				</form>
				<?php
				break;

			case 'early_capture':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo sprintf(
						/* translators: %s - Link */
						 esc_html__('Try saving more recoverable abandoned carts by enabling Early capture to collect customer’s email or phone right after the "Add to cart" button is clicked. You can also enable mandatory input to make sure guest visitors are not able to add anything to their carts until a valid email or phone is provided. Please note that Early capture will only be presented to unregistered visitors once every 60 minutes. Learn how to %scustomize contents%s of Early capture request.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . '/early-capture-add-to-cart-popup', 'ec_modify_content' ) ) .'" target="_blank" title="'. esc_html__( 'How to customize contents of Early capture', 'woo-save-abandoned-carts' ) .'">', '</a>' );
					?>
				</div>
				<form>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('General', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable email or phone request before adding an item to the cart.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-early-capture-status" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-early-capture-status" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-early-capture-status" class="cartbounty-unavailable"><?php esc_html_e('Enable Early capture', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'early_capture_enable' ); ?></i>
								</p>
							</div>
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-early-capture-mandatory" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-early-capture-mandatory" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-early-capture-mandatory" class="cartbounty-control-visibility"><?php esc_html_e('Enable mandatory input', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden'>
										<?php esc_html_e('Your guest visitors will not be able to add anything to their carts until a valid email or phone is provided.', 'woo-save-abandoned-carts'); ?>
									</i>
								</p>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Field type', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('Choose which input field should be collected in the request.', 'woo-save-abandoned-carts'); ?>
								</p>
								<label for="cartbounty-early-capture-field-type-email" class="cartbounty-radiobutton-label">
									<input id="cartbounty-early-capture-field-type-email" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
										<?php esc_html_e('Email', 'woo-save-abandoned-carts'); ?>
								</label>
								<label for="cartbounty-early-capture-field-type-phone" class="cartbounty-radiobutton-label">
									<input id="cartbounty-early-capture-field-type-phone" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
										<?php esc_html_e('Phone', 'woo-save-abandoned-carts'); ?>
								</label>
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-early-capture-heading"><?php esc_html_e('Main title', 'woo-save-abandoned-carts'); ?></label>
								<div class="cartbounty-content-creation cartbounty-flex">
									<input id="cartbounty-early-capture-heading" class="cartbounty-text" type="text" placeholder="<?php echo esc_attr( $this->get_tools_defaults('heading', 'early_capture') ); ?>" disabled /><?php $this->add_emojis(); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Appearance', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Adjust the visual appearance of your Early capture request.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Style', 'woo-save-abandoned-carts'); ?></h4>
								<div class="cartbounty-flex-container">
									<div id="cartbounty-early-capture-near-button" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-center">
											<em>
												<i>
													<svg viewBox="0 0 61 35">
														<g id="cartbounty-early-capture-popup-group">
															<path id="cartbounty-near-button-1" d="M58,35H3a3,3,0,0,1-3-3V20a3,3,0,0,1,3-3H9l3-4,3,4H58a3,3,0,0,1,3,3V32A3,3,0,0,1,58,35Z"/>
															<path id="cartbounty-near-button-2" d="M38.88,27.33H12a1.86,1.86,0,0,1-2-1.67V25.5a1.86,1.86,0,0,1,2-1.67H38.88Z"/>
															<path id="cartbounty-near-button-3" d="M49.38,27.33H38.5v-3.5H49.38a1.86,1.86,0,0,1,2,1.67v.16A1.86,1.86,0,0,1,49.38,27.33Z"/>
														</g>
														<rect id="cartbounty-near-button-4" width="42" height="12" rx="3"/>
													</svg>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ec_style_button' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-center" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Next to button', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
									<div id="cartbounty-early-capture-center" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-left">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/early-capture-form-popup.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Popup overlay', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Popup overlay', 'woo-save-abandoned-carts'); ?>"/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ec_style_popup' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-left" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Popup overlay', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Colors', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('If you leave the Inverse color empty, it will automatically use the inverse color of the main color you have picked. Clear both colors to use the default colors.', 'woo-save-abandoned-carts'); ?>
								</p>
								<div class="cartbounty-colors">
									<label for="cartbounty-early-capture-main-color"><?php esc_html_e('Main:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-early-capture-main-color" type="text" class="cartbounty-color-picker cartbounty-text" disabled autocomplete="off" />
								</div>
								<div class="cartbounty-colors">
									<label for="cartbounty-early-capture-inverse-color"><?php esc_html_e('Inverse:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-early-capture-inverse-color" type="text" class="cartbounty-color-picker cartbounty-text" disabled autocomplete="off" />
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Miscellaneous', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable test mode to see how the Early capture looks like.', 'woo-save-abandoned-carts');?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-early-capture-test-mode" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-early-capture-test-mode" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-early-capture-test-mode" class="cartbounty-control-visibility"><?php esc_html_e('Enable test mode', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden'>
										<?php esc_html_e('Now open your store and try adding a product to your cart. Please note that while this is enabled, only users with Admin rights will be able to see the Early capture request and appearance limitations have been removed which means that you will see the request each time you try to add an item to your cart. Don’t forget to disable this after you have done testing.', 'woo-save-abandoned-carts'); ?>
									</i>
								</p>
							</div>
						</div>
					</div>
					<div class='cartbounty-button-row'>
						<a class="button cartbounty-button button-primary" href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'early_capture_enable_button' ) ); ?>" target="_blank"> <?php esc_html_e('Get Pro to enable', 'woo-save-abandoned-carts'); ?></a>
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
		foreach ($sections as $key => $section) { //Getting integration name from array
			if($key == $current_section){
				$name = $section['name'];
			}
		}
		echo '<p class="cartbounty-description">' . sprintf(
			/* translators: %s - Gets replaced by an integration name, e.g. MailChimp */
			esc_html__('Not so fast, sailor! You must enable WooCommerce before we can set sail or this %s boat gets us nowhere.', 'woo-save-abandoned-carts'), esc_html( $name ) ) . '</p>';
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
				$text = esc_html__('Enabled', 'woo-save-abandoned-carts');

				if($tab == 'recovery'){
					$text = esc_html__('Connected', 'woo-save-abandoned-carts');
				}
				
			}
			$status = '<em class="cartbounty-connection">'. esc_html( $text ) .'</em>';
		}
		return $status;
	}

	/**
	 * Schedules Wordpress events
	 * Moved outside of Plugin activation class in v.9.4 since there were many ocurances when events were not scheduled after plugin activation
	 *
	 * @since    4.3
	 */
	function schedule_events(){
		$user_settings_notification_frequency = get_option('cartbounty_notification_frequency');

		if(intval($user_settings_notification_frequency['hours']) == 0){ //If Email notifications have been disabled, we disable cron job
			wp_clear_scheduled_hook( 'cartbounty_notification_sendout_hook' );
		}else{
			if (! wp_next_scheduled ( 'cartbounty_notification_sendout_hook' )) {
				wp_schedule_event(time(), 'cartbounty_notification_sendout_interval', 'cartbounty_notification_sendout_hook');
			}
		}
		if (! wp_next_scheduled ( 'cartbounty_sync_hook' )) {
			wp_schedule_event(time(), 'cartbounty_sync_interval', 'cartbounty_sync_hook'); //Schedules a hook which will be executed by the WordPress actions core on a specific interval
		}
		if (! wp_next_scheduled ( 'cartbounty_remove_empty_carts_hook' )) {
			wp_schedule_event(time(), 'cartbounty_remove_empty_carts_interval', 'cartbounty_remove_empty_carts_hook');
		}
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
		$intervals['cartbounty_sync_interval'] = array( //Defining cron Interval for sending out abandoned carts
			'interval' => 5 * 60,
			'display' => 'Every 5 minutes'
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
	 * Output admin notices
	 *
	 * @since    6.1.2
	 */
	function display_notices(){
		$this->display_wp_cron_warnings();
		$this->display_unsupported_plugin_notice();
	}

	/**
	 * Method shows warnings if any of the WP Cron events not scheduled or if the WP Cron has been disabled
	 *
	 * @since    4.3
	 */
	function display_wp_cron_warnings(){
		$wordpress = new CartBounty_WordPress();

		if( $wordpress->automation_enabled() ){ //Check if we have connected to WordPress automation
			$missing_hooks = array();
			$user_settings_notification_frequency = get_option( 'cartbounty_notification_frequency' );

			if( wp_next_scheduled( 'cartbounty_notification_sendout_hook' ) === false && intval( $user_settings_notification_frequency['hours'] ) != 0 ){ //If we havent scheduled email notifications and notifications have not been disabled
				$missing_hooks[] = 'cartbounty_notification_sendout_hook';
			}

			if( wp_next_scheduled( 'cartbounty_sync_hook' ) === false ){
				$missing_hooks[] = 'cartbounty_sync_hook';
			}

			if ( !empty($missing_hooks ) ) { //If we have hooks that are not scheduled
				$hooks = '';
				$current = 1;
				$total = count( $missing_hooks );
				
				foreach( $missing_hooks as $missing_hook ){
					$hooks .= $missing_hook;
					
					if ( $current != $total ){
						$hooks .= ', ';
					}
					$current++;
				}
				$message = sprintf(
					/* translators: %s - Cron event name */
					wp_kses( _n( 'It seems that WP Cron event <strong>%s</strong> required for automation is not scheduled.', 'It seems that WP Cron events <strong>%s</strong> required for automation are not scheduled.', esc_html( $total ), 'woo-save-abandoned-carts' ), 'data' ), esc_html( $hooks ) ) . ' ' .
					sprintf(
					/* translators: %1$s - Plugin name, %2$s - Link start, %3$s - Link end */
					esc_html__( 'Please try disabling and enabling %1$s plugin. If this notice does not go away after that, please %2$sget in touch with us%3$s.', 'woo-save-abandoned-carts' ), esc_html( CARTBOUNTY_ABREVIATION ), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_SUPPORT_LINK, 'wp_cron_disabled' ) ) .'" target="_blank">', '</a>' );
				echo $this->get_notice_output( $message, $handle = '', 'warning' );
			}
		}

		//Checking if WP Cron is enabled
		if( defined( 'DISABLE_WP_CRON' ) ){
			
			if( DISABLE_WP_CRON == true ){
				$handle = 'cartbounty_cron_warning';
				$message = esc_html__( "WP Cron has been disabled. Several WordPress core features, such as checking for updates or sending notifications utilize this function. Please enable it or contact your system administrator to help you with this.", 'woo-save-abandoned-carts' );
				echo $this->get_notice_output( $message, $handle, $class = 'warning', true, 'close' );
			}
		}
	}

	/**
	 * Method shows notice in case there are active plugins which the free CartBounty version does not support
	 *
	 * @since    7.1.2.5
	 */
	function display_unsupported_plugin_notice(){
		$active_unsupported_plugins = array();
		$unsupported_plugins = array(
			array( //Extra Product Options for WooCommerce (Free) by ThemeHigh
				'path' => 'woo-extra-product-options/woo-extra-product-options.php',
				'name' => 'Extra Product Options for WooCommerce'
			),
			array( //Extra Product Options for WooCommerce by ThemeHigh
				'path' => 'woocommerce-extra-product-options-pro/woocommerce-extra-product-options-pro.php',
				'name' => 'Extra Product Options for WooCommerce'
			),
			array( //WooCommerce Custom Product Addons (Free) by Acowebs
				'path' => 'woo-custom-product-addons/start.php',
				'name' => 'WooCommerce Custom Product Addons'
			),
			array( //WooCommerce Custom Product Addons by Acowebs
				'path' => 'woo-custom-product-addons-pro/start.php',
				'name' => 'WooCommerce Custom Product Addons'
			),
			array( //Advanced Product Fields for WooCommerce (Free) by StudioWombat
				'path' => 'advanced-product-fields-for-woocommerce/advanced-product-fields-for-woocommerce.php',
				'name' => 'Advanced Product Fields for WooCommerce'
			),
			array( //Advanced Product Fields for WooCommerce by StudioWombat
				'path' => 'advanced-product-fields-for-woocommerce-pro\advanced-product-fields-for-woocommerce-pro.php',
				'name' => 'Advanced Product Fields for WooCommerce'
			),
			array( //WooCommerce Product Bundles by SomewhereWarm
				'path' => 'woocommerce-product-bundles/woocommerce-product-bundles.php',
				'name' => 'WooCommerce Product Bundles'
			)
		);

		foreach ( $unsupported_plugins as $key => $plugins ) {
			
			if( is_plugin_active( $plugins['path'] ) ){
				$active_unsupported_plugins[] = '<strong>' . $plugins['name'] . '</strong>';
			}
		}

		$active_unsupported_plugins = array_unique( $active_unsupported_plugins ); //Removing duplicate items from array

		if( !empty( $active_unsupported_plugins ) ){
			$handle = 'cartbounty_unsupported_plugin_notice';
			$total = count( $active_unsupported_plugins );
			$unsupported_plugins_list = implode( ' ' . __('and') . ' ', array_filter( array_merge( array( implode( ', ', array_slice( $active_unsupported_plugins, 0, -1 ) ) ), array_slice( $active_unsupported_plugins, -1 ) ), 'strlen')); //Join plugins with commas and the last two elements with "and"
			$message = sprintf(
				/* translators: %s - Cron event name */
				wp_kses( _n( 'You are using %s. The Free version of %s does not support saving all options this plugin adds.', 'You are using %s. The Free version of %s does not support saving all options these plugins add.', esc_html( $total ), 'woo-save-abandoned-carts' ), 'data' ), $unsupported_plugins_list, esc_html( CARTBOUNTY_ABREVIATION ) ) . '<br/>' . $this->display_unavailable_notice( 'unsupported_plugins' );
			echo $this->get_notice_output( $message, $handle, 'notice', true, 'close', $user_specific = true );
		}
	}

	/**
	 * Handling notice output
	 *
	 * @since    6.1.2
	 * @return   string
	 * @param    string   	$message   		Content of the message
	 * @param    string   	$handle   		Unique ID for the notice. Default empty
	 * @param    string   	$class   		Additional classes required for the notice. Default empty
	 * @param    boolean   	$submit   		Should a submit button be added or not. Default false
	 * @param    string   	$button_type   Type of the button (done or close). Default done
	 * @param    boolean   	$user_specific  Weather notice is saved in the user or global site level
	 */
	function get_notice_output( $message, $handle = '', $class = '', $submit = false, $button_type = 'done', $user_specific = false ){
		$closed = false;

		if( $user_specific ){

			if( isset( $_GET[$handle] ) ){ //Check if we should update the option and close the notice
				check_admin_referer( 'cartbounty-notice-nonce', $handle ); //Exit in case security check is not passed
				update_user_meta( get_current_user_id(), $handle, 1 );
			}
			$closed = get_user_meta( get_current_user_id(), $handle, false );

		}else{

			if( isset( $_GET[$handle] ) ){ //Check if we should update the option and close the notice
				check_admin_referer( 'cartbounty-notice-nonce', $handle ); //Exit in case security check is not passed
				update_option( $handle, 1 );
			}
			$closed = get_option( $handle );
		}

		if( $closed ){ //Exit if notice previously has been already closed
			return;
		}

		$button = false;
		$button_text = esc_html__( 'Done', 'woo-save-abandoned-carts' );
		
		if( $button_type == 'close' ){
			$button_text = esc_html__( 'Close', 'woo-save-abandoned-carts' );
		}

		if( $submit ){
			$button = '<span class="cartbounty-button-container"><a class="cartbounty-close-notice cartbounty-button button button-secondary cartbounty-progress" href="'. esc_url( wp_nonce_url( add_query_arg( 'handle', $handle ), 'cartbounty-notice-nonce', $handle ) ) .'">'. esc_html( $button_text ) .'</a></span>';
		}

		$output = '<div class="cartbounty-notification notice updated '. esc_attr( $class ) .'">
			<p>'. $message .'</p>'. $button .'
		</div>';

		return $output;
	}

	/**
	 * Method for sending out email notification in order to notify about new abandoned carts and recovered
	 *
	 * @since    7.0
	 */
	function send_email(){
		$exclude_recovered_carts = get_option('cartbounty_exclude_recovered');
		$this->prepare_email( 'recoverable' );
		if(!$exclude_recovered_carts){ //If we do not exclude recovered carts from emails
			$this->prepare_email( 'recovered' );
		}
	}

	/**
	 * Method prepares and sends the email
	 *
	 * @since    7.0
	 * @param    string   	$type   	The type of notification email
	 */
	private function prepare_email( $type ){
		global $wpdb;
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time_intervals = $this->get_time_intervals();
		$time = $time_intervals['cart_abandoned'];
		$where_sentence = $this->get_where_sentence( $type );
		$cart_count = false;

		if($type == 'recovered'){
			$time = $time_intervals['cart_recovered'];
		}

		//Retrieve from database rows that have not been emailed and are older than 60 minutes
		$cart_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id)
				FROM $cart_table
				WHERE mail_sent = %d
				$where_sentence AND
				cart_contents != '' AND
				time < %s",
				0,
				$time
			)
		);
		
		if(!$cart_count){
			return;
		}

		$to = esc_html(get_option( 'admin_email' ));
		$user_settings_email = get_option('cartbounty_notification_email'); //Retrieving email address if the user has entered one
		if(!empty($user_settings_email)){
			$to = esc_html($user_settings_email);
			$to_without_spaces = str_replace(' ', '', $to);
			$to = explode(',', $to_without_spaces);
		}
		
		$sender = 'WordPress@' . preg_replace('#^www\.#', '', strtolower( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'Unknown' ) );
		$from = "From: ". esc_html( CARTBOUNTY_ABREVIATION ) ." <" . apply_filters( 'cartbounty_from_email', esc_html( $sender ) ) . ">";
		$blog_name = get_option( 'blogname' );
		$admin_link = get_admin_url() .'admin.php?page='. CARTBOUNTY;

		if($type == 'recovered'){ //In case if we are sending notification email about newly recovered carts
			$subject = '['.$blog_name.'] '. esc_html( _n('Bounty! Cart recovered! 🤟', 'Bounty! Carts recovered! 🤑', $cart_count, 'woo-save-abandoned-carts') );
			$heading = esc_html( _n('Cart recovered! 🤟', 'Carts recovered! 🤑', $cart_count, 'woo-save-abandoned-carts') );
			$content = sprintf(
			/* translators: %1$d - Abandoned cart count, %2$s - Plugin name */
			esc_html( _n('Excellent, you have recovered an abandoned cart using %2$s.', 'Amazing, you have recovered %1$d abandoned carts using %2$s.', $cart_count, 'woo-save-abandoned-carts') ), esc_html( $cart_count ), esc_html( CARTBOUNTY_ABREVIATION ) );
			$content .= ' ' . sprintf(
			/* translators: %s - Link tags */
			esc_html__('Please use %sthis link%s to see full information about your carts.', 'woo-save-abandoned-carts'), '<a href="' . esc_url( $admin_link ) . '">', '</a>');
			$button_color = '#20bca0';

		}else{
			$subject = '['.$blog_name.'] '. esc_html( _n('New abandoned cart saved! 🛒', 'New abandoned carts saved! 🛒', $cart_count, 'woo-save-abandoned-carts') );
			$heading = esc_html( _n('New abandoned cart!', 'New abandoned carts! ', $cart_count, 'woo-save-abandoned-carts') );
			$content = sprintf(
			/* translators: %1$d - Abandoned cart count, %2$s - Plugin name */
			esc_html( _n('Great, you have saved a new recoverable abandoned cart using %2$s.', 'Congratulations, you have saved %1$d new recoverable abandoned carts using %2$s.', $cart_count, 'woo-save-abandoned-carts') ), esc_html( $cart_count ), esc_html( CARTBOUNTY_ABREVIATION ) );
			$content .= ' ' . sprintf(
			/* translators: %s - Link tags */
			esc_html__('Please use %sthis link%s to see full information about your carts.', 'woo-save-abandoned-carts'), '<a href="' . esc_url( $admin_link ) . '">', '</a>');
			$button_color = '#aa88fc';
		}

		$main_color = '#ffffff';
		$text_color = '#000000';
		$background_color = '#f2f2f2';
		$footer_color = '#353535';
		$border_color = '#e9e8e8';
		$get_pro_text = sprintf(
		/* translators: %s - Link tags */
		esc_html__('Get %sCartBounty Pro%s to enable cart data preview above.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'enable_admin_email_contents' ) ) .'">', '</a>');

		$args = array(
			'main_color'			=> $main_color,
			'button_color'			=> $button_color,
			'text_color'			=> $text_color,
			'background_color'		=> $background_color,
			'footer_color'			=> $footer_color,
			'border_color'			=> $border_color,
			'heading'				=> $heading,
			'content'				=> $content,
			'total_1'				=> $this->format_price('320'),
			'total_2'				=> $this->format_price('4820'),
			'total_3'				=> $this->format_price('51'),
			'carts_link'			=> $admin_link,
			'get_pro_text'			=> $get_pro_text
		);

		ob_start();
		echo $public->get_template( 'cartbounty-admin-email-notification.php', $args, false, plugin_dir_path( __FILE__ ) . '../templates/emails/');
		$message = ob_get_contents();
		ob_end_clean();

		$headers = "$from\n" . "Content-Type: text/html; charset=\"" . esc_attr( get_option('blog_charset') ) . "\"\n";
		//Sending out email
		wp_mail( $to, esc_html($subject), $message, $headers );

		//Update mail_sent status to true with mail_status = 0 and are older than 60 minutes
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET mail_sent = %d 
				WHERE mail_sent = %d
				$where_sentence AND
				cart_contents != '' AND
				type != %d AND
				time < %s",
				1,
				0,
				$this->get_cart_type('ordered'),
				$time
			)
		);
	}

	/**
	 * Method retrieves Settings tab url
	 *
	 * @since    1.1
	 */
	public static function get_settings_tab_url(){
		$url = menu_page_url(CARTBOUNTY, false);
		$url = $url.'&tab=settings'; //Adding settings tab manually
		return $url;
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
		$settings_tab = $this->get_settings_tab_url();
		
		$action_links = array();
		$action_links['cartbounty_settings'] = array(
			'label' => esc_html__('Settings', 'woo-save-abandoned-carts'),
			'url'   => $settings_tab
		);
		$action_links['cartbounty_carts'] = array(
			'label' => esc_html__('Carts', 'woo-save-abandoned-carts'),
			'url'   => menu_page_url(CARTBOUNTY, false)
		);
		$action_links['cartbounty_get_pro'] = array(
			'label' => esc_html__('Get Pro', 'woo-save-abandoned-carts'),
			'url'   => $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'plugin_link' )
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
				$link = array( $key => '<a href="' . esc_url( $value['url'] ) . '">' . esc_html( $value['label'] ) . '</a>' );
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
		$status = '';
		if($options){
			if($options['forced'] == true){
				$status = 'disabled=""';
			}
		}
		elseif(!current_user_can( 'manage_options' )){
			$status = 'disabled=""';
		}
		return $status;
	}

	/**
	 * Method outputs bubble content
	 *
	 * @since    1.4.2
	 */
	function output_bubble_content(){ ?>
		<?php $bubble_nonce = wp_create_nonce( 'bubble_security' );

		$tab = $this->get_open_tab();
		$current_section = $this->get_open_section();

		if($tab == 'carts'): //In case we are on Abandoned cart page ?>
			<div id="cartbounty-bubbles">
				<?php if(!get_option('cartbounty_review_submitted')): //Don't output Review bubble if review has been left ?>
					<div id="cartbounty-review" class="cartbounty-bubble">
						<div class="cartbounty-header-image">
							<a href="<?php echo esc_url( CARTBOUNTY_REVIEW_LINK ); ?>" title="<?php esc_attr_e('If you like our plugin, please leave us a 5-star rating. It is the easiest way to help us grow and keep evolving further.', 'woo-save-abandoned-carts' ); ?>" target="_blank">
								<img src="<?php echo esc_url( plugins_url( 'assets/review-notification.gif', __FILE__ ) ); ?>"/>
							</a>
						</div>
						<div id="cartbounty-review-content">
							<?php
								$expression = $this->get_expressions();
								$saved_cart_count = $this->total_cartbounty_recoverable_cart_count();
							?>
							<h2><?php echo sprintf(
								/* translators: %s - Gets replaced by an excitement word e.g. Awesome!, %d - Abandoned cart count */
								esc_html( _n('%s You have already captured %d abandoned cart!', '%s You have already captured %d abandoned carts!', $saved_cart_count , 'woo-save-abandoned-carts' ) ), esc_html( $expression['exclamation'] ), esc_html( $saved_cart_count ) ); ?></h2>
							<p><?php esc_html_e('If you like our plugin, please leave us a 5-star rating. It is the easiest way to help us grow and keep evolving further.', 'woo-save-abandoned-carts' ); ?></p>
							<div class="cartbounty-button-row">
								<a href="<?php echo esc_url( CARTBOUNTY_REVIEW_LINK ); ?>" class="button" target="_blank"><?php esc_html_e("Leave a 5-star rating", 'woo-save-abandoned-carts'); ?></a>
								<button type="button" class='button cartbounty-review-submitted cartbounty-bubble-close' data-operation='submitted' data-type='review' data-nonce='<?php echo esc_attr( $bubble_nonce ); ?>'><?php esc_html_e('Done that', 'woo-save-abandoned-carts'); ?></button>
								<button type="button" class='button cartbounty-close cartbounty-bubble-close' data-operation='declined' data-type='review' data-nonce='<?php echo esc_attr( $bubble_nonce ); ?>'><?php esc_html_e('Close', 'woo-save-abandoned-carts'); ?></button>
							</div>
						</div>
					</div>
				<?php endif; ?>
				<div id="cartbounty-go-pro" class="cartbounty-bubble">
					<div class="cartbounty-header-image">
						<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'bubble' ) ); ?>" title="<?php esc_attr_e('Fully automate your abandoned cart recovery workflow and get back to those lovely cat videos (:', 'woo-save-abandoned-carts'); ?>" target="_blank">
							<img src="<?php echo esc_url( plugins_url( 'assets/notification-email.gif', __FILE__ ) ); ?>"/>
						</a>
					</div>
					<div id="cartbounty-go-pro-content">
						<h2><?php esc_html_e('Fully automate your abandoned cart recovery workflow and get back to those lovely cat videos (:', 'woo-save-abandoned-carts' ); ?></h2>
						<p><?php esc_html_e('Use your time wisely by enabling Pro features and increase your sales.', 'woo-save-abandoned-carts' ); ?></p>
						<div class="cartbounty-button-row">
							<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'bubble' ) ); ?>" class="button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
							<button type="button" class='button cartbounty-close cartbounty-bubble-close' data-operation='declined' data-type='upgrade' data-nonce='<?php echo esc_attr( $bubble_nonce ); ?>'><?php esc_html_e('Not now', 'woo-save-abandoned-carts'); ?></button>
						</div>
					</div>
				</div>
				<?php echo $this->draw_bubble(); ?>
			</div>
		<?php elseif($current_section == 'wordpress'): //In case we are on WordPress Recovery cart page ?>
			<div id="cartbounty-bubbles">
				<div id="cartbounty-steps" class="cartbounty-bubble">
					<div class="cartbounty-header-image">
						<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'bubble_steps' ) ); ?>" title="<?php esc_attr_e('Make the most of your automation with a 3-step email series', 'woo-save-abandoned-carts'); ?>" target="_blank">
							<img src="<?php echo esc_url( plugins_url( 'assets/3-step-email-series.gif', __FILE__ ) ); ?>"/>
						</a>
					</div>
					<div id="cartbounty-go-pro-content">
						<h2><?php esc_html_e('Make the most of your automation with a 3-step email series', 'woo-save-abandoned-carts' ); ?></h2>
						<p><?php esc_html_e('A single recovery email can raise your sales but sending 2 or 3 follow-up emails is proved to get the most juice out of your recovery campaigns.', 'woo-save-abandoned-carts' ); ?></p>
						<div class="cartbounty-button-row">
							<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'bubble_steps' ) ); ?>" class="button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
							<button type="button" class='button cartbounty-close cartbounty-bubble-close' data-operation='declined' data-type='upgrade_steps' data-nonce='<?php echo esc_attr( $bubble_nonce ); ?>'><?php esc_html_e('Not now', 'woo-save-abandoned-carts'); ?></button>
						</div>
					</div>
				</div>
				<?php echo $this->draw_bubble(); ?>
			</div> 
		<?php endif;
	}

	/**
	 * Show bubble slide-out window
	 *
	 * @since 	1.3
	 */
	function draw_bubble(){
		$display_bubble = false;
		$bubble_type = false;
		$position  = is_rtl() ? 'left' : 'right';
		$wordpress = new CartBounty_WordPress();


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

		}elseif($this->total_cartbounty_recoverable_cart_count() > 5 && $this->days_have_passed('cartbounty_last_time_bubble_displayed', 18 )){ //If we have more than 5 abandoned carts or the last time bubble was displayed was 18 days ago, display the bubble info about Pro version
			$bubble_type = '#cartbounty-go-pro';
			$display_bubble = true; //Show the bubble

		}elseif($wordpress->get_stats() > 9 && $this->days_have_passed('cartbounty_last_time_bubble_steps_displayed', 18 )){ //If we have already sent out more than 10 reminder emails using WordPress or the last time bubble was displayed was 18 days ago, display the bubble info about Pro version
			$bubble_type = '#cartbounty-steps';
			$display_bubble = true; //Show the bubble
		}
		
		if($display_bubble){ //Check ff we should display the bubble ?>
			<script>
				jQuery(document).ready(function($) {
					var bubble = $(<?php echo "'". $bubble_type ."'"; ?>);
					var close = $('.cartbounty-close, .cartbounty-review-submitted');
					
					//Function loads the bubble after a given time period in seconds	
					setTimeout(function() {
						bubble.css({top:"60px", <?php echo $position; ?>: "50px"});
					}, 2500);
				});
			</script>
			<?php
		}else{
			//Do nothing
			return;
		}
	}

	/**
	 * Handles bubble button actions
	 *
	 * @since    7.0
	 */
	function handle_bubble(){
		if ( check_ajax_referer( 'bubble_security', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing function
			wp_send_json_error(esc_html__( 'Looks like you are not allowed to do this.', 'woo-save-abandoned-carts' ));
		}
		if(isset($_POST['type'])){
			if($_POST['type'] == 'review'){ //If we are handling review bubble
				//Check what button has been pressed and what should we do next
				if(isset($_POST['operation'])){
					if($_POST['operation'] == 'submitted'){
						update_option( 'cartbounty_review_submitted', 1 ); //Update option that the review has been added
						wp_send_json_success();
					}elseif($_POST['operation'] == 'declined'){
						update_option( 'cartbounty_times_review_declined', get_option('cartbounty_times_review_declined') + 1 ); //Update declined count by one
						wp_send_json_success();
					}
				}
			}
			if($_POST['type'] == 'upgrade'){ //If we are handling upgrade bubble
				if($_POST['operation'] == 'declined'){
					update_option( 'cartbounty_last_time_bubble_displayed', current_time('mysql') ); //Update option that the upgrade bubble has been declined
					wp_send_json_success();
				}
			}
			if($_POST['type'] == 'upgrade_steps'){ //If we are handling upgrade bubble
				if($_POST['operation'] == 'declined'){
					update_option( 'cartbounty_last_time_bubble_steps_displayed', current_time('mysql') ); //Update option that the upgrade bubble has been declined
					wp_send_json_success();
				}
			}
		}
		wp_send_json_error(esc_html__( 'Something is wrong.', 'woo-save-abandoned-carts' ));
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

		if(empty($captured_abandoned_cart_count)){ //If we do not have any carts
			$captured_abandoned_cart_count = 0;
		}

		return $captured_abandoned_cart_count;
	}

	/**
	 * Sets the path to language folder for internationalization
	 *
	 * @since    2.1
	 */
	function cartbounty_text_domain(){
		return load_plugin_textdomain( 'woo-save-abandoned-carts', false, basename( plugin_dir_path( __DIR__ ) ) . '/languages' );
	}

	/**
	 * Method removes empty abandoned carts that do not have any products and are older than the set cart abandonment time (Default 1 hour)
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
				$time['cart_abandoned']
			)
		);
		$public->decrease_ghost_cart_count( $ghost_row_count );

		//Deleting rest of the abandoned carts without products or ones that have been turned into orders
		$rest_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE (cart_contents = '' OR type = %d) AND
				time < %s",
				$this->get_cart_type('ordered'),
				$time['cart_abandoned']
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
		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if( !isset( WC()->session ) ){
			return;
		}

		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart = $public->read_cart();

		if( !isset( $cart['session_id'] ) ){
			return;
		}

		//Cleaning Cart data
		$update_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET cart_contents = '',
				cart_total = %d,
				currency = %s,
				time = %s
				WHERE session_id = %s AND
				type = %d",
				0,
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$cart['session_id'],
				$this->get_cart_type('abandoned')
			)
		);
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
		
		if($public->cart_saved($user_id)){ //If we have saved an abandoned cart for the user - go ahead and reset in case it has been abandoned or payment is still pending
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
			$updated_rows = $wpdb->query(
				$wpdb->prepare(
					"UPDATE $cart_table
					SET name = '',
					surname = '',
					email = '',
					phone = '',
					location = '',
					cart_contents = '',
					cart_total = '',
					currency = '',
					time = '',
					other_fields = ''
					WHERE session_id = %s AND
					type != %d",
					$user_id,
					$this->get_cart_type('recovered')
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
				'exclamation' => esc_html__('Congrats!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 30){
			$expressions = array(
				'exclamation' => esc_html__('Awesome!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 100){
			$expressions = array(
				'exclamation' => esc_html__('Amazing!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 300){
			$expressions = array(
				'exclamation' => esc_html__('Incredible!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 500){
			$expressions = array(
				'exclamation' => esc_html__('Crazy!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 1000){
			$expressions = array(
				'exclamation' => esc_html__('Fantastic!', 'woo-save-abandoned-carts')
			);
		}else{
			$expressions = array(
				'exclamation' => esc_html__('Insane!', 'woo-save-abandoned-carts')
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
	 * @param    $grid 		Wheather the icon is located section items grid - boolean
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
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26.34 29.48"><path d="M7.65,24c-2.43,0-3.54-1.51-3.54-2.91V3.44C3.77,3.34,3,3.15,2.48,3L.9,2.59A1.28,1.28,0,0,1,0,1.15,1.32,1.32,0,0,1,1.34,0a1.52,1.52,0,0,1,.42.06l.68.2c1.38.41,2.89.85,3.25,1A1.72,1.72,0,0,1,6.79,2.8V5.16L24.67,7.53a1.75,1.75,0,0,1,1.67,2v6.1a3.45,3.45,0,0,1-3.59,3.62h-16v1.68c0,.14,0,.47,1.07.47H21.13a1.32,1.32,0,0,1,1.29,1.38,1.35,1.35,0,0,1-.25.79,1.18,1.18,0,0,1-1,.5Zm-.86-7.5,15.76,0c.41,0,1.11,0,1.11-1.45V10c-3-.41-13.49-1.69-16.87-2.11Z"/><path d="M21.78,29.48a4,4,0,1,1,4-4A4,4,0,0,1,21.78,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.35,1.35,0,0,0,21.78,24.11ZM10.14,29.48a4,4,0,1,1,4-4A4,4,0,0,1,10.14,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.34,1.34,0,0,0,10.14,24.11Z"/><path d="M18.61,18.91a1.34,1.34,0,0,1-1.34-1.34v-9a1.34,1.34,0,1,1,2.67,0v9A1.34,1.34,0,0,1,18.61,18.91Z"/><path d="M12.05,18.87a1.32,1.32,0,0,1-1.34-1.29v-10a1.34,1.34,0,0,1,2.68,0v10A1.32,1.32,0,0,1,12.05,18.87Z"/></svg>';
		}

		elseif( $icon == 'recovery' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 66.07 49.48"><path d="M28.91,26.67A7.62,7.62,0,0,0,33,28a7,7,0,0,0,4.16-1.37c.77-.46,23.19-17.66,26.05-20s1.06-4.9,1.06-4.9S63.51,0,60.64,0H6.08c-3.83,0-4.5,2-4.5,2S0,4.49,3.28,7Z"/><path d="M40.84,32.14A13.26,13.26,0,0,1,33,34.9a13,13,0,0,1-7.77-2.76C24.33,31.55,1.11,14.49,0,13.25V43a6.52,6.52,0,0,0,6.5,6.5H59.57a6.51,6.51,0,0,0,6.5-6.5V13.25C65,14.46,41.74,31.55,40.84,32.14Z"/></svg>';
		}

		elseif( $icon == 'settings' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 70"><path d="M58.9,23.28a23.37,23.37,0,0,1,1.41,3.1c.12.49.49.68,1.33.88s6.43,1.55,6.72,1.64c1,.32,1.64.85,1.62,2.42,0,2.65,0,5,0,7.63a2.08,2.08,0,0,1-1.67,2.16c-.7.2-5.77,1.44-7.61,1.87-.27.06-.36.25-.45.49A25.48,25.48,0,0,1,58.78,47a.65.65,0,0,0,0,.78c.24.33,2.33,3.88,3.19,5.29a2.89,2.89,0,0,1-.63,3.58c-1,1-3.55,3.59-4.61,4.61a3,3,0,0,1-3.53.64C51.8,61,49,59.48,48.37,59.12s-.9-.62-1.44-.3-2.2,1-3.49,1.45a.6.6,0,0,0-.47.45c-.42,1.84-1.66,6.91-1.86,7.6A2.08,2.08,0,0,1,39,70c-2.62,0-5.24,0-7.86,0a2.1,2.1,0,0,1-2.21-1.7c-.08-.28-1.5-6-1.86-7.58-.07-.31-.31-.38-.56-.47-.84-.32-2.79-1.11-3.34-1.4a.86.86,0,0,0-1.08,0l-5.16,3a2.2,2.2,0,0,1-3.19-.4c-.63-.61-4.52-4.5-5.21-5.2a2.17,2.17,0,0,1-.4-3.08c.88-1.5,2.54-4.35,2.87-4.94a1.36,1.36,0,0,0,0-1.79,19.48,19.48,0,0,1-1.2-2.75c-.24-.78-.46-.68-1.47-.9S2.1,41.24,1.81,41.15A2.15,2.15,0,0,1,0,38.85Q0,35,0,31.14a2.11,2.11,0,0,1,1.8-2.28c.75-.2,5.68-1.42,7.39-1.82a.74.74,0,0,0,.56-.56,23.39,23.39,0,0,1,1.29-3.09,1.12,1.12,0,0,0,0-1.36s-3.35-5-3.82-5.84a2,2,0,0,1,.4-2.54l5.51-5.51a2.24,2.24,0,0,1,2.55-.43c.71.41,5.42,2.92,6.05,3.28s.9.44,1.47.15a21.56,21.56,0,0,1,3.25-1.38c.28-.1.49-.21.56-.55.4-1.72,1.62-6.7,1.84-7.48A2.08,2.08,0,0,1,31.08,0h7.81a2.1,2.1,0,0,1,2.24,1.76c.09.3,1.49,5.93,1.83,7.43a.68.68,0,0,0,.49.53c.83.29,3.12,1.27,3.46,1.44s.52.31.85.08c.54-.38,4.18-2.53,5.43-3.27,1.5-.88,2.18-.79,3.44.45.64.64,4.5,4.49,5.15,5.16a2.13,2.13,0,0,1,.37,2.95L59,21.9A1,1,0,0,0,58.9,23.28ZM35,20.68A14.32,14.32,0,1,0,49.32,35,14.32,14.32,0,0,0,35,20.68Z"/></svg>';
		}

		elseif( $icon == 'exit_intent' || $icon == 'tools' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.5 70"><path d="M29,7.23A7.23,7.23,0,1,1,21.75,0,7.23,7.23,0,0,1,29,7.23Z"/><path d="M17.32,70a5.73,5.73,0,0,1-4.78-2.6,4.85,4.85,0,0,1-.18-4.84q1-2.12,2-4.25c1.33-2.8,2.71-5.68,4.14-8.5,1.33-2.6,5-5.49,11.29-8.81-2.17-4.18-4.25-8-6.35-11.61a21.16,21.16,0,0,1-5.12.66C11.6,30.05,5.59,26.63,1,20.18a4.58,4.58,0,0,1-.48-4.86,5.76,5.76,0,0,1,5.06-3,5.28,5.28,0,0,1,4.39,2.29c2.32,3.26,5.1,4.92,8.26,4.92A13.46,13.46,0,0,0,25,17.43c.18-.12.63-.36,1.12-.64l.31-.17,1.36-.78a23.44,23.44,0,0,1,12-3.55c6.76,0,12.77,3.42,17.39,9.89A4.56,4.56,0,0,1,57.58,27,5.76,5.76,0,0,1,52.52,30a5.26,5.26,0,0,1-4.38-2.28c-2.33-3.26-5.11-4.91-8.27-4.91a10.63,10.63,0,0,0-1.66.14c2.44,4.4,6.53,12.22,7.08,13.58,2.23,4.07,4.78,7.82,8.25,7.82A7,7,0,0,0,57,43.23a5.68,5.68,0,0,1,2.85-.81,5.85,5.85,0,0,1,5.41,4.43A5.27,5.27,0,0,1,62.74,53a18,18,0,0,1-9.08,2.68c-5,0-9.91-2.61-14.08-7.55-2.93,1.44-8.65,4.38-11.3,6.65-.53.87-4.4,8.16-6.4,12.29A5,5,0,0,1,17.32,70Z"/></svg>';
		}

		elseif( $icon == 'early_capture' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38.87 70"><path d="M38.53,32.71,23,67.71A3.89,3.89,0,0,1,19.43,70a5.56,5.56,0,0,1-.81-.08,3.87,3.87,0,0,1-3.07-3.81V42.78H3.88A3.89,3.89,0,0,1,.34,37.3l15.55-35A3.88,3.88,0,0,1,23.32,3.9V27.23H35a3.9,3.9,0,0,1,3.54,5.48Zm0,0"/></svg>';
		}

		elseif( $icon == 'activecampaign' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 13.24 20"><path d="M12.52,8.69C12.23,8.45.76,0.44,0.24,0.12L0.08,0V2A1.32,1.32,0,0,0,.8,3.14l0.08,0L10.7,10c-1.09.76-9.38,6.52-9.9,6.84a1.16,1.16,0,0,0-.68,1.25V20s12.19-8.49,12.43-8.69h0a1.52,1.52,0,0,0,.68-1.25V9.82A1.4,1.4,0,0,0,12.52,8.69Z"/><path d="M5.35,10.91a1.61,1.61,0,0,0,1-.36L7.08,10,7.2,9.94,7.08,9.86s-5.39-3.74-6-4.1A0.7,0.7,0,0,0,.36,5.63,0.71,0.71,0,0,0,0,6.28V7.53l0,0s3.7,2.58,4.43,3.06A1.63,1.63,0,0,0,5.35,10.91Z"/></svg>';
		}

		elseif( $icon == 'getresponse' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 44.54"><path d="M35,35a29,29,0,0,1-17.22-5.84A28.38,28.38,0,0,1,6.89,11.35c-.07-.47-.15-.94-.21-1.33A3.2,3.2,0,0,1,9.86,6.36h1.48a23.94,23.94,0,0,0,8.4,13.76A24.74,24.74,0,0,0,34.91,25.5C48.16,25.78,61.05,14,69.31,1.15A3,3,0,0,0,67,0H3A3,3,0,0,0,0,3V41.55a3,3,0,0,0,3,3H67a3,3,0,0,0,3-3V8.5C59.14,27.59,46.65,35,35,35Z"/></svg>';
		}

		elseif( $icon == 'mailchimp' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.96 70"><path d="M49.61,33.08a5.41,5.41,0,0,1,1.45,0,4.92,4.92,0,0,0,.07-2.75c-.34-1.66-.82-2.67-1.79-2.52s-1,1.37-.66,3a5.7,5.7,0,0,0,.93,2.24Z"/><path d="M41.26,34.4c.69.3,1.12.5,1.29.33s.07-.32-.09-.59a4,4,0,0,0-1.8-1.45,4.88,4.88,0,0,0-4.78.57c-.47.34-.91.81-.84,1.1,0,.09.09.16.25.19a27.75,27.75,0,0,1,3.27-.73,5.65,5.65,0,0,1,2.7.58Z"/><path d="M39.85,35.2a3.23,3.23,0,0,0-1.72.72,1.1,1.1,0,0,0-.45.69.19.19,0,0,0,.07.16.2.2,0,0,0,.15.06,2.81,2.81,0,0,0,.67-.18,5.74,5.74,0,0,1,2.92-.31c.45,0,.67.08.77-.07a.26.26,0,0,0,0-.29,2.62,2.62,0,0,0-2.38-.78Z"/><path d="M46.79,38.13a1.13,1.13,0,0,0,1.52-.26c.22-.45-.1-1.06-.72-1.37a1.13,1.13,0,0,0-1.52.27,1.11,1.11,0,0,0,.72,1.36Z"/><path d="M50.75,34.67c-.5,0-.92.54-.93,1.23s.39,1.25.89,1.26.91-.55.93-1.23-.39-1.25-.89-1.26Z"/><path d="M17.14,47c-.12-.15-.33-.1-.53-.06a2.11,2.11,0,0,1-.46.07,1,1,0,0,1-.86-.44,1.59,1.59,0,0,1,0-1.47,2,2,0,0,1,.12-.26c.4-.9,1.07-2.41.31-3.85a3.38,3.38,0,0,0-2.6-1.89,3.34,3.34,0,0,0-2.87,1,4.14,4.14,0,0,0-1.07,3.47c.08.22.2.28.29.29s.47-.11.64-.58a.86.86,0,0,0,0-.15,5,5,0,0,1,.46-1.08,2,2,0,0,1,1.26-.87,2,2,0,0,1,1.53.29,2,2,0,0,1,.74,2.36A5.58,5.58,0,0,0,13.8,46a2.11,2.11,0,0,0,1.87,2.16,1.59,1.59,0,0,0,1.5-.75.31.31,0,0,0,0-.37Z"/><path d="M24.76,19.66a31,31,0,0,1,8.71-7.12.11.11,0,0,1,.15.15,8.56,8.56,0,0,0-.81,2,.12.12,0,0,0,.18.12,17,17,0,0,1,7.65-2.7.13.13,0,0,1,.08.22,6.6,6.6,0,0,0-1.21,1.21.12.12,0,0,0,.1.18A15.09,15.09,0,0,1,46,15.38c.12.06,0,.3-.1.27a25.86,25.86,0,0,0-11.58,0,26.57,26.57,0,0,0-9.41,4.15.11.11,0,0,1-.15-.17Zm13,29.25Zm10.78,1.27a.21.21,0,0,0,.12-.2.2.2,0,0,0-.22-.18,24.86,24.86,0,0,1-10.84-1.1c.57-1.87,2.1-1.19,4.4-1a32.17,32.17,0,0,0,10.64-1.15,24.28,24.28,0,0,0,8-3.95,16,16,0,0,1,1.11,3.78,1.86,1.86,0,0,1,1.17.22c.5.31.87.95.62,2.61a14.39,14.39,0,0,1-4,7.93,16.67,16.67,0,0,1-4.86,3.63,20,20,0,0,1-3.17,1.34c-8.35,2.73-16.9-.27-19.65-6.71a9.46,9.46,0,0,1-.55-1.52,13.36,13.36,0,0,1,2.93-12.54h0a1.09,1.09,0,0,0,.39-.75,1.27,1.27,0,0,0-.3-.7c-1.09-1.59-4.86-4.28-4.11-9.49C30.68,26.64,34,24,37,24.16l.77,0c1.33.08,2.48.25,3.57.3a7.19,7.19,0,0,0,5.41-1.81,4.13,4.13,0,0,1,2.07-1.17,2.71,2.71,0,0,1,.79-.08,2.68,2.68,0,0,1,1.33.43c1.56,1,1.78,3.55,1.86,5.38,0,1.05.17,3.58.21,4.31.1,1.67.54,1.9,1.42,2.19.5.17,1,.29,1.65.48a9.31,9.31,0,0,1,4,1.92,2.56,2.56,0,0,1,.74,1.45c.24,1.77-1.38,4-5.67,5.95a28.69,28.69,0,0,1-14.3,2.29l-1.37-.15c-3.15-.43-4.94,3.63-3,6.42,1.21,1.79,4.52,3,7.84,3,7.59,0,13.43-3.24,15.6-6l.17-.24c.11-.16,0-.25-.11-.16-1.77,1.21-9.66,6-18.08,4.58a11.38,11.38,0,0,1-2-.53c-.75-.29-2.3-1-2.49-2.6,6.8,2.1,11.09.11,11.09.11ZM11.18,34a9.06,9.06,0,0,0-5.72,3.65A15.45,15.45,0,0,1,3,35.33C1,31.46,5.24,24,8.22,19.7c7.35-10.49,18.86-18.43,24.19-17,.86.25,3.73,3.58,3.73,3.58a74.88,74.88,0,0,0-10.26,7.07A46.63,46.63,0,0,0,11.18,34Zm4,17.73a5,5,0,0,1-1.09.08c-3.56-.09-7.41-3.3-7.79-7.1-.42-4.2,1.72-7.43,5.52-8.2a6.67,6.67,0,0,1,1.6-.11c2.13.11,5.27,1.75,6,6.39.64,4.11-.37,8.29-4.22,8.94Zm48.22-7.43c0-.11-.23-.84-.51-1.71a13.28,13.28,0,0,0-.55-1.49,5.47,5.47,0,0,0,1-3.94,5,5,0,0,0-1.45-2.81,11.64,11.64,0,0,0-5.11-2.53l-1.3-.36c0-.06-.07-3.07-.13-4.37a15,15,0,0,0-.57-3.84,10.35,10.35,0,0,0-2.66-4.74c3.24-3.36,5.27-7.06,5.26-10.24,0-6.11-7.51-8-16.76-4.13l-2,.83L35,1.47c-10.54-9.2-43.51,27.44-33,36.34l2.31,1.95A11.32,11.32,0,0,0,3.71,45,10.3,10.3,0,0,0,7.27,51.6a10.86,10.86,0,0,0,7,2.81C18.35,63.86,27.72,69.66,38.71,70c11.78.35,21.68-5.18,25.82-15.11A20.84,20.84,0,0,0,66,48.26c0-2.79-1.58-3.94-2.58-3.94Z"/></svg>';
		}

		elseif( $icon == 'wordpress' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 70"><path d="M35,0A35,35,0,1,0,70,35,35,35,0,0,0,35,0M3.53,35A31.33,31.33,0,0,1,6.26,22.19l15,41.13A31.48,31.48,0,0,1,3.53,35M35,66.47a31.42,31.42,0,0,1-8.89-1.28l9.44-27.44,9.67,26.5a3.45,3.45,0,0,0,.23.43A31.21,31.21,0,0,1,35,66.47m4.34-46.22c1.89-.1,3.6-.3,3.6-.3a1.3,1.3,0,0,0-.2-2.6s-5.1.4-8.39.4c-3.09,0-8.29-.4-8.29-.4a1.3,1.3,0,0,0-.2,2.6s1.61.2,3.3.3l4.91,13.43L27.18,54.33,15.72,20.25c1.9-.1,3.6-.3,3.6-.3a1.3,1.3,0,0,0-.2-2.6s-5.1.4-8.39.4c-.59,0-1.28,0-2,0a31.46,31.46,0,0,1,47.54-5.92l-.41,0a5.44,5.44,0,0,0-5.28,5.58c0,2.6,1.49,4.79,3.09,7.38a16.66,16.66,0,0,1,2.59,8.68c0,2.69-1,5.82-2.39,10.17L50.71,54.07Zm23.27-.35A31.46,31.46,0,0,1,50.82,62.2l9.61-27.79a29.62,29.62,0,0,0,2.39-11.27,23.42,23.42,0,0,0-.21-3.24"/></svg>';
		}

		elseif( $icon == 'bulkgate' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 53.8"><g id="g3418"><path id="path3420" class="cls-1" d="M35,12.83c11.44,7.83,19.44,17.88,22.73,41H70C68.8,30.78,55,11,35,0,15,11,1.29,30.78,0,53.8H12.29S34,54.61,48.41,32c0,0-14.44,7.68-22.94,4.49-8-3-4.15-10-3.71-10.72A48,48,0,0,1,35,12.83"/></g></svg>';
		}

		return "<span class='cartbounty-icon-container cartbounty-icon-$icon'><img src='data:image/svg+xml;base64," . esc_attr( base64_encode($svg) ) . "' alt='" . esc_attr( $icon ) . "' /></span>";
    }

    /**
	 * Method reads GET parameter from the link to restore the cart
	 * At first all products from the cart are removed and then populated with those that were previously saved
	 *
	 * @since    7.0
	 */
	function restore_cart(){
		if(!class_exists('WooCommerce')){ //Exit if WooCommerce not active
			return;
		}

		global $wpdb;

		//Checking if GET argument is present in the link. If not, exit function
		if (empty( $_GET['cartbounty'] )){
			return;
		}
		
		//Processing GET parameter from the link
		$hash_id = sanitize_text_field($_GET['cartbounty']); //Getting and sanitizing GET value from the link
		$parts = explode('-', $hash_id); //Splitting GET value into hash and ID
		$hash = $parts[0];
		$id = $parts[1];

		//Retrieve row from the abandoned cart table in order to check if hashes match
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, email, cart_contents, session_id
				FROM $cart_table
				WHERE id = %d AND
				type != %d",
				$id,
				$this->get_cart_type('recovered')
			)
		);

		if(empty($row)){ //Exit function if no row found
			return;
		}

		//Checking if hashes match
		$row_hash = hash_hmac('sha256', $row->email . $row->session_id, CARTBOUNTY_ENCRYPTION_KEY); //Building encrypted hash from the row
		if(!hash_equals($hash, $row_hash)){ //If hashes do not match, exit function
			return;
		}

		//If we have received an Unsubscribe request - stop restoring cart and unsubscribe user instead
		if (isset( $_GET['cartbounty-unsubscribe'])){
			$wordpress = new CartBounty_WordPress();
			$wordpress->unsubscribe_user( $id );
			wp_die( esc_html__('You have successfully unsubscribed from further emails about your shopping cart.', 'woo-save-abandoned-carts'), esc_html__( 'Successfully unsubscribed', 'woo-save-abandoned-carts'), $args = array( 'link_url' => get_site_url(), 'link_text' => esc_html__( 'Return to store', 'woo-save-abandoned-carts') ) );
		}

		$this->build_cart( $row ); //Restore cart with previous products

		//Redirecting user to Checkout page
		$checkout_url = wc_get_checkout_url();
		wp_redirect( $checkout_url, '303' );
		exit();
	}

	/**
	 * Build cart
	 *
	 * @since    7.0.5
	 * @return   boolean
	 * @param    object     $cart   		    Cart data
	 */
	public function build_cart( $cart ){
		
		if( WC()->cart ){ //Checking if WooCommerce has loaded
			WC()->cart->empty_cart();//Removing any products that might have be added in the cart
			$products = @unserialize( $cart->cart_contents );
			
			if( !$products ){ //If missing products
				return;
			}

			foreach( $products as $product ){ //Looping through cart products
				$product_exists = wc_get_product( $product['product_id'] ); //Checking if the product exists
				
				if( $product_exists ){
					
					//Get product variation attributes if present
					if( $product['product_variation_id'] ){
						$single_variation = new WC_Product_Variation( $product['product_variation_id'] );
						$single_variation_data = $single_variation->get_data();
						$variation_attributes = $single_variation->get_variation_attributes(); //Handling variable product title output with attributes

					}else{
						$variation_attributes = '';
					}
					
					$restore = WC()->cart->add_to_cart( $product['product_id'], $product['quantity'], $product['product_variation_id'], $variation_attributes ); //Adding previous products back to cart
				}
			}

			//Restore previous session id because we want the user abandoned cart data to be in sync
			//Starting session in order to check if we have to insert or update database row with the data from input boxes
			WC()->session->set( 'cartbounty_session_id', $cart->session_id ); //Putting previous customer ID back to WooCommerce session
			WC()->session->set( 'cartbounty_from_link', true ); //Setting a marker that current user arrived from email
		}
	}

    /**
	 * Method tries to move email field higher in the checkout form
	 *
	 * @since    4.5
	 * @return 	 Array
	 * @param 	 $fields    Checkout form fields
	 */ 
	public function lift_checkout_fields( $fields ) {
		$lift_email_on = esc_attr( get_option('cartbounty_lift_email'));
		if($lift_email_on){ //Changing the priority and moving the email higher
			$fields['billing_email']['priority'] = 4;
		}
		return $fields;
	}

	/**
	 * Method prepares and returns an array of different time intervals used for calulating time substractions
	 *
	 * @since    4.6
	 * @return 	 Array
	 * @param    integer     $interval    		  	  Time interval that has to be waited for in minues
	 * @param    boolean     $first_step    		  Wheather function requested during the first step of WordPress automation. Default false
	 */
	public function get_time_intervals( $interval = false, $first_step = false ){
		$waiting_time = $this->get_waiting_time();

		if($first_step){ //In case if we need to get WordPress first automation step time interval, we must add additional time the cart was waiting to be recognized as abandoned
			$interval = $interval + $waiting_time;
		}

		//Calculating time intervals
		$datetime = current_time( 'mysql' );
		return array(
			'cart_abandoned' 	=> date( 'Y-m-d H:i:s', strtotime( '-' . $waiting_time . ' minutes', strtotime( $datetime ) ) ),
			'cart_recovered' 	=> date( 'Y-m-d H:i:s', strtotime( '-30 seconds', strtotime( $datetime ) ) ),
			'old_cart' 			=> date( 'Y-m-d H:i:s', strtotime( '-' . CARTBOUNTY_NEW_NOTICE . ' minutes', strtotime( $datetime ) ) ),
			'day' 				=> date( 'Y-m-d H:i:s', strtotime( '-1 day', strtotime( $datetime ) ) ),
			'wp_step_send_period' 		=> date( 'Y-m-d H:i:s', strtotime( '-' . $interval . ' minutes', strtotime( $datetime ) ) ),
			'maximum_sync_period'		=> date( 'Y-m-d H:i:s', strtotime( '-' . CARTBOUNTY_MAX_SYNC_PERIOD . ' days', strtotime( $datetime ) ) )
		);
	}

	/**
	 * Getting time period during which the cart is not considered abandoned - the customer is presumed to be shopping
	 *
	 * @since    6.1.3
	 * @return 	 Number
	 */
	public function get_waiting_time(){
		$default_waiting_time = 60; //In minutes. Defines the time period after which an email notice will be sent to admin and the cart is presumed abandoned
		$waiting_time = apply_filters( 'cartbounty_waiting_time', $default_waiting_time );
		if($waiting_time < 20){ //Making sure minimum waiting period is not less than 20 minutes
			$waiting_time = 20;
		}
		return $waiting_time;
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
		$ordered = $this->get_cart_type('ordered');

		$total_items = $wpdb->get_var(
			"SELECT COUNT(id)
			FROM $cart_table
			WHERE cart_contents != '' AND
			type != $ordered
			$where_sentence"
		);

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
    		'all' 			=> esc_html__('All', 'woo-save-abandoned-carts'),
    		'recoverable' 	=> esc_html__('Recoverable', 'woo-save-abandoned-carts'),
    		'ghost' 		=> esc_html__('Ghost', 'woo-save-abandoned-carts'),
    		'recovered' 	=> esc_html__('Recovered', 'woo-save-abandoned-carts')
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
    			$url = '?page="'. esc_attr( CARTBOUNTY ) .'"&tab='. esc_attr( $tab ) .'&cart-status='. esc_attr( $key );
	    		echo "<li><a href='". esc_url( $url ) ."' title='". esc_attr( $type ) ."' class='". esc_attr( $class ) ."'>". esc_html( $type ) ." <span class='count'>(". esc_html( $count ) .")</span></a>". wp_kses( $divider, 'data' ) ."</li>";
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
			$where_sentence = "AND (email != '' OR phone != '') AND type != ". $this->get_cart_type('recovered') ." AND type != " . $this->get_cart_type('ordered');

		}elseif($cart_status == 'ghost'){
			$where_sentence = "AND ((email IS NULL OR email = '') AND (phone IS NULL OR phone = '')) AND type != ". $this->get_cart_type('recovered') ." AND type != " . $this->get_cart_type('ordered');

		}elseif($cart_status == 'recovered'){
			$where_sentence = "AND type = ". $this->get_cart_type('recovered');

		}elseif(get_option('cartbounty_exclude_ghost_carts')){ //In case Ghost carts have been excluded
			$where_sentence = "AND (email != '' OR phone != '')";
		}

		return $where_sentence;
    }

    /**
	 * Method handles "Force sync" button functionality
	 *
	 * @since    7.0
	 * @return 	 Boolean
	 */
	public function force_sync(){
		$data = $_POST;
		if ( check_ajax_referer( 'force_sync', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing the function
	        wp_send_json_error(esc_html__( 'Sync failed. Looks like you are not allowed to do this.', 'woo-save-abandoned-carts' ));
	    }

	    if($data['integration'] == 'wordpress'){
	    	$wordpress = new CartBounty_WordPress();
			$wordpress->auto_send();
	    }
		wp_send_json_success( esc_html__("Sync finished", 'woo-save-abandoned-carts' ));
	}

	/**
	 * Method displays the Force sync button
	 *
	 * @since    7.0
	 * @return 	 String
	 */
	public function display_force_sync_button( $integration ){
		if( !$integration ){
			return;
		}

		$button_name = esc_html__('Force send', 'woo-save-abandoned-carts');
		$nonce = wp_create_nonce( 'force_sync' );
		$button = "<a id='force_sync' class='cartbounty-button button button-secondary' href='#' data-integration='". esc_attr( $integration ) ."' data-nonce='". esc_attr( $nonce ) ."'>". esc_html( $button_name ) ."</a>";
		return $button;
	}

	/**
	 * Method creates abandoned cart URL that is used to restore the shopping cart
	 *
	 * @since    7.0
	 * @return   string
	 * @param    string   	$email			Cart email
	 * @param    string   	$session_id		Cart session ID
	 * @param    integer   	$cart_id		Cart ID
	 */
	public function create_cart_url( $email, $session_id, $cart_id ){
		$cart_url = wc_get_cart_url();
		$hash = hash_hmac('sha256', $email . $session_id, CARTBOUNTY_ENCRYPTION_KEY) . '-' . $cart_id; //Creating encrypted hash with abandoned cart row ID in the end
		return $checkout_url = $cart_url . '?cartbounty=' . $hash;
	}

	/**
	 * Return cart type from cart name
	 *
	 * @since    7.0.8
	 * @return   integer
	 * @param    string    	$status		    Cart status. 0 = default, 1 = recovered, 2 = order created
	 */
	function get_cart_type( $status ){
		if( empty($status) ){
			return;
		}

		$type = 0;

		switch ( $status ) {
			case 'abandoned':

				$type = 0;
				break;

			case 'recovered':

				$type = 1;
				break;

			case 'ordered':

				$type = 2;
				break;
				
		}
		return $type;
	}

	/**
	 * Method updates cart type accoringly
	 * In future might add additional statuses e.g. 2, 3, 4 etc.
	 *
	 * @since    7.0
	 * @param    string    	$session_id		Session ID
	 * @param    integer   	$type			Cart status type 0 = default, 1 = recovered, 2 = order created
	 */
	function update_cart_type( $session_id, $type ){
		if($session_id){
			global $wpdb;
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
			$field = 'session_id';
			$where_value = $session_id;
			$data = array(
				'type = ' . sanitize_text_field($type)
			);

			if( $type == $this->get_cart_type('recovered') ){ //If order should be marked as recovered
				//Increase total
				$data[] = 'mail_sent = 0';
				$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
				$public->increase_recovered_cart_count();
			}

			$data = implode(', ', $data);

			$updated_rows = $wpdb->query(
				$wpdb->prepare(
					"UPDATE $cart_table
					SET $data
					WHERE $field = %s AND
					type != %d",
					$where_value,
					$this->get_cart_type('recovered')
				)
			);
		}
	}

    /**
	 * Handling abandoned carts in case of a new order is placed
	 *
	 * @since    5.0.2
	 * @param    integer    $order_id - ID of the order created by WooCommerce
	 */
	function handle_order( $order_id ){

		if( !isset($order_id) ){ //Exit if Order ID is not present
			return;
		}

		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$public->update_logged_customer_id(); //In case a user chooses to create an account during checkout process, the session id changes to a new one so we must update it
		if( WC()->session ){ //If session exists
			$cart = $public->read_cart();
			$type = $this->get_cart_type('ordered'); //Default type describing an order has been placed

			if(isset($cart['session_id'])){
				if(WC()->session->get('cartbounty_from_link')){ //If the user has arrived from CartBounty link
					$type = $this->get_cart_type('recovered');
				}
				$this->update_cart_type($cart['session_id'], $type); //Update cart type to recovered
			}
		}
		$this->clear_cart_data(); //Clearing abandoned cart after it has been synced
	}

	/**
	 * Adds one or more classes to the body tag in the dashboard
	 *
	 * @since    6.0
	 * @return   String    Altered body classes
	 * @param    String    $classes    Current body classes
	 */
	function add_cartbounty_body_class( $classes ) {
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();
	    if(is_object($screen) && $screen->id == $cartbounty_admin_menu_page){ //If we are on CartBounty page
			$classes = $classes .' '.  CARTBOUNTY_PLUGIN_NAME_SLUG .' ';
		}
	    return $classes;
	}

	/**
	 * Returns a link with tracking information
	 *
	 * @since    6.1.2
	 * @return   String
	 * @param    String    $url        URL
	 * @param    String    $medium     Determines where the button was clicked from. Default none
	 * @param    String    $tag        Hashtag to a specific section in the document. Default none
	 */
	function get_trackable_link( $url, $medium = '', $tag = '' ) {
		return $url .'?utm_source='. urlencode(get_bloginfo("url")) .'&utm_medium='. esc_attr( $medium ) .'&utm_campaign='. esc_attr( CARTBOUNTY ) . esc_attr( $tag );
	}

	/**
	 * Outputs message about upgrade to Pro
	 *
	 * @since    6.0
	 * @param    String    $medium    Determines where the button was clicked from
	 * @return   String    Message
	 */
	function display_unavailable_notice( $medium = false ) {
		$message = sprintf(
			/* translators: %s - URL link tags */
			esc_html__('Please consider upgrading to %sCartBounty Pro%s to enable this feature.', 'woo-save-abandoned-carts'),
			'<a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_LICENSE_SERVER_URL, $medium) ) .'" target="_blank">','</a>');
		return $message;
	}

	/**
	 * Returns abandoned cart product price with or without VAT
	 *
	 * @since    7.0.2
	 * @return   Number
	 * @param    Array or Object    $product          		Cart line item (array) or WooCommerce Product (object)
	 * @param    Boolean  			$force_tax     			Should we bypass all filters and display price with VAT. Default false
	 * @param    Boolean  			$force_exclude_tax     	Weather we should bypass all filters and exclude taxes in the price. Default false
	 */
	function get_product_price( $product, $force_tax = false, $force_exclude_tax = false ) {
		if ( !class_exists( 'WooCommerce' ) ){ //If WooCommerce is not active
			return;
		}

		$tax = 0;
		$price = 0;
		$decimals = 0;

		if( wc_get_price_decimals() ){
			$decimals = wc_get_price_decimals();
		}

		if( is_array( $product ) ){ //In case we are working with CartBounty line item
			$price = $product['product_variation_price'];
			
			if( empty( $price ) ){
				$price = 0;
			}

			if( isset( $product['product_tax'] ) ){ //If tax data exists
				$tax = $product['product_tax'];
			}

		}elseif( is_object( $product ) ){ //In case we are working with WooCommerce product
			$price_with_tax = wc_get_price_including_tax( $product );
			$price = $product->get_price();
			
			if( empty( $price_with_tax ) ){
				$price_with_tax = 0;
			}

			if( empty( $price ) ){
				$price = 0;
			}
			$tax = $price_with_tax - $price;
		}

		if( $force_exclude_tax ){ //If we do not wat to include taxes. E.g., in GetResponse sync
			$tax = 0;
		}

		if( !empty( $tax ) && ( apply_filters( 'cartbounty_include_tax', true ) || $force_tax == true ) ){ //If the tax value is set, the filter is set to true or the taxes are forced to display
			$price = $price + $tax;
		}

		if( empty( $price ) ){ //In case the price is empty
			$price = 0;
		}

		return round( $price, $decimals );
	}

	/**
	* Method sanitizes field
	*
	* @since    7.0.2
	* @return   string
	* @param    string    $field    		  Field that should be sanitized
	*/
	public function sanitize_field( $field ){
		$field = str_replace('"', '', $field);
		return wp_specialchars_decode( sanitize_text_field( wp_unslash( $field ) ), ENT_NOQUOTES );
	}

	/**
     * Returning Tools defaults
     *
     * @since    7.0.6
     * @return   array or string
     * @param    string     $value    		  	  Value to return
     * @param    integer    $tool    		      Tool name
     */
	public function get_tools_defaults( $value = false, $tool = false ){
		switch ( $tool ) {
			case 'exit_intent':

				$defaults = array(
					'heading'		=> esc_html__( 'You were not leaving your cart just like that, right?', 'woo-save-abandoned-carts' ),
					'content'		=> esc_html__( 'Enter your details below to save your shopping cart for later. And, who knows, maybe we will even send you a sweet discount code :)', 'woo-save-abandoned-carts' )
				);

				break;

			case 'early_capture':

				$defaults = array(
					'heading'		=> esc_html__( 'Please enter your details to add this item to your cart', 'woo-save-abandoned-carts' )
				);
			
				break;
		}

		if($value){ //If a single value should be returned
			if(isset($defaults[$value])){ //Checking if value exists
				$defaults = $defaults[$value];
			}
		}

		return $defaults;
	}

	/**
	* Method for outputting price accordingly to the user's selected WooCommerce currency position
	*
	* @since    7.0.6
	* @return   string
	* @param    float      $price    		  	  Price value
	* @param    string     $currency_code   	  Currency code, e.g. EUR, USD
	*/
	public function format_price( $price, $currency_code = false ){
		if (!class_exists('WooCommerce')){ //If WooCommerce is not active
			return;
		}

		$decimals = 0;
		if(wc_get_price_decimals()){
			$decimals = wc_get_price_decimals();
		}

		$price = number_format((float)$price, $decimals, '.', ''); //Format price so there would always be correct number of decimals after comma, e.g. 2.30 instead of 2.3
		$woocommerce_price_format = get_woocommerce_price_format(); //Retrieve the pricing format the user has set
		$currency = $this->get_currency( $currency_code );

		$price = sprintf( apply_filters( 'cartbounty_price_format', $woocommerce_price_format ), $currency, $price);
		return $price;
	}

	/**
	* Return currency from provided currency code
	*
	* @since    9.6
	* @return   string
	* @param    string     $currency_code   	  Currency code, e.g. EUR, USD
	*/
	public function get_currency( $currency_code = false ){
		if (!class_exists('WooCommerce')){ //If WooCommerce is not active
			return;
		}

		$currency = get_woocommerce_currency_symbol( $currency_code );

		if(apply_filters( 'cartbounty_display_currency_code', false )){ //If currency code display is enabled, display currency code instead of symbol. By default we display currency symbol
			$currency = $currency_code;
		}

		if(empty($currency)){ //If the currency is empty, retrieve default WooCommerce currency ignoring the one saved in the abandoned cart
			$currency = get_woocommerce_currency_symbol();
		}
		return $currency;
	}

	/**
	* Method scans all files inside CartBounty templates folder. 
	* Returns an array of all found files.
	*
	* @since    7.0.7
	* @return   array
	* @param    string    $default_path			Default path to template files.
	*/
	function scan_files( $default_path ){
		$files  = scandir($default_path);
		$result = array();

		if(!empty($files)){
			foreach($files as $key => $value){
				if(!in_array($value, array( '.', '..' ), true)){
					if(is_dir($default_path . '/' . $value)){
						$sub_files = $this->scan_files( $default_path . '/' . $value );
						foreach($sub_files as $sub_file){
							$result[] = $sub_file;
						}
						foreach ($sub_files as $sub_file){
							$result[] = $value . '/' . $sub_file;
						}

					}else{
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	* Get an array of templates that have been overriden
	*
	* @since    7.0.7
	* @return   array
	*/
	function get_template_overrides(){
		$template_path = 'templates/';
		$default_path = plugin_dir_path( __FILE__ ) . '../templates/';
		$override_files = array();
		$scan_files = $this->scan_files( $default_path );

		foreach($scan_files as $file){
			if(file_exists(get_stylesheet_directory() . '/' . $file)){
				$theme_file = get_stylesheet_directory() . '/' . $file;

			}elseif(file_exists(get_stylesheet_directory() . '/' . $template_path . $file)){
				$theme_file = get_stylesheet_directory() . '/' . $template_path . $file;

			}elseif(file_exists(get_template_directory() . '/' . $file)){
				$theme_file = get_template_directory() . '/' . $file;

			}elseif(file_exists(get_template_directory() . '/' . $template_path . $file)){
				$theme_file = get_template_directory() . '/' . $template_path . $file;

			}else{
				$theme_file = false;
			}

			if(!empty($theme_file)){
				$override_files[] = str_replace(WP_CONTENT_DIR . '/themes/', '', $theme_file);
			}
		}
		return $override_files;
	}

	/**
	* Add email badge
	*
	* @since    7.0.7
	* @return   html
	*/
	function add_email_badge(){
		$tag = 'automation_email';
		if( current_filter() == 'cartbounty_admin_email_footer_end' ){ //If the function triggered inside admin notification email
			$tag = 'admin_notification_email';
		}
		$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$image = $public->get_plugin_url() . '/public/assets/sent-via-cartbounty.png';
		$output = '';
		$output .= '<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
			<tr>
				<td valign="top">
					<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
						<tr>
							<td valign="top" width="650"  align="center" style="text-align: center;">
								<a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_LICENSE_SERVER_URL, $tag ) ) .'" style="display: block;">
									<img src="'. esc_url( $image ) .'" alt="Reminded using CartBounty" title="Reminded using CartBounty" width="130" height="auto" style="display:inline; text-align: center; margin: 20px 0 5px; -ms-interpolation-mode: bicubic; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; border: none 0;" />
								</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>';
		echo $output;
	}

	/**
	 * Fire functions on WordPress load
	 *
	 * @since    7.0.7.1
	 */
	function trigger_on_load(){
		$this->restore_cart(); //Restoring abandoned cart if a user returns back from an abandoned cart email link
	}

	/**
	 * Output emoji button
	 *
	 * @since    7.1
	 */
	function add_emojis(){
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 67 67"><path d="M33.5,0A33.5,33.5,0,1,0,67,33.5,33.5,33.5,0,0,0,33.5,0Zm0,61.88A28.37,28.37,0,1,1,61.86,33.51,28.37,28.37,0,0,1,33.49,61.88Z"/><path d="M24.23,32a2.55,2.55,0,0,1-2.55-2.55c0-2,0-3.1,0-5.21a2.46,2.46,0,0,1,2.55-2.54,2.51,2.51,0,0,1,2.54,2.57c0,.72,0,5.2,0,5.2A2.56,2.56,0,0,1,24.25,32Z"/><path d="M42.78,32a2.55,2.55,0,0,1-2.54-2.58c0-2.17,0-3.2,0-5.21a2.55,2.55,0,0,1,5.09,0c0,.71,0,5.2,0,5.2A2.55,2.55,0,0,1,42.78,32Z"/><path d="M18.64,43.33a27.92,27.92,0,0,0,5.52,4.35,19.35,19.35,0,0,0,18.52.08,27,27,0,0,0,5.67-4.43c2.54-2.38-1.28-6.2-3.82-3.82a22.36,22.36,0,0,1-4.11,3.33,14,14,0,0,1-6.92,1.84,13.83,13.83,0,0,1-6.77-1.75,22.73,22.73,0,0,1-4.27-3.42c-2.53-2.38-6.36,1.44-3.82,3.82Z"/></svg>';
		$output = "<div class='cartbounty-icon-button-container cartbounty-preview-container'><div class='cartbounty-button button-secondary cartbounty-emoji cartbounty-icon-button button button-preview'>". $icon ."</div>". $this->display_preview_contents( 'emojis' ) ."</div>";
		echo $output;
	}

	/**
	 * Output persnalization tags button
	 *
	 * @since    7.1
	 */
	function add_tags(){
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.56 67.4"><path d="M3.55,67.4c-1.93,0-3.28-.49-3.49-2a18.69,18.69,0,0,1,.22-4.31c.16-1.13.31-2.19.55-3.23,2.55-11,9.08-19,19.39-23.74L22.56,33l-1.68-1.92c-5.08-5.42-6.58-11.6-4.46-18.34S23.34,1.73,30.56.32A17.8,17.8,0,0,1,33.89,0,18.22,18.22,0,0,1,35.5,36.35c-.82.09-1.65.13-2.53.17a28.77,28.77,0,0,0-4.19.39C17.7,39.05,10.2,45.6,6.51,56.37,6.19,57.32,5,62.25,5,62.25H33.45a2.69,2.69,0,0,1,2.28,1.16A2.46,2.46,0,0,1,36,65.74a2.38,2.38,0,0,1-2.14,1.6C33.56,67.37,3.55,67.4,3.55,67.4Zm30-61.91a12.91,12.91,0,0,0-9.14,22,12.74,12.74,0,0,0,9.1,3.79,12.91,12.91,0,0,0,.08-25.82Z"/><path d="M80,46.72H67.12V33.89a2.61,2.61,0,0,0-5.21,0V46.72H49.08a2.61,2.61,0,1,0,0,5.21H61.91V64.76a2.61,2.61,0,1,0,5.21,0V51.93H80a2.61,2.61,0,1,0,0-5.21Z"/></svg>';
		$output = "<div class='cartbounty-icon-button-container cartbounty-preview-container'><div class='cartbounty-button button-secondary cartbounty-tags cartbounty-icon-button button button-preview'>". $icon ."</div>". $this->display_preview_contents( 'personalization' ) ."</div>";
		echo $output;
	}

	/**
	* Return preview contents according to feature
	*
	* @since    7.1
	* @return   HTML
	* @param    string    $feature			Feature that has to be displayed
	*/
	function display_preview_contents( $feature ){
		$output = "<div class='cartbounty-preview-contents cartbounty-preview-". $feature ." cartbounty-bubble'>";
		$tracking_label = 'preview_' . $feature;

		switch ( $feature ) {
			case 'emojis':

				ob_start(); ?>
				<div class="cartbounty-header-image">
					<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, $tracking_label ) ); ?>" title="<?php esc_attr_e('Upgrade to allow easy emoji insertion', 'woo-save-abandoned-carts'); ?>" target="_blank">
						<img src="<?php echo esc_url( plugins_url( 'assets/emoji-preview.gif', __FILE__ ) ); ?>"/>
					</a>
				</div>
				<div class="cartbounty-preview-text">
					<h2><?php esc_html_e('Upgrade to allow easy emoji insertion', 'woo-save-abandoned-carts' ); ?></h2>
					<div class="cartbounty-button-row cartbounty-close-preview">
						<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, $tracking_label ) ); ?>" class="button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
						<button type="button" class='button cartbounty-close'><?php esc_html_e('Close', 'woo-save-abandoned-carts'); ?></button>
					</div>
				</div>
				<?php
				$output .= ob_get_contents();
				ob_end_clean();

				break;

			case 'personalization':

				ob_start(); ?>
				<div class="cartbounty-header-image">
					<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . '/personalization-tags', $tracking_label ) ); ?>" title="<?php esc_attr_e('Increase open-rate and sales using personalization', 'woo-save-abandoned-carts'); ?>" target="_blank">
						<img src="<?php echo esc_url( plugins_url( 'assets/personalization-preview.gif', __FILE__ ) ); ?>"/>
					</a>
				</div>
				<div class="cartbounty-preview-text">
					<h2><?php esc_html_e('Increase open-rate and sales using personalization', 'woo-save-abandoned-carts' ); ?></h2>
					<div class="cartbounty-button-row cartbounty-close-preview">
						<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, $tracking_label ) ); ?>" class="button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
						<button type="button" class='button cartbounty-close'><?php esc_html_e('Close', 'woo-save-abandoned-carts'); ?></button>
					</div>
				</div>
				<?php
				$output .= ob_get_contents();
				ob_end_clean();

				break;
		}
		$output .= "</div>";

		return $output;
	}
}