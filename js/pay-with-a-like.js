var wpmudev_pwal = jQuery.extend(wpmudev_pwal || {}, {
	buttons: {},
	fan_pages: {},
	fb_user: {},
	cookies: {},
	cookies_loaded: false,
	reload_pending: false,
	init: function() {

		if (pwal_data.debug == "true") {
			console.log('pwal_data[%o]', pwal_data);
		}

		wpmudev_pwal.load_browser_cookie();
		
		wpmudev_pwal.setup_facebook_js();
		//wpmudev_pwal.setup_linkedin_js();
		wpmudev_pwal.setup_twitter_js();
		wpmudev_pwal.google_plusone_init_js();
		
		if (pwal_data.buttons != undefined) {
			wpmudev_pwal.buttons = pwal_data.buttons;
		}
		wpmudev_pwal.check_buttons_to_likes();
		wpmudev_pwal.bind_buttons();

	},
	load_browser_cookie: function() {
		if (wpmudev_pwal.cookies_loaded == true)
			return;
			
		wpmudev_pwal.cookies_loaded = true;
		
		if (pwal_data['cookie_key'] == undefined)
			return;
		
		var pay_with_a_like_cookie = wpmudev_pwal.cookie(pwal_data['cookie_key']);
		if ((pay_with_a_like_cookie != undefined) && (pay_with_a_like_cookie != '')) {
			try {
				pay_with_a_like_cookie = JSON.parse(pay_with_a_like_cookie);
				//pay_with_a_like_cookie = wpmudev_pwal.unserialize(pay_with_a_like_cookie);

				if ((pay_with_a_like_cookie != undefined) && (pay_with_a_like_cookie['data'] != undefined) && (!jQuery.isEmptyObject(pay_with_a_like_cookie['data']))) {

					wpmudev_pwal.cookies = pay_with_a_like_cookie['data'];
					if (pwal_data.debug == "true")
						console.log('wpmudev_pwal.cookies: [%o]', wpmudev_pwal.cookies);
				} else {
					if (pwal_data.debug == "true")
						console.log('wpmudev_pwal.cookies: is empty');
				}
			} catch(e) {
				if (pwal_data.debug == "true")
					console.log('wpmudev_pwal.cookies: Error on JSON.parse: '+e.message);
			}
		} else {
			if (pwal_data.debug == "true") {
				console.log('load_browser_cookie: cookie ['+pwal_data['cookie_key']+'] EMPTY');
			}
		}		
	},
	// This function get called after everything is loaded. The concept is when the page loads the server *should* handle the cookie logic correctly
	// But what if the server is cached either via a plugin or some other front-end. And we get a stale page showing the button(s). We can check here
	// For a valid cookie and load the hidden content over AJAX. 
	check_buttons_to_likes: function() {
		
		// We need both cookies and butons. 
		if ((Object.keys(wpmudev_pwal.buttons).length > 0) && (Object.keys(wpmudev_pwal.cookies).length > 0)) {
			var pwal_info_items = {};
			var pwal_urls = '';

			for (var pwal_id in wpmudev_pwal.buttons) {
				if (!wpmudev_pwal.buttons.hasOwnProperty(pwal_id)) continue;

				var pwal_info = wpmudev_pwal.buttons[pwal_id];
				
				if (wpmudev_pwal.cookies[pwal_id] != undefined) {
					if (pwal_data.debug == "true") {
						console.log('check_buttons_to_likes: previous like cookie found: [%o]', wpmudev_pwal.cookies[pwal_id]);
					}
					
					var pwal_id = pwal_info['content_id'];
					pwal_info_items[pwal_id] = pwal_info;
				}
			}
			
			pwal_info_items = wpmudev_pwal.handle_sitewide(pwal_info_items);
			
			//if (Object.keys(pwal_info_items).length > 0) {
				wpmudev_pwal.handle_buttons(pwal_info_items);
			//}
		}
	},
	register_button_href: function(pwal_id, pwal_post_id, pwal_href) {

		// Setup our global array of links. Later when the user clicks the FB Like button we can 
		// determine from the href function parameter of the 'edge.create' what our ID it. 

		wpmudev_pwal.load_browser_cookie();
		
		if (pwal_data.debug == "true")
			console.log('register_button_href: pwal_id['+pwal_id+'] pwal_post_id['+pwal_post_id+'] pwal_href['+pwal_href+']');

		if (wpmudev_pwal.buttons[pwal_id] == undefined) {
			var pwal_info = {};
			pwal_info['id'] 		= pwal_id;
			pwal_info['post_id'] 	= pwal_post_id;
			pwal_info['href']		= pwal_href;

			wpmudev_pwal.buttons[pwal_id] = pwal_info;
		
			if (wpmudev_pwal.cookies[pwal_id] != undefined) {
				cookie_item = wpmudev_pwal.cookies[pwal_id];
				if (pwal_data.debug == "true") {
					console.log('user has previously likes this content');
					console.log('cookie_item[%o]', cookie_item);
					console.log('pwal_info[%o]', pwal_info); 
				}
				var pwal_wrapper_el = jQuery('#pwal_content_wrapper_'+pwal_info['id']);
				if (pwal_wrapper_el != undefined) {
					var pwal_reload = jQuery(pwal_wrapper_el).attr('reload');

					if (pwal_data.debug == "true") {
						console.log('reload['+pwal_reload+']');
						console.log('sitewide ['+pwal_data['options']['sitewide']+']');
					}
					if (pwal_reload == "ajax") {

						// Hide while the AJAX loads
						jQuery(pwal_wrapper_el).hide();
						pwal_info['service'] = '';
						wpmudev_pwal.handle_button(pwal_info, true);
					}	
				}
			} else if ((pwal_data['options']['sitewide'] != undefined) && (pwal_data['options']['sitewide'] == "true")) {
				var pwal_wrapper_el = jQuery('#pwal_content_wrapper_'+pwal_info['id']);
				if (pwal_wrapper_el != undefined) {
					var pwal_reload = jQuery(pwal_wrapper_el).attr('reload');

					if (pwal_data.debug == "true") {
						console.log('reload['+pwal_reload+']');
						console.log('sitewide ['+pwal_data['options']['sitewide']+']');
					}
					if (pwal_reload == "ajax") {

						// Hide while the AJAX loads
						jQuery(pwal_wrapper_el).hide();
						pwal_info['service'] = '';
						wpmudev_pwal.handle_button(pwal_info, true);
					}	
				}
			}
		}
		
	},
	bind_buttons: function () {
		// Setup the event for the click
		jQuery(document).bind('pwal_button_action', function (event, pwal_info) {

			if (pwal_data.debug == "true") {
				console.log('event [%o]', event);
				console.log('pwal_info [%o]', pwal_info);
			}	
			// The call the real function to perform the AJAX
			wpmudev_pwal.handle_button(pwal_info);
		});
	},
	/* This single 'handle_button' is a front-end to the plural 'handle_buttons' where a group of buttons can be send to the server */
	handle_button: function(pwal_info) {
		
		var pwal_info_items = {};
		var pwal_id = pwal_info['content_id'];
		pwal_info_items[pwal_id] = pwal_info;
		
		//pwal_info_items = wpmudev_pwal.handle_sitewide(pwal_info_items);
		console.log('pwal_info_items [%o]', pwal_info_items);
		//console.log('buttons [%o]', wpmudev_pwal.buttons);
		
		wpmudev_pwal.handle_buttons(pwal_info_items);
		
	},
	handle_sitewide: function(pwal_info_items) {

		// If site has enabled sitewide likes then we need to pass in all the visible PWAL button
		if (pwal_data.options['sitewide'] == 'true') {
			for (var pwal_id in wpmudev_pwal.buttons) {
				if (!wpmudev_pwal.buttons.hasOwnProperty(pwal_id)) continue;
				
				var pwal_button = wpmudev_pwal.buttons[pwal_id];
				
				// We don't add the same the one we already added.
				if (pwal_info_items[pwal_id] == undefined) {
					//pwal_button['service'] = pwal_info['service'];

					if (pwal_data.debug == "true") {
						console.log('handle_sitewide: adding button: [%o]', pwal_info_items);
					}
					
					pwal_info_items[pwal_id] = pwal_button;
				}
			}
		}
		return pwal_info_items;
	},
	/* Handler for multiple buttons. Generally called by 'handle_button'. But also called directly from the Facebook functions below */
	handle_buttons: function(pwal_info_items) {
		
		pwal_info_items = wpmudev_pwal.handle_sitewide(pwal_info_items);

		if (pwal_data.debug == "true") {
			console.log('handle_buttons: pwal_info_items [%o]', pwal_info_items);				
		} 	

		if (Object.keys(pwal_info_items).length > 0) {
		
			jQuery.ajax({
				type: "POST",
				url: pwal_data['ajax_url'],
				dataType: "json",
				cache: false,
				data: {  
				    'action': 'pwal_buttons_action',
					'pwal_info_items': pwal_info_items,
					'nonce': pwal_data['ajax-nonce'],
					'debug': pwal_data.debug
				},
				error: function(jqXHR, textStatus, errorThrown ) {
					console.log('handle_button: error HTTP Status['+jqXHR.status+'] '+errorThrown);					
				},
				success: function(reply_data) {
					if (reply_data != undefined) {
						if (pwal_data.debug == "true") {
							console.log('handle_button: reply_data[%o]', reply_data);
						}
					
						if ((reply_data['errorStatus'] != undefined) && (reply_data['errorStatus'] == false)) {
						
							// We want to set the cookie since the server might not register is due to caching. 
							if ((reply_data['cookie'] != undefined) && (!jQuery.isEmptyObject(reply_data['cookie']))) {
								wpmudev_pwal.cookie(pwal_data['cookie_key'], JSON.stringify(reply_data['cookie']), { path: pwal_data['COOKIEPATH'], domain: pwal_data['COOKIEDOMAIN']});

								var pwal_cookie = wpmudev_pwal.cookie(pwal_data['cookie_key']);
								pwal_cookie = JSON.parse(pwal_cookie);
								if (pwal_data.debug == "true") {
									console.log('handle_button: pwal_cookie[%o]', pwal_cookie);
								}

								if ((reply_data['cookie']['data'] != undefined) && (!jQuery.isEmptyObject(reply_data['cookie']['data']))) {
									wpmudev_pwal.cookies = reply_data['cookie']['data'];
								}
							}

							if ((reply_data['pwal_info_items'] != undefined) && (!jQuery.isEmptyObject(reply_data['pwal_info_items']))) {
								var deferred_reload = false;

								for (var pwal_id in reply_data['pwal_info_items']) {
									if (!reply_data['pwal_info_items'].hasOwnProperty(pwal_id)) continue;

									var pwal_info = reply_data['pwal_info_items'][pwal_id];
									if (pwal_data.debug == "true") {
										console.log('handle_button: pwal_info[%o]', pwal_info);
									}
								
									if ((pwal_info['content_reload'] != undefined) && (pwal_info['content_reload'] == 'ajax')) {
										if ((pwal_info['content_id'] != undefined) && (pwal_info['content_id'] != '') && (pwal_info['content'] != undefined)) {
											jQuery('#pwal_content_wrapper_'+pwal_info['content_id']).replaceWith(pwal_info['content']);
										}
									} else {
										deferred_reload = true;
									}		
								}
							
								if (deferred_reload == true) {
									window.location.href = window.location.href;
								}
							}
												
						
						} else if ((reply_data['errorStatus'] != undefined) && (reply_data['errorStatus'] == true)) {
							if (reply_data['errorText'] != undefined) {
								console.log('Errors: '. reply_data['errorText']);
							}
						
						} else {
							console.log('expected JSON [errorStatus] response from sever');
						}
					
					} else {
						console.log('expected JSON response from sever');
					}
				},
				complete: function(e, xhr, settings) {
					//console.log('status=['+e.status+']');
				}
	    	});	
		}
	},
	google_plusone_init_js: function() {
		if ((pwal_data.options['use_google'] == undefined) || (pwal_data.options['use_google'] != "true"))
			return;
			
		if (pwal_data.debug == "true") {
			console.log('google_plusone_init_js: use_google['+pwal_data.options['use_google']+']');			
		}
		
		if ((pwal_data['google-plusone-js'] != undefined) && (pwal_data['google-plusone-js'] != '')) {
		
			if ((pwal_data['google_button_lang'] != undefined) && (pwal_data['google_button_lang'] != '')) {
				if (pwal_data.debug == "true") {
					console.log('google: setting lang[%o]', pwal_data['google_button_lang']);
				}
				window.___gcfg = {
					lang: pwal_data['google_button_lang']
				};
			}
			(function() {
	        	var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
	        	po.src = pwal_data['google-plusone-js'];
	        	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	      	})();
		}
	},
	
	google_plus_callback_js: function(pwal_id, data) {

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

		if ((pwal_data.options['use_linkedin'] == undefined) || (pwal_data.options['use_linkedin'] != "true"))
			return;
			
		if (pwal_data.debug == "true") {
			console.log('setup_linkedin_js: use_linkedin['+pwal_data.options['use_linkedin']+']');			
		}

		if (typeof IN == 'undefined') {
			if ((pwal_data['linkedin-js'] != undefined) && (pwal_data['linkedin-js'] != '')) {
				jQuery.getScript(pwal_data['linkedin-js'], function() {
					//Stuff to do after someScript has loaded
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
					
				});	
			}
		}
	},
	setup_twitter_js: function() {

		if ((pwal_data.options['use_twitter'] == undefined) || (pwal_data.options['use_twitter'] != "true"))
			return;

		if (pwal_data.debug == "true") {
			console.log('setup_twitter_js: use_twtter['+pwal_data.options['use_twitter']+']');			
		}

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
			if (pwal_data.debug == "true") {
				console.log('setup_facebook_js: use_facebook['+pwal_data.options['use_facebook']+']');
			}
			return;
		} else {
			if (pwal_data.debug == "true")
				console.log('setup_facebook_js: use_facebook['+pwal_data.options['use_facebook']+']');			
		}
			
		if (!jQuery('#fb-root').length) {
			if (pwal_data.debug == "true")
				console.log('setup_facebook_js: adding fb-root');
				
			jQuery("body").append('<div id="fb-root"></div>');
		}
		
		if (window.FB != undefined) {
			if (pwal_data.debug == "true")
				console.log('setup_facebook_js: FB already defined in window');
			wpmudev_pwal.facebook_after_load();
		} else {
			if (pwal_data.debug == "true")
				console.log('setup_facebook_js: FB no defined in window');
			window.fbAsyncInit = function() {
				// init the FB JS SDK

				wpmudev_pwal.facebook_after_load();
			};
		}
		
		// Load the SDK asynchronously
		(function(d, s, id){
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement(s); js.id = id;
			if ((pwal_data['facebook-all-js'] != undefined) && (pwal_data['facebook-all-js'] != ''))
				js.src = pwal_data['facebook-all-js'];
			else
				js.src = "//connect.facebook.net/en_US/all.js";
				
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	},
	facebook_after_load: function() {
		if (pwal_data.debug == "true")
			console.log('facebook_after_load: load_facebook['+pwal_data.options['load_facebook']+']');

		if ((pwal_data.options['load_facebook'] != undefined) && (pwal_data.options['load_facebook'] == "true")) {
				
			if (pwal_data.debug == "true")
				console.log('facebook_after_load: calling FB.init');

			//if ((pwal_data.options['facebook_api_key']) && (pwal_data.options['facebook_api_key'] != '')) {
			if (pwal_data.options['facebook_api_use']) {
				
				if (pwal_data.debug == "true")
					console.log('facebook_after_load: facebook API key['+pwal_data.options['facebook_api_key']+']');
					
				FB.init({
					appId: pwal_data.options['facebook_api_key'],
					status: true,
					cookie: true,
					xfbml: true,
			    });
			} else {
				pwal_data.options['facebook_auth_polling'] = 'no';
				FB.init({
					status: true,
					cookie: true,
					xfbml: true,
			    });
			}
		} 
			
		if (pwal_data.debug == "true")
			console.log('facebook_after_load: calling FB.XFBML.parse');
		FB.XFBML.parse();
		
		//if (pwal_data.debug == "true")
		//	console.log('setup_facebook_js: calling facebook_auth_loop');
		wpmudev_pwal.facebook_auth_loop();
		//if (pwal_data.debug == "true")
		//	console.log('setup_facebook_js: returned from facebook_auth_loop');
		
						
		FB.Event.subscribe('edge.create', function(href) {
			
			if (href != undefined) {
				if (pwal_data.debug == "true") {
					console.log('setup_facebook_js: href['+href+']');
				}
									
				var pwal_info;
				var fb_like_div;
				var fb_like_span;
				var fb_like_iframe;
				var hasFBCommentPopup = false;
				
				// When a user clicks the like there will be a comment popup. We need to find the button with the expanded comment form 
				// and show it to the user...
				jQuery("div.pwal_container fb\\:like.pwal_facebook_iframe").each(function( index ) {						
					
					fb_like_div = this;
					var fb_id = jQuery(fb_like_div).attr('id');
					pwal_id = fb_id.replace('pwal_facebook_', '');
					
					if (wpmudev_pwal.buttons[pwal_id] != undefined) {
						var pwal_info_tmp = wpmudev_pwal.buttons[pwal_id];
						if (pwal_data.debug == "true")
							console.log('pwal_info_tmp[%o]', pwal_info_tmp);
						
						fb_like_span = jQuery('span:first-child', fb_like_div);
						var fb_span_height = jQuery(fb_like_span).height();

						fb_like_iframe = jQuery('iframe:first-child', fb_like_span);
						var fb_iframe_height = jQuery(fb_like_iframe).height();							
						
						if (fb_iframe_height > fb_span_height) { // Should be close to 179 but have seen this as low as 148 and as large as 211
							pwal_info = pwal_info_tmp;
							
							if ((pwal_data.options['show_facebook_comment_popup'] != undefined) && (pwal_data.options['show_facebook_comment_popup'] == "true")) {
							
								jQuery(fb_like_span).width('450px');
								jQuery(fb_like_span).height('179px');
								jQuery(fb_like_span).css('z-index', '999');
								//jQuery(fb_like_iframe).css('margin-left', '0');
							
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
						console.log('setup_facebook_js: pwal_info[%o]', pwal_info);

					var pollTimerFB = window.setInterval(function() {
						//if (pwal_data.debug == "true")
						//	console.log('setup_facebook_js: inside hasFBCommentPopup == true');
						
						jQuery('#pwal_container_'+pwal_info['content_id']+' ul').css('overflow', 'visible');
							
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
							jQuery('#pwal_container_'+pwal_info['content_id']+' ul').css('overflow', 'auto');
							
							//if (pwal_data.debug == "true")
							//	console.log('setup_facebook_js: in hasFBCommentPopup == false');
								
							window.clearInterval(pollTimerFB);
							jQuery(document).trigger('pwal_button_action', pwal_info);
						}
						
					}, 500);
				} else {
					
					for (var pwal_id in wpmudev_pwal.buttons) {
						var pwal_info = wpmudev_pwal.buttons[pwal_id];
						if (pwal_info['href'] == href) {
							pwal_info['service'] = 'facebook';							

							if (pwal_data.debug == "true")
								console.log('setup_facebook_js: pwal_info #2 [%o]', pwal_info);

							jQuery(document).trigger('pwal_button_action', pwal_info);
						} 
					}
				}
			} else {
				if (pwal_data.debug == "true")
					console.log('setup_facebook_js: href not defined');
			}
			
		});
			
// Leave for future when we track un-like
//		FB.Event.subscribe('edge.remove', function(href, widget) {
//			if (href != undefined) {
//				if (pwal_data.debug == "true")
//					console.log('facebook: edge.remove href['+href+']');
//			}
//		});
	},
	facebook_auth_loop: function() {
		if (!jQuery('.pwal_container').length) {
			if (pwal_data.debug == "true") {
				console.log('facebook_auth_loop: zero pwal_container elements');
			}
			return;
		}
		
		FB.getLoginStatus(function(response) {
			if (pwal_data.debug == "true") {
				console.log('facebook_auth_loop: FB.getLoginStatus: response[%o]', response);
			}

			if (response.status === 'connected') {

				FB.api('/me', function(response) {
					if ((response != undefined) && (response != '')) {
						if (pwal_data.debug == "true") {
							console.log('facebook_auth_loop: FB.getLoginStatus: /me: response[%o]', response);
						}
						wpmudev_pwal.fb_user = response;

						wpmudev_pwal.facebook_check_access();
					}
				});

		  	} else if (response.status === 'not_authorized') {
		    	// the user is logged in to Facebook, 
		    	// but has not authenticated your app
				//console.log('FB.getLoginStatus: status is not_authorized');
				FB.login(function(response) {
					if (pwal_data.debug == "true") {
						console.log('facebook_auth_loop: FB.getLoginStatus: not_authorized: response [%o]', response);
					}

					if (response.authResponse) {
						//fbUserId = response.authResponse.userID;
						//token = response.authResponse.accessToken;

						FB.api('/me', function(response) {
							if ((response != undefined) && (response != '')) {
								if (pwal_data.debug == "true") {
									console.log('facebook_auth_loop: FB.getLoginStatus: /me: response[%o]', response);
								}
								wpmudev_pwal.fb_user = response;

								wpmudev_pwal.facebook_check_access();
							}
						});
					} else {
						if (pwal_data.debug == "true") {
							console.log('facebook_auth_loop: User cancelled login or did not fully authorize.');
						}
					}
				}, {scope: 'user_likes'});
				
		  	} else {
				if (pwal_data.options['facebook_auth_polling'] == 'yes') {
					var poll_interval = pwal_data.options['facebook_auth_polling_interval'];
					setTimeout(function() {
						wpmudev_pwal.facebook_auth_loop();
					}, poll_interval*1000);					
				}
		  	}
		}, true);	
	},
	facebook_check_access: function() {

		// Good for checking if user likes a FB Fan page. 
		if (Object.keys(pwal_data['options']['facebook_fan_pages']).length > 0) {
			
			//var pwal_facebook_page_ids = pwal_data['options']['facebook_fan_pages'].join();
			if (pwal_data.debug == "true")
				console.log('facebook_check_access: pwal_facebook_page_ids['+pwal_data['options']['facebook_fan_pages']+']');
			
			var query_str = "SELECT page_id, uid FROM page_fan WHERE uid = me() AND page_id IN ("+pwal_data['options']['facebook_fan_pages']+")";
			if (pwal_data.debug == "true")
				console.log('facebook_check_access: page_fan query_str['+query_str+']');

			var query = {
				method: 'fql.query',
				query: query_str
			};

			if (pwal_data.debug == "true")
				console.log('facebook_check_access: FQL: query [%o]', query);
			
			FB.api(query, 
				function(responses) {
					if (pwal_data.debug == "true")
						console.log('facebook_check_access: FQL: responses page_fan: [%o]', responses);
										
					if ((responses != undefined) && (responses != '') && (responses.length > 0)) {
						for (var i = 0; i < responses.length; i++) {
						    var response = responses[i];
						
							if (jQuery.inArray(response['page_id'], pwal_data['options']['facebook_fan_pages']) !== false) {
								wpmudev_pwal.fan_pages[response['page_id']] = response['page_id'];
								if (pwal_data.debug == "true")
									console.log('facebook_check_access: response page_id match['+response['page_id']+']');
							}						
						}
					}
					
					wpmudev_pwal.facebook_check_buttons();
				}
			);
		} else {
			wpmudev_pwal.facebook_check_buttons();
			
		}
	},
	facebook_check_buttons: function() {
		if (Object.keys(wpmudev_pwal.buttons).length > 0) {
			var pwal_info_items = {};
			var pwal_urls = '';


			// Loop through the buttons. We want to build a a string containing all the URLs from the buttons. This will be passed to 
			// Facebook FQL query to check if the user had previously liked. 
			for (var pwal_id in wpmudev_pwal.buttons) {
				if (!wpmudev_pwal.buttons.hasOwnProperty(pwal_id)) continue;

				var pwal_info = wpmudev_pwal.buttons[pwal_id];
			
				// IF the button is already liked then skip. No need to reprocess
				if ((pwal_info['liked'] != undefined) && (pwal_info['liked'] == true)) continue;
			
				// Next, if we have valid fan pages the user has liked we give them access to all 
				if (Object.keys(wpmudev_pwal.fan_pages).length > 0) {
				
					wpmudev_pwal.buttons[pwal_id]['liked'] = true;
				
					//if ((pwal_info['service'] == undefined) || (pwal_info['service'] == ''))
					//	pwal_info['service'] = 'facebook';
					//wpmudev_pwal.handle_button(pwal_info);
					pwal_info_items[pwal_id] = pwal_info;
					continue;
				} else {
				
					if (pwal_urls != '') {
						pwal_urls = pwal_urls+',';
					}
					pwal_urls = pwal_urls+"'"+pwal_info['href']+"'";
				}
			}
			
			if (pwal_urls != '') {	
				var fb_query = "SELECT url, user_id FROM url_like WHERE user_id = me() AND url IN ("+pwal_urls+")";
				if (pwal_data.debug == "true") {
					console.log('facebook_check_buttons: fb_query ['+fb_query+']');
				}
			
				FB.api(
					{
						method: 'fql.query',
						query: fb_query
					}, function(responses) {
						// On the url_like response we get an array of objects. So need to loop over the array and process each. Should be only one
						if ((responses != undefined) && (responses != '') && (responses.length > 0)) {
							for (var i = 0; i < responses.length; i++) {
							    var response = responses[i];
						
								if (pwal_data.debug == "true") {
									console.log('facebook_check_buttons: response[%o]', response);
								}
						
								// Check that the URL and User_id from the response match out pwal url and user_id from previous FB reply on login status
								if ( (response['url'] != undefined) && (response['url'] != '') && (response['user_id'] != undefined) && (response['user_id'] != '')) {

									for (var pwal_id in wpmudev_pwal.buttons) {
										if (!wpmudev_pwal.buttons.hasOwnProperty(pwal_id)) continue;

										var pwal_info = wpmudev_pwal.buttons[pwal_id];

										if ((response['url'] == pwal_info['href']) && (response['user_id'] == wpmudev_pwal.fb_user['id'])) {
									
											if (pwal_data.debug == "true") {
												console.log('facebook_check_buttons: FQL: url_like URL ['+pwal_info['href']+'] [%o] [%o]', response, wpmudev_pwal.fb_user);
											}
									
											wpmudev_pwal.buttons[pwal_id]['liked'] = true;

											pwal_info_items[pwal_id] = pwal_info;
											pwal_info_items[pwal_id]['service'] = '';
											//if ((pwal_info['service'] == undefined) || (pwal_info['service'] == ''))
											//	pwal_info['service'] = 'facebook';
										}
									}
								} 
							}
						} 
						
						//if (Object.keys(pwal_info_items).length > 0) {
							wpmudev_pwal.handle_buttons(pwal_info_items);
						//}
					}
				);
			} else /* if (Object.keys(pwal_info_items).length > 0) */ {
				wpmudev_pwal.handle_buttons(pwal_info_items);
			}
		}	
	},
	cookie: function(name, value, options) {
		if (typeof value != 'undefined') { // name and value given, set cookie
			options = options || {};
			if (value === null) {
	            value = '';
	            options = $.extend({}, options); // clone object since it's unexpected behavior if the expired property were changed
	            options.expires = -1;
	        }
	        var expires = '';
	        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
	            var date;
	            if (typeof options.expires == 'number') {
	                date = new Date();
	                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
	            } else {
	                date = options.expires;
	            }
	            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
	        }
	        // NOTE Needed to parenthesize options.path and options.domain
	        // in the following expressions, otherwise they evaluate to undefined
	        // in the packed version for some reason...
	        var path = options.path ? '; path=' + (options.path) : '';
	        var domain = options.domain ? '; domain=' + (options.domain) : '';
	        var secure = options.secure ? '; secure' : '';
	        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
	    } else { // only name given, get cookie
	        var cookieValue = null;
	        if (document.cookie && document.cookie != '') {
	            var cookies = document.cookie.split(';');
	            for (var i = 0; i < cookies.length; i++) {
	                var cookie = jQuery.trim(cookies[i]);
	                // Does this cookie string begin with the name we want?
	                if (cookie.substring(0, name.length + 1) == (name + '=')) {
	                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
	                    break;
	                }
	            }
	        }
	        return cookieValue;
	    }
	},
	unserialize: function(data) {
		var that = this,
		utf8Overhead = function (chr) {
      	
      		// http://phpjs.org/functions/unserialize:571#comment_95906
			var code = chr.charCodeAt(0);
			if (code < 0x0080) {
        		return 0;
      		}
	
	      	if (code < 0x0800) {
    	    	return 1;
      		}
	      	return 2;
    	},
	    error = function (type, msg, filename, line) {
      		throw new that.window[type](msg, filename, line);
    	},
	    read_until = function (data, offset, stopchr) {
      		var i = 2, buf = [], chr = data.slice(offset, offset + 1);

			while (chr != stopchr) {
				if ((i + offset) > data.length) {
					error('Error', 'Invalid');
				}
				buf.push(chr);
				chr = data.slice(offset + (i - 1), offset + i);
				i += 1;
			}
			return [buf.length, buf.join('')];
		},
		read_chrs = function (data, offset, length) {
			var i, chr, buf;

			buf = [];
			for (i = 0; i < length; i++) {
				chr = data.slice(offset + (i - 1), offset + i);
				buf.push(chr);
				length -= utf8Overhead(chr);
			}
			return [buf.length, buf.join('')];
		},
		_unserialize = function (data, offset) {
			var dtype, dataoffset, keyandchrs, keys, contig,
			length, array, readdata, readData, ccount,
			stringlength, i, key, kprops, kchrs, vprops,
			vchrs, value, chrs = 0,
			typeconvert = function (x) {
				return x;
			};

			if (!offset) {
				offset = 0;
			}
			dtype = (data.slice(offset, offset + 1)).toLowerCase();

			dataoffset = offset + 2;

			switch (dtype) {
				case 'i':
					typeconvert = function (x) {
						return parseInt(x, 10);
					};
					readData = read_until(data, dataoffset, ';');
					chrs = readData[0];
					readdata = readData[1];
					dataoffset += chrs + 1;
					break;
				case 'b':
					typeconvert = function (x) {
						return parseInt(x, 10) !== 0;
					};
					readData = read_until(data, dataoffset, ';');
					chrs = readData[0];
					readdata = readData[1];
					dataoffset += chrs + 1;
					break;
				case 'd':
					typeconvert = function (x) {
						return parseFloat(x);
					};
					readData = read_until(data, dataoffset, ';');
					chrs = readData[0];
					readdata = readData[1];
					dataoffset += chrs + 1;
					break;
				case 'n':
					readdata = null;
					break;
				case 's':
					ccount = read_until(data, dataoffset, ':');
					chrs = ccount[0];
					stringlength = ccount[1];
					dataoffset += chrs + 2;

					readData = read_chrs(data, dataoffset + 1, parseInt(stringlength, 10));
					chrs = readData[0];
					readdata = readData[1];
					dataoffset += chrs + 2;
					
					if (chrs != parseInt(stringlength, 10) && chrs != readdata.length) {
						error('SyntaxError', 'String length mismatch');
					}
					break;
				case 'a':
					readdata = {};

					keyandchrs = read_until(data, dataoffset, ':');
					chrs = keyandchrs[0];
					keys = keyandchrs[1];
					dataoffset += chrs + 2;

					length = parseInt(keys, 10);
					contig = true;

					for (i = 0; i < length; i++) {
						kprops = _unserialize(data, dataoffset);
						kchrs = kprops[1];
						key = kprops[2];
						dataoffset += kchrs;

						vprops = _unserialize(data, dataoffset);
						vchrs = vprops[1];
						value = vprops[2];
						dataoffset += vchrs;

						if (key !== i)
							contig = false;

						readdata[key] = value;
					}
          
					if (contig) {
						array = new Array(length);
						for (i = 0; i < length; i++)
							array[i] = readdata[i];
						readdata = array;
					}

					dataoffset += 1;
					break;
        
				default:
					error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype);
					break;
			}
			return [dtype, dataoffset - offset, typeconvert(readdata)];
		};

		return _unserialize((data + ''), 0)[2];
	}
});
jQuery(document).ready(function() { 
	wpmudev_pwal.init(); 
}); 
