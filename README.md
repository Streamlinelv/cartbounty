[![CartBounty - Save and recover abandoned carts for WooCommerce](https://ps.w.org/woo-save-abandoned-carts/assets/banner-1544x500.gif "Save abandoned carts")](https://www.cartbounty.com)

# CartBounty - Save and recover abandoned carts for WooCommerce

Save abandoned carts and increase your sales by recovering them. Plugin instantly saves WooCommerce checkout form before submission.

### Description

CartBounty - Save and recover abandoned carts for WooCommerce plugin saves all activity in the [WooCommerce](https://woocommerce.com) checkout form before it is submitted. The plugin allows to see who abandons your shopping carts and get in touch with them. You can also make use of the new Exit Intent popup technology to capture users email and later remind him about his shopping cart.

You will receive regular email notifications about newly abandoned shopping carts and will be able to manually remind about these abandoned carts. You could offer them an additional discount on the cart by sending them a coupon in order to persuade them.

If you would like to send automated abandoned cart recovery emails to customers via [ActiveCampaign](https://www.activecampaign.com/?_r=5347LGDC)*, [GetResponse](https://www.getresponse.com/?a=vPJGRchyVX&c=cartbounty_free_readme)* or [MailChimp](https://mailchimp.com), please visit our [CartBounty Pro - Save and recover abandoned carts for WooCommerce](https://www.cartbounty.com "CartBounty Pro - Save and recover abandoned carts for WooCommerce") plugin page.

#### Plugin basics and features:

* Instantly save WooCommerce checkout field data before submission to save abandoned carts.

* Save and view ghost shopping carts.

* Receive notifications on newly abandoned shopping carts via email. You can set notification frequency or disable them in case you want to take some time off :)

* The "Remember checkout fields" function will allow your customers to refresh the checkout page after entering their information and walk around the page without losing previously entered data in the checkout form. Please note that this feature is enabled only for users who haven't logged in - WooCommerce takes care of this for authorized users.

* If a user is logged in, the shopping cart will be instantly captured as soon as an item is added to the cart. After this, the cart will be instantly updated if it is altered or an item is removed from the cart.

* You can enable Exit Intent popup to offer saving unregistered user's shopping cart for later. With the help of Exit Intent Technology you can capture even more abandoned carts by displaying a message including an email field that the customer can fill to save his shopping cart.
Exit Intent will be triggered as soon as the user tries to leave your shop with a filled shopping cart. If you would like to make it work on mobile devices, please upgrade to our Pro version.
Please note that Exit Intent popup will only be showed to unregistered users once per hour after they have added an item to their shopping cart.

* If the user completes the payment and reaches WooCommerce "Thank you" page, he is removed from the abandoned cart table and the Checkout form fields will be cleared.

* Since we love to make things run smooth, in case if you Uninstall this plugin, it will automatically clean up after itself (delete abandoned cart data, table and options created by this plugin) leaving your project clean.

#### Plugin dependencies:

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

* Please note that this link has been linked under an affiliate marketing program which helps us to support and invest in the future evolution of this plugin since we get a small percentage of earnings for each new ActiveCampaign or GetResponse customer.

### Installation

1. Upload the plugin files to the "/wp-content/plugins/plugin-name" directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the "Plugins" screen in WordPress.
1. Look for the page "CartBounty Abandoned carts" under "WooCommerce" menu - WooCommerce abandoned carts data collected from your checkout form will be saved here unless the user completes the checkout process.
1. Optionally setup Exit Intent notice that will be displayed to unregistered users once per hour in case the user has added items to his shopping cart and tries to leave your shop.

## Frequently Asked Questions

### When is the cart and checkout form data saved?

Data and information about the cart is saved right after the user gets to the Checkout form and one of the following events happen:

* Correct email address is entered
* Phone number is entered
* On Checkout page load if email or phone number input fields are already filled
* Any Checkout form input field with a class "input-text" is entered or changed if a valid Email or Phone number has been entered

If ghost carts have been enabled, the cart will be saved as soon as the user adds an item to his cart. It will remain as a ghost cart until one of the above events has occurred.

In case a user is logged in, the shopping cart will be instantly captured as soon as an item is added to the cart. After this, the cart will be instantly updated if it is altered or an item is removed from the cart.

If a customer completes the checkout process and arrives on the WooCommerce "Thank you" page, the cart is removed from the abandoned cart table and the Checkout form fields are cleared.

### When would a cart be considered as abandoned?

Once the cart is saved, it is considered as abandoned after a period of 60 minutes. Email notifications will be sent out only after the cart is abandoned.

### What are ghost carts, how do they work and how to use them?

Ghost cart is a cart that can’t be identified since the customer has neither signed in your store nor he has entered his email / phone in your checkout form or Exit intent popup.

Any customer who is unidentifiable and adds anything to his shopping cart instantly appears in CartBounty cart list as a ghost shopping cart. If during his shopping journey he adds his details, his ghost cart automatically is turned into a recoverable cart.

There can be many different reasons why you would like to see ghost cart data, here are a couple of ideas:

* Monitor live cart activity in your store and have a better overview of what is happening in your store
* See which products are being placed into shopping carts to know which are products are trending and what your customers are interested in
* Manually analyze which products are being placed into the cart, but not getting purchased
* Knowledge about the potential revenue that is missed out

If you would rather not see ghost carts, you can exclude them from being saved in your CartBounty settings tab.

### How do the email notifications work?

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

### How to send automated abandoned cart recovery emails?

The free version of CartBounty does all the hard work of saving abandoned shopping carts and presenting them to you, it is simple and efficient.
If you would like to [send your visitors automated abandoned cart recovery emails via ActiveCampaign, GetResponse or MailChimp](https://www.cartbounty.com "send your visitors automated abandoned cart recovery emails") please consider supporting our efforts and purchase our Pro version.

[ActiveCampaign](https://www.activecampaign.com/?_r=5347LGDC)* offers exceptional ways to automate your abandoned carts using If/Else statements to create different actions and triggers when to send out emails.
[GetResponse](https://www.getresponse.com/?a=vPJGRchyVX&c=cartbounty_free_readme)* is a beautifully designed email marketing platform to save and recover online abandoned shopping carts.
And [MailChimp](https://mailchimp.com) offers a forever Free plan that you can use to send abandoned cart recovery emails.

### How does Exit Intent Technology work?

If a user tries to leave your shop with an abandoned cart, just before leaving, he will be presented with an additional form that will ask for his email address. Once it is entered (no need to submit the form), user's cart will be automatically captured and labeled as recoverable.

Exit Intent form will be displayed only to unregistered users once per hour. If the user enters his email address either in Exit Intent form or in the Checkout form - popup will no longer be displayed upon leaving your shop.

If you would like to test the visual appearance of Exit Intent, please check the "Enable Test Mode" checkbox. Please note that only users with Admin rights will be able to see Exit Intent during this stage and appearance limits will be removed. This means that it will be showed to the Admin each time he tries to leave the shop.

In case you would like to enable Exit Intent Technology on mobile phones and tablets, please upgrade to [CartBounty Pro - Save and recover abandoned carts for WooCommerce](https://www.cartbounty.com "CartBounty Pro - Save and recover abandoned carts for WooCommerce") version. Mobile Exit Intent will be triggered on mobile devices once the page is quickly scrolled up or if the "Back" button is used.

### How to change the contents of Exit Intent popup?

If you would like to make adjustments to the default contents of Exit Intent, you can use either [actions and filters](https://www.cartbounty.com/#actions-and-filters) that we have provided for you or use our Exit Intent template file to make the necessary adjustments.

You can find the Exit Intent template file inside "/plugins/woo-save-abandoned-carts/templates/cartbounty-exit-intent.php". This template file contains markup required to display the popup and to capture the Abandoned cart prior the user leaves your shop. Please copy this template to your shops theme to keep your customization intact after plugin updates.

You can copy this template file to either one of these locations:

* yourtheme/templates/cartbounty-exit-intent.php
* yourtheme/cartbounty-exit-intent.php

When modifying our template, please do not change the ID #cartbounty-exit-intent-email of the email input field. If changed, the plugin will not be able to capture abandoned carts using Exit Intent form.

### What hooks are available for additional customization?

CartBounty comes with different hooks that make it possible to change some parts or extend the existing functionality of the plugin without modifying core files.

**General hooks**

Filters:

* cartbounty_from_email

Here is an example how to change the From email that sends out notification emails using "cartbounty_from_email" filter. Please add it to your theme's functions.php file:

	function change_from_email( $html ){
		return 'your@email.com';
	}
	add_filter( 'cartbounty_from_email', 'change_from_email' );

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

### How to prevent bots from leaving ghost carts?

If you have noticed unusual amounts of multiple new ghost carts being left almost in the same time, from one country and consisting of a single product, it might be that they are left by bots who are visiting your store.

Bots can be divided into two groups – good ones and bad ones.

* Good bots. The most common example of a good bot could be a web crawler. It is a bot that is sent via a search engine like Google to index your shop. Online store owners generally welcome these bots, because it keeps their content and products visible in the search engine results and hopefully will attract new visitors
* Harmful bots. These bots are visiting your store for malicious purposes. Their actions range from mildly harmful to potentially critical. Bad bots are scanning your store for weak spots, security holes, ways to take over your store, steal your visitor credit card data etc. Besides that, they are also increasing stress on your server thus slowing down your store

Harmful bots are the ones that might be responsible for leaving new ghost carts on your website. While this is not dangerous, it can be frustrating and annoying. Here are three solutions that will help you to deal to with them:

1. The quick solution is to simply disable ghost carts from being saved by CartBounty. You can do this in the CartBounty Settings tab. As easy as this solution is, it only deals with consequences and does not stop these harmful bots from visiting your store, continuously searching for new vulnerabilities and slowing down your shop
1. A better solution would be to install a WordPress plugin that helps to prevent bots from visiting your store. You could try out a couple of different plugins, but this might be a good starting point: [Blackhole for Bad Bots](https://wordpress.org/plugins/blackhole-bad-bots). This way you will block harmful bots from wandering around your store and keep ghost carts enabled to see what your customers are shopping for
1. If you would not like to install a new plugin and you have a developer who is able to help, you could try this solution. At first you will have to find your server access logs and find which of these entries have been left by bots. After that you can use .htaccess file to block these bots from further visits. Here is a good article [Block bad bots](https://perishablepress.com/block-bad-bots) which will provide in depth steps on doing this

### WooCommerce order "Failed", but no abandoned cart saved?

Once a user reaches WooCommerce "Thank you" page - the abandoned cart is automatically removed from the table since the cart is no longer considered as abandoned (regardless of the order status). In this case you can see all of the submitted user data under WooCommerce > Orders.

## Screenshots

![Location of the CartBounty - Save and recover abandoned carts for WooCommerce plugin after activation](https://ps.w.org/woo-save-abandoned-carts/assets/screenshot-1.jpg "Location of the CartBounty - Save and recover abandoned carts for WooCommerce plugin after activation")
![Email notification settings tab](https://ps.w.org/woo-save-abandoned-carts/assets/screenshot-2.jpg "Email notification settings tab")
![Exit Intent popup settings tab](https://ps.w.org/woo-save-abandoned-carts/assets/screenshot-3.jpg "Exit Intent popup settings tab")
![How Exit Intent popup looks like once the user tries to leave the shop](https://ps.w.org/woo-save-abandoned-carts/assets/screenshot-4.gif "How Exit Intent popup looks like once the user tries to leave the shop")

## Changelog

##### 5.0.3

* Added individual product prices in the Cart contents column
* Improved "Remember user input" function for authorized users who edit their account details

##### 5.0.2

* Added filter "cartbounty_from_email" to change the From email address that sends out notifications about abandoned carts
* Added abbreviation to country in abandoned cart table. Hover over country code to view its name
* Added link to user's profile page for registered abandoned cart users in the "Name, Surname" column
* Fixed issue with adding a manual WooCommerce order
* Code cleanup

##### 5.0.1

* CartBounty database table name renamed from "captured_wc_fields" to "cartbounty"

##### 5.0

* Added option to save and view ghost carts
* Added option to filter between ghost and recoverable carts
* Added screen options tab

##### 4.7

* Added option to replace the default Exit Intent image via admin panel
* Minor visual design updates
* Other minor fixes

##### 4.6.1

* Fixed conflict issue with WP Cron schedules

##### 4.6

* Introduced Compact abandoned Cart contents with product thumbnails
* Added Postcode to location output
* Fixed abandoned cart sorting by Name and added sorting by Email and Phone number
* Improved Time column output in a more user friendly way (hover to see get the exact time)

##### 4.5.1

* Improved abandoned cart removal after order completion
* Abandoned cart time calculations changed to local time
* Removed link to product in the Cart contents column in case the product no longer exists

##### 4.5

* Added option to move email field higher in the checkout form
* Changed the script loading hook from "woocommerce_after_checkout_form" to "woocommerce_before_checkout_form"

##### 4.4.1

* Translation files updated

##### 4.4

* Fixed issue when an additional abandoned cart was left after a user logged in
* Added a function that removes duplicate abandoned carts of registered users

##### 4.3.1

* Fixed issue when restoring state field for logged in users

##### 4.3

* Added email notifications about newly abandoned carts
* Added option to set notification frequency or disable notifications
* Added option to set custom email address for notifications

##### 4.2

* Improved function that restores checkout fields after user logged in

##### 4.1

* Fixed Cart content saving if product's title contains HTML tags

##### 4.0

* Baby's got a new name - please welcome CartBounty :) (ex. WooCommerce Live Checkout Field Capture)
* All class names and hooks changed

##### 3.3

* Improved database query security
* Optimized plugin load time
* Minor content updates

##### 3.2.1

* Minor content updates

##### 3.2

* Fixed issue when saving City data for logged in users
* Fixed PHP notices if checkboxes were not defined

##### 3.1

* Added support for Checkout form checkboxes

##### 3.0

* Added Exit Intent popup
* Added Instant shopping cart capture for logged in users
* Fixed total captured abandoned cart counter

##### 2.1

* Added language support
* Improved review bubble

##### 2.0.6

* Improved review bubble

##### 2.0.5

* Improved bubble display timing function

##### 2.0.4

* Fixed PHP notice and a bug when working with WooCommerce orders within admin panel

##### 2.0.3

* Updated Bubble timing function

##### 2.0.2

* Fixed bug with Checkout form textarea field

##### 2.0.1

* Modified "Remember user input" function. All Checkout form input fields are now triggering save data action

##### 2.0

* Added "Remember user input" function that keeps user input in Checkout form until the Session has expired or user completes the Checkout
* PHP default sessions functionality replaced by WooCommerce sessions

##### 1.5.2

* Added additional hook for removing abandoned cart from the table once a corresponding WooCommerce order is created

##### 1.5.1

* Added ability for Shop managers to access Abandoned carts

##### 1.5

* Added ability to save abandoned carts via phone number input
* Added function that collects and saves input field data if input fields already filled on Checkout page load

##### 1.4.3

* Fixed bug when in some cases abandoned carts not being removed from table after reaching WooCommerce "Thank you" page

##### 1.4.2

* Fixed bug related to notification output

##### 1.4.1

* Fixed database update issue when upgrading to 1.4

##### 1.4

* Added notification near menu about newly abandoned carts (last 2 hours)
* Added location registration (Country and City)
* Added links on product titles in Cart content column
* Added additional output for product variations

##### 1.3

* Fixed issue when in some cases single abandoned cart was saved multiple times creating duplicate entries in the table

##### 1.2

* Fixed minor database warnings and notices

##### 1.1

* Fixed PHP and MySQL warnings and notices
* Updated security requirements that were introduced in WooCommerce 3.0

##### 1.0

* Birthday