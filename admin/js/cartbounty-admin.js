(function( $ ) {
	'use strict';
	 
	 jQuery(document).ready(function(){

	 	$('.cartbounty-color-picker').wpColorPicker(); //Activating color picker

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

		jQuery(".cartbounty-type").on("click", addActiveClass );
		jQuery(".cartbounty-progress").on("click", addLoadingIndicator );
		jQuery("#cartbounty-upload-image").on("click", replaceExitIntentImage );
		jQuery("#cartbounty-remove-image").on("click", removeExitIntentImage );
		jQuery(".cartbounty-toggle .cartbounty-control-visibility").on("click", addCheckedClass );
		jQuery(".cartbounty-unavailable").on("click", addUnavailableClass );
		jQuery(".cartbounty-unavailable .cartbounty-section-image, #cartbounty-sections .cartbounty-unavailable a").on("click", disableLink );
	});

})( jQuery );