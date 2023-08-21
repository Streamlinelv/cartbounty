(function( $ ) {
	'use strict';
	 
	 jQuery(document).ready(function(){

		var timer;
		var save_custom_email = cartbounty_co.save_custom_email;
		var custom_email_selectors = cartbounty_co.custom_email_selectors;
		var selector_timeout = cartbounty_co.selector_timeout;
		var contact_saved = localStorage.getItem('cartbounty_contact_saved');

		function getCheckoutData() { //Reading WooCommerce field values

			if(jQuery("#billing_email").length > 0 || jQuery("#billing_phone").length > 0){ //If at least one of these two fields exist on page

				var cartbounty_email = jQuery("#billing_email").val();
				if (typeof cartbounty_email === 'undefined' || cartbounty_email === null) { //If email field does not exist on the Checkout form
				   cartbounty_email = '';
				}
				var atposition = cartbounty_email.indexOf("@");
				var dotposition = cartbounty_email.lastIndexOf(".");

				var cartbounty_phone = jQuery("#billing_phone").val();
				if (typeof cartbounty_phone === 'undefined' || cartbounty_phone === null) { //If phone number field does not exist on the Checkout form
				   cartbounty_phone = '';
				}
				
				clearTimeout(timer);

				if (!(atposition < 1 || dotposition < atposition + 2 || dotposition + 2 >= cartbounty_email.length) || cartbounty_phone.length > 4){ //Checking if the email field is valid or phone number is longer than 4 digits
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
						action:								"cartbounty_save",
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
						jQuery.post(cartbounty_co.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
						function(response) {
							//console.log(response);
							//If we have successfully captured abandoned cart, we do not have to display Exit intent form anymore
							if(response.success){ //If successfuly saved data
								localStorage.setItem('cartbounty_contact_saved', true);
								removeExitIntentForm();
							}
						});
						
					}, 800);
				}else{
					//console.log("Not a valid email or phone address");
				}
			}
		}

		function saveCustomEmail(){ //Function for saving custom email field
			var custom_email_selector = jQuery(this);
			var cartbounty_contact_saved = localStorage.getItem('cartbounty_contact_saved');

			if(cartbounty_contact_saved){ //Exit in case any of CartBounty tools have already saved data
				return;
			}

			if(jQuery(custom_email_selector).length > 0 && !contact_saved){ //If email field is present and contact information is not saved
				var cartbounty_custom_email = jQuery( custom_email_selector ).val();
				
				if (typeof cartbounty_custom_email === 'undefined' || cartbounty_custom_email === null) { //If email field does not exist in the form
				   cartbounty_custom_email = '';
				}

				var atposition = cartbounty_custom_email.indexOf("@");
				var dotposition = cartbounty_custom_email.lastIndexOf(".");

				if (!(atposition < 1 || dotposition < atposition + 2 || dotposition + 2 >= cartbounty_custom_email.length)){ //Checking if the email field is valid
					if(cartbounty_custom_email != ''){ //If Email is not empty
						localStorage.setItem('cartbounty_custom_email', cartbounty_custom_email); //Saving user's input in browser memory
					}
				}
			}
		}

		function passCustomEmailToCartBounty(){ //Function passes custom email field to backend
			var cartbounty_custom_email_stored = localStorage.getItem('cartbounty_custom_email');
			var cartbounty_contact_saved = localStorage.getItem('cartbounty_contact_saved');

			if( cartbounty_custom_email_stored == null || cartbounty_contact_saved ){ //If data is missing or any of the CartBounty tools have already saved data - exit
				return;
			}

			var data = {
				action:									"cartbounty_save",
				source:									"cartbounty_custom_email",
				cartbounty_email:						cartbounty_custom_email_stored
			}

			jQuery.post(cartbounty_co.ajaxurl, data, //Send data over to backend for saving
			function(response) {
				if(response.success){ //If data successfuly saved
					localStorage.setItem('cartbounty_contact_saved', true);
					removeCustomEmailFields();
					removeExitIntentForm();
				}
			});
		}

		function removeCustomEmailFields(){ //Removing from local storage custom email field
			localStorage.removeItem('cartbounty_custom_email');
		}

		function removeExitIntentForm(){//Removing Exit Intent form
			if(jQuery('#cartbounty-exit-intent-form').length > 0){ //If Exit intent HTML exists on page
				jQuery('#cartbounty-exit-intent-form').remove();
				jQuery('#cartbounty-exit-intent-form-backdrop').remove();
			}
		}

		jQuery( '#billing_email, #billing_phone, input.input-text, input.input-checkbox, textarea.input-text' ).on( 'keyup keypress change', getCheckoutData ); //All action happens on or after changing Email or Phone fields or any other fields in the Checkout form. All Checkout form input fields are now triggering plugin action. Data saved to Database only after Email or Phone fields have been entered.
		jQuery(window).on( 'load', getCheckoutData ); //Automatically collect and save input field data if input fields already filled on page load
		
		if( ( save_custom_email && !contact_saved ) ){ //If custom email saving enabled and contact is not saved - try to save email
			passCustomEmailToCartBounty();

			setTimeout(function() { //Using timeout since some of the plugins add their input forms later instead of immediatelly
				jQuery( custom_email_selectors ).on( 'keyup keypress change', saveCustomEmail );
			}, selector_timeout );

			jQuery(document).on( 'added_to_cart', passCustomEmailToCartBounty ); //Sending data over for saving in case WooCommerce "added_to_cart" event fires after product added to cart
		}
	});

})( jQuery );