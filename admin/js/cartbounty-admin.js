(function( $ ) {
	'use strict';
	 
	 jQuery(document).ready(function(){

	 	jQuery('.cartbounty-color-picker').wpColorPicker(); //Activating color picker
	 	jQuery('.cartbounty-select, .bulkactions select').selectize(); //Activating custom dropdown for all select fields

	 	function addActiveClass(){ //Adding class when changing radio button to display Get Pro notice
			$(this).siblings().removeClass('cartbounty-radio-active');
			$(this).addClass('cartbounty-radio-active');
		}

		function addLoadingIndicator(){ //Adding loading indicator once Submit button pressed
			$(this).addClass('cartbounty-loading');
		}

		function replaceExitIntentImage(e){ //Replacing Exit Intent image
			e.preventDefault();
			var button = $(this),
			custom_uploader = wp.media({
				title: 'Add custom Exit Intent image',
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
				$('#cartbounty_exit_intent_image').val(attachment.id);
				$('#cartbounty-remove-image').show();
			}).open();
		}

		function removeExitIntentImage(e){ //Removing Exit Intent image
			e.preventDefault();
			var button = $(this).hide();
			$('#cartbounty_exit_intent_image').val('');
			$('#cartbounty-upload-image').html('<input type="button" class="cartbounty-button button-secondary button" value="Add custom image">');
		};

		function addCheckedClass(){ //Adding checked state to the parent in case if the Toggle checkbox is turned on
			if( $(this).find('input').prop('checked') ){
				$(this).parent().addClass('cartbounty-checked'); //Necessary to show/hide small text additions
				$(this).parent().parent().addClass('cartbounty-checked-parent');
			}else{
				$(this).parent().removeClass('cartbounty-checked'); //Necessary to show/hide small text additions
				$(this).parent().parent().removeClass('cartbounty-checked-parent');
			}
		}

		function addUnavailableClass(){ //Adding unavailable class to display a message
			var current = $(this);
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
            var button = $(this);
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
					}catch(e) {
						console.log(e);
					}

					button.removeClass('cartbounty-loading');
					return false;
				}else{ //In case an error occurs
					console.log( response.data );
				}
			});
		}

		jQuery(".cartbounty-type").on("click", addActiveClass );
		jQuery(".cartbounty-progress").on("click", addLoadingIndicator );
		jQuery("#cartbounty-upload-image").on("click", replaceExitIntentImage );
		jQuery("#cartbounty-remove-image").on("click", removeExitIntentImage );
		jQuery(".cartbounty-toggle .cartbounty-control-visibility").on("click", addCheckedClass );
		jQuery(".cartbounty-unavailable").on("click", addUnavailableClass );
		jQuery(".cartbounty-unavailable .cartbounty-section-image, #cartbounty-sections .cartbounty-unavailable a").on("click", disableLink );
		jQuery('#cartbounty-copy-system-status').on("click", copySystemReport );
	});

})( jQuery );