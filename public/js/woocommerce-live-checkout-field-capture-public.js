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
				var billing_country = jQuery("#billing_country").val();
				var billing_city = jQuery("#billing_city").val();
				
				//console . log(wlcfc_email);
				//console . log(wlcfc_name);
				//console . log(wlcfc_surname);
				//console . log(wlcfc_phone);
				//console . log(billing_country);
				//console . log(billing_city);
				
				var data = {
					action:				"save_data",
					wlcfc_email:		wlcfc_email,
					wlcfc_name:			wlcfc_name,
					wlcfc_surname:		wlcfc_surname,
					wlcfc_phone:		wlcfc_phone,
					billing_country:	billing_country,
					billing_city:		billing_city
				}

				timer = setTimeout(function(){
					jQuery.post(ajaxLink.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
					function(response) {
						//console.log(response);
					});
					
				}, 400);
			}else{
				//console.log("Not a valid e-mail or phone address");
			}
		}

		jQuery("#billing_email, #billing_phone").on("keyup keypress change", getCheckoutData ); //All action happens on or after changing Email or Phone fields
		jQuery(window).on("load", getCheckoutData ); //Automatically collect and save input field data if input fields already filled on page load
	});

})( jQuery );