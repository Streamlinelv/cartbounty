(function( $ ) {
	'use strict';
	 
	 jQuery(document).ready(function(){

	 	jQuery('.cartbounty-color-picker').wpColorPicker(); //Activating color picker
	 	jQuery('.cartbounty-select, .bulkactions select').selectize(); //Activating custom dropdown for all select fields

	 	function addActiveClass(){ //Adding class when changing radio button to display Get Pro notice
			jQuery(this).siblings().removeClass('cartbounty-radio-active');
			jQuery(this).addClass('cartbounty-radio-active');
		}

		function addActiveStepClass(){ //Adding active class when clicking on a stairway item
			var step = jQuery(this).closest('.cartbounty-step');
			if(!step.hasClass('cartbounty-step-disabled')){ //In case current step has not deactivated, open it
				step.toggleClass('cartbounty-step-active');
			}
		}

		function removeActiveStepClassUpgradeNotice(e){ //Removing active class if the user click the open upgrade notice window
			var step = jQuery(this).closest('.cartbounty-step');
			if(jQuery(e.target).is('a')){
	            return;
	        }else{
				if(!step.hasClass('cartbounty-step-disabled')){ //In case current step has not deactivated, open it
					step.toggleClass('cartbounty-step-active');
				}
	        }
		}

		function addLoadingIndicator(){ //Adding loading indicator once Submit button pressed
			jQuery(this).addClass('cartbounty-loading');
		}

		function addCustomImage(e){ //Adding a custom image
			e.preventDefault();
			var button = jQuery(this),
			custom_uploader = wp.media({
				title: 'Add a custom image',
				library : {
					type : 'image'
				},
				button: {
					text: 'Use image'
				},
				multiple: false
			}).on('select', function(){ //It also has "open" and "close" events
				var attachment = custom_uploader.state().get('selection').first().toJSON();
				var image_url = attachment.url;

 				if(typeof attachment.sizes.thumbnail !== "undefined"){ //Checking if the selected image has a thumbnail image size
 					var thumbnail = attachment.sizes.thumbnail.url;
 					image_url = thumbnail;
 				}
				button.html('<img src="' + image_url + '">');
				var input_field = jQuery('#cartbounty-custom-image');
				var remove_button = jQuery('#cartbounty-remove-custom-image');
				
				input_field.val(attachment.id);
				remove_button.show();

			}).open();
		}

		function removeCustomImage(e){ //Removing Custom image
			e.preventDefault();
			var button = jQuery(this).hide();
			var input_field = jQuery('#cartbounty-custom-image');
			var add_button = jQuery('#cartbounty-upload-custom-image');

			input_field.val('');
			add_button.html('<input type="button" class="cartbounty-button button-secondary button" value="Add a custom image">');
		};

		function getPreviewData( button, action ){
			var data = {
				nonce				: button.data('nonce'),
				action				: action,
				email				: jQuery('#cartbounty-send-test').val(),
				subject				: jQuery('#cartbounty-automation-subject').val(),
				main_title			: jQuery('#cartbounty-automation-heading').val(),
				content				: jQuery('#cartbounty-automation-content').val(),
				main_color			: jQuery('#cartbounty-template-main-color').val(),
				button_color		: jQuery('#cartbounty-template-button-color').val(),
				text_color			: jQuery('#cartbounty-template-text-color').val(),
				background_color	: jQuery('#cartbounty-template-background-color').val()
			};
			return data;
		}

		function previewEmail(e){
			e.preventDefault();
			var button = jQuery(this)
			var data = getPreviewData( button, "email_preview" );

			jQuery.post(cartbounty_admin_data.ajaxurl, data,
			function(response){
				var content = jQuery('#cartbounty-modal-content');
				var modal = jQuery('#cartbounty-modal');
				modal.addClass('content-loaded');
				content.html(response.data);
				MicroModal.show('cartbounty-modal', {
					onClose(){ 
						content.empty(); //Removing email preview once preview closed
					}
				});
				button.removeClass('cartbounty-loading');
			});
		}

		function sendTestEmail(e){
			e.preventDefault();
			var button = jQuery(this);
			var email_data = jQuery('#cartbounty-send-test').val();
			var label = button.closest('.cartbounty-settings-group').find('label');
			var data = getPreviewData( button, "send_test" );
			label.find('.license-status').remove();
			
			jQuery.post(cartbounty_admin_data.ajaxurl, data,
			function(response){
				label.append(response.data);
				button.removeClass('cartbounty-loading');
			});
		}

		function force_sync(e){
			e.preventDefault();
            var button = jQuery(this);
            button.addClass('cartbounty-loading');

			var data = {
				nonce		: button.data('nonce'),
				integration : button.data('integration'),
				action		: "force_sync"
			};

			jQuery.post(cartbounty_admin_data.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
			function(response){
				if ( response.success == true ){
			        button.removeClass('cartbounty-loading');

			    }else{
			    	button.removeClass('cartbounty-loading');
			    }
			});
		}

		function disableSubmitOnEnter(e){ //Disable form submit using enter on a specific input field
			return e.which !== 13;
		}

		function addCheckedClass(){ //Adding checked state to the parent in case if the Toggle checkbox is turned on
			if( jQuery(this).find('input').prop('checked') ){
				jQuery(this).parent().addClass('cartbounty-checked'); //Necessary to show/hide small text additions
				jQuery(this).parent().parent().addClass('cartbounty-checked-parent');
			}else{
				jQuery(this).parent().removeClass('cartbounty-checked'); //Necessary to show/hide small text additions
				jQuery(this).parent().parent().removeClass('cartbounty-checked-parent');
			}
		}

		function addUnavailableClass(){ //Adding unavailable class to display a message
			var current = jQuery(this);
			current.parent().addClass('cartbounty-checked'); //Necessary to show/hide small text additions
		}

		function disableLink(e){ //Function prevents link from firing
			e.preventDefault();
		}

		function rewriteTable(i,l,s,w) { //Function used to rewrite table structure into readable text
			var o = i.toString();
			if (!s) { s = '0'; }
			while (o.length < l) {
				if(w == 'undefined'){ //empty
					o = s + o;
				}else{
					o = o + s;
				}
			}
			return o;
		};

		function copySystemReport(){
            var button = jQuery(this);
            var container = button.parent();
            container.removeClass('cartbounty-container-active');

			var data = {
				nonce		: button.data('nonce'),
				action		: "get_system_status"
			};

			jQuery.post(cartbounty_admin_data.ajaxurl, data,
			function(response){
				if ( response.success == true ){
			        var system_report = '';
			        //Transforming HTML table into readable text that we can copy later
					jQuery(response.data).each(function(){
						jQuery('tr', jQuery( this )).each(function(){
							var the_name    = rewriteTable( jQuery.trim( jQuery( this ).find('td:eq(0)').text() ), 30, ' ' );
							var the_value   = jQuery.trim( jQuery( this ).find('td:eq(1)').text() );
							var value_array = the_value.split( ', ' );
							if ( value_array.length > 1 ){
								var output = '';
								var temp_line = '';
								jQuery.each( value_array, function(key, line){
									var tab = ( key == 0 ) ? 0 : 30;
									temp_line = temp_line + rewriteTable( '', tab, ' ', 'f' ) + line +'\n';
								});
								the_value = temp_line;
							}
							system_report = system_report +''+ the_name + the_value + "\n";
						});
					});

					try { //Try adding a temporary textarea input field that will hold the system report so it can be copied
						var textarea = jQuery("<textarea>");
						jQuery("body").append(textarea);
						textarea.val( system_report ).select();
						document.execCommand("copy");
						textarea.remove();
						container.addClass('cartbounty-container-active');
					}catch(e) {
						console.log(e);
					}

					setTimeout(function(){
						container.removeClass('cartbounty-container-active');
					}, 3000);

					button.removeClass('cartbounty-loading');
					return false;
				}else{ //In case an error occurs
					console.log( response.data );
				}
			});
		}

		function closeBubble(e){
			e.preventDefault();
			var button = jQuery(this);
			var bubble = button.closest('.cartbounty-bubble');
			var data = {
				nonce		: button.data('nonce'),
				action		: 'handle_bubble',
				operation	: button.data('operation'),
				type		: button.data('type')
			};
			
			jQuery.post(cartbounty_admin_data.ajaxurl, data,
			function(response){
				bubble.css({top:"-600px", right: "50px"}); //Hide the bubble from screen
				if ( response.success != true ){
					console.log(response.data);
				}
			});
		}

		function togglePreview(){ //Show or hide preview
			var preview_parent = jQuery(this).parent();
			var active_preview_class = 'cartbounty-preview-active';

			if(!preview_parent.hasClass( active_preview_class )){ //Open preview
				jQuery('.cartbounty-preview-container, .cartbounty-button-row').removeClass( active_preview_class );
				preview_parent.addClass( active_preview_class );

			}else{ //Close preview
				preview_parent.removeClass( active_preview_class );
			}
		}

		jQuery(".cartbounty-type").on("click", addActiveClass );
		jQuery(".cartbounty-progress").on("click", addLoadingIndicator );
		jQuery(".cartbounty-upload-image").on("click", addCustomImage );
		jQuery(".cartbounty-remove-image").on("click", removeCustomImage );
		jQuery(".cartbounty-preview-email").on("click", previewEmail );
		jQuery(".cartbounty-send-email").on("click", sendTestEmail );
		jQuery(".cartbounty-disable-submit").on("keypress", disableSubmitOnEnter );
		jQuery("#force_sync").on("click", force_sync );
		jQuery(".cartbounty-toggle .cartbounty-control-visibility").on("click", addCheckedClass );
		jQuery(".cartbounty-unavailable").on("click", addUnavailableClass );
		jQuery(".cartbounty-unavailable .cartbounty-section-image, #cartbounty-sections .cartbounty-unavailable a").on("click", disableLink );
		jQuery('#cartbounty-copy-system-status').on("click", copySystemReport );
		jQuery('.cartbounty-step-opener').on("click", addActiveStepClass );
		jQuery('.cartbounty-wordpress-get-additional-step').on("click", removeActiveStepClassUpgradeNotice );
		jQuery(".cartbounty-bubble-close").on("click", closeBubble );
		jQuery(".button-preview, .cartbounty-close-preview").on("click", togglePreview );
	});

})( jQuery );