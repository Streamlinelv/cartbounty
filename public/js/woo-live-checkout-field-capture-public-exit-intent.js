(function($){
	'use strict';
	 
	jQuery(document).ready(function(){

	 	var timer;

	 	function showExitIntentForm(event){
	 		var currentTime = new Date().getTime();
			var timePeriod = public_data.hours; //Time period in hours
			var last_time_displayed = localStorage.getItem('wclcfc_ei_last_time');
			var productCount = public_data.product_count; //Products in the shopping cart

			if (event.clientY <= 0 && event.target.tagName.toLowerCase() != "select" && event.target.tagName.toLowerCase() != "option" && event.target.tagName.toLowerCase() != "input") { //Checking if mouse Y poosition goes beyond the top screen and that we haven't clicked on dropdown or autocomplete input field
		        if(productCount == 0){

		        }
		        else if(last_time_displayed == null || timePeriod == 0) { //If time period has passed or we Exit Intent test mode is enabled
		            $('#wclcfc-exit-intent-form').addClass('wclcfc-visible'); //Display form
		        	$('#wclcfc-exit-intent-form-backdrop').css('opacity', '').addClass('wclcfc-visible'); //Show backdrop
		        	if(timePeriod != 0){
		        		localStorage.setItem('wclcfc_ei_last_time', currentTime);
		        	}
		        }else{
		        	if(currentTime - last_time_displayed > timePeriod * 60 * 60 * 1000){ // If the time has expired, clear the cookie
						localStorage.removeItem('wclcfc_ei_last_time');
					}
		        }
		    }
	 	}

		function getExitIntentEmail() { //Reading email entered in exit intent
			var wlcfc_email = jQuery("#wclcfc-exit-intent-email").val();
			var atposition = wlcfc_email.indexOf("@");
			var dotposition = wlcfc_email.lastIndexOf(".");
			
			clearTimeout(timer);

			if (!(atposition < 1 || dotposition < atposition + 2 || dotposition + 2 >= wlcfc_email.length)){ //Checking if the email field is valid
				var data = {
					action:			"save_data",
					wlcfc_email:	wlcfc_email
				}

				timer = setTimeout(function(){
					jQuery.post(public_data.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
					function(response) {
						//console.log(response);
					});
					
				}, 800);
			}else{
				//console.log("Not a valid e-mail or phone address");
			}
		}

		function insertExitIntentForm(){//Adding Exit Intent form in case if Ajax Add To Cart button pressed
			var data = {
				action: 		"insert_exit_intent",
				wlcfc_insert: 	true
			}
			
			if($('#wclcfc-exit-intent-form').length <= 0){ //If Exit intent HTML does not exist on page
				jQuery.post(public_data.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
				function(response){ //Response consists of HTML
					var output = response;
					$("body").append(output); //Adding Exit Intent form to the footer
					//Binding these functions once again since HTML added by Ajax is new
					jQuery("#wclcfc-exit-intent-email").on("keyup keypress change", getExitIntentEmail ); //All action happens on or after changing Email field. Data saved to Database only after Email fields have been entered.
					jQuery("#wclcfc-exit-intent-close, #wclcfc-exit-intent-form-backdrop").on("click", closeExitIntentForm ); //Close Exit intent window
				});
			}

			public_data.product_count = parseInt(public_data.product_count) + 1; //Updating product count in cart data variable once Add to Cart button is pressed
		
		}

		function removeExitIntentFormIfEmptyCart(){//Removing Exit Intent form in case if cart emptied using Ajax
			var data = {
				action: 		"remove_exit_intent",
				wlcfc_remove: 	true
			}
			if($('#wclcfc-exit-intent-form').length > 0){ //If Exit intent HTML exists on page
				jQuery.post(public_data.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
				function(response){
					if(response.data == 'true'){ //If the cart is empty - removing exit intent HTML
						$('#wclcfc-exit-intent-form').remove();
						$('#wclcfc-exit-intent-form-backdrop').remove();
					}
				}); 
			}
		}

		function closeExitIntentForm(){ //Close exit intent window
 		    $('#wclcfc-exit-intent-form').removeClass('wclcfc-visible'); //Hide form
        	$('#wclcfc-exit-intent-form-backdrop').removeClass('wclcfc-visible'); //Hide backdrop
	 	}

		jQuery(document).on("mouseleave", showExitIntentForm); //Displaying Exit intent if the mouse leaves the window
		jQuery("#wclcfc-exit-intent-email").on("keyup keypress change", getExitIntentEmail ); //All action happens on or after changing Email field. Data saved to Database only after Email fields have been entered.
		jQuery("#wclcfc-exit-intent-close, #wclcfc-exit-intent-form-backdrop").on("click", closeExitIntentForm ); //Close Exit intent window
		jQuery(document).on("added_to_cart", insertExitIntentForm ); //Calling Exit Intent form function output if Add to Cart button is pressed
		jQuery(document).on("removed_from_cart", removeExitIntentFormIfEmptyCart ); //Firing the function if item is removed from cart via Ajax 
	});

})(jQuery);