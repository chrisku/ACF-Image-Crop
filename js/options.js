jQuery(function($){
	// The custom width/height settings are shown and hidden by ACF's setting conditions.

	acf.add_action('append', function(){
		$('.acf-field-object-image-crop .save-in-media-library-select input').each(function() {
			toggleSaveFormats(this);
		});
	});

	$(document).on('click', '.acf-field-object-image-crop .save-in-media-library-select input', function(e) {
		toggleSaveFormats(this);
	});

	// "Image ID" is not available when the cropped image is not saved to the media library
	function toggleSaveFormats(saveToMediaSelect){
		if($(saveToMediaSelect).is(':checked')){
			var saveToMedia = $(saveToMediaSelect).val() == 'yes';
			var $returnValueField = $(saveToMediaSelect).parents('.acf-field-object-image-crop').find('.return-value-select');
			if(! saveToMedia){
				$returnValueField.find('input[value=id]').prop('disabled', true).parents('label').addClass('disabled');
				if($returnValueField.find('input[value=id]').is(':checked')){
					$returnValueField.find('input[value=url]').prop('checked', true);
				}
			}
			else{
				$returnValueField.find('input').prop('disabled', false).parents('label').removeClass('disabled');
			}
		}
	}

	$('.acf-field-object-image-crop .save-in-media-library-select input').each(function() {
		toggleSaveFormats(this);
	});

});
