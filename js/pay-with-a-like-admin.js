jQuery(document).ready(function() { 
	
	// On the Settings panel for the 'Social Buttons' tab we want to uncheck the load script checkbox if/when a user unchecks the 'use button'. So
	// for example if a user unchecks the user Facebook we will automatically uncheck and disable the load facebook button.
	jQuery('ul#pwal-use-buttons input.pwal-use-button').click(function () {
		var button_id_type = jQuery(this).attr('id').replace('pwal-use-button-', '');
		if (jQuery(this).prop('checked')) {
			jQuery('ul#pwal-load-scripts input#pwal-load-script-'+button_id_type).removeAttr("disabled");;
		} else {
			// $("#captureAudio").prop('checked', false); 
			jQuery('ul#pwal-load-scripts input#pwal-load-script-'+button_id_type).attr("disabled", true);
			jQuery('ul#pwal-load-scripts input#pwal-load-script-'+button_id_type).prop('checked', false);
		}
	});


	if (jQuery('form#pwal-settings-form-pay-with-a-like-buttons ul#pwal-social-buttons').length) {
		jQuery( 'form#pwal-settings-form-pay-with-a-like-buttons ul#pwal-social-buttons' ).sortable();
		jQuery( 'form#pwal-settings-form-pay-with-a-like-buttons ul#pwal-social-buttons' ).disableSelection();

		/* We capture the configure form submit. We want to store the sort order or items and store into a hidden form field to save as user meta */
		jQuery('form#pwal-settings-form-pay-with-a-like-buttons').submit(function() {

			var pwal_social__buttons_sort = '';
			jQuery('form#pwal-settings-form-pay-with-a-like-buttons input.pwal-use-button').each(function() {
				
				var item_id = jQuery(this).attr('id').replace('pwal-use-button-', '');
				//console.log('item_id['+item_id+']');
				if (pwal_social__buttons_sort.length) {
					pwal_social__buttons_sort = pwal_social__buttons_sort+",";
				}
				pwal_social__buttons_sort = pwal_social__buttons_sort+item_id;
			});

			if (pwal_social__buttons_sort.length) {
				jQuery('form#pwal-settings-form-pay-with-a-like-buttons input#pwal-social-button-sort').val(pwal_social__buttons_sort);
			}
		});
	}


	jQuery("select#pwal_facebook_auth_polling").change(function() {
		if ( jQuery('select#pwal_facebook_auth_polling').val() == "yes" ) { jQuery("tr#pwal_facebook_auth_polling_interval_section").show(); }
		else { jQuery("tr#pwal_facebook_auth_polling_interval_section").hide(); }
	});

	jQuery("select#pwal_random").change(function() {
		if ( jQuery('select#pwal_random').val() == "true" ) { jQuery("#url_to_like_section").hide(); }
		else { jQuery("#url_to_like_section").show(); }
	});

	jQuery("select#pwal_method").change(function() {
		if ( jQuery('select#pwal_method').val() == "automatic" ) { jQuery("#excerpt_length").show(); }
		else { jQuery("#excerpt_length").hide(); }
	});


	jQuery("select#pwal_authorized").change(function() {
		if ( jQuery('select#pwal_authorized').val() == "true" ) { jQuery("#pwal_level_section").show(); }
		else { jQuery("#pwal_level_section").hide(); }
	});

	

	// Manages the Facebook Fan page Add row button
	jQuery('button#pwal-facebook-fan-page-add').click(function(event) {
		event.preventDefault();
		jQuery('table#pwal-facebook-fan-page-listing tbody>tr:last')
			.clone(true)
			.insertAfter('table#pwal-facebook-fan-page-listing tbody>tr:last').find('input').each(function() {
				jQuery(this).val('');
			});
	});

	// Manages the Facebook Fan page Remove row button
	jQuery('a.pwal-facebook-fan-page-remove').click(function(event) {
		event.preventDefault();
		var page_id = jQuery(this).attr('id').replace('pwal-facebook-fan-page-remove-', '');
		//console.log('page_id['+page_id+']');
		if (jQuery('table#pwal-facebook-fan-page-listing tbody tr#row-pwal-facebook-page-action-'+page_id).length) {
			jQuery('table#pwal-facebook-fan-page-listing tbody tr#row-pwal-facebook-page-action-'+page_id).remove();
		}
	});

	// On the Global tab sows the Post Types checkboxed and metabox checkboxes. If the post type is not checked or unchecked
	// then the show metabox will be unchecked and disabled. 
//	jQuery('ul#pwal-post-types input.pwal-post-type-item').click(function(event) {
//		//event.preventDefault();
//		var post_type = jQuery(this).attr('id').replace('pwal-post-type-', '');
//		if (jQuery(this).is(':checked')) {
//			jQuery('ul#pwal-post-types input#pwal-show-meta-'+post_type).attr('disabled', false);
//		} else {
//			jQuery('ul#pwal-post-types input#pwal-show-meta-'+post_type).attr('checked', false);
//			jQuery('ul#pwal-post-types input#pwal-show-meta-'+post_type).attr('disabled', 'disabled');
//		} 
//	});

	// On the Social Buttons checkboxes. If the user button is unchecked the load checkbox will be unchecked and disabled. 
	jQuery('ul#pwal-social-buttons input.pwal-use-button').click(function(event) {
		//event.preventDefault();
		var social_button = jQuery(this).attr('id').replace('pwal-use-button-', '');
		//console.log('social_button['+social_button+']');
		if (jQuery(this).is(':checked')) {
			jQuery('ul#pwal-social-buttons input#pwal-load-button-'+social_button).attr('disabled', false);
		} else {
			jQuery('ul#pwal-social-buttons input#pwal-load-button-'+social_button).attr('checked', false);
			jQuery('ul#pwal-social-buttons input#pwal-load-button-'+social_button).attr('disabled', 'disabled');
		} 
	});
	
	
	pwal_metabox_check();
	
	// The following are used on the post editor form metabox shown form PWAL
	if (jQuery('#pwal_metabox select#pwal_enable').length) {
		jQuery("#pwal_metabox select#pwal_enable").change(function() {
			pwal_metabox_check();
		});
	}
	if (jQuery('#pwal_metabox select#pwal_method').length) {
		jQuery("#pwal_metabox select#pwal_method").change(function() {
			pwal_metabox_check();
		});
	}
}); 

