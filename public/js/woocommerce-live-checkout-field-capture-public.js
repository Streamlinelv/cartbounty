(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 
	 jQuery(document).ready(function(){

		var timer;

		function getCheckoutData() { //Reading WooCommerce field values
			//var wlcfc_email = this . value;
			var wlcfc_phone = jQuery("#billing_phone").val();
			var wlcfc_email = jQuery("#billing_email").val();
			var atposition = wlcfc_email.indexOf("@");
			var dotposition = wlcfc_email.lastIndexOf(".");

			if (typeof wlcfc_phone === 'undefined' || wlcfc_phone === null) { //If phone number field does not exist on the Checkout form
			   wlcfc_phone = '';
			}
			
			clearTimeout(timer);

			if (!(atposition < 1 || dotposition < atposition + 2 || dotposition + 2 >= wlcfc_email.length) || wlcfc_phone.length >= 1){ //Checking if the email field is valid or phone number is longer than 1 digit
				//If Email or Phone valid
				var wlcfc_name = jQuery("#billing_first_name").val();
				var wlcfc_surname = jQuery("#billing_last_name").val();
				var wlcfc_phone = jQuery("#billing_phone").val();
				var wlcfc_country = jQuery("#billing_country").val();
				var wlcfc_city = jQuery("#billing_city").val();
				
				//Other fields used for "Remember user input" function
				var wlcfc_billing_company = jQuery("#billing_company").val();
				var wlcfc_billing_address_1 = jQuery("#billing_address_1").val();
				var wlcfc_billing_address_2 = jQuery("#billing_address_2").val();
				var wlcfc_billing_state = jQuery("#billing_state").val();
				var wlcfc_billing_postcode = jQuery("#billing_postcode").val();
				var wlcfc_shipping_first_name = jQuery("#shipping_first_name").val();
				var wlcfc_shipping_last_name = jQuery("#shipping_last_name").val();
				var wlcfc_shipping_company = jQuery("#shipping_company").val();
				var wlcfc_shipping_country = jQuery("#shipping_country").val();
				var wlcfc_shipping_address_1 = jQuery("#shipping_address_1").val();
				var wlcfc_shipping_address_2 = jQuery("#shipping_address_2").val();
				var wlcfc_shipping_city = jQuery("#shipping_city").val();
				var wlcfc_shipping_state = jQuery("#shipping_state").val();
				var wlcfc_shipping_postcode = jQuery("#shipping_postcode").val();
				var wlcfc_order_comments = jQuery("#order_comments").val();
				
				var data = {
					action:						"save_data",
					wlcfc_email:				wlcfc_email,
					wlcfc_name:					wlcfc_name,
					wlcfc_surname:				wlcfc_surname,
					wlcfc_phone:				wlcfc_phone,
					wlcfc_country:				wlcfc_country,
					wlcfc_city:					wlcfc_city,
					wlcfc_billing_company:		wlcfc_billing_company,
					wlcfc_billing_address_1:	wlcfc_billing_address_1,
					wlcfc_billing_address_2: 	wlcfc_billing_address_2,
					wlcfc_billing_state:		wlcfc_billing_state,
					wlcfc_billing_postcode: 	wlcfc_billing_postcode,
					wlcfc_shipping_first_name: 	wlcfc_shipping_first_name,
					wlcfc_shipping_last_name: 	wlcfc_shipping_last_name,
					wlcfc_shipping_company: 	wlcfc_shipping_company,
					wlcfc_shipping_country: 	wlcfc_shipping_country,
					wlcfc_shipping_address_1: 	wlcfc_shipping_address_1,
					wlcfc_shipping_address_2: 	wlcfc_shipping_address_2,
					wlcfc_shipping_city: 		wlcfc_shipping_city,
					wlcfc_shipping_state: 		wlcfc_shipping_state,
					wlcfc_shipping_postcode: 	wlcfc_shipping_postcode,
					wlcfc_order_comments: 		wlcfc_order_comments
				}

				timer = setTimeout(function(){
					jQuery.post(ajaxLink.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
					function(response) {
						//console.log(response);
					});
					
				}, 800);
			}else{
				//console.log("Not a valid e-mail or phone address");
			}
		}

		jQuery("#billing_email, #billing_phone, input.input-text, textarea.input-text").on("keyup keypress change", getCheckoutData ); //All action happens on or after changing Email, Phone fields or any other fields in the Checkout form. All Checkout form input fields are now triggering plugin action. Data saved to Database only after Email or Phone fields have been entered.
		jQuery(window).on("load", getCheckoutData ); //Automatically collect and save input field data if input fields already filled on page load
	});

})( jQuery );