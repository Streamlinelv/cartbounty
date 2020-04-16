=== CartBounty - Save and recover abandoned carts for WooCommerce ===
Donate link: https://www.cartbounty.com
Contributors: streamlinestar, nauriskolats
Tags: woocommerce, abandoned carts, cart abandonment, exit popup, activecampaign
Requires at least: 4.6
Requires PHP: 5.2.4
Tested up to: 5.4
Stable tag: 4.4.1
License: GPLv2 or later

Save abandoned carts and increase your sales by recovering them. Plugin instantly saves WooCommerce checkout form before submission.

== Description ==

CartBounty - Save and recover abandoned carts for WooCommerce plugin saves all activity in the [WooCommerce](https://woocommerce.com) checkout form before it is submitted and sends notifications on newly abandoned carts. The plugin allows to see who abandons your shopping carts and get in touch with them. You can also make use of the new Exit Intent popup technology to capture users email and later remind him about his shopping cart.

You will receive regular email notifications about newly abandoned shopping carts and will be able to manually remind about these abandoned carts. You could offer them an additional discount on the cart by sending them a coupon in order to persuade them.

If you would like to send automated abandoned cart recovery emails to customers via [MailChimp](https://mailchimp.com) or [ActiveCampaign](https://www.activecampaign.com/?_r=5347LGDC)*, please visit our [CartBounty Pro - Save and recover abandoned carts for WooCommerce](https://www.cartbounty.com "CartBounty Pro - Save and recover abandoned carts for WooCommerce") plugin version.

[youtube https://www.youtube.com/watch?v=Sb4DpkDilw0]

### Plugin basics and features:

* Instantly capture WooCommerce checkout field data before submission to save abandoned carts.

* Receive notifications on newly abandoned shopping carts via email. You can set notification frequency or disable them in case you want to take some time off :)

* The "Remember checkout fields" function will allow your customers to refresh the checkout page after entering their information and walk around the page without losing previously entered data in the checkout form. Please note that this feature is enabled only for users who haven't logged in - WooCommerce takes care of this for authorized users.

* If a user is logged in, the shopping cart will be instantly captured as soon as an item is added to the cart. After this, the cart will be instantly updated if it is altered or an item is removed from the cart.

* You can enable Exit Intent popup to offer saving unregistered user's shopping cart for later. With help of the Exit Intent Technology you can capture even more abandoned carts by displaying a message including an email field that the customer can fill to save his shopping cart.
The Exit Intent will be triggered as soon as the user tries to leave your shop with a filled shopping cart. If you would like to make it work on mobile devices, please upgrade to our Pro version.
Please note that the Exit Intent will only be showed to unregistered users once per hour after they have added an item to their shopping cart.

* If the user completes the payment and receives a "Thank you" page, he is removed from the abandoned cart table and the Checkout form fields will be cleared.

* Since we love to make things run smooth, in case if you Uninstall this plugin, it will automatically clean up after itself (delete abandoned carts data, table and options created by this plugin) leaving your project clean.

### How the idea was born:

We started working on this plugin since WooCommerce currently does not come with an integrated solution for recovering abandoned carts and wanted to develop one that would be very simple and lightweight. We built this plugin in order to register and recover abandoned carts in a website that sells [light cube](https://www.uniqcube.com/shop "light cube") lamps since there were many people who left the checkout process.

At the time when we started working on this project we knew that there were couple of plugins already available but they were offering a lot of functionality that slowed down our project and had many features that were not necessary. Also we weren't sure about the security that they provided and wanted to contribute to WordPress community by helping others with the same need to simply see abandoned carts and recover them.

While continuing our work on [Mājas lapu izstrāde](https://www.majas-lapu-izstrade.lv "Mājas lapu izstrāde") (website design and development) we will be managing this plugin in order to keep up with the WooCommerce and WordPress updates.

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

*Please note that this link to ActiveCampaign has been linked under an affiliate marketing program which helps us to support and invest in the future evolution of this plugin since we get a small percentage of earnings for each new ActiveCampaign customer.

== Installation ==

1. Upload the plugin files to the "/wp-content/plugins/plugin-name" directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the "Plugins" screen in WordPress.
1. Look for the page "CartBounty Abandoned carts" under "WooCommerce" menu - WooCommerce abandoned carts data collected from your checkout form will be saved here unless the user completes the checkout process.
1. Optionally setup the Exit Intent notice that will be displayed to unregistered users once per hour in case the user has added items to his shopping cart and tries to leave your shop.

== Frequently Asked Questions ==

= When is the cart and checkout form input field data saved? =

Data and information about the cart is saved right after the user gets to the Checkout form and one of the following events happen:

* Correct email address is entered
* Phone number is entered
* On Checkout page load if email or phone number input fields are already filled
* Any Checkout form input field with a class "input-text" is entered or changed if a valid Email or Phone number has been entered

If the user completes the checkout process and receives a "Thank you" page, the cart is removed from the abandoned cart table and the Checkout form fields are cleared.

= Where can I view WooCommerce abandoned carts? =

After installation the plugin will be available under "WooCommerce" menu. Please see 1st screenshot.

= WooCommerce shows order status "Failed" but I don't see an abandoned cart. =

Once user reaches the "Thank you" page the abandoned cart is automatically removed from the table since the cart is no longer considered as abandoned (regardless of the order status). In this case you can see all of the submitted user data under WooCommerce > Orders.

= How to enable email notifications about abandoned carts? =

Once the cart is saved and is considered as abandoned (after 1 hour of inactivity), you will get a notification about it in your email. You will not be notified about previously abandoned carts.

You can set the following notification intervals:

* Every 10 minutes
* Every 20 minutes
* Every 30 minutes
* Every hour
* Every 2 hours
* Every 3 hours
* Every 4 hours
* Every 5 hours
* Every 6 hours
* Twice a day
* Once a day
* Once every 2 days
* Disable notifications

Default notification interval is “Every hour”. You also have the option to disable notifications in case you ever get tired of them ;)

Please also note how WordPress handles Cron job that is responsible for sending out email notifications. Scheduled actions can only be triggered when a visitor arrives on a WordPress site. Therefore, if you are not getting any traffic on your website you will not receive any e-mail notifications until a visitor lands on your website.

= How to send automated abandoned cart recovery emails? =

The free version of CartBounty does all the hard work of saving abandoned shopping carts and presenting them to you, it is simple and efficient.
If you would like to [send your visitors automated abandoned cart recovery emails via MailChimp or ActiveCampaign](https://www.cartbounty.com "send your visitors automated abandoned cart recovery emails") please consider supporting our efforts and purchase our Pro version.

[MailChimp](https://mailchimp.com) offers a forever Free plan that you can use to send abandoned cart recovery emails.
And [ActiveCampaign](https://www.activecampaign.com/?_r=5347LGDC)* offers exceptional ways to automate your abandoned carts using If/Else statements to create different actions and triggers when to send out emails.

= How does the Exit Intent Technology work? =

If a user tries to leave your shop with an abandoned cart, just before leaving, he will be presented with an additional form that will ask for his email address. Once it is entered (no need to submit the form), user's cart will be automatically captured.

Exit Intent form will be displayed only to unregistered users once per hour. If the user enters his email address either in the Exit Intent form or in the Checkout form - Exit Intent will no longer be displayed upon leaving your shop.

If you would like to test the visual appearance of the Exit Intent, please check the "Enable Test Mode" checkbox. Please note that only users with Admin rights will be able to see the Exit Intent during this stage and appearance limits will be removed. This means that it will be showed to the Admin each time he tries to leave the shop.

In case you would like to enable Exit Intent Technology on mobile phones and tablets, please upgrade to [CartBounty Pro - Save and recover abandoned carts for WooCommerce](https://www.cartbounty.com "CartBounty Pro - Save and recover abandoned carts for WooCommerce") version. Mobile Exit Intent will be triggered on mobile devices once the page is quickly scrolled up or if the "Back" button is used.

= How to change the content and image of the Exit Intent? =

If you would like to make adjustments to the default content of the Exit Intent, you can use either [action hooks and filters](https://www.cartbounty.com/#actions-and-filters) that we have provided for you or use our Exit Intent template file to make the necessary adjustments.

You can find the Exit Intent template file inside "/plugins/woo-save-abandoned-carts/templates/cartbounty-exit-intent.php". This template file contains the markup to display the Exit Intent and to capture the Abandoned cart prior the user leaves your shop. Please copy this template to your shops theme to keep your customization intact after plugin updates.

You can copy this template file to either one of these locations:

* yourtheme/templates/cartbounty-exit-intent.php
* yourtheme/cartbounty-exit-intent.php

When modifying our template, please do not change the ID #cartbounty-exit-intent-email of the email input field. If changed, the plugin will not be able to capture abandoned carts using the Exit Intent form.

= What action hooks and filters are available for additional customization? =

Our Exit Intent template contains different action hooks and filters that allow you to create new, edit, replace or remove existing content including the main image in the Exit Intent window.

Available action hooks:

* cartbounty_exit_intent_start
* cartbounty_exit_intent_after_title
* cartbounty_exit_intent_before_form_fields
* cartbounty_exit_intent_end

Available filters:

* cartbounty_exit_intent_close_html
* cartbounty_exit_intent_image_html
* cartbounty_exit_intent_title_html
* cartbounty_exit_intent_description_html
* cartbounty_exit_intent_email_label_html
* cartbounty_exit_intent_email_field_html
* cartbounty_exit_intent_button_html

Here is an example how to add additional subtitle after the main title using our "cartbounty_exit_intent_after_title" action hook. Please add it to your theme's functions.php file:

	function add_extra_html_after_title() {
    	echo "<p>Additional subtitle here...</p>";
	}
	add_action('cartbounty_exit_intent_after_title', 'add_extra_html_after_title' );

Example how to change the main image using a filter:

	function modify_image( $html ){
		return '<img src="http://www.link-to-your-custom-image-here..."/>';
	}
	add_filter( 'cartbounty_exit_intent_image_html', 'modify_image' );

Example how to change the main title using a filter:

	function modify_title( $html ) {
		$custom_title = 'Your text here...';
		return preg_replace('#(<h2[^>]*>).*?(</h2>)#', "$1 $custom_title $2", $html);
	}
	add_filter( 'cartbounty_exit_intent_title_html', 'modify_title' );

== Screenshots ==

1. Location of the CartBounty - Save and recover abandoned carts for WooCommerce plugin after activation
2. Email notification settings tab
3. The Exit Intent popup settings tab
4. How the Exit Intent popup looks like once the user tries to leave the shop

== Changelog ==

= 4.4.1 =
* Translation files updated

= 4.4 =
* Fixed issue when an additional abandoned cart was left after a user logged in
* Added a function that removes duplicate abandoned carts of registered users

= 4.3.1 =
* Fixed issue when restoring state field for logged in users

= 4.3 =
* Added email notifications about newly abandoned carts
* Added option to set notification frequency or disable notifications
* Added option to set custom email address for notifications

= 4.2 =
* Improved function that restores checkout fields after user logged in

= 4.1 =
* Fixed cart content saving if product's title contains HTML tags

= 4.0 =
* Baby's got a new name - please welcome CartBounty :) (ex. WooCommerce Live Checkout Field Capture)
* All class names and hooks changed

= 3.3 =
* Improved database query security
* Optimized plugin load time
* Minor content updates

= 3.2.1 =
* Minor content updates

= 3.2 =
* Fixed issue when saving City data for logged in users
* Fixed PHP notices if checkboxes were not defined

= 3.1 =
* Added support for Checkout form checkboxes

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