function pwal_metabox_check() {
	var pwal_enabled = jQuery('#pwal_metabox select#pwal_enable').val();
	if ((pwal_enabled == 'disable') || (pwal_enabled == 'global_disable')) {
		
		jQuery('#pwal_metabox p#section_pwal_method').hide();
		jQuery('#pwal_metabox p#section_pwal_reload').hide();
		jQuery('#pwal_metabox p#section_pwal_excerpt').hide();
		jQuery('#pwal_metabox p#section_pwal_description').hide();
		jQuery('#pwal_metabox p#section_pwal_container_width').hide();
		jQuery('#pwal_metabox p#section_pwal_url_to_like').hide();
		
		jQuery('#content_paywithalike').hide();
		
	} else if ((pwal_enabled == 'enable') || (pwal_enabled == 'global_enable')) {

		jQuery('#pwal_metabox p#section_pwal_method').show();
		jQuery('#pwal_metabox p#section_pwal_reload').show();
		jQuery('#pwal_metabox p#section_pwal_excerpt').show();
		jQuery('#pwal_metabox p#section_pwal_description').show();
		jQuery('#pwal_metabox p#section_pwal_container_width').show();
		jQuery('#pwal_metabox p#section_pwal_url_to_like').show();


		var pwal_method = jQuery('#pwal_metabox select#pwal_method').val();
		if ((pwal_method == 'automatic') || (pwal_method == 'global_automatic')) {
			jQuery('#pwal_metabox p#section_pwal_excerpt').show();
			
		} else {
			jQuery('#pwal_metabox p#section_pwal_excerpt').hide();	
		}

		// Hide the TinyMCE button.
		if ((pwal_method == 'tool') || (pwal_method == 'global_tool')) {
			jQuery('#content_paywithalike').show();
			jQuery('#pwal_metabox p#section_pwal_description').hide();
		} else {
			jQuery('#content_paywithalike').hide();
			jQuery('#pwal_metabox p#section_pwal_description').show();
		}
	}
}
