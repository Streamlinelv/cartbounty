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

			if(jQuery("#billing_email").length > 0){ //If email address exists

				var cartbounty_phone = jQuery("#billing_phone").val();
				var cartbounty_email = jQuery("#billing_email").val();

				var atposition = cartbounty_email.indexOf("@");
				var dotposition = cartbounty_email.lastIndexOf(".");

				if (typeof cartbounty_phone === 'undefined' || cartbounty_phone === null) { //If phone number field does not exist on the Checkout form
				   cartbounty_phone = '';
				}
				
				clearTimeout(timer);

				if (!(atposition < 1 || dotposition < atposition + 2 || dotposition + 2 >= cartbounty_email.length) || cartbounty_phone.length >= 1){ //Checking if the email field is valid or phone number is longer than 1 digit
					//If Email or Phone valid
					var cartbounty_name = jQuery("#billing_first_name").val();
					var cartbounty_surname = jQuery("#billing_last_name").val();
					var cartbounty_phone = jQuery("#billing_phone").val();
					var cartbounty_country = jQuery("#billing_country").val();
					var cartbounty_city = jQuery("#billing_city").val();
					
					//Other fields used for "Remember user input" function
					var cartbounty_billing_company = jQuery("#billing_company").val();
					var cartbounty_billing_address_1 = jQuery("#billing_address_1").val();
					var cartbounty_billing_address_2 = jQuery("#billing_address_2").val();
					var cartbounty_billing_state = jQuery("#billing_state").val();
					var cartbounty_billing_postcode = jQuery("#billing_postcode").val();
					var cartbounty_shipping_first_name = jQuery("#shipping_first_name").val();
					var cartbounty_shipping_last_name = jQuery("#shipping_last_name").val();
					var cartbounty_shipping_company = jQuery("#shipping_company").val();
					var cartbounty_shipping_country = jQuery("#shipping_country").val();
					var cartbounty_shipping_address_1 = jQuery("#shipping_address_1").val();
					var cartbounty_shipping_address_2 = jQuery("#shipping_address_2").val();
					var cartbounty_shipping_city = jQuery("#shipping_city").val();
					var cartbounty_shipping_state = jQuery("#shipping_state").val();
					var cartbounty_shipping_postcode = jQuery("#shipping_postcode").val();
					var cartbounty_order_comments = jQuery("#order_comments").val();
					var cartbounty_create_account = jQuery("#createaccount");
					var cartbounty_ship_elsewhere = jQuery("#ship-to-different-address-checkbox");

					if(cartbounty_create_account.is(':checked')){
						cartbounty_create_account = 1;
					}else{
						cartbounty_create_account = 0;
					}

					if(cartbounty_ship_elsewhere.is(':checked')){
						cartbounty_ship_elsewhere = 1;
					}else{
						cartbounty_ship_elsewhere = 0;
					}
					
					var data = {
						action:								"save_data",
						cartbounty_email:					cartbounty_email,
						cartbounty_name:					cartbounty_name,
						cartbounty_surname:					cartbounty_surname,
						cartbounty_phone:					cartbounty_phone,
						cartbounty_country:					cartbounty_country,
						cartbounty_city:					cartbounty_city,
						cartbounty_billing_company:			cartbounty_billing_company,
						cartbounty_billing_address_1:		cartbounty_billing_address_1,
						cartbounty_billing_address_2: 		cartbounty_billing_address_2,
						cartbounty_billing_state:			cartbounty_billing_state,
						cartbounty_billing_postcode: 		cartbounty_billing_postcode,
						cartbounty_shipping_first_name: 	cartbounty_shipping_first_name,
						cartbounty_shipping_last_name: 		cartbounty_shipping_last_name,
						cartbounty_shipping_company: 		cartbounty_shipping_company,
						cartbounty_shipping_country: 		cartbounty_shipping_country,
						cartbounty_shipping_address_1: 		cartbounty_shipping_address_1,
						cartbounty_shipping_address_2: 		cartbounty_shipping_address_2,
						cartbounty_shipping_city: 			cartbounty_shipping_city,
						cartbounty_shipping_state: 			cartbounty_shipping_state,
						cartbounty_shipping_postcode: 		cartbounty_shipping_postcode,
						cartbounty_order_comments: 			cartbounty_order_comments,
						cartbounty_create_account: 			cartbounty_create_account,
						cartbounty_ship_elsewhere: 			cartbounty_ship_elsewhere
					}

					timer = setTimeout(function(){
						jQuery.post(ajaxLink.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
						function(response) {
							//console.log(response);
							//If we have successfully captured abandoned cart, we do not have to display Exit intent form anymore
							removeExitIntentForm();
						});
						
					}, 800);
				}else{
					//console.log("Not a valid e-mail or phone address");
				}
			}
		}

		function removeExitIntentForm(){//Removing Exit Intent form
			if($('#cartbounty-exit-intent-form').length > 0){ //If Exit intent HTML exists on page
				$('#cartbounty-exit-intent-form').remove();
				$('#cartbounty-exit-intent-form-backdrop').remove();
			}
		}

		jQuery("#billing_email, #billing_phone, input.input-text, input.input-checkbox, textarea.input-text").on("keyup keypress change", getCheckoutData ); //All action happens on or after changing Email or Phone fields or any other fields in the Checkout form. All Checkout form input fields are now triggering plugin action. Data saved to Database only after Email or Phone fields have been entered.
		jQuery(window).on("load", getCheckoutData ); //Automatically collect and save input field data if input fields already filled on page load
		
	});

})( jQuery );