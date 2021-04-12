=== CartBounty - Save and recover abandoned carts for WooCommerce ===
Donate link: https://www.cartbounty.com
Contributors: streamlinestar, nauriskolats
Tags: woocommerce, abandoned carts, cart abandonment, exit popup, activecampaign
Requires at least: 4.6
Requires PHP: 5.2.4
Tested up to: 5.7
Stable tag: 7.0.2
License: GPLv3

Save abandoned carts and increase your sales by recovering them. Plugin instantly saves WooCommerce checkout form before submission.

== Description ==

CartBounty - Save and recover abandoned carts for WooCommerce plugin saves all activity in the [WooCommerce](https://woocommerce.com) checkout form before it is submitted. The plugin allows to see who abandons your shopping carts and get in touch with them.

You will receive regular email notifications about newly abandoned shopping carts and will be able to remind about these carts either manually or using WordPress default mail server to send automated abandoned cart recovery emails.

If you would like to send automated abandoned cart recovery emails to customers via [ActiveCampaign](https://www.activecampaign.com/?_r=5347LGDC)*, [GetResponse](https://www.getresponse.com/?a=vPJGRchyVX&c=cartbounty_free_readme)* or [MailChimp](https://mailchimp.com), please visit our [CartBounty Pro - Save and recover abandoned carts for WooCommerce](https://www.cartbounty.com "CartBounty Pro - Save and recover abandoned carts for WooCommerce") plugin page.

[youtube https://www.youtube.com/watch?v=Sb4DpkDilw0]

### Plugin basics and features:

* Instantly save WooCommerce checkout field data before submission to save abandoned carts.

* Save and view ghost shopping carts.

* Receive notifications on newly abandoned shopping carts via email. You can set notification frequency or disable them in case you want to take some time off :)

* The "Remember checkout fields" function will allow your customers to refresh the checkout page after entering their information and walk around the page without losing previously entered data in the checkout form. Please note that this feature is enabled only for users who haven't logged in - WooCommerce takes care of this for authorized users.

* If a user is logged in, the shopping cart will be instantly captured as soon as an item is added to the cart. After this, the cart will be instantly updated if it is altered or an item is removed from the cart.

* You can enable Exit Intent popup to offer saving unregistered user's shopping cart for later. With the help of Exit Intent Technology, you can capture even more abandoned carts by displaying a popup offering to save customer’s cart if he provides his email.
Exit Intent will be triggered as soon as the user tries to leave your shop with a filled shopping cart. If you would like to make it work on mobile devices, please upgrade to our Pro version.
Please note that Exit Intent popup will only be showed to unregistered users once every 60 minutes after they have added an item to their shopping cart.
Please do check out our Pro version if you are interested in Early email capture feature that will allow collecting customer’s email or phone right after the customer tries to add an item to the cart using "Add to cart" button.

* If the user completes the payment and reaches WooCommerce "Thank you" page, he is removed from the abandoned cart table and the Checkout form fields will be cleared. In case the user returns to his abandoned shopping cart via abandoned cart recovery email and places an order - the cart will be marked as "recovered" and will remain in the list of carts.

* Since we love to make things run smooth, in case if you Uninstall this plugin, it will automatically clean up after itself (delete abandoned cart data, table and options created by this plugin) leaving your project nice and clean.

### Plugin dependencies:

1. Uses WordPress private WP_List_Table class
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

*Please note that this link has been linked under an affiliate marketing program which helps us to support and invest in the future evolution of this plugin since we get a small percentage of earnings for each new ActiveCampaign or GetResponse customer.

== Installation ==

1. Upload the plugin files to the "/wp-content/plugins/plugin-name" directory or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the "Plugins" screen in WordPress.
1. Look for the page "CartBounty Abandoned carts" under "WooCommerce" menu - WooCommerce abandoned carts data collected from your checkout form will be saved here unless the user completes the checkout process.
1. Optionally setup automated abandoned cart recovery emails via WordPress recovery settings.
1. Optionally enable additional productivity Tools like Exit Intent which will allow you to increase the ratio of recoverable abandoned carts.

== Frequently Asked Questions ==

= When is the cart and checkout form data saved? =

Data and information about the cart is saved right after the user gets to the Checkout form and one of the following events happen:

* Correct email address is entered
* Phone number is entered
* On Checkout page load if email or phone number input fields are already filled
* Any Checkout form input field with a class "input-text" is entered or changed if a valid Email or Phone number has been entered

If ghost carts have been enabled, the cart will be saved as soon as the user adds an item to his cart. It will remain as a ghost cart until one of the above events has occurred.

In case a user is logged in, the shopping cart will be instantly captured as soon as an item is added to the cart. After this, the cart will be instantly updated if it is altered or an item is removed from the cart.

If a customer completes the checkout process and arrives on the WooCommerce "Thank you" page, the cart is removed from the abandoned cart table and the Checkout form fields are cleared.

= When would a cart be considered as abandoned? =

Once the cart is saved, it is considered as abandoned after a period of 60 minutes. Email notifications will be sent out only after the cart is abandoned.

= How to send automated abandoned cart recovery emails? =

The free version of CartBounty offers a basic solution for sending abandoned cart recovery emails using the default WordPress mail server. This recovery option works best if you have a small to medium number of abandoned carts.

If your emails are not reaching your recipients or they end up in the spam box, you might try switching from your default WordPress mail server to an SMTP. To do this just install one of the available WordPress SMTP plugins available in the [WordPress plugin directory](https://wordpress.org/plugins/).

If you would like to [send your visitors automated abandoned cart recovery emails via ActiveCampaign, GetResponse or MailChimp](https://www.cartbounty.com "send your visitors automated abandoned cart recovery emails") please consider supporting our efforts and purchase our Pro version.

[ActiveCampaign](https://www.activecampaign.com/?_r=5347LGDC)* offers exceptional ways to automate your abandoned carts using If/Else statements to create different actions and triggers when to send out emails.
[GetResponse](https://www.getresponse.com/?a=vPJGRchyVX&c=cartbounty_free_readme)* is a beautifully designed email marketing platform to save and recover online abandoned shopping carts.
And [MailChimp](https://mailchimp.com) offers a forever Free plan that you can use to send abandoned cart recovery emails.

If you would like to enable additional WordPress recovery email features and add multiple language support, please consider [upgrading to Pro](https://www.cartbounty.com "CartBounty Pro - Save and recover abandoned carts for WooCommerce").

= What are ghost carts, how do they work and how to use them? =

Ghost cart is a cart that can’t be identified since the customer has neither signed in your store nor he has entered his email / phone in your checkout form or Exit intent popup.

Any customer who is unidentifiable and adds anything to his shopping cart instantly appears in CartBounty cart list as a ghost shopping cart. If during his shopping journey he adds his details, his ghost cart automatically is turned into a recoverable cart.

There can be many different reasons why you would like to see ghost cart data, here are a couple of ideas:

* Monitor live cart activity in your store and have a better overview of what is happening in your store
* See which products are being placed into shopping carts to know which products are trending and what your customers are interested in
* Manually analyze which products are being placed into the cart, but not getting purchased
* Knowledge about the potential revenue that is missed out

If you would rather not see ghost carts, you can exclude them from being saved in your CartBounty settings tab.

= How do the email notifications work? =

Once the cart is saved and is considered as abandoned, you will receive a notification about it in your email. You will not be notified about previously abandoned carts.

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

Default notification interval is "Every hour". You also have the option to disable notifications in case you ever get tired of them ;)

Please also note how WordPress handles Cron job that is responsible for sending out email notifications. Scheduled actions can only be triggered when a visitor arrives on a WordPress site. Therefore, if you are not getting any traffic on your website you will not receive any email notifications until a visitor lands on your website.

By default, notifications will be sent to WordPress registered admin email. But you can also set a different email address.

= How does Exit Intent Technology work? =

If a user tries to leave your shop with an abandoned cart, just before leaving, he will be presented with an additional form that will ask for his email address. Once it is entered (no need to submit the form), user's cart will be automatically captured and labeled as recoverable.

Exit Intent form will be displayed only to unregistered users once per hour. If the user enters his email address either in Exit Intent form or in the Checkout form - popup will no longer be displayed upon leaving your shop.

If you would like to test the visual appearance of Exit Intent, please check the "Enable Test Mode" checkbox. Please note that only users with Admin rights will be able to see Exit Intent during this stage and appearance limits will be removed. This means that it will be showed to the Admin each time he tries to leave the shop.

In case you would like to enable Exit Intent Technology on mobile phones and tablets, please upgrade to [CartBounty Pro - Save and recover abandoned carts for WooCommerce](https://www.cartbounty.com "CartBounty Pro - Save and recover abandoned carts for WooCommerce") version. Mobile Exit Intent will be triggered on mobile devices once the page is quickly scrolled up or if the "Back" button is used.

= How to use CartBounty templates for Advanced customization? =

Public sections of the plugin can be quickly and easily styled using plugin settings. However, if you are looking for a more customized appearance, you can use template files that come along with CartBounty or take a look at [actions and filters](https://www.cartbounty.com/#actions-and-filters).

You can find all template files inside "/plugins/woo-save-abandoned-carts/templates". The template files contain markup required to present the data. Please copy this template to your active theme to keep your customizations intact after plugin updates.

You can copy template files to either one of these locations:

* yourtheme/templates/emails/cartbounty-email-light.php
* yourtheme/templates/cartbounty-exit-intent.php
* yourtheme/cartbounty-exit-intent.php

When modifying our template, please do not change the ID #cartbounty-exit-intent-email of the email input field. If changed, the plugin will not be able to capture abandoned carts using Exit Intent form.

= What hooks are available for additional customization? =

CartBounty comes with different hooks that make it possible to change some parts or extend the existing functionality of the plugin without modifying core files.

**General hooks**

Filters:

* cartbounty_from_email
* cartbounty_waiting_time
* cartbounty_include_tax

Here is an example how to change the From email that sends out notification emails using "cartbounty_from_email" filter. Please add it to your theme's functions.php file:

	function change_from_email( $html ){
		return 'your@email.com';
	}
	add_filter( 'cartbounty_from_email', 'change_from_email' );

Example how to customize default waiting time after which the cart is considered abandoned using "cartbounty_waiting_time" filter from 60 minutes (default time) to 30 minutes. Add it to your theme's functions.php file:

	function change_waiting_time( $minutes ){
		return 30; //Minimum allowed time is 20 minutes
	}
	add_filter( 'cartbounty_waiting_time', 'change_waiting_time' );

Example how to display abandoned cart product prices excluding taxes:

	add_filter( 'cartbounty_include_tax', '__return_false' );

**Exit Intent hooks**

Exit Intent template contains different actions and filters that allow you to create new, edit, replace or remove existing content including the main image in Exit Intent window.

Actions:

* cartbounty_exit_intent_start
* cartbounty_exit_intent_after_title
* cartbounty_exit_intent_before_form_fields
* cartbounty_exit_intent_end

Filters:

* cartbounty_exit_intent_close_html
* cartbounty_exit_intent_image_html
* cartbounty_exit_intent_title_html
* cartbounty_exit_intent_description_html
* cartbounty_exit_intent_field_html
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

Example how to change the description using a filter:

	function modify_description( $html ){
		$custom_description = 'New description here...';
		return preg_replace('#(<p[^>]*>).*?(</p>)#', "$1 $custom_description $2", $html);
	}
	add_filter( 'cartbounty_exit_intent_description_html', 'modify_description' );

**WordPress email hooks**

WordPress abandoned cart reminder template uses multiple actions and filters which can be used to alter the contents an appearance of the email.

Actions:

* cartbounty_automation_before_title
* cartbounty_automation_after_title
* cartbounty_automation_after_intro
* cartbounty_automation_after_button
* cartbounty_automation_footer_start
* cartbounty_automation_footer_end

Filters:

* cartbounty_automation_title_html
* cartbounty_automation_intro_html
* cartbounty_automation_button_html
* cartbounty_automation_copyright
* cartbounty_automation_footer_address_1
* cartbounty_automation_footer_address_2
* cartbounty_automation_unsubscribe_html

Example how to add additional content right before the main title in WordPress recovery reminder email:

	function cartbounty_automation_add_extra_title(){
	    esc_html_e( 'Additional content before main title', 'woo-save-abandoned-carts' );
	}
	add_action( 'cartbounty_automation_before_title', 'cartbounty_automation_add_extra_title' );

An example how to use a filter to alter the main title:

	function cartbounty_alter_automation_title( $title ){
	    return '<h1 style="font-size: 60px; padding-bottom: 30px;">'. __('My new title', 'woo-save-abandoned-carts') .'</h1>';
	}
	add_filter( 'cartbounty_automation_title_html', 'cartbounty_alter_automation_title' );

Example how to replace existing button name from "Complete checkout" to "Return to cart":

	function cartbounty_alter_automation_button( $button ){
	    return str_replace( 'Complete checkout', __('Return to cart', 'woo-save-abandoned-carts') , $button);
	}
	add_filter( 'cartbounty_automation_button_html', 'cartbounty_alter_automation_button' );

How to change the default footer address. By default, it is taken from WooCommerce store address you have entered, but you can change it using a filter:

	function cartbounty_alter_automation_footer_address_1( $address ){
	    esc_html_e('First address line...', 'woo-save-abandoned-carts');
	}
	add_filter( 'cartbounty_automation_footer_address_1', 'cartbounty_alter_automation_footer_address_1' );

	function cartbounty_alter_automation_footer_address_2( $address ){
	    esc_html_e('Second address line...', 'woo-save-abandoned-carts');
	}
	add_filter( 'cartbounty_automation_footer_address_2', 'cartbounty_alter_automation_footer_address_2' );

= How to prevent bots from leaving ghost carts? =

If you have noticed unusual amounts of multiple new ghost carts being left almost at the same time, from one country and consisting of a single product, it might be that they are left by bots who are visiting your store.

Bots can be divided into two groups – good ones and bad ones.

* Good bots. The most common example of a good bot could be a web crawler. It is a bot that is sent via a search engine like Google to index your shop. Online store owners generally welcome these bots, because it keeps their content and products visible in the search engine results and hopefully will attract new visitors
* Harmful bots. These bots are visiting your store for malicious purposes. Their actions range from mildly harmful to potentially critical. Bad bots are scanning your store for weak spots, security holes, ways to take over your store, steal your visitor credit card data etc. Besides that, they are also increasing stress on your server thus slowing down your store

Harmful bots are the ones that might be responsible for leaving new ghost carts on your website. While this is not dangerous, it can be frustrating and annoying. Here are three solutions that will help you to deal to with them:

1. The quick solution is to simply disable ghost carts from being saved by CartBounty. You can do this in the CartBounty Settings tab. As easy as this solution is, it only deals with consequences and does not stop these harmful bots from visiting your store, continuously searching for new vulnerabilities and slowing down your shop
1. A better solution would be to install a WordPress plugin that helps to prevent bots from visiting your store. You could try out a couple of different plugins, but this might be a good starting point: [Blackhole for Bad Bots](https://wordpress.org/plugins/blackhole-bad-bots). This way you will block harmful bots from wandering around your store and keep ghost carts enabled to see what your customers are shopping for
1. If you would not like to install a new plugin and you have a developer who is able to help, you could try this solution. At first you will have to find your server access logs and find which of these entries have been left by bots. After that you can use .htaccess file to block these bots from further visits. Here is a good article on [how to block bad bots](https://www.seoblog.com/block-bots-spiders-htaccess) which will provide more about this topic

In addition, the Pro version allows you to select if guests from specific countries should be able to leave ghost carts thus making sure that bots coming from countries you do not sell to are not able to leave ghost carts.

= WooCommerce order "Failed", but no abandoned cart saved? =

Once a user reaches WooCommerce "Thank you" page - the abandoned cart is automatically removed from the table since the cart is no longer considered as abandoned (regardless of the order status). In this case you can see all of the submitted user data under WooCommerce > Orders.

== Screenshots ==

1. Location of CartBounty after activation
2. Automated abandoned cart recovery using WordPress email reminders
3. WordPress recovery email settings
4. WordPress recovery email preview
5. Exit Intent popup settings tab
6. General settings tab
7. How Exit Intent popup looks like once the user tries to leave the shop

== Changelog ==

= 7.0.2 =
* Abandoned cart contents will now display prices including taxes. Use "cartbounty_include_tax" filter to disable it
* Improved WordPress recovery input field content options

[See changelog for all versions](https://raw.githubusercontent.com/Streamlinelv/woo-save-abandoned-carts/master/changelog.txt).