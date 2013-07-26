var wpmudev_pwal = jQuery.extend(wpmudev_pwal || {}, {
	buttons: {},
	init: function() {

		if (pwal_data.debug == "true")
			console.log('pwal_data[%o]', pwal_data);
			
		if (pwal_data['is_cachable'] == '') {
			wpmudev_pwal.setup_facebook_js();
			wpmudev_pwal.setup_twitter_js();

			wpmudev_pwal.bind_buttons();
		}
	},
	register_button_href: function(pwal_id, pwal_post_id, pwal_href) {

		// Setup our global array of links. Later when the user clicks the FB Like button we can 
		// determine from the href function parameter of the 'edge.create' what our ID it. 

		if (pwal_data.debug == "true")
			console.log('register_button_href: pwal_id['+pwal_id+'] pwal_post_id['+pwal_post_id+'] pwal_href['+pwal_href+']');

		if (wpmudev_pwal.buttons[pwal_id] == undefined) {
			var pwal_info = {};
			pwal_info['id'] 		= pwal_id;
			pwal_info['post_id'] 	= pwal_post_id;
			pwal_info['href']		= pwal_href;
			
			wpmudev_pwal.buttons[pwal_id] = pwal_info;
		}
	},
	bind_buttons: function () {
		jQuery(document).bind('pwal_button_action', function (event, pwal_info) {

			if (pwal_data.debug == "true") {
				//console.log('in bind_buttons event[%o] service['+service+'] pwal_id['+pwal_id+'] pwal_post_id['+pwal_post_id+']', event);
				console.log('in bind_buttons pwal_info[%o]', pwal_info);				
			}
			
			if (pwal_data.debug != "true") {
				jQuery.post(pwal_data['ajax_url'], {
					'action': 'pwal_action',
					'post_id': pwal_info['post_id'],
					'content_id': pwal_info['id'],
					'href': pwal_info['href'],
					'service': pwal_info['service'],
					'nonce': pwal_data['ajax-nonce']
				}, function (data) {
					if ( data && data.error ) {alert(data.error);}
					else{ window.location.href = window.location.href;  }
				},
				'json'
				);
			}
		});
	},
	setup_google_plus_js: function(pwal_id, data) {

		if (pwal_data.debug == "true")
			console.log('google: pwal_id['+pwal_id+'] data[%o]', data);
		
		if (pwal_id == undefined) return;
		if (data == undefined) return;
		
		if (data.state == 'off') {
			if (pwal_data.debug == "true")
				console.log('google: id['+pwal_id+'] data.state['+data.state+']');
			return false;
		} else {

			if (wpmudev_pwal.buttons[pwal_id] != undefined) {
				var pwal_info = wpmudev_pwal.buttons[pwal_id];
				if (pwal_data.debug == "true") {
					console.log('google: pwal_info[%o]', pwal_info);
				}
				pwal_info['service'] = 'google';
				jQuery(document).trigger('pwal_button_action', pwal_info);
			}
		}
	},
	setup_linkedin_js: function(pwal_id) {

		if (pwal_data.debug == "true")
			console.log('linkedin: pwal_id['+pwal_id+']');

		if ((pwal_id != undefined) && (pwal_id != '')) {
			if (wpmudev_pwal.buttons[pwal_id] != undefined) {
				var pwal_info = wpmudev_pwal.buttons[pwal_id];
				if (pwal_data.debug == "true") {
					console.log('linkedin: pwal_info[%o]', pwal_info);
				}
				pwal_info['service'] = 'linkedin';
				jQuery(document).trigger('pwal_button_action', pwal_info);
			} 
		}
	},
	setup_twitter_js: function() {

		if ((pwal_data.options['use_twitter'] == undefined) || (pwal_data.options['use_twitter'] != "true"))
			return;

		twttr.ready(function(twttr) {
			twttr.events.bind('tweet',function(event) {
				if (pwal_data.debug == "true")
					console.log('twitter: event[%o]', event);
				
				if (event.target.parentElement.id == undefined)
				 	return;
				var pwal_id = event.target.parentElement.id;
				pwal_id = pwal_id.replace('pwal_twitter_', '');
				if (pwal_data.debug == "true")
					console.log('twitter: pwal_id['+pwal_id+']');
				
				if (wpmudev_pwal.buttons[pwal_id] == undefined) {
					if (pwal_data.debug == "true")
						console.log('twitter: pwal_info not found');
						
				 	return;
				}
				
				var pwal_info = wpmudev_pwal.buttons[pwal_id];
				pwal_info['service'] = 'twitter';
									
				if (pwal_data.debug == "true")
					console.log('twitter: pwal_info[%o]', pwal_info);

				jQuery(document).trigger('pwal_button_action', pwal_info);
			});
		});
	},
	setup_facebook_js: function() {
		if ((pwal_data.options['use_facebook'] == undefined) || (pwal_data.options['use_facebook'] != "true")) {
			if (pwal_data.debug == "true")
				console.log('use_facebook['+pwal_data.options['use_facebook']+']');
			
			return;
		} else {
			if (pwal_data.debug == "true")
				console.log('use_facebook['+pwal_data.options['use_facebook']+']');			
		}
			
		if (!jQuery('#fb-root').length) {
			if (pwal_data.debug == "true")
				console.log('facebook: adding fb-root');
				
			jQuery("body").append('<div id="fb-root"></div>');
		}
		
		//window.fbAsyncInit = function() {
			// init the FB JS SDK

			if (pwal_data.debug == "true")
				console.log('load_facebook['+pwal_data.options['load_facebook']+']');

			//if ((pwal_data.options['load_facebook'] != undefined) && (pwal_data.options['load_facebook'] == "true")) {
					
				if (pwal_data.debug == "true")
					console.log('calling FB.init');
				FB.init({
					status: true,
					cookie: true,
					xfbml: true
			    });
			//} 
			
			FB.XFBML.parse();
			
			/*
			FB.Event.subscribe('auth.statusChange', function(response) {
				console.log('auth.statusChange: The status of the session is: ' + response.status);
			});
			FB.Event.subscribe('auth.authResponseChange', function(response) {
			  alert('auth.authResponseChange: The status of the session is: ' + response.status);
			});
			FB.Event.subscribe('auth.login', function(response) {
			  alert('auth.login: The status of the session is: ' + response.status);
			});
			*/
			
			/*
			FB.getLoginStatus(function(response) {
				if (response.status === 'connected') {
			    	// the user is logged in and has authenticated your
			    	// app, and response.authResponse supplies
			    	// the user's ID, a valid access token, a signed
			    	// request, and the time the access token 
			    	// and signed request each expire
			    	//var uid = response.authResponse.userID;
			    	//var accessToken = response.authResponse.accessToken;
					console.log('FB.getLoginStatus: response[%o]', response);
			  	} else if (response.status === 'not_authorized') {
			    	// the user is logged in to Facebook, 
			    	// but has not authenticated your app
					console.log('FB.getLoginStatus: response[%o]', response);
			  	} else {
			    	// the user isn't logged in to Facebook.
					console.log('FB.getLoginStatus: response[%o]', response);
			  	}
			});
			*/
			
			FB.Event.subscribe('edge.create', function(href){
								
				if (href != undefined) {
					if (pwal_data.debug == "true") {
						console.log('facebook: href['+href+']');
					}
					
					var pwal_info;
					var fb_like_div;
					var fb_like_span;
					var fb_like_iframe;
					var hasFBCommentPopup = false;
					
					// When a user clicks the like there will be a comment popup. We need to find the button with the expanded comment form 
					// and show it to the user...
					jQuery("fb\\:like.pwal_facebook_iframe").each(function( index ) {						
						
						fb_like_div = this;
						var fb_id = jQuery(fb_like_div).attr('id');
						pwal_id = fb_id.replace('pwal_facebook_', '');
						
						if (wpmudev_pwal.buttons[pwal_id] != undefined) {
							var pwal_info_tmp = wpmudev_pwal.buttons[pwal_id];
							if (pwal_data.debug == "true")
								console.log('pwal_info_tmp[%o]', pwal_info_tmp);
							
							fb_like_span = jQuery('span:first-child', fb_like_div);
							//var fb_span_width = jQuery(fb_like_span).width();
							var fb_span_height = jQuery(fb_like_span).height();
							//console.log('fb_span width['+fb_span_width+'] height['+fb_span_height+']');

							fb_like_iframe = jQuery('iframe:first-child', fb_like_span);
							//var fb_iframe_width = jQuery(fb_like_iframe).width();
							var fb_iframe_height = jQuery(fb_like_iframe).height();							
							//console.log('fb_iframe width['+fb_iframe_width+'] height['+fb_iframe_height+']');
							
							//if (fb_iframe_height > 100) { // Should be close to 179 but have seen this as low as 148 and as large as 211
							if (fb_iframe_height > fb_span_height) { // Should be close to 179 but have seen this as low as 148 and as large as 211
								pwal_info = pwal_info_tmp;
								
								if ((pwal_data.options['show_facebook_comment_popup'] != undefined) && (pwal_data.options['show_facebook_comment_popup'] == "true")) {
								
									jQuery(fb_like_span).width('450px');
									jQuery(fb_like_span).height('179px');
								
									hasFBCommentPopup = true;
								} else {
									jQuery(fb_like_div).css('margin-left', '-999em');
								}
								
								// This return does not return from the function. 
								// Per jQuery this is to break out of the loop
								// see note on http://api.jquery.com/each/
								return false;
							}
						}
					});

					// ...then we loop until the user can submitted or close the comment form.
					if ((pwal_info != undefined) && (pwal_info != '')) {
					
						pwal_info['service'] = 'facebook';
						
						if (pwal_data.debug == "true")
							console.log('facebook: pwal_info[%o]', pwal_info);

						var pollTimerFB = window.setInterval(function() {
							if (pwal_data.debug == "true")
								console.log('inside hasFBCommentPopup == true');
								
							if (fb_like_iframe != undefined) {
								var fb_iframe_width = jQuery(fb_like_iframe).width();
								var fb_iframe_height = jQuery(fb_like_iframe).height();

								if (pwal_data.debug == "true")
									console.log('fb_iframe h['+fb_iframe_height+'] w['+fb_iframe_width+']');

								if (fb_iframe_height < 179) {
									hasFBCommentPopup = false;
									
									jQuery(fb_like_span).width(fb_iframe_width);
									jQuery(fb_like_span).height(fb_iframe_height);
								} 
							}
							
							if (hasFBCommentPopup == false) {	
								if (pwal_data.debug == "true")
									console.log('in hasFBCommentPopup == false');
									
								window.clearInterval(pollTimerFB);
								jQuery(document).trigger('pwal_button_action', pwal_info);
							}
							
						}, 1000);
					} else {
						
						for (var pwal_id in wpmudev_pwal.buttons) {
							var pwal_info = wpmudev_pwal.buttons[pwal_id];
							if (pwal_info['href'] == href) {
								pwal_info['service'] = 'facebook';							

								if (pwal_data.debug == "true")
									console.log('facebook: pwal_info #2 [%o]', pwal_info);

								jQuery(document).trigger('pwal_button_action', pwal_info);
							} 
						}
					}
				} else {
					if (pwal_data.debug == "true")
						console.log('facebook: href not defined');
				}
				
			});
			
			FB.Event.subscribe('edge.remove', function(href, widget) {
				if (href != undefined) {
					if (pwal_data.debug == "true")
						console.log('facebook: edge.remove href['+href+']');
				}
			});
		//};

		// Load the SDK asynchronously
//		(function(d, s, id){
//			var js, fjs = d.getElementsByTagName(s)[0];
//			if (d.getElementById(id)) {return;}
//			js = d.createElement(s); js.id = id;
//			if ((pwal_data['facebook-all-js'] != undefined) && (pwal_data['facebook-all-js'] != ''))
//				js.src = pwal_data['facebook-all-js'];
//			else
//				js.src = "//connect.facebook.net/en_US/all.js";
//				
//			fjs.parentNode.insertBefore(js, fjs);
//		}(document, 'script', 'facebook-jssdk'));
	}
});
jQuery(document).ready(wpmudev_pwal.init());
