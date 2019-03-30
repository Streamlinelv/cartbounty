=== Save Abandoned Carts - WooCommerce Live Checkout Field Capture ===
Donate link: https://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro/
Contributors: streamlinestar, nauriskolats
Tags: woocommerce, abandoned carts, cart abandonment, exit popup, checkout fields
Requires at least: 4.6
Requires PHP: 5.2.4
Tested up to: 5.1
Stable tag: 3.0
Version: 3.0
License: GPLv2 or later

Save Abandoned Carts and increase your sales by recovering them. Plugin instantly saves WooCommerce checkout field data before submission.

== Description ==

WooCommerce Live Checkout Field Capture plugin saves all activity in the [WooCommerce](https://woocommerce.com) checkout form before it is submitted. The plugin allows to see who abandons your shopping carts and get in touch with them.

You will be able to manually contact your visitors and remind about the abandoned cart. You could offer them an additional discount on the cart by sending them a coupon in order to persuade them.

If you would like to receive email notifications about abandoned carts and send automated abandoned cart recovery emails to customers, please visit our [WooCommerce Live Checkout Field Capture Pro - save abandoned carts](http://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro "WooCommerce Live Checkout Field Capture Pro - save abandoned carts") plugin version.

### Plugin basics and features:

* Instantly capture WooCommerce checkout field data before submission to save abandoned carts.

* The "Remember checkout fields" function will allow your customers to refresh the checkout page after entering their information and walk around the page without losing previously entered data in the checkout form. Please note that this feature is enabled only for users who haven't logged in - WooCommerce takes care of this for authorized users.

* If a user is logged in, the shopping cart will be instantly captured as soon as an item is added to the cart. After this, the cart will be instantly updated if it is altered or an item is removed from the cart.

* You can enable Exit Intent popup to offer saving unregistered user's shopping cart for later. With the help of Exit Intent you can capture even more abandoned carts by displaying a message including an email field that the customer can fill to save his shopping cart.
The Exit Intent will be triggered as soon as the user tries to leave your shop with a filled shopping cart.
Please note that the Exit Intent will only be showed to unregistered users once per hour after they have added an item to their shopping cart.

* If the user completes the payment and receives a "Thank you" page, he is removed from the Checkout Field Capture table and the Checkout form fields will be cleared.

* Since I love to make things run smooth, in case if you Uninstall this plugin, it will automatically clean up after itself (delete abandoned carts data, table and options created by this plugin) leaving your project clean.

### How the idea was born:

I started working on this plugin since WooCommerce currently does not come with an integrated solution for recovering abandoned carts and I wanted to develop one myself that would be very simple and lightweight. I built this plugin in order to register and recover abandoned carts in a website that sells [light cube](http://www.uniqcube.com/shop "light cube") lamps since there were many people who left the checkout process.

At the time when I started working on this project I knew that there were couple of plugins already available but they were offering a lot of functionality that slowed down my project and had many features that were not necessary. Also I wasn’t sure about the security that they provided and I wanted to contribute to WordPress community by helping others with the same need to see abandoned carts and recover them.

While continuing to be working on [Mājas lapu izstrāde](http://www.majas-lapu-izstrade.lv "Mājas lapu izstrāde") (website design and development) I will be managing this plugin in order to keep up with the WooCommerce and WordPress updates.

### Plugin dependencies:

1. Uses WordPress private WP_List_Table class in order to output the table in the admin section. If this class changes, the table and all of its functions might break.
1. WooCommerce hooks
1. WooCommerce session

Note: If the fields are added outside of Checkout page or Checkout page input field ID values are changed, the plugin will not be able to load data.
Input field ID values should be default:

* #billing_first_name
* #billing_last_name
* #billing_company
* #billing_email
* #billing_phone
* etc.

If WordPress changes the location of "admin-ajax.php" file, then will have to update it.

Since version 2.0.1 plugin also uses WooCommerce Checkout form input field class "input-text" in order to trigger save action from all form fields.

== Installation ==

1. Upload the plugin files to the "/wp-content/plugins/plugin-name" directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the "Plugins" screen in WordPress.
1. Look for the page "Checkout Field Capture" under "WooCommerce" menu - WooCommerce abandoned carts data collected from your checkout form will be saved here unless the user completes the checkout process.
1. Optionally setup the Exit Intent notice that will be displayed to unregistered users once per hour in case the user has added items to his shopping cart and tries to leave your shop.

== Frequently Asked Questions ==

= When is the cart and checkout form input field data saved? =

Data and information about the cart is saved right after the user gets to the Checkout form and one of the following events happen:

* Correct email address is entered
* Phone number is entered
* On Checkout page load if email or phone number input fields are already filled
* Any Checkout form input field with a class "input-text" is entered or changed if a valid Email or Phone number has been entered

If the user completes the checkout process and receives a "Thank you" page, the cart is removed from the Checkout Field Capture table of abandoned carts and the Checkout form fields are cleared.

= Where can I view WooCommerce abandoned carts? =

After installation the plugin will be available under "WooCommerce" menu. Please see 1st screenshot.

= WooCommerce shows order status "Failed" but I don't see an abandoned cart. =

Once user reaches the "Thank you" page the abandoned cart is automatically removed from the table since the cart is no longer considered as abandoned (regardless of the order status). In this case you can see all of the submitted user data under WooCommerce > Orders.

= How to enable email notifications about abandoned carts? =

This version does all the hard work of collecting the data and presenting it to you, it is simple and efficient. You will have to manually check newly abandoned carts. If you would like to receive automated email notifications, please visit our Pro [WooCommerce save abandoned carts](http://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro "WooCommerce save abandoned carts") plugin version.

= How to send automated abandoned cart recovery emails? =

The free version of our plugin allows collecting abandoned carts and you will be able to get in touch with your visitors manually.
If you would like to [send your visitors automated abandoned cart recovery emails](http://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro "send your visitors automated abandoned cart recovery emails") please take a look at our Pro version.

= How does the Exit Intent work? =

If a user tries to leave your shop with an abandoned cart, just before leaving, he will be presented with an additional form that will ask for his email address. Once it is entered (no need to submit the form), user's cart will be automatically captured.

Exit Intent form will be displayed only to unregistered users once per hour. If the user enters his email address either in the Exit Intent form or in the Checkout form - Exit Intent will no longer be displayed upon leaving your shop.

If you would like to test the visual appearance of the Exit Intent, please check the "Enable Test Mode" checkbox. Please note that only users with Admin rights will be able to see the Exit Intent during this stage and appearance limits will be removed. This means that it will be showed to the Admin each time he tries to leave the shop.

= How to change the content and image of the Exit Intent? =

If you would like to make adjustments to the default content of the Exit Intent, you can use either [action hooks and filters](https://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro/#actions-and-filters) that we have provided for you or use our Exit Intent template file to make the necessary adjustments.

You can find the Exit Intent template file inside "/plugins/woo-save-abandoned-carts/templates/wclcfc-exit-intent.php". This template file contains the markup to display the Exit Intent and to capture the Abandoned cart prior the user leaves your shop. Please copy this template to your shops theme to keep your customization intact after plugin updates.

You can copy this template file to either one of these locations:

* yourtheme/templates/wclcfc-exit-intent.php
* yourtheme/wclcfc-exit-intent.php

When modifying our template, please do not change the ID #wclcfc-exit-intent-email of the email input field. If changed, the plugin will not be able to capture abandoned carts using the Exit Intent form.

= What action hooks and filters are available for additional customization? =

Our Exit Intent template contains different action hooks and filters that allow you to create new, edit, replace or remove existing content including the main image in the Exit Intent window.

Available action hooks:

* wclcfc_exit_intent_start
* wclcfc_exit_intent_after_title
* wclcfc_exit_intent_before_form_fields
* wclcfc_exit_intent_end

Available filters:

* wclcfc_exit_intent_close_html
* wclcfc_exit_intent_image_html
* wclcfc_exit_intent_title_html
* wclcfc_exit_intent_description_html
* wclcfc_exit_intent_email_label_html
* wclcfc_exit_intent_email_field_html
* wclcfc_exit_intent_button_html

Here is an example how to add additional subtitle after the main title using our "wclcfc_exit_intent_after_title" action hook. Please add it to your theme's functions.php file:

	function add_extra_html_after_title() {
    	echo "<p>Additional subtitle here...</p>";
	}
	add_action('wclcfc_exit_intent_after_title', 'add_extra_html_after_title' );

Example how to change the main image using a filter:

	function modify_image( $html ){
		return '<img src="http://www.link-to-your-custom-image-here..."/>';
	}
	add_filter( 'wclcfc_exit_intent_image_html', 'modify_image' );

Example how to change the main title using a filter:

	function modify_title( $html ) {
		$custom_title = 'Your text here...';
		return preg_replace('#(<h2[^>]*>).*?(</h2>)#', "$1 $custom_title $2", $html);
	}
	add_filter( 'wclcfc_exit_intent_title_html', 'modify_title' );

== Screenshots ==

1. Location of the Save Abandoned Carts - WooCommerce Live Checkout Field Capture plugin after activation
2. The Exit Intent popup settings tab
3. How the Exit Intent popup looks like once the user tries to leave the shop

== Changelog ==

= 3.0 =
* Added Exit Intent popup
* Added Instant shopping cart capture for logged in users
* Fixed total captured abandoned cart counter

= 2.1 =
* Added language support
* Improved review bubble

= 2.0.6 =
* Improved review bubble

= 2.0.5 =
* Improved bubble display timing function

= 2.0.4 =
* Fixed PHP notice and a bug when working with WooCommerce orders within admin panel

= 2.0.3 =
* Updated Bubble timing function

= 2.0.2 =
* Fixed bug with Checkout form textarea field

= 2.0.1 =
* Modified "Remember user input" function. All Checkout form input fields are now triggering save data action

= 2.0 =
* Added "Remember user input" function that keeps user input in Checkout form until the Session has expired or user completes the Checkout
* PHP default sessions functionality replaced by WooCommerce sessions

= 1.5.2 =
* Added additional hook for removing abandoned cart from the table once a corresponding WooCommerce order is created

= 1.5.1 =
* Added ability for Shop managers to access Abandoned carts

= 1.5 =
* Added ability to save abandoned carts via phone number input
* Added function that collects and saves input field data if input fields already filled on Checkout page load

= 1.4.3 =
* Fixed bug when in some cases abandoned carts not being removed from table after reaching WooCommerce "Thank you" page

= 1.4.2 =
* Fixed bug related to notification output

= 1.4.1 =
* Fixed database update issue when upgrading to 1.4

= 1.4 =
* Added notification near menu about newly abandoned carts (last 2 hours)
* Added location registration (Country and City)
* Added links on product titles in Cart content column
* Added additional output for product variations

= 1.3 =
* Fixed issue when in some cases single abandoned cart was saved multiple times creating duplicate entries in the table

= 1.2 =
* Fixed minor database warnings and notices

= 1.1 =
* Fixed PHP and MySQL warnings and notices
* Updated security requirements that were introduced in WooCommerce 3.0

= 1.0 =
* Birthday