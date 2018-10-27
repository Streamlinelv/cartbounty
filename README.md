[![WooCommerce Live Checkout Field Capture - save abandoned carts](https://ps.w.org/woo-save-abandoned-carts/assets/banner-1544x500.png "Save abandoned carts")](http://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro)

# WooCommerce Live Checkout Field Capture - Save Abandoned Carts

Save Abandoned Carts and increase your sales by recovering them. Plugin instantly saves WooCommerce checkout field data before submission.

### Description

WooCommerce Live Checkout Field Capture plugin saves all activity in the [WooCommerce](https://woocommerce.com) checkout form before it is submitted. The plugin allows to see who abandons your shopping carts and get in touch with them.

You will be able to manually contact your visitors and remind about the abandoned cart. You could offer them an additional discount on the cart by sending them a coupon in order to persuade them.

If you would like to receive email notifications about abandoned carts and send automated abandoned cart recovery emails to customers, please visit our [WooCommerce Live Checkout Field Capture Pro - save abandoned carts](http://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro "WooCommerce Live Checkout Field Capture Pro - save abandoned carts") plugin version.

The "Remember checkout fields" function will allow your customers to refresh the checkout page after entering their information and walk around the page without losing previously entered data in the checkout form.

If the user completes the payment and receives a "Thank you" page, he is removed from the Checkout Field Capture table and the Checkout form fields will be cleared.

Since I love to make things run smooth, in case if you Uninstall this plugin, it will automatically clean up after itself (delete abandoned carts data, table and options created by this plugin) leaving your project clean.

I started working on this plugin since WooCommerce currently does not come with an integrated solution for recovering abandoned carts and I wanted to develop one myself that would be very simple and lightweight. I built this plugin in order to register and recover abandoned carts in a website that sells [light cube](http://www.uniqcube.com/shop "light cube") lamps since there were many people who left the checkout process.

At the time when I started working on this project I knew that there were couple of plugins already available but they were offering a lot of functionality that slowed down my project and had many features that were not necessary. Also I wasn’t sure about the security that they provided and I wanted to contribute to WordPress community by helping others with the same need to see abandoned carts and recover them.

While continuing to be working on [Mājas lapu izstrāde](http://www.majas-lapu-izstrade.lv "Mājas lapu izstrāde") (website design and development) I will be managing this plugin in order to keep up with the WooCommerce and WordPress updates.

Note: If the fields are added outside of Checkout page or Checkout page input field ID values are changed, the plugin will not be able to load data.
Input field ID values should be default:

* \#billing_first_name
* \#billing_last_name
* \#billing_company
* \#billing_email
* \#billing_phone
* etc.

Since version 2.0.1 plugin also uses WooCommerce Checkout form input field class "input-text" in order to trigger save action from all form fields.

If WordPress changes the location of "admin-ajax.php" file, then will have to update it.

Plugin dependencies:

1. Uses WordPress private WP_List_Table class in order to output the table in the admin section. If this class changes, the table and all of its functions might break.
1. WooCommerce hooks
1. WooCommerce session

### Installation

1. Upload the plugin files to the "/wp-content/plugins/plugin-name" directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the "Plugins" screen in WordPress.
1. Look for the page "Checkout Field Capture" under "WooCommerce" menu - WooCommerce abandoned carts data collected from your checkout form will be saved here unless the user completes the checkout process.

## Frequently Asked Questions

### When is the cart and checkout form input field data saved?

Data and information about the cart is saved right after the user gets to the Checkout form and one of the following events happen:

* Correct email address is entered
* Phone number is entered
* On Checkout page load if email or phone number input fields are already filled
* Any Checkout form input field with a class "input-text" is entered or changed if a valid Email or Phone number has been entered

If the user completes the checkout process and receives a "Thank you" page, the cart is removed from the Checkout Field Capture table of abandoned carts and the Checkout form fields are cleared.

### Where can I view WooCommerce abandoned carts?

After installation the plugin will be available under "WooCommerce" menu. Please see 1st screenshot.

### WooCommerce shows order status "Failed" but I don't see an abandoned cart.

Once user reaches the "Thank you" page the abandoned cart is automatically removed from the table since the cart is no longer considered as abandoned (regardless of the order status). In this case you can see all of the submitted user data under WooCommerce > Orders.

### How to enable email notifications about abandoned carts?

This version does all the hard work of collecting the data and presenting it to you, it is simple and efficient. You will have to manually check newly abandoned carts. If you would like to receive automated email notifications, please visit our Pro [WooCommerce save abandoned carts](http://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro "WooCommerce save abandoned carts") plugin version.

### How to send automated abandoned cart recovery emails?

The free version of our plugin allows collecting abandoned carts and you will be able to get in touch with your visitors manually.
If you would like to [send your visitors automated abandoned cart recovery emails](http://majas-lapu-izstrade.lv/woocommerce-save-abandoned-carts-pro "send your visitors automated abandoned cart recovery emails") please take a look at our Pro version.

## Screenshots

![Location of the WooCommerce Live Checkout Field Capture - save abandoned carts plugin after activation](https://ps.w.org/woo-save-abandoned-carts/assets/screenshot-1.png "Location of the WooCommerce Live Checkout Field Capture - save abandoned carts plugin after activation")

## Changelog

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

##### 2.0.0

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