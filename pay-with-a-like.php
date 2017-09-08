<?php
/*
Plugin Name: Pay With a Like
Description: Allows protecting posts/pages until visitor likes the page or parts of the page with Facebook, Linkedin, Twitter or Google +1.
Plugin URI: http://premium.wpmudev.org/project/pay-with-a-like
Version: 2.0.2-BETA-1
Author: WPMU DEV
Author URI: http://premium.wpmudev.org/
TextDomain: pwal
Domain Path: /languages
WDP ID: 7330
*/

/* 
Copyright 2009-2014 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//include_once( dirname(__FILE__) .'/pwal-uninstall.php');

if ( !class_exists( 'PayWithaLike' ) ) {

class PayWithaLike {

	var $version					=	"2.0.2-BETA-1";
	var $pwal_js_data 				= 	array();
	var $_pagehooks 				= 	array();
	var $_options_defaults 			= 	array();
	var $options 					= 	array();

	var $doing_set_cookie;
	var $cookie_key					=	'pay_with_a_like';
	var $cookies					= 	array();

	var $facebook_sdk_ref;
	var $facebook_user_profile;
	
	var $_registered_scripts		= array();	
	var $_registered_styles			= array();		

	var $_fb_api_ep               = 'https://graph.facebook.com/' ; 
	var $_fb_api_ver              = 'v2.8' ; 
	var $_fb_api_acctoken; 
	
	var $sitewide_id;
	
	/**
     * Constructor
     */
	function __construct() {
		// Constants
		$this->plugin_name = "pay-with-a-like";
		$this->plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_name;
		$this->plugin_url = plugins_url( '/' . $this->plugin_name );
		$this->page = 'settings_page_' . $this->plugin_name;
		$this->doing_set_cookie = false;

		$this->sitewide_id				= defined('PWAL_SITEWIDE_ID') ? PWAL_SITEWIDE_ID : 'XXX123456789';
		

		$this->facebook_sdk_ref = false;
		$this->facebook_user_profile = false;
		
		register_activation_hook( __FILE__, array( &$this, 'install' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'uninstall' ) );
		
		// Support for WPMU DEV Dashboard plugin
		global $wpmudev_notices;
		$wpmudev_notices[] = array( 'id'=> 7330, 'name'=> 'Pay With a Like', 'screens' => array( 'toplevel_page_pay-with-a-like', 'pay-with-a-like_page_pay-with-a-like-buttons', 'pay-with-a-like_page_pay-with-a-like-statistics', 'pay-with-a-like_page_pay-with-a-like-customization' ) );
		include_once( dirname(__FILE__) . '/lib/dash-notices/wpmudev-dash-notification.php' );
				
		add_action( 'init', array(&$this, 'init'));
		
		// Admin side actions
		add_action( 'admin_notices', array($this, 'no_button') );						// Warn admin if no Social button is selected
		add_action( 'admin_notices', array($this, 'notice_settings') );					// Notice admin to make some settings
		add_filter( 'plugin_row_meta', array(&$this,'set_plugin_meta'), 10, 2 );		// Add settings link on plugin page
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) ); 						// Creates admin settings window
		
		add_action( 'add_meta_boxes', array( &$this, 'add_custom_box' ) ); 				// Add meta box to posts
		//add_action( 'wp_ajax_delete_stats', array( &$this, 'delete_stats' ) ); 		// Clear statistics
		add_action( 'wp_ajax_pwal_export_stats', array( &$this, 'export_stats' ) ); 	// Export statistics
		add_action( 'admin_print_scripts-'. $this->page , array(&$this,'admin_scripts'));


		add_action( 'wp_head', array(&$this, 'wp_head') );
		add_action( 'wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts') );

		add_action( 'plugins_loaded', array(&$this, 'localization') );			// Localize the plugin

		add_action( 'save_post', array( &$this, 'save_postmeta' ), 10, 2 ); 			// Calls post meta addition function on each save


		add_filter( 'the_content', array( &$this, 'content' ), 8 ); 			// Manipulate the content. 
		add_filter( 'the_content', array($this, 'clear'), 130 );				// Clear if a shortcode is left

		add_shortcode('pwal', array($this, 'pwal_shortcode'));

//		add_action( 'wp_ajax_nopriv_pwal_action', array(&$this, 'set_cookie') );// Ajax request after a button is clicked
//		add_action( 'wp_ajax_pwal_action', array(&$this, 'set_cookie') ); 		// Ajax request after a button is clicked

		add_action( 'wp_ajax_nopriv_pwal_buttons_action', array(&$this, 'handle_buttons_action') );// Ajax request after a button is clicked
		add_action( 'wp_ajax_pwal_buttons_action', array(&$this, 'handle_buttons_action') ); 		// Ajax request after a button is clicked


		add_action( 'wp_footer', array(&$this, 'footer') );
		
		// tinyMCE stuff
		add_action( 'wp_ajax_pwalTinymceOptions', array(&$this, 'tinymce_options') );
		add_action( 'admin_init', array(&$this, 'load_tinymce') );
	
		add_action( 'wp_ajax__pwal_getstats', array(&$this,'ajax__pwal_getstats') );
				
		// By default assume that pages are cachable (Cache plugins are allowed)
		$this->buttons_added = false;
		$this->footer_script = "";
		
	}
	
	function PayWithaLike() {
		$this->__construct();
	}

	function init() {
		
		$this->set_option_defaults();

		// Read all options at once
		$this->options 			= get_option( 'pwal_options', array() );
		$this->options['salt'] 	= get_option( 'pay_with_a_like_salt' );
		
		$this->pwal_js_data['debug'] 		= "false";
		if (isset($_GET['PWAL_DEBUG']))
			$this->pwal_js_data['debug'] 	= "true";
		
		$this->options = wp_parse_args( $this->options, $this->_options_defaults );

		if (($this->options['use_facebook']) 
		 && ($this->options['facebook_api_key']) && (!empty($this->options['facebook_api_key']))
		 && ($this->options['facebook_api_secret']) && (!empty($this->options['facebook_api_secret']))) {
			$this->options['facebook_api_use'] = 'true';
			//$this->facebook_sdk_setup();
		} else {
			$this->options['facebook_api_use'] = 'false';
			$this->options['facebook_auth_polling'] = 'no';
		}
		//unset($this->options['post_types']);
		if (!isset($this->options['post_types'])) {
			$this->options['post_types'] = array();
			
			if ((isset($this->options['post_default'])) && ($this->options['post_default'] == 'enable')) {
				$this->options['post_types']['post'] = 'enable';
			}
			if ((isset($this->options['page_default'])) && ($this->options['page_default'] == 'enable')) {
				$this->options['post_types']['page'] = 'enable';
			}
			if ((isset($this->options['custom_default'])) && ($this->options['custom_default'] == 'enable')) {
				//echo "in here<br />";
				$post_types = get_post_types(array('public' => true), 'objects');
				//echo "post_types<pre>"; print_r($post_types); echo "</pre>";
				if (!empty($post_types)) {
					foreach($post_types as $slug => $post_type) {
						if (($slug == 'attachment') || ($slug == 'post') || ($slug == "page")) {
							continue;
						}
						$this->options['post_types'][$slug] = 'enable';
					}
				}
			}
		}
		
		if (!isset($this->options['show_metabox'])) {
			$this->options['show_metabox'] = $this->options['post_types'];
		}
		
		$this->load_cookie_likes();
		
	}
	
	function set_option_defaults() {
		
		if ( !$salt = get_option( "pay_with_a_like_salt" ) ) {
			$salt = mt_rand();
			add_option( "pay_with_a_like_salt", $salt ); // Save it to be used until it is cleared manually
		}
		
		$this->_options_defaults = array(
			'post_default'					=> 	'enable',
			'page_default'					=> 	'',
			'custom_default'				=> 	'',
			'method'						=> 	'automatic',
			'excerpt'						=> 	100,
			'content_reload'				=> 	'refresh',
			'admin'							=> 	'true',
			'home'							=> 	'',
			'multi'							=> 	'true',
			'authorized'					=> 	'',
			'level'							=> 	'editor',
			'bot'							=> 	'',
			'usermeta'						=>	'true',
			'cookie'						=> 	24,
			'social_buttons' 				=>	 array(
													'facebook'	=>	__('Facebook', 	'pwal'),
													'linkedin'	=>	__('Linkedin', 	'pwal'),
													'twitter'	=>	__('Twitter', 	'pwal'),
													'google'	=>	__('Google+1', 	'pwal')
												),
			'social_button_sort'				=>	'',	// empty will load based on social_button order
			'use_facebook'						=> 	'true',
			'use_linkedin'						=> 	'true',
			'use_twitter'						=> 	'true',
			'use_google'						=> 	'true',
			'url_to_like'						=> 	'',
			'salt'								=> 	$salt,
			'sitewide'							=> 	'',
			'description'						=> 	__('To see the full content, share this page by clicking one of the buttons below','pwal'),
			'random'							=> 	'',
			'no_visit'							=> 	'',
			'load_facebook'						=> 	'true',
			'load_linkedin'						=> 	'true',
			'load_twitter'						=> 	'true',
			'load_google'						=> 	'true',
			'container_width'					=>	'',
			'container_height'					=>	'',
			'container_border_width'			=>	'1',
			'container_border_style'			=>	'solid',
			'container_border_color'			=>	'#E3E3E3',
			'show_facebook_comment_popup' 		=> 	'false',
			'facebook_api_key'					=>	'',
			'facebook_api_secret'				=>	'',
			'facebook_fan_pages'				=>	array(),
			'facebook_layout_style'				=>	'box_count',
			'facebook_auth_polling'				=>	'yes',
			'facebook_auth_polling_interval'	=>	'1',
			'linkedin_layout_style'				=>	'top',
			'linkedin_button_lang'				=>	'',
			'twitter_layout_style'				=>	'vertical',
			//'twitter_button_size'				=>	'medium',
			'twitter_message'					=>	'',
			'twitter_button_lang'				=>	'',
			'google_layout_style'				=>	'tall-bubble',
			'google_button_lang'				=>	'',
			//'facebook_color_scheme'			=>	'',
			'facebook_verb'						=>	'like',
			'facebook_button_lang'				=>	'',
			'facebook_include_share'			=>	'',
			'facebook_include_faces'			=>	''
		);

		
	}

	function wp_head() {
		if (is_admin()) return;
		
		if ( $this->options["use_linkedin"] && $this->options["load_linkedin"] ) {
			$locale = $this->options["linkedin_button_lang"];
			if (!empty($locale)) {
				?><script src="//platform.linkedin.com/in.js" type="text/javascript">lang: <?php echo $locale ?></script><?php
			}
		}
	}
	


	/**
	* Make it possible to save status of postboxes
	*/
	function admin_enqueue_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('postbox');
	}
	
	/**
	* Add Settings link to the plugin page
	* @ http://wpengineer.com/1295/meta-links-for-wordpress-plugins/
	*/
	function set_plugin_meta($links, $file) {
		// create link
		$plugin = plugin_basename(__FILE__);
		if ($file == $plugin) {
			return array_merge(
				$links,
				array( sprintf( '<a href="options-general.php?page=%s">%s</a>', $this->plugin_name, __('Settings') ) )
			);
		}
		return $links;
	}
	
	/**
	 * Load css and js
	 */
	function wp_enqueue_scripts() {
		
		if (is_admin()) return;
		
		wp_enqueue_script('jquery');
		wp_enqueue_script( 'pay-with-a-like-js', plugins_url('/js/pay-with-a-like.js', __FILE__), array('jquery'), $this->version, true);
		$this->_registered_scripts['pay-with-a-like-js'] = 'pay-with-a-like-js';
		
		//echo "user_facebook[". $this->options["use_facebook"] ."] load_facebook[". $this->options["load_facebook"] ."]<br />";
		if ( $this->options["use_facebook"] && $this->options["load_facebook"] ) {
			
			$locale = $this->options["facebook_button_lang"];
			if (empty($locale)) {
				$locale = get_locale();
				$locale = preg_replace('/-/', '_', $locale);
			
				// Fix for de_DE_Sie type locale
				if ( substr_count( $locale, '_' ) > 1 ) {
					$l = explode( '_', $locale );
					$locale = $l[0] . '_' . $l[1];
				}
				$locale = apply_filters( 'pwal_fb_locale', $locale );
			}
			
			// We don't enqueue the JS just yet. We pass the JS url to out script where it will be loaded dynamcially. If needed.
			$this->pwal_js_data['facebook-all-js'] = '//connect.facebook.net/' . $locale . '/all.js';
				
		}
		
		// See also the wp_head function in thei class. The logic is such that when a button_lang is defined we need load 
		// the in.js via the wp_head call because within the <script></script> wrapper we need to pass in the language. We
		// do this in wp_head ONLY if the button_lang value is not empty. If it is empty we instea us the code just below
		// to properly enqueue the JS. damn linkedin!
		if ( $this->options["use_linkedin"] && $this->options["load_linkedin"] ) {
			if (empty($this->options["linkedin_button_lang"])) {
				wp_enqueue_script( 'linkedin', '//platform.linkedin.com/in.js', array( 'jquery' ), '', true );
				
				$this->_registered_scripts['linkedin'] = 'linkedin';
			}
		}
		
		if ( $this->options["use_twitter"] && $this->options["load_twitter"] ) {
			wp_enqueue_script( 'twitter', '//platform.twitter.com/widgets.js', array( 'jquery' ), '', true );
			$this->_registered_scripts['twitter'] = 'twitter';
			
		}
		
		if ( $this->options["use_google"] && $this->options["load_google"] ) {
			$this->pwal_js_data['google-plusone-js'] = '//apis.google.com/js/plusone.js';
			$google_button_lang = $this->options["google_button_lang"];
			if (!empty($google_button_lang)) {
				$this->pwal_js_data['google_button_lang'] = $google_button_lang;
			}
		}
			
		do_action("pwal_additional_button_scripts");
		
		if ( current_theme_supports( 'pay_with_a_like_style' ) )
			return;
		
		$uploads = wp_upload_dir();
		if ( !$uploads['error'] && file_exists( $uploads['basedir'] . "/". $this->plugin_name .".css" ) ) {
			wp_enqueue_style( $this->plugin_name, $uploads['baseurl']. "/". $this->plugin_name .".css", array(), $this->version );
		} else if ( file_exists( $this->plugin_dir. "/css/front.css" ) ) {
			wp_enqueue_style( $this->plugin_name, $this->plugin_url. "/css/front.css", array(), $this->version );
		}
		
		if ($this->buttons_added == true) {
			if (!empty($this->_registered_scripts)) {
				foreach($this->_registered_scripts as $_handle) {
					wp_dequeue_script($_handle);
					wp_deregister_script($_handle);
				}
			}
		}
    }

	/**
     * Localize the plugin
     */
	function localization() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's "languages" folder and name it "pwal-[value in wp-config].mo"
		//load_plugin_textdomain( 'pwal', false, '/pay-with-a-like/languages/' );
		load_plugin_textdomain('pwal', false, dirname(plugin_basename(__FILE__)).'/languages/');
	}
	
	function get_user_role_highest_level($user_role_capabilities = array()) {
		$user_role_hightest_level = 0;

		foreach($user_role_capabilities as $capability => $is_set) {
			if (strncasecmp($capability, 'level_', strlen('level_')) == 0) {
				$capability_int = intval(str_replace('level_', '', $capability));
				if ($capability_int > $user_role_hightest_level) 
					$user_role_hightest_level = $capability_int;
			}
		}
		return $user_role_hightest_level;
	}

	function pwal_shortcode($atts, $content = null) {

				
		$default_atts = array(
			'id'				=>	'',
			'post_id'			=>	'',
			'post_url'			=>	'',
			'wpautop'			=>	'yes',
			'content_reload'	=>	'', 
			'container_width'	=>	'', 
			'description'		=>	'' 
		);
		$atts = shortcode_atts( $default_atts, $atts );
		$atts['method'] = 'tool';
		$atts['content_id'] = $atts['id'];
		unset($atts['id']);

		// IF there is no hidden content. Then no point continuing.
		if ((!$content) || (empty($content))) return '';
								
		// We need to see if we are here from a post_content shortcode or some template calling do_shortcode
		//$post_id = 0;

		if ((empty($atts['post_id'])) && (in_the_loop())) {
			// We basically attempt to verify the post_id being being process is the correct one by parsing the shortcode from the post content if found.
			global $post;
		
			if ((isset($post->post_content)) 
			 && (!empty($post->post_content)) 
			 && (has_shortcode( $post->post_content, 'pwal' ))) {
		
				$pattern = get_shortcode_regex();
				preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches, PREG_SET_ORDER );
				if (!empty($matches)) {
					//echo "matches<pre>"; print_r($matches); echo "</pre>";
					foreach($matches as $match_set) {
						if ($match_set[2] = 'pwal') {
							$shortcode_atts = shortcode_parse_atts( $match_set[3] );
							//echo "shortcode_atts<pre>"; print_r($shortcode_atts); echo "</pre>";
							if ((is_array($shortcode_atts)) && (isset($shortcode_atts['id'])) 
							 && ($shortcode_atts['id'] == $atts['content_id'])) {
								$atts['post_id'] = $post->ID;
								
								break;
							}
						}
					}
				}
			} 
		}
		
		if (!empty($atts['post_id'])) {
			if ((!isset($atts['description'])) || (empty($atts['description']))) {
				$description = get_post_meta( $atts['post_id'], 'pwal_description', true );
				if (!empty($description)) {
					$atts['description'] = $description;
				}
			}
		
			if ((!isset($atts['content_reload'])) || (empty($atts['content_reload']))) {
				$content_reload = get_post_meta( $post->ID, 'pwal_content_reload', true );
				if (!empty($content_reload)) {
					$atts['content_reload'] = $content_reload;
				}
			}

			if ((!isset($atts['container_width'])) || (empty($atts['container_width']))) {
				$container_width = get_post_meta( $post->ID, 'pwal_container_width', true );
				if (!empty($container_width)) {
					$atts['container_width'] = $container_width;
				} 
			}
		}

		if ((!isset($atts['description'])) || (empty($atts['description']))) {
			$atts['description'] = $this->options['description'];
		}

		if ((!isset($atts['content_reload'])) || (empty($atts['content_reload']))) {
			$atts['content_reload'] = $this->options['content_reload'];
		}

		if ((!isset($atts['container_width'])) || (empty($atts['container_width']))) {
			$atts['container_width'] = $this->options['container_width'];
		}
		
		if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
			if ($this->pwal_js_data['debug'] == 'true') {
				echo "PWAL_DEBUG: ". __FUNCTION__ .": atts<pre>". print_r($atts, true) ."</pre>";
			}
		}
		
		$display_buttons = $this->can_display_buttons($atts);
		if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
			if ($this->pwal_js_data['debug'] == 'true') {
				if ($display_buttons === true) {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": can_display_buttons returned: TRUE<br />";
				} else {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": can_display_buttons returned: FALSE<br />";
				}
			}
		}
		
		$display_buttons_filtered = apply_filters('pwal_display_buttons', $display_buttons, $atts['post_id'], $atts['content_id']);
		if ($display_buttons_filtered != $display_buttons) {
			if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
				if ($this->pwal_js_data['debug'] == 'true') {
					if ($display_buttons === true) {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": pwal_display_buttons filter returned: TRUE<br />";
					} else {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": pwal_display_buttons filter returned: FALSE<br />";
					}
				}
			}
		}
		if (!$display_buttons) {
			if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": display_buttons is FALSE: returning hidden content.<br />";
				}
			}
			return $this->the_content_filter($content, $atts['wpautop']);
		}
		
		delete_transient('pwal_'.$atts['content_id']);
		set_transient( 'pwal_'.$atts['content_id'], $content, 1 * HOUR_IN_SECONDS );
		$pwal_content = '<div id="pwal_content_wrapper_'. $atts['content_id'] .'" class="pwal_content_wrapper">'. $this->render_buttons( $atts ) .'</div>';

		return $pwal_content;
	}

	function the_content_filter($content, $wpautop = 'yes') {
		
		$content = stripslashes($content);
		if ((has_filter('the_content', 'wpautop')) && ($wpautop == 'no')) {
			$wpautop_org_state = true;
			remove_filter( 'the_content', 'wpautop' );
		} else if ((!has_filter('the_content', 'wpautop')) && ($wpautop == 'yes')) {
			$wpautop_org_state = false;
			add_filter( 'the_content', 'wpautop' );
		}
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		if ($wpautop_org_state == true) {
			add_filter( 'the_content', 'wpautop' );
		} else if ($wpautop_org_state == false) {
			remove_filter( 'the_content', 'wpautop' );
		}
		return $content;
	}
	
	
	function can_display_buttons($atts) {
		global $post;
		
		if ($this->pwal_js_data['debug'] == 'true') {
			echo "PWAL_DEBUG: ". __FUNCTION__ .": atts<pre>". print_r($atts, true) ."</pre>";
		}

		// Show the bot full content, if selected so
		if ($this->options["bot"] == 'true') {
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": option(bot): true<br />";

			if ($this->is_bot()) {
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": is_bot: true<br />";
				return false;
			} else {
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": is_bot: false<br />";
			}
		} else {
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": option(bot): false<br />";
		}
		
		if ( $this->options["sitewide"] ) {
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": option(sitewide): enabled<br />";

			$post_id = $this->sitewide_id;
			// Check if this post is liked or sitewide like is selected
			foreach ( $this->cookie_likes['data'] as $like ) {
				// Cookie is already encrypted, so we are looking if post_id matches to the encryption 
				if ( $like["post_id"] == md5( $post_id . $this->options["salt"] ) ) { 
					
					if ($this->pwal_js_data['debug'] == 'true')
						echo "PWAL_DEBUG: ". __FUNCTION__ .": sitewide cookie exists<br />";
					
					return false;
				}
			}
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": sitewide cookie not found<br />";
		} else {
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": option(sitewide): disabled<br />";
		}
		
		if (!empty($this->cookie_likes['data'])) {
			foreach ( $this->cookie_likes['data'] as $like ) {
				if (!empty($atts['post_id'])) {
					if ( $like["post_id"] == md5( $atts['post_id'] . $this->options["salt"] ) ) {
						if ($this->pwal_js_data['debug'] == 'true') {
							echo "PWAL_DEBUG: ". __FUNCTION__ .": found like cookie for post_id[". $atts['post_id'] ."]<br />";
						}
						return false;
					}
				} else {
					if ( $like["content_id"] == $atts['content_id'] ) {
						if ($this->pwal_js_data['debug'] == 'true') {
							echo "PWAL_DEBUG: ". __FUNCTION__ .": found like cookie for content_id[". $atts['content_id'] ."]<br />";
						}
						return false;
					}
				}
			}
		} else {
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": previous cookie likes not found<br />";
		}
	
		if (is_user_logged_in()) {
			global $current_user;
			
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": current user logged into WP: true<br />";

			if ($this->options["usermeta"] == 'true') {
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": option(usermeta): true<br />";

				$usermeta_likes = get_user_meta($current_user->ID, 'pwal_likes', true);
				if (!empty($usermeta_likes)) {
					$usermeta_likes = maybe_unserialize($usermeta_likes);
					if (isset($usermeta_likes[$atts['content_id']])) {
						if ($this->pwal_js_data['debug'] == 'true')
							echo "PWAL_DEBUG: ". __FUNCTION__ .": previous usermeta like found<br />";
						return false;
					}
				}
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": previous usermeta like not found<br />";
				}
				
			} else {
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": option(usermeta): false<br />";
				}
			}
			// Show the admin full content, if selected so
			//echo "admin[". $this->options["admin"] ."]<br />";
			if ( $this->options["admin"] == 'true') { 
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": option(admin): true<br />";
				
				if ((current_user_can('administrator')) || (is_super_admin())) {
					if ($this->pwal_js_data['debug'] == 'true')
						echo "PWAL_DEBUG: ". __FUNCTION__ .": current user is admin/super admin<br />";
				
					return false;
				
				} else {
					if ($this->pwal_js_data['debug'] == 'true')
						echo "PWAL_DEBUG: ". __FUNCTION__ .": current user is not admin/super admin<br />";
				}
			} else {
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": option(admin): false<br />";
			}
			
			//echo "authorized[". $this->options['authorized'] ."]<br />";
			if ( $this->options["authorized"] == 'true') { 
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": option(authorized): true<br />";
					
				//echo "current_user<pre>"; print_r($current_user); echo "</pre>";
				$current_user_role_level = $this->get_user_role_highest_level($current_user->allcaps);
				if (!$current_user_role_level) {
					$current_user_role_level = 0;
				}
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": current_user_role_level[". $current_user_role_level ."]<br />";
		
				//echo "level[". $this->options['level'] ."]<br />";
				$log_user_role_level = intval(str_replace('level_', '', $this->options['level']));
				if (!$log_user_role_level) {
					$log_user_role_level = 0;
				}
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": log_user_role_level[". $log_user_role_level ."]<br />";
		
				// If the current user level is less than our limit return the empty content.	
				if ($log_user_role_level <= $current_user_role_level ) {
					return false;
				}
			} else {
				if ($this->pwal_js_data['debug'] == 'true')
					echo "PWAL_DEBUG: ". __FUNCTION__ .": option(authorized): false<br />";
			}
		} else {
			if ($this->pwal_js_data['debug'] == 'true')
				echo "PWAL_DEBUG: ". __FUNCTION__ .": current user logged into WP: false<br />";
		}

		if ((is_object( $post )) && (isset($post->ID)) && ($post->ID > 0) && ($post->ID == $atts['post_id'])) {
			
			if ( is_singular($this->options['post_types'])) {

				$post_info = array(
					'post'		=>	false,
					'post_type'	=>	false
				);

				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": post->ID: [". $post->ID ."] post->post_type: [". $post->post_type ."]<br />";
				}
			
				if (get_post_meta( $post->ID, 'pwal_enable', true ) == 'enable') {
					$post_info['post'] = true;
				}
			
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": options[post_types]:<pre>[". print_r($this->options['post_types'], true) ."]</pre>";
				}
				if (isset($this->options['post_types'][$post->post_type])) {
					$post_info['post_type'] = true;
				}

				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": is_singular<br />";
				}

				if ($post_info['post_type'] != true) {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": post type [". $post->post_type ."] not enabled<br />";
					}
				} else {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": post type [". $post->post_type ."] is enabled<br />";
					}
				}
			
				if ($post_info['post'] != true) {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": post not enabled<br />";
					}
				} else {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": post is enabled<br />";
					}
				}

				if ($this->pwal_js_data['debug'] == 'true') {
					echo "post_info<pre>"; print_r($post_info); echo "</pre>";
				}

				if (($post_info['post'] != true) && ($post_info['post_type'] != true))
					return false;

			} else if (is_front_page())	{
			
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": is_front_page: true<br />";
				}
			
				if ($this->options["home"] != 'true') {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": option(home) not true<br />";
					}
					return false;
				} else {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": option(home) is true<br />";
					}
				}
					
			} else if ( is_archive() ) {
			
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": is_archive<br />";
				}

				if ($this->options["multi"] != 'true') {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": option(multi): not true<br />";
					}
					return false;
				} else {
					if ($this->pwal_js_data['debug'] == 'true') {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": option(multi): true<br />";
					}
				}

			}
			
		} else {
			if ($this->pwal_js_data['debug'] == 'true') {
				echo "PWAL_DEBUG: ". __FUNCTION__ .": not a valid post->ID<br />";
			}
		}
		//die();
		
		
		return true;
	}


	/**
	 * Changes the content according to selected settings
	 *	
	 */
	function content( $content, $force=false ) {
		if (is_admin()) return $content;
			
		global $post;
		
		
		// Find method
		$method = get_post_meta( $post->ID, 'pwal_method', true );

		// IF the post doesn't have a method defined use the global 
		if ( empty($method) ) {
			$method = $this->options["method"]; 
		}
		if ($method == 'tool') {
			// If this post is using the select tool (shortcode) the we let it pass and catch the processing there
			if ( has_shortcode( $content, 'pwal' ) !== false) { 
				return $content;
			}
		}
		
		// But check the post type to make sure it supports an excerpt. 
		if ($method == "manual") {
			// If not set the method to auto.
			if (!post_type_supports($post->post_type, 'excerpt')) {
				$method = 'automatic';
			} else if ( empty($post->post_excerpt) ) {
				$method = 'automatic';
			}
		}
		
		// Unsupported post type
		if ( (!is_object( $post )) && (!$content) )
			return $content;
		
		$description = get_post_meta( $post->ID, 'pwal_description', true );
		if (empty($description))
			$description = $this->options['description'];
		
		$content_reload = get_post_meta( $post->ID, 'pwal_content_reload', true );
		if (empty($content_reload))
			$content_reload = $this->options['content_reload'];

		$container_width = get_post_meta( $post->ID, 'pwal_container_width', true );
		if (empty($container_width))
			$container_width = $this->options['container_width'];
		
		$buttons_atts = array(
			'content_id'		=>	$post->ID,
			'post_id'			=>	$post->ID,
			'container_width'	=>	$container_width,
			'description'		=>	$description,
			'content_reload'	=>	$content_reload
		);
		
		
//		$display_buttons = $this->can_display_buttons($buttons_atts);
//		$display_buttons = apply_filters('pwal_display_buttons', $display_buttons, $post->ID, $post->ID);
//		if (!$display_buttons)
//			return $content;


		$display_buttons = $this->can_display_buttons($buttons_atts);
		if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
			if ($this->pwal_js_data['debug'] == 'true') {
				if ($display_buttons === true) {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": can_display_buttons returned: TRUE<br />";
				} else {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": can_display_buttons returned: FALSE<br />";
				}
			}
		}
		
		$display_buttons_filtered = apply_filters('pwal_display_buttons', $display_buttons, $post->ID, $post->ID);
		if ($display_buttons_filtered != $display_buttons) {
			if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
				if ($this->pwal_js_data['debug'] == 'true') {
					if ($display_buttons === true) {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": pwal_display_buttons filter returned: TRUE<br />";
					} else {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": pwal_display_buttons filter returned: FALSE<br />";
					}
				}
			}
		}
//		echo "after can_display_buttons<br />";
//		die();

		if ($display_buttons == false) {
			if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": display_buttons is FALSE: returning hidden content.<br />";
				}
			}
			return $this->clear($content);
		}
			
		$pwal_enable = $this->is_content_enabled( $post->ID );
		if ($pwal_enable != 'enable') {
			if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
				if ($this->pwal_js_data['debug'] == 'true') {
					if ($pwal_enable === true) {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": is_content_enabled returned: TRUE<br />";
					} else {
						echo "PWAL_DEBUG: ". __FUNCTION__ .": is_content_enabled returned: FALSE<br />";
					}
				}
			}
			return $this->clear($content);
		}
		
		//if ( $this->facebook_check_like_post($post->ID) ) {
		//	return $this->clear($content);
		//	break;
		//}

		//if ( $this->facebook_check_like_fan_pages() ) {
		//	return $this->clear($content);
		//	break;
		//}
		
		// If we are here, it means content will be restricted.
		// Now prepare the restricted output
		if ( $method == "automatic" ) {
			$buttons_atts['method'] = 'automatic';
			
			$content = preg_replace( '%\[pwal(.*?)\](.*?)\[( *)\/pwal( *)\]%is', '$2', $content ); // Clean shortcode
			$temp_arr = explode( " ", $content );
			
			// If the article is shorter than excerpt, show full content
			// Zero value is also valid
			$excerpt_len = get_post_meta( $post->ID, 'pwal_excerpt', true );
			if ( empty($excerpt_len) ) {
				$excerpt_len = $this->options["excerpt"];
			}
			
			if ( count( $temp_arr ) <= $excerpt_len ) {
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": content length [". count( $temp_arr ) ."] less then excerpt length [". $excerpt_len ."]<br />";
				}
				if ($this->doing_set_cookie !== true) {
					return $this->clear($content);
				} else {
					return '';
				}
			}
			
			// Otherwise prepare excerpt
			$e = ""; 
			
			$e_arr = array_slice($temp_arr, 0, intval($excerpt_len)-1);
			if ($e_arr) {
				$e = join(' ', $e_arr);
			}

			// If a tag is broken, try to complete it within reasonable limits, i.e. 50 words
			if ( substr_count( $e, '<') != substr_count( $e, '>' ) ) {
				// Save existing excerpt
				$e_saved = $e;
				$found = false;
				for ( $n=$excerpt_len; $n<$excerpt_len+50; $n++ ) {
					if ( isset( $temp_arr[$n] ) ) {
						$e .= $temp_arr[$n] . " ";
						if ( substr_count( $e, '<') == substr_count( $e, '>' ) ) {
							$found = true;
							break;
						}
					}
				}
				// Revert back to original excerpt if a fix is not found
				if ( !$found )
					$e = $e_saved;
			}

			if ($this->doing_set_cookie !== true) {
			
				return '<div id="pwal_content_wrapper_'. $post->ID .'" class="pwal_content_wrapper">'. $e . $this->render_buttons( $buttons_atts ) .'</div>';
				
			} else {
				return $this->clear($content);
			}
		} else if ( $method == "manual" ) {
			$buttons_atts['method'] = 'automatic';
			//echo "post method[". 'manual' ."] ID[". $post->ID ."]<br />";	
			
			if ($this->doing_set_cookie !== true) {
				return '<div id="pwal_content_wrapper_'. $post->ID .'" class="pwal_content_wrapper">'. $post->post_excerpt . $this->render_buttons( $buttons_atts ) .'</div>';
			} else {
				return $this->clear($content);
			}
		}
		return $this->clear($content); // Script cannot come to this point, but just in case.
	}

	// Checked via Facebook PHP (server side) if the user has previously liked the post URL. If yes we can load the full content. 
	function facebook_check_like_post($post_id = 0) {
		
		if (!$post_id) {
			return false;
		}
		
		if ((!$this->options['use_facebook']) || (!$this->options['facebook_api_key']) || (!$this->options['facebook_api_secret'])) {
			return false;
		}
				
		if (!$this->facebook_sdk_ref) {
			return false;
		}
		
		$this->facebook_sdk_load_user();
		if (!$this->facebook_user_profile) {
			return false;
		}
		
		$post_url = get_permalink($post_id);
		
		//Create Query
		$params = array(
			'method'	=>	'fql.query',
			'query'		=> 	"SELECT url, user_id FROM url_like WHERE user_id = me() AND url='". $post_url. "'"
		);
		//echo "params<pre>"; print_r($params); echo "</pre>";
				
		//Run Query
		$result = $this->facebook_sdk_ref->api($params);
		//echo "result<pre>"; print_r($result); echo "</pre>";
		if ($result) {
			foreach($result as $result_item) {
				if ($result_item['url'] == $post_url) {
					return true;
				}
			}
		}
	}

	function facebook_check_like_fan_pages() {
		
		if ((!$this->options['use_facebook']) || (!$this->options['facebook_api_key']) || (!$this->options['facebook_api_secret'])) {
			return false;
		}
				
		if (!$this->facebook_sdk_ref) {
			return false;
		}
		
		$this->facebook_sdk_load_user();
		if (!$this->facebook_user_profile) {
			return false;
		}
		
		if ((!$this->options['facebook_fan_pages']) || (!count($this->options['facebook_fan_pages']))) 
			return false;
		
		$facebook_fan_pages = join(',', $this->options['facebook_fan_pages']);
		
		//Create Query
		// Use to check if FB user liked a FB page.
		$params = array (
			'method'	=>	'fql.query',
			'query'		=> 'SELECT created_time, page_id, profile_section, type, uid FROM page_fan WHERE uid = me() AND page_id IN ('. $facebook_fan_pages .')'
		);

		//echo "params<pre>"; print_r($params); echo "</pre>";
				
		//Run Query
		$results = $this->facebook_sdk_ref->api($params);
		//echo "results<pre>"; print_r($results); echo "</pre>";

		if ($results) {
			foreach($results as $result) {
				if (array_search($result['page_id'], $this->options['facebook_fan_pages']) !== false) {
					//echo "match found (at least one)<br />";
					return true;
				}
			}
		}
	}
	
//	function facebook_sdk_setup() {
//		if (!$this->facebook_sdk_ref) {
//			//include_once( dirname(__FILE__) .'/lib/facebook-php-sdk/facebook.php');
//
//			$this->facebook_sdk_ref = new PWALFacebook(array(
//		  		'appId'  => $this->options['facebook_api_key'],
//		  		'secret' => $this->options['facebook_api_secret'],
//				)
//			);
//		}		
//	}
	
	function facebook_sdk_load_user() {
		//echo "facebook_sdk_ref<pre>"; print_r($this->facebook_sdk_ref); echo "</pre>";
		
		$user_id = $this->facebook_sdk_ref->getUser();
		//echo "user_id[". $user_id ."]<br />";
		
		if ($user_id) {
			try {
				$this->facebook_user_profile = $this->facebook_sdk_ref->api('/me','GET');
				
			} catch(PWALFacebookApiException $e) {
				//echo "ERROR:<pre>"; print_r($e); echo "</pre>";
				return false;
			}
		} else {
			//echo "user_id is invalid<br />";
			return false;
		}
	}
	
	/**
	 * Try to clear remaining shortcodes 
	 *	
	 */
	function clear( $content ) {
		// Don't even try to touch an object, just in case
		if ( is_object( $content ) )
			return $content;
		else {
			$content = preg_replace( '%\[pwal(.*?)\]%is', '', $content );
			$content = preg_replace( '%\[\/pwal\]%is', '', $content );
			return $content;
		}
	}

	/**
	 *	Number of active buttons
	 */	
	function button_count( ) {
		$n = 0;
		if ( $this->options["use_facebook"] )
			$n++;
		if ( $this->options["use_linkedin"] )
			$n++;
		if ( $this->options["use_twitter"] )
			$n++;
		if ( $this->options["use_google"] )
			$n++;
		$n = apply_filters( "pwal_active_button_count", $n );
		return $n;
	}
	
	function get_buttons_show() {
		$pwal_buttons_show = array();
		
		// We make a copy of the buttons because we run an unset on the ones used.
		$social_buttons_tmp = $this->options['social_buttons'];
		
		if (!empty($this->options['social_button_sort'])) {
			foreach($this->options['social_button_sort'] as $button_key) {

				if (isset($social_buttons_tmp[$button_key])) {
					$pwal_buttons_show[$button_key] = $social_buttons_tmp[$button_key];
					unset($social_buttons_tmp[$button_key]);
				}
			}
		}
		
		// Append any missing ones to the end of the show array.
		if (!empty($social_buttons_tmp)) {
			foreach($social_buttons_tmp as $button_key => $button_label) {
				$pwal_buttons_show[$button_key] = $social_buttons_tmp[$button_key];
			}
		}
		foreach($pwal_buttons_show as $button_key => $button_label) {
			if ((!isset($this->options['use_'.$button_key])) || ($this->options['use_'.$button_key] != 'true'))
				unset($pwal_buttons_show[$button_key]);
		}
		
		return $pwal_buttons_show;
	}
	/**
	 *	Add button html codes and handle post based embedded scripts
	 */	
	function render_buttons( $atts ) {
		//global $post;
		
		//echo "atts (before)<pre>"; print_r($atts); echo "</pre>";
		$default_atts = array(
			'content_id'		=>	0,
			'post_id'			=>	0,
			'post_url'			=>	'',
			'container_width'	=>	'',
			'description'		=>	$this->options['description'],
			'content_reload'	=>	$this->options['content_reload'],
			'method'			=>	$this->options['method']
		);
		//echo "default_atts<pre>"; print_r($default_atts); echo "</pre>";
		
		$atts = shortcode_atts( $default_atts, $atts );
		//echo "atts (after)<pre>"; print_r($atts); echo "</pre>";
		//die();
		
		$n = $this->button_count();
		//echo "button_count[". $n ."]<br />";
		if ( $n == 0 )
			return; // No button selected. Nothing to do.
				
		
		// Check if the post has a alternate URL to Like
		$url_to_like = '';
		
		// First check of the URL was passed to us from the shortcode. See pwal_shortcode where the atts['post_url'] can be set. 
		if ((empty($url_to_like)) && (!empty($atts['post_url']))) {
			$url_to_like = $atts['post_url'];
		}

		// IF not, then check the post meta if the post_id is provided. We don't grab the post permalink just yet. 
		if ((empty($url_to_like)) && (!empty($atts['post_id']))) {
			$url_to_like = get_post_meta( $atts['post_id'], 'pwal_url_to_like', true );
		}

		// If url to like is left empty and Random Like is not selected, take existing post's url
		if (empty($url_to_like)) {
		
			// If Random Like is selected, find a random, published post/page
			if ( $this->options["random"] || apply_filters( 'pwal_force_random', false ) ) {
				global $wpdb;
			
				if (count($this->options['post_types'])) {
					$post_types_sql = '';
					foreach($this->options['post_types'] as $post_type) {
						if (!empty($post_types_sql)) $post_types_sql .= ',';
						$post_types_sql .= "'". $post_type ."'";
					}
				}
			
				$result = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE (post_type IN (". $post_types_sql .")) AND post_status='publish' ORDER BY RAND()");
				if ( $result != null ) {
					$url_to_like = get_permalink( $result->ID );
				} else {
					return;
				}
			} 
			
			if (empty($url_to_like)) {
				if (!empty($this->options["url_to_like"])) {
					$url_to_like = 	$this->options["url_to_like"];
				}
			}
			
			if (empty($url_to_like)) {
				//echo "here atts<pre>"; print_r($atts); echo "</pre>";
				if ($atts['post_id']) {
					$url_to_like = get_permalink( $atts['post_id'] );
				} else {
					$url_to_like = remove_query_arg('PWAL_DEBUG', get_permalink());
					//echo "url_to_like[". $url_to_like ."]<br />";
				}
			}
		}
		
		$url_to_like = remove_query_arg('PWAL_DEBUG', $url_to_like);
		$url_to_like = esc_url( $url_to_like );  // Avoid XSS
		$url_to_like = apply_filters( 'pwal_url_to_like', $url_to_like );

		if ($this->pwal_js_data['debug'] == 'true') {
			echo "PWAL_DEBUG: ". __FUNCTION__ .": url_to_like[". $url_to_like ."]<br />";
		}

		if (empty($atts['container_width'])) {
			
			if ((isset($atts['post_id'])) && (!empty($atts['post_id']))) {
				$atts['container_width'] = get_post_meta( $atts['post_id'], 'pwal_container_width', true );
			}
			
			if ((empty($atts['container_width'])) && (in_the_loop())) {
				
				//global $content_width;
				//if ((isset($content_width)) && (!empty($content_width))) {
				//	$atts['container_width'] = $content_width .'px';
				//}
				$atts['container_width'] = $this->options['container_width'];
				
			} else {
				if ($atts['method'] == 'tool') {
					$atts['container_width'] = '100%';
				}
			}
			if (empty($atts['container_width'])) 
				$atts['container_width'] = '100%';
		}
		//echo "atts<pre>"; print_r($atts); echo "</pre>";
		//die();
		
		$pwal_container_style = '';

		if (!empty($atts['container_width']))
			$pwal_container_style .= 'width:'. $this->check_size_qualifier($atts['container_width']) .'; ';
		
		//echo "pwal_container_style[". $pwal_container_style ."]<br />";
		if (!empty($pwal_container_style)) {
			$pwal_container_style = ' style="'. $pwal_container_style .'" ';
		}
		
		$content  = '<div class="pwal_container" id="pwal_container_'. $atts['content_id'] .'"'. $pwal_container_style .'>';
		if ( $atts['description'] )
			$content .= '<div class="pwal_description">'. $atts['description'] . '</div>';
		$content .= '<div class="pwal_buttons">';
		$script   = "";

		// Now show the buttons. 
		$pwal_buttons_show = $this->get_buttons_show();
		if ((is_array($pwal_buttons_show)) && (!empty($pwal_buttons_show))) {
			foreach($pwal_buttons_show as $button_key => $button_label) {
		
				//$content .= "<li class='pwal_list_item_". $n ."'>";
			
				switch($button_key) {
					case 'facebook':
						if ( $this->options["use_facebook"] ) {
							//echo "options<pre>"; print_r($this->options); echo "</pre>";
						
							$content .= "<div class='pwal_button pwal_button_".$n." pwal_button_facebook'><fb:like href='". $url_to_like ."' ref='pwal_facebook_". $atts['content_id'] ."' class='pwal_facebook_iframe' id='pwal_facebook_". $atts['content_id'] ."'  ";
			
							if (!empty($this->options['facebook_layout_style'])) {
								$content .= ' layout="'. $this->options['facebook_layout_style'] .'"';
							}
							//if (!empty($this->options['facebook_color_scheme'])) {
							//	$content .= ' colorscheme="'. $this->options['facebook_color_scheme'] .'"';
							//}
							if (!empty($this->options['facebook_verb'])) {
								$content .= ' action="'. $this->options['facebook_verb'] .'"';
							}
							if ($this->options['facebook_include_faces'] == 'yes') {
								$content .= ' show_faces="true"';
							}
							if ($this->options['facebook_include_share'] == 'yes') {
								$content .= ' share="true"';
							}
							$content .= "></div>";
						}
						break;
				
					case 'linkedin':
						if ( $this->options["use_linkedin"] ) {
							$linkedin_layout_style = $this->options['linkedin_layout_style'];
							if (empty($linkedin_layout_style)) {
								$linkedin_layout_style = 'top';
							}
							if ($linkedin_layout_style != 'none') {
								$linkedin_data_counter = " data-counter='". $linkedin_layout_style ."' ";
							}
			
							$content .= "<div class='pwal_button pwal_button_".$n." pwal_button_linkedin'><script type='IN/Share' ". $linkedin_data_counter ." data-url='". $url_to_like ."' data-onsuccess='pwal_linkedin_callback_". $atts['content_id'] ."'></script><script type='text/javascript'>function pwal_linkedin_callback_". $atts['content_id'] ."(){ wpmudev_pwal.setup_linkedin_js('". $atts['content_id'] ."'); } </script></div>";
						}
						break;

					case 'twitter':
						if ( $this->options["use_twitter"] ) {
							$twitter_layout_style = $this->options['twitter_layout_style'];
							if (empty($twitter_layout_style)) {
								$twitter_layout_style = 'vertical';
							}

							//$twitter_button_size = $this->options['twitter_button_size'];
							//if (empty($twitter_button_size)) {
								$twitter_button_size = 'medium';
							//}
							$twitter_button_lang = $this->options['twitter_button_lang'];
							if (empty($twitter_button_lang)) {
								$twitter_button_lang = get_locale();;
							}

							$twitter_message = $this->options['twitter_message'];
							if (!empty($twitter_message)) {
								if ((isset($atts['post_id'])) && (intval($atts['post_id']))) {
									$twitter_message = str_replace('[POST_TITLE]', get_the_title($atts['post_id']), $twitter_message);
								} else {
									$twitter_message = str_replace('[POST_TITLE]', '', $twitter_message);
								}
								$twitter_message = str_replace('[SITE_TITLE]', get_option('blogname'), $twitter_message);
								$twitter_message = str_replace('[SITE_TAGLINE]', get_option('blogdescription'), $twitter_message);
							} 
							$twitter_message = apply_filters('pwal_twitter_message', $twitter_message, $atts['post_id']);
							if (!empty($twitter_message)) {
								$twitter_data_message = ' data-text="'. htmlentities($twitter_message) .'" ';
							} else {
								$twitter_data_message = '';
							}
			
							$content .= "<div class='pwal_button pwal_button_".$n." pwal_button_twitter' id='pwal_twitter_". $atts['content_id'] ."' ><a href='https://twitter.com/share' class='twitter-share-button' ". $twitter_data_message ." data-url='". $url_to_like ."' data-size='". $twitter_button_size ."' data-count='". $twitter_layout_style ."' data-lang='". $twitter_button_lang ."'>Tweet</a></div>";
						}
						break;
					
					case 'google':
						if ( $this->options["use_google"] ) {
						
							$google_layout_style = $this->options['google_layout_style'];
							if (empty($google_layout_style)) {
									$google_layout_style = 'tall-bubble';
							} 
							list($google_button_size, $google_button_annotation) = explode('-', $google_layout_style);
			
							$content .= "<div class='pwal_button pwal_button_".$n." pwal_button_google'><g:plusone size='". $google_button_size ."' href='". $url_to_like ."' annotation='". $google_button_annotation ."' callback='pwal_google_callback_". $atts['content_id'] ."'></g:plusone><script type='text/javascript'> function pwal_google_callback_". $atts['content_id'] ."(data){ wpmudev_pwal.google_plus_callback_js('".$atts['content_id'] ."', data); } </script></div>";
						}
						break;
				}
				//$content .= '</li>';
			}		
		}
		$content .= "</div>";
		
		if (!empty($content)) {
			$content .= "</div>";
			
			$content = apply_filters( "pwal_render_button_html", $content, $atts['content_id'], $atts['post_id'] );

			if (!isset($this->pwal_js_data['buttons']))
				$this->pwal_js_data['buttons'] 		= array();
			
			if (!isset($this->pwal_js_data['buttons'][$atts['content_id']])) {
				$this->pwal_js_data['buttons'][$atts['content_id']] 					= array();
				$this->pwal_js_data['buttons'][$atts['content_id']]['content_id'] 		= $atts['content_id'];
				$this->pwal_js_data['buttons'][$atts['content_id']]['post_id'] 			= $atts['post_id'];
				$this->pwal_js_data['buttons'][$atts['content_id']]['href'] 			= $url_to_like;
				$this->pwal_js_data['buttons'][$atts['content_id']]['content_reload'] 	= $atts['content_reload'];
				$this->pwal_js_data['buttons'][$atts['content_id']]['method'] 			= $atts['method'];
			}
				
			//$script  .= " wpmudev_pwal.register_button_href('". $atts['id'] ."', '". $atts['post_id'] ."','". $url_to_like ."'); ";
			//$script  = apply_filters( "pwal_render_button_script", $script, $atts['id'], $atts['post_id'] );

			// Save scripts to be added to the footer 
			//$this->footer_script .= $script;
		
			// Flag that we have already included some common scripts
			$this->buttons_added = true;
		}
		return $content;
	}

	/**
	 *	Add embedded scripts to the footer and compress them
	 */	
	function footer() {
		
		if ($this->buttons_added == true) {
			$this->pwal_js_data['options'] 		= $this->options;
		
			// For the facebook fan pages we remove all the details and use only the page ids. 
			if ((isset($this->pwal_js_data['options']['facebook_fan_pages'])) && (!empty($this->pwal_js_data['options']['facebook_fan_pages']))) {
				$this->pwal_js_data['options']['facebook_fan_pages'] = array_keys($this->pwal_js_data['options']['facebook_fan_pages']);
			}
			$this->pwal_js_data['ajax_url'] 		= admin_url('admin-ajax.php');
			$this->pwal_js_data['ajax-nonce']		= wp_create_nonce("pwal-ajax-nonce");
			$this->pwal_js_data['cookie_key']		= $this->cookie_key;
						
			if ( defined('COOKIEPATH') ) $cookiepath = COOKIEPATH;
			else $cookiepath = "/";
			$this->pwal_js_data['COOKIEPATH']	= $cookiepath;
		
			if ( defined('COOKIEDOMAIN') ) $cookiedomain = COOKIEDOMAIN;
			else $cookiedomain = '';
			$this->pwal_js_data['COOKIEDOMAIN']	= $cookiedomain;
		
			wp_localize_script('pay-with-a-like-js', 'pwal_data', $this->pwal_js_data);
			echo "<script type='text/javascript'>jQuery(document).ready(function($) { ". $this->footer_script. " }); </script>";
			wp_print_scripts('pay-with-a-like-js');
		} else {
			if (!empty($this->_registered_scripts)) {
				foreach($this->_registered_scripts as $_handle) {
					wp_deregister_script($_handle);
					wp_dequeue_script($_handle);
				}
			}
		}
		
	}
	
	function handle_buttons_action() {

		if (!check_ajax_referer( 'pwal-ajax-nonce', 'nonce', false )) {
			$reply_data = array();
			$reply_data['errorStatus'] 	= true;
			$reply_data['errorText']	= __('Something went wrong. Please refresh the page and try again. (nonce)', 'pwal');
			die( json_encode( $reply_data ) );
		}
		
		$this->doing_set_cookie = true;
		
		if (!isset($_POST['pwal_info_items'])) {
			$reply_data = array();
			$reply_data['errorStatus'] 	= true;
			$reply_data['errorText']	= __('Something went wrong. Please refresh the page and try again. (pwal_info_items)', 'pwal');
			die( json_encode( $reply_data ) );
		}
		
		$reply_data = array();
		$reply_data['current_time'] = current_time('mysql', true); 
		
		// Set our global reply status. We will also have one per pwal_info item
		$reply_data['errorStatus'] 	= false;
		$reply_data['errorText']	= '';
		
		$reply_data['pwal_info_items'] = $_POST['pwal_info_items'];

		if ((is_array($reply_data['pwal_info_items'])) && (count($reply_data['pwal_info_items']))) {
			
			foreach($reply_data['pwal_info_items'] as $pwal_id => $pwal_info) {
				$pwal_info['reply_data']['errorStatus'] 	= false;
				$pwal_info['reply_data']['errorText']		= '';				
				
				if (!isset($pwal_info['post_id'])) {
					$pwal_info['reply_data']['errorStatus'] = true;
					$pwal_info['reply_data']['errorText']	= __('Something went wrong. Please refresh the page and try again. (post_id)', 'pwal');			
					
				} else {
					$pwal_info['post_id'] = intval($pwal_info['post_id']);
				}
				
				if (!isset($pwal_info['content_id'])) {
					$pwal_info['reply_data']['errorStatus'] = true;
					$pwal_info['reply_data']['errorText']	= __('Something went wrong. Please refresh the page and try again. (content_id)', 'pwal');
				} else {
					$pwal_info['content_id'] = intval($pwal_info['content_id']);
				}
		
				if (intval($pwal_info['post_id'])) {
					$post = get_post(intval($pwal_info['post_id']));
					//if (!$post) {
					//	$pwal_info['reply_data']['errorStatus'] 	= true;
					//	$pwal_info['reply_data']['errorText']	= __('Something went wrong. Please refresh the page and try again. (invalid post_id)', 'pwal');			
					//}		
				} else {
					// In the rare cases where the post_id is zero like when using the pwal template or shortcode outside of at post...
					// We set the post_id to be the value of the content_id since that is ALWAYS present.
					$pwal_info['post_id'] = $pwal_info['id'];
				}
				
				if ($pwal_info['reply_data']['errorStatus'] == false) {
					$this->handle_button_cookie($pwal_info);
					$this->handle_button_statistics($pwal_info);
					$pwal_info['content'] = $this->handle_button_content($pwal_info, $post);

					// Do this last to allow external processes to be triggered by the PWAL button action
					do_action("pwal_ajax_request", $pwal_info['post_id'], $pwal_info['content_id']);
				}
				$reply_data['pwal_info_items'][$pwal_id] = $pwal_info;
				$this->set_cookie_likes();
			}
			$reply_data['cookie'] = $this->cookie_likes;
		}
		die( json_encode( $reply_data ) );
	}
	
	function handle_button_cookie($pwal_info) {
		
		//echo "pwal_info<pre>"; print_r($pwal_info); echo "</pre>";
		//echo "options<pre>"; print_r($this->options); echo "</pre>";
		
		$new_like = array(
			'content_id' 	=> $pwal_info['content_id'],
			'post_id'		=> md5( $pwal_info['post_id'] . $this->options["salt"] )
		);
		//echo "new_like<pre>"; print_r($new_like); echo "</pre>";
		
		// Always save the "sitewide like" thing
		$new_like_sitewide = array(
			'content_id' 	=> $this->sitewide_id,
			'post_id'		=> md5( $this->sitewide_id . $this->options["salt"] )
		);
		//echo "new_like_sitewide<pre>"; print_r($new_like_sitewide); echo "</pre>";
		
		// Check if user has got likes saved in the cookie

//		echo "DEBUG: new_like<pre>"; print_r($new_like); echo "</pre>";
//		echo "DEBUG: cookie_likes<pre>"; print_r($this->cookie_likes); echo "</pre>";
//		die();
			
		// Prevent cookie growing with duplicate entries	
		$duplicate = false;
		foreach ( $this->cookie_likes['data'] as $like ) {
			if ( isset( $like['content_id'] ) && $like['content_id'] == $new_like['content_id'] ) {
				//echo "DEBUG: Found Duplicate<br />";
				$duplicate = true;
				break; // One match is enough
			}
		}
		//echo "DEBUG: duplicate[". $duplicate ."]<br />";
		//die();
		
		// IF we don't have a duplicate for the liked item we add it.
		if ( !$duplicate )
			$this->cookie_likes['data'][$new_like['content_id']] = $new_like;
		
		
		/** Now processing the Sitewide Like cookie. We always add this  */
		$duplicate_sitewide = false;
		foreach ( $this->cookie_likes['data'] as $like ) {
			if ( isset( $like['content_id'] ) && $like['content_id'] == $new_like_sitewide['content_id'] ) {
				$duplicate_sitewide = true;
				break;
			}
		}
		if ( !$duplicate_sitewide )
			$this->cookie_likes['data'][$new_like_sitewide['content_id']] = $new_like_sitewide;
		
		// Clear empty entries, just in case
		$this->cookie_likes['data'] = array_filter( $this->cookie_likes['data'] );
		
		// Secondary check if the user is WP authenticated. Then stores the like(s) as part of the usermeta
		if ($this->options["usermeta"] == 'true') {
			if (is_user_logged_in()) {
				global $current_user;
			
				$usermeta_likes = get_user_meta($current_user->ID, 'pwal_likes', true);
				if (!$usermeta_likes) $usermeta_likes = array();
			
				$usermeta_likes[$new_like['content_id']] = $new_like;
			
				update_user_meta($current_user->ID, 'pwal_likes', $usermeta_likes);
			}
		}
	}

	function load_cookie_likes() {
		if ( isset( $_COOKIE[$this->cookie_key] ) ) {
			//if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
			//	if ($this->pwal_js_data['debug'] == 'true') {
			//		echo "PWAL_DEBUG: ". __FUNCTION__ .": _COOKIE[". $this->cookie_key ."]<pre>". print_r($_COOKIE[$this->cookie_key], true) ."</pre>";
			//	}
			//}
			//$this->cookie_likes = unserialize( stripslashes( $_COOKIE[$this->cookie_key] ) );
			$this->cookie_likes = json_decode( stripslashes( $_COOKIE[$this->cookie_key] ), true );
			//$this->chat_user = json_decode(stripslashes($_COOKIE['wpmudev-chat-user']), true);		
		} else {
			$this->cookie_likes = array();
		}
		
		//if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
		//	if ($this->pwal_js_data['debug'] == 'true') {
		//		echo "PWAL_DEBUG: ". __FUNCTION__ .": cookie_likes<pre>". print_r($this->cookie_likes, true) ."</pre>";
		//	}
		//}
		
		$upgrade_cookies = false;
		if (!isset($this->cookie_likes['version'])) {
			$this->cookie_likes['version'] = $this->version;
			$upgrade_cookies = true;
		}
		
		if (!isset($this->cookie_likes['data'])) {
			$this->cookie_likes['data'] = array();
			$upgrade_cookies = true;
		}
		if ($upgrade_cookies == true) {
			$cookie_likes = $this->cookie_likes;
			$this->cookie_likes = array();
			if (isset($cookie_likes['version'])) {
				$this->cookie_likes['version'] = $cookie_likes['version'];
				unset($cookie_likes['version']);
			}
			if (isset($cookie_likes['data'])) {
				$this->cookie_likes['data'] = $cookie_likes['data'];
				unset($cookie_likes['data']);
			}

			foreach($cookie_likes as $cookie_id => $cookie_like) {
				//echo "cookie_like<pre>"; print_r($cookie_like); echo "</pre>";
					
				if (is_array($cookie_like)) {
					if ((isset($cookie_like['post_id'])) && (isset($cookie_like['content_id']))) {
						// We found a data item. So save it there. 
						$this->cookie_likes['data'][$cookie_like['content_id']] = $cookie_like;
						//unset($this->cookie_likes[$cookie_id]);
					}
				}
			}
			$this->set_cookie_likes();
		}
		
		//if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
		//	if ($this->pwal_js_data['debug'] == 'true') {
		//		echo "PWAL_DEBUG: ". __FUNCTION__ .": cookie_likes<pre>". print_r($this->cookie_likes, true) ."</pre>";
		//	}
		//}
		
		//echo "after cookie_likes<pre>"; print_r($this->cookie_likes); echo "</pre>";
		//die();
	}
	
	
	function set_cookie_likes() {
		// Let admin set cookie expire at the end of session
		if ( $this->options['cookie'] == 0  || trim( $this->options['cookie'] ) == '' )
			$expire = 0; 
		else
			$expire = time() + 3600 * $this->options['cookie']; 
		
		if ( defined('COOKIEPATH') ) $cookiepath = COOKIEPATH;
		else $cookiepath = "/";
		if ( defined('COOKIEDOMAIN') ) $cookiedomain = COOKIEDOMAIN;
		else $cookiedomain = '';
		
		// Always update the version to the cookie in case we change the structre later. 
		$this->cookie_likes['version'] = $this->version;
		
		// Setting cookie works in ajax request!!
		//@setcookie("pay_with_a_like", serialize($this->cookie_likes), $expire, $cookiepath, $cookiedomain);				
		
		// It looks like FF cannot write the cookie immediately. Let's put a delay.
		//sleep(1);
	}
	
	function handle_button_statistics($pwal_info) {
		if ((isset($pwal_info["service"])) && (!empty($pwal_info["service"]))) {
		
			// We can handle statistics here, as this is ajax request and it will not affect performance
			$statistics = get_option( "pwal_statistics" );
			if ( !is_array( $statistics  ) )
				$statistics = array();
	
			global $blog_id;

			// Let's try to be economical in key usage
			$statistics[] = array(
							'b'		=> $blog_id,
							'p'		=> $pwal_info['post_id'],
							'c'		=> $pwal_info['content_id'],
							's'		=> $pwal_info['service'],
							'i'		=> $_SERVER['REMOTE_ADDR'],
							't'		=> current_time('timestamp')
							);
					
			$statistics = apply_filters( "pwal_ajax_statistics", $statistics, $pwal_info["post_id"], $pwal_info["service"] );
					
			update_option( "pwal_statistics", $statistics );
		}
	}
	
	function handle_button_content($pwal_info, $post) {
		
		$content = '';
		
		
		if ((isset($pwal_info['content_reload'])) && ($pwal_info['content_reload'] == "ajax")) {
			if ((isset($pwal_info['method'])) && ($pwal_info['method'] == 'tool')) {

				$trans_key = 'pwal_'. trim($pwal_info['content_id']);
				//echo "trans_key[". $trans_key ."]<br />";
				$content = get_transient( $trans_key );
				
				//echo "content from transient [". $content ."]<br />";
				
				if (!empty($content)) {
					$content = apply_filters('the_content', $content);
					//echo "content after filters [". $content ."]<br />";
				}
				
				if (!empty($content)) {
					$content = str_replace(']]>', ']]&gt;', $content);
					//echo "content after str_replace [". $content ."]<br />";
				}
				
			} else {
				$content = apply_filters('the_content', $post->post_content);
				$content = str_replace(']]>', ']]&gt;', $content);
			}
		}
		return $content;
	}
	
	
	/**
	 *	Custom box create call
	 *
	 */
	function add_custom_box( ) {
		$pwal_name = __('Pay With a Like', 'pwal');

		if (isset($this->options['show_metabox'])) {
			foreach($this->options['show_metabox'] as $slug => $enabled) {
				add_meta_box( 'pwal_metabox', $pwal_name, array( &$this, 'custom_box' ), $slug, 'side', 'high' );
			}
			wp_enqueue_style( 'pay-with-a-like-admin-css', plugins_url('/css/pay-with-a-like-admin.css', __FILE__), array(), $this->version);
			wp_enqueue_script( 'pay-with-a-like-admin-js', plugins_url('/js/pay-with-a-like-admin.js', __FILE__), array(), $this->version, true);		
			
		} else {

			//echo "options<pre>"; print_r($this->options); echo "</pre>";
			if ($this->options['post_default'] == "enable")
				add_meta_box( 'pwal_metabox', $pwal_name, array( &$this, 'custom_box' ), 'post', 'side', 'high' );

			if ($this->options['page_default'] == "enable")
				add_meta_box( 'pwal_metabox', $pwal_name, array( &$this, 'custom_box' ), 'page', 'side', 'high' );

			$args = array(
				'public'   => true,
				'_builtin' => false
			); 
	
			if ($this->options['custom_default'] == "enable") {
				$post_types = get_post_types( $args );
				if ( is_array( $post_types ) ) {
					foreach ($post_types as $post_type )
						add_meta_box( 'pwal_metabox', $pwal_name, array( &$this, 'custom_box' ), $post_type, 'side', 'high' );
				}
			}
		}
	}

	/**
	 *	Custom box html codes
	 *
	 */
	function custom_box( $post ) {
		//echo "post<pre>"; print_r($post); echo "</pre>";
		// IF the post is not a new post
		if ($post->post_status != 'auto-draft') {
			?><p><a href="<?php echo admin_url('admin.php?page=pay-with-a-like-statistics&post='. $post->ID); ?>"><?php _e('Show Like Statistics', 'pwal'); ?></a></p><?php
		}
		
		wp_nonce_field( plugin_basename(__FILE__), 'pwal_nonce' );
		
		$pwal_enable = get_post_meta( $post->ID, 'pwal_enable', true );
		if (isset($this->options['post_types'][$post->post_type])) {
			$global_enable_label 	= 	__("Default:", "pwal") .' '. __('Enabled','pwal');
			$global_enable_val		= 	'global_enable';
		} else {
			$global_enable_label 	= __("Default:", "pwal") .' '. __('Disabled','pwal');
			$global_enable_val		= 	'global_disable';
		}

		$pwal_enable_values = array(
			$global_enable_val	=>	$global_enable_label,
			'enable'			=>	__('Enabled', 'pwal'),
			'disable'			=>	__('Disabled', 'pwal')
		);
		?>
		<p id="section_pwal_enable"><label for="pwal_enable"><?php _e('Enabled?', 'pwal'); ?></label><br />
		<select name="pwal_enable" id="pwal_enable">
		<?php
			foreach($pwal_enable_values as $val => $label) {
				$selected = selected($pwal_enable, $val);
				
				?><option value="<?php echo $val; ?>" <?php echo $selected ?> ><?php echo $label ?></option><?php
			}
		?>	
		</select></p>
		<?php
		$pwal_method 			= get_post_meta( $post->ID, 'pwal_method', true );
		$global_method_val 		= 	'global_'. $this->options['method'];
		$global_method_label	=	__("Default:", "pwal") .' '. ucwords($this->options['method']);
		if (!post_type_supports($post->post_type, 'excerpt'))  {
			$global_method_val 		= 	'global_automatic';
			$global_method_label	=	__("Default:", "pwal") .' '. __('Automatic', 'pwal');
		}
		
		$pwal_method_values = array(
			$global_method_val	=>	$global_method_label,
			'automatic'			=>	__('Automatic excerpt', 'pwal'),
			'manual'			=>	__('Manual excerpt', 'pwal'),
			'tool'				=>	__('Use selection tool', 'pwal')
		);
		
		if (!post_type_supports($post->post_type, 'excerpt')) 
			unset($pwal_method_values['manual']);
		?>
		<p id="section_pwal_method"><label for="pwal_method"><?php _e('Excerpt Method', 'pwal'); ?></label><br />
		<select name="pwal_method" id="pwal_method">
		<?php
			foreach($pwal_method_values as $val => $label) {
				$selected = selected($pwal_method, $val);
				
				?><option value="<?php echo $val; ?>" <?php echo $selected ?> ><?php echo $label ?></option><?php
			}
		?>	
		</select></p>
		<?php

		$global_excerpt_length = $this->options['excerpt'];
		
		$pwal_excerpt = get_post_meta( $post->ID, 'pwal_excerpt', true );
		?>	
		<p id="section_pwal_excerpt"><label for="pwal_excerpt"><?php _e('Excerpt length (words)', 'pwal') ?></label><br />
		<input type="text" name="pwal_excerpt" id="pwal_excerpt" value="<?php echo $pwal_excerpt; ?>" /><br />
		<span class="pwal-global-value description"><?php echo __('Default', 'pwal'). ': '. $global_excerpt_length ?></span></p>
		
		<?php


		$global_container_width = $this->options['container_width'];
		if (empty($global_container_width)) $global_container_width = '100%';
		
		$pwal_container_width = get_post_meta( $post->ID, 'pwal_container_width', true );
			
		?>	
		<p id="section_pwal_container_width"><label for="pwal_container_width"><?php _e('Button Container Width', 'pwal') ?></label><br />
		<input type="text" name="pwal_container_width" id="pwal_container_width" value="<?php echo $pwal_container_width; ?>" /><br />
		<span class="pwal-global-value description"><?php echo __('Default', 'pwal'). ': '. $global_container_width ?></span></p>
		<?php

		$pwal_url_to_like = get_post_meta( $post->ID, 'pwal_url_to_like', true );
		?>	
		<p id="section_pwal_url_to_like"><label for="pwal_url_to_like"><?php _e('Alt. URL to Like', 'pwal') ?> </label><br />
		<input type="text" name="pwal_url_to_like" id="pwal_url_to_like" value="<?php echo $pwal_url_to_like; ?>" /><br />
		<span class="pwal-global-value description"><?php echo __('If blank current post URL is used', 'pwal') ?></span></p>
		<?php




		$global_reload_val 		= 	'global_'. strtolower( $this->options['content_reload'] );
		
		if (strtolower( $this->options['content_reload'] ) == 'ajax')
			$global_reload_label	=	__("Default:", "pwal") .' '. strtoupper( $this->options['content_reload'] );
		else
			$global_reload_label	=	__("Default:", "pwal") .' '. ucwords( $this->options['content_reload'] );
		$pwal_reload_values = array(
			$global_reload_val	=>	$global_reload_label,
			'ajax'				=>	__("AJAX","pwal"),
			'refresh'			=>	__("Refresh","pwal")
		);
		
		$content_reload = get_post_meta( $post->ID, 'pwal_content_reload', true );
		?>
		<p id="section_pwal_reload"><label for="pwal_content_reload"><?php _e('Reload Content', 'pwal'); ?></label><br />
		<select name="pwal_content_reload" id="pwal_content_reload">
		<?php
			foreach($pwal_reload_values as $val => $label) {
				$selected = selected($content_reload, $val);
				
				?><option value="<?php echo $val; ?>" <?php echo $selected ?> ><?php echo $label ?></option><?php
			}
		?>	
		</select></p>
		<?php

		$pwal_description = get_post_meta( $post->ID, 'pwal_description', true );
		?>
		<p id="section_pwal_description"><label for="pwal_description"><?php _e('Description', 'pwal') ?></label><br />
		<textarea name="pwal_description" id="pwal_description"><?php echo $pwal_description ?></textarea><br />
		<span class="pwal-global-value description"><?php echo __('If blank global setting is used', 'pwal') ?></span></p>
		<?php
	}
	
	
	/**
	 *	Saves post meta values
	 *
	 */
	function save_postmeta( $post_id, $post ) {

		//echo "post_id[". $post_id."]<br />";
		//echo "post<pre>"; print_r($post); echo "</pre>";
		//echo "_GET<pre>"; print_r($_GET); echo "</pre>";
		//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
		//die();

		if ( !wp_verify_nonce( @$_POST['pwal_nonce'], plugin_basename(__FILE__) ) ) 
			return $post_id;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return $post_id;

		// Check permissions
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		}
		elseif ( !current_user_can( 'edit_post', $post_id ) ) 
			return $post_id;

		// Auth ok
		
		if ( isset( $_POST['pwal_enable'] ) ) {
			if (( $_POST['pwal_enable'] == 'enable' ) || ( $_POST['pwal_enable'] == 'disable' )) {
				update_post_meta( $post_id, 'pwal_enable', esc_attr($_POST['pwal_enable']) );
			} else {
				delete_post_meta( $post_id, 'pwal_enable' );
			}
		} else {
			delete_post_meta( $post_id, 'pwal_enable' );
		}
		
		//echo "pwal_method[". $_POST['pwal_method'] ."]<br />";
		//die();
		if ( isset( $_POST['pwal_method'] ) ) {
			if (( $_POST['pwal_method'] == 'automatic' ) || ( $_POST['pwal_method'] == 'manual' ) || ( $_POST['pwal_method'] == 'tool' )) {
				update_post_meta( $post_id, 'pwal_method', esc_attr($_POST['pwal_method']) );
			} else {
				delete_post_meta( $post_id, 'pwal_method' );
			}
		}
		//die();
		
		if ( isset( $_POST['pwal_excerpt'] ) ) {
			if ( !empty($_POST['pwal_excerpt']) ) {
				update_post_meta( $post_id, 'pwal_excerpt', intval($_POST['pwal_excerpt']) );
			} else {
				delete_post_meta( $post_id, 'pwal_excerpt' );
			}
		} else {
			delete_post_meta( $post_id, 'pwal_excerpt' );
		}

		if ( isset( $_POST['pwal_url_to_like'] ) ) {
			if ( $_POST['pwal_url_to_like'] != '') {
				update_post_meta( $post_id, 'pwal_url_to_like', esc_url($_POST['pwal_url_to_like']) );
			} else {
				delete_post_meta( $post_id, 'pwal_url_to_like' );
			}
		} else {
			delete_post_meta( $post_id, 'pwal_url_to_like' );
		}
		
		if ( isset( $_POST['pwal_container_width'] ) ) {
			if ( $_POST['pwal_container_width'] != '') {
				update_post_meta( $post_id, 'pwal_container_width', esc_attr($_POST['pwal_container_width']) );
			} else {
				delete_post_meta( $post_id, 'pwal_container_width' );
			}
		} else {
			delete_post_meta( $post_id, 'pwal_container_width' );
		}

		if ( isset( $_POST['pwal_content_reload'] ) ) {
			if ( ($_POST['pwal_content_reload'] == 'ajax') || ( $_POST['pwal_content_reload'] == 'refresh' ) ) {
				update_post_meta( $post_id, 'pwal_content_reload', esc_attr($_POST['pwal_content_reload']) );
			} else {
				delete_post_meta( $post_id, 'pwal_content_reload' );
			}
		} else {
			delete_post_meta( $post_id, 'pwal_content_reload' );
		}


		if ( isset( $_POST['pwal_description'] ) ) {
			if ( $_POST['pwal_description'] != '') {
				update_post_meta( $post_id, 'pwal_description', esc_attr($_POST['pwal_description']) );
			} else {
				delete_post_meta( $post_id, 'pwal_description' );				
			}
		} else {
			delete_post_meta( $post_id, 'pwal_description' );				
		}
	}
	

	/**
	 *	Set some default settings
	 *
	 */
	function install() {

		//$pwal = new PayWithaLike();
		//$pwal->init();
		
		//update_option( 'pwal_options', $pwal->options );
	}
	
	function uninstall() {
		// does nothing
	}
	
	function checkbox_value($name) {
		return (isset($_POST['pwal'][$name]) ? "true" : "");
	}


	function admin_menu() {
		$this->_pagehooks['pwal'] =  add_menu_page( 
				__('Pay With a Like','pwal'), 
				__('Pay With a Like','pwal'), 
				'manage_options', 
				'pay-with-a-like', 
				array(&$this,'settings') 
			);

		$this->_pagehooks['pwal_settings'] = add_submenu_page( 
			'pay-with-a-like', 
			__('Settings', 'pwal'), 
			__('Settings', 'pwal'), 
			'manage_options', 
			'pay-with-a-like', 
			array(&$this,'settings') 
		);

/*
		$this->_pagehooks['pwal_accessibility'] = add_submenu_page( 
				'pay-with-a-like', 
				__('Accessibility', 'pwal'), 
				__('Accessibility', 'pwal'), 
				'manage_options', 
				'pay-with-a-like-accessibility', 
				array(&$this,'settings') 
			);
*/
		$this->_pagehooks['pwal_buttons'] = add_submenu_page( 
				'pay-with-a-like', 
				__('Social Buttons', 'pwal'), 
				__('Social Buttons', 'pwal'), 
				'manage_options', 
				'pay-with-a-like-buttons', 
				array(&$this,'settings') 
			);

		$this->_pagehooks['pwal_statistics'] = add_submenu_page( 
				'pay-with-a-like', 
				__('Statistics', 'pwal'), 
				__('Statistics', 'pwal'), 
				'manage_options', 
				'pay-with-a-like-statistics', 
				array(&$this,'settings') 
			);

		$this->_pagehooks['pwal_customization'] = add_submenu_page( 
				'pay-with-a-like', 
				__('Customization', 'pwal'), 
				__('Customization', 'pwal'), 
				'manage_options', 
				'pay-with-a-like-customization', 
				array(&$this,'settings') 
			);
		
		
/*		
		$this->_pagehooks['pwal_settings'] = add_options_page(
			__('Pay With a Like Settings','pwal'), 
			__('Pay With a Like','pwal'), 'manage_options',  
			'pay-with-a-like', 
			array(&$this,'settings') );
*/
		add_action( 'load-'. $this->_pagehooks['pwal_settings'], 		array(&$this, 'on_load_panels') );
//		add_action( 'load-'. $this->_pagehooks['pwal_accessibility'], 	array(&$this, 'on_load_panels') );
		add_action( 'load-'. $this->_pagehooks['pwal_buttons'], 		array(&$this, 'on_load_panels') );
		add_action( 'load-'. $this->_pagehooks['pwal_statistics'], 		array(&$this, 'on_load_panels') );
		add_action( 'load-'. $this->_pagehooks['pwal_customization'], 	array(&$this, 'on_load_panels') );
		
	}

	function on_load_panels() {
		$this->process_panel_actions();
		
		if ((isset($_GET['page'])) && ($_GET['page'] == 'pay-with-a-like-buttons')) {
			wp_enqueue_script('jquery-ui-sortable');
		}
		
		wp_enqueue_style( 'pay-with-a-like-admin-css', plugins_url('/css/pay-with-a-like-admin.css', __FILE__), array(), $this->version);
		wp_enqueue_script( 'pay-with-a-like-admin-js', plugins_url('/js/pay-with-a-like-admin.js', __FILE__), array(), $this->version);		
		
		if ((isset($_GET['page'])) && ($_GET['page'] == 'pay-with-a-like-statistics')) {
			//wp_enqueue_style( 'pay-with-a-like-admin-css', plugins_url('/css/pay-with-a-like-admin.css', __FILE__), array('jquery'), $this->version);
			wp_enqueue_script( 'pay-with-a-like-admin-statistics-js', plugins_url('/js/pay-with-a-like-admin-statistics.js', __FILE__), array('jquery'), $this->version);		
			wp_enqueue_script('flot_js', plugins_url('/js/jquery.flot.min.js', __FILE__), array('jquery'));
			wp_enqueue_script('flot_js', plugins_url('/js/jquery.flot.pie.min.js', __FILE__), array('flot_js'));

			add_action('admin_head', array(&$this, 'add_iehead') );
		}
	}

	function add_iehead() {
		echo '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="' . plugins_url('/js/excanvas.min.js', __FILE__) . '"></script><![endif]-->';
	}

	function ajax__pwal_getstats() {

		if (isset($_GET['number'])) {
			$number = intval($_GET['number']);
		} else {
			$number = 12; // show last 12 months
		}

		if (isset($_GET['post_id'])) {
			$post_id = intval($_GET['post_id']);
		} else {
			$post_id = 0;
		}

		// We make a copy of the buttons because we run an unset on the ones used.
		$social_buttons_tmp = $this->options['social_buttons'];
		
		if (!empty($this->options['social_button_sort'])) {
			foreach($this->options['social_button_sort'] as $button_key) {

				if (isset($social_buttons_tmp[$button_key])) {
					$pwal_buttons_show[$button_key] = $social_buttons_tmp[$button_key];
					unset($social_buttons_tmp[$button_key]);
				}
			}
		}
		
		// Append any missing ones to the end of the show array.
		if (!empty($social_buttons_tmp)) {
			foreach($social_buttons_tmp as $button_key => $button_label) {
				$pwal_buttons_show[$button_key] = $social_buttons_tmp[$button_key];
			}
		}

		foreach($pwal_buttons_show as $button_key => $button_label) {
			if ( !$this->options["use_".$button_key]) 
				unset($pwal_buttons_show[$button_key]);
		}

		$stats = get_option( "pwal_statistics" );
		if (empty($stats)) $stats = array(); //return array();;
		//echo "stats<pre>"; print_r($stats); echo "</pre>";
	
	
		/*
		'b'		=> $blog_id,
		'p'		=> $_POST["post_id"],
		'c'		=> $_POST["content_id"],
		's'		=> $_POST["service"],
		'i'		=> $_SERVER["REMOTE_ADDR"],
		't'		=> current_time('timestamp')
		*/
	
		$stat_items = array();
		foreach($stats as $stat) {
			
			$period = date('Ym', $stat['t']);
			if (!isset($stat_items[$period]))
				$stat_items[$period] = array();
			
			if (!isset($stat_items[$period]['facebook']))
				$stat_items[$period]['facebook'] = intval(0);
			if (!isset($stat_items[$period]['linkedin']))
				$stat_items[$period]['linkedin'] = intval(0);
			if (!isset($stat_items[$period]['twitter']))
				$stat_items[$period]['twitter'] = intval(0);
			if (!isset($stat_items[$period]['google']))
				$stat_items[$period]['google'] = intval(0);
			
			if ( ($post_id != 0) && ($stat['p'] != $post_id) ) {
				continue;
			}
			
			if (isset($stat['s'])) {
				$stat['s'] = strtolower(trim($stat['s']));
				//echo "service[". $stat['s'] ."] period[". $period ."]\r\n";
				
				if ($stat['s'] === 'facebook') {
					$stat_items[$period]['facebook'] += 1;
				} else if ($stat['s'] === 'twitter') {
					$stat_items[$period]['twitter'] += 1;
				} else if ($stat['s'] === 'linkedin') {
					$stat_items[$period]['linkedin'] += 1;
				} else if ($stat['s'] === 'google') {
					$stat_items[$period]['google'] += 1;
				}
				
			}
		}

		//echo "stat_items<pre>"; print_r($stat_items); echo "</pre>";

		$output				= array();
		foreach($pwal_buttons_show as $button_key => $button_label) {
			$output[$button_key] = array();
		}
		
		$ticks 				= array();

		$startat = strtotime(date("Y-m-15"));
		for($n = 0; $n < $number; $n++) {

			$place = $number - $n;
			$rdate = strtotime("-$n month", $startat);
			$period = date('Ym', $rdate);
			$ticks[] = array((int) $place, date('M', $rdate) . '<br/>' . date('Y', $rdate) );
		
			if (!isset($stat_items[$period])) {
				// A zero blank row
				foreach($pwal_buttons_show as $button_key => $button_label) {
					$output[$button_key][] = array( (int) $place, (int) 0);
				}
				
			} else {
				foreach($pwal_buttons_show as $button_key => $button_label) {
					$output[$button_key][] = array( (int) $place, (int) $stat_items[$period][$button_key]);
				}
			}
		}

		$return = array();
		$return['ticks'] = $ticks;

		foreach($pwal_buttons_show as $button_key => $button_label) {
			$return['chart'][] = array("label" => $button_label, "data" => $output[$button_key]);
		}

		echo json_encode($return);

		die();
	}

	function process_panel_actions() {
		
		if ( isset( $_POST['pwal_action'] )) {
		
			//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
			//die();
		
			if ( isset($_POST["pwal_action"]) && !wp_verify_nonce($_POST['pwal_nonce'], 'pwal_update_settings') ) {
				add_action( 'admin_notices', array( &$this, 'warning' ) );
				return;
			}
			
			switch ( $_GET['page'] ) {

	    		case 'pay-with-a-like-buttons':
					//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
					//die();
				
					if (isset($_POST['tab'])) {
						switch($_POST['tab']) {
							case 'buttons':
								if (isset($_POST['pwal']['social_button_sort']))
									$this->options['social_button_sort']						= explode(',', $_POST['pwal']['social_button_sort']);
								else
									$this->options['social_button_sort']						= '';

								//echo "social_button_sort<pre>"; print_r($this->options['social_button_sort']); echo "</pre>";
								//die();
				
								if (isset($_POST['pwal']['use_facebook']))
									$this->options['use_facebook']						= $_POST['pwal']['use_facebook'];
								else
									$this->options['use_facebook']						= '';

								// Ensure we don't load the social library IF we are not using it. 
								if ($this->options['use_facebook'] == '')
									$this->options['load_facebook'] = '';
								else {
									if (isset($_POST['pwal']['load_facebook']))
										$this->options['load_facebook']						= $_POST['pwal']['load_facebook'];
									else
										$this->options['load_facebook']						= '';
								}
					
								if (isset($_POST['pwal']['use_linkedin']))
									$this->options['use_linkedin']						= $_POST['pwal']['use_linkedin'];
								else
									$this->options['use_linkedin']						= '';

								// Ensure we don't load the social library IF we are not using it. 
								if ($this->options['use_linkedin'] == '')
									$this->options['load_linkedin'] = '';
								else {
									if (isset($_POST['pwal']['load_linkedin']))
										$this->options['load_linkedin']						= $_POST['pwal']['load_linkedin'];
									else
										$this->options['load_linkedin']						= '';
								}

								if (isset($_POST['pwal']['use_twitter']))
									$this->options['use_twitter']						= $_POST['pwal']['use_twitter'];
								else
									$this->options['use_twitter']						= '';

								// Ensure we don't load the social library IF we are not using it. 
								if ($this->options['use_twitter'] == '')
									$this->options['load_twitter'] = '';
								else {
									if (isset($_POST['pwal']['load_twitter']))
										$this->options['load_twitter']						= $_POST['pwal']['load_twitter'];
									else
										$this->options['load_twitter']						= '';
								}


								if (isset($_POST['pwal']['use_google']))
									$this->options['use_google']						= $_POST['pwal']['use_google'];
								else
									$this->options['use_google']						= '';

								// Ensure we don't load the social library IF we are not using it. 
								if ($this->options['use_google'] == '')
									$this->options['load_google'] = '';
								else {
									if (isset($_POST['pwal']['load_google']))
										$this->options['load_google']						= $_POST['pwal']['load_google'];
									else
										$this->options['load_google']						= '';
								}

								break;
			
							case 'facebook':
								//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
								//die();
					
								if (isset($_POST['pwal']['facebook_api_key']))
									$this->options['facebook_api_key']					= esc_attr($_POST['pwal']['facebook_api_key']);
								else
									$this->options['facebook_api_key']					= '';

								if (isset($_POST['pwal']['facebook_api_secret']))
									$this->options['facebook_api_secret']				= esc_attr($_POST['pwal']['facebook_api_secret']);
								else
									$this->options['facebook_api_secret']				= '';
						
								if (isset($_POST['pwal']['facebook_layout_style']))
									$this->options['facebook_layout_style']				= esc_attr($_POST['pwal']['facebook_layout_style']);
								else
									$this->options['facebook_layout_style']				= '';

								//if (isset($_POST['pwal']['facebook_color_scheme']))
								//	$this->options['facebook_color_scheme']				= esc_attr($_POST['pwal']['facebook_color_scheme']);
								//else
								//	$this->options['facebook_color_scheme']				= '';

								if (isset($_POST['pwal']['facebook_verb']))
									$this->options['facebook_verb']						= esc_attr($_POST['pwal']['facebook_verb']);
								else
									$this->options['facebook_verb']						= '';

								if (isset($_POST['pwal']['facebook_include_share']))
									$this->options['facebook_include_share']			= esc_attr($_POST['pwal']['facebook_include_share']);
								else
									$this->options['facebook_include_share']			= '';

								if (isset($_POST['pwal']['facebook_include_faces']))
									$this->options['facebook_include_faces']			= esc_attr($_POST['pwal']['facebook_include_faces']);
								else
									$this->options['facebook_include_faces']			= '';

								if (isset($_POST['pwal']['show_facebook_comment_popup']))
									$this->options['show_facebook_comment_popup']		= esc_attr($_POST['pwal']['show_facebook_comment_popup']);
								else
									$this->options['show_facebook_comment_popup']		= '';


								if (isset($_POST['pwal']['facebook_button_lang']))
									$this->options['facebook_button_lang']				= esc_attr($_POST['pwal']['facebook_button_lang']);
								else
									$this->options['facebook_button_lang']				= '';

								if (isset($_POST['pwal']['facebook_auth_polling']))
									$this->options['facebook_auth_polling']				= esc_attr($_POST['pwal']['facebook_auth_polling']);
								else
									$this->options['facebook_auth_polling']				= '';

								if (isset($_POST['pwal']['facebook_auth_polling_interval']))
									$this->options['facebook_auth_polling_interval']	= esc_attr($_POST['pwal']['facebook_auth_polling_interval']);
								else
									$this->options['facebook_auth_polling_interval']	= '';






								// Here we take the current form items and compare to the options by key. If the form item was deleted then 
								// it will not be posted into here. So we remove the item from the samed options. Then we process the new URLs
								//echo "facebook_fan_pages<pre>"; print_r($this->options['facebook_fan_pages']); echo "</pre>";
								//echo "facebook_fan_page_urls_current<pre>"; print_r($_POST['pwal']['facebook_fan_page_urls_current']); echo "</pre>";
								//die();
					
								if (empty($_POST['pwal']['facebook_fan_page_urls_current'])) {
									unset($this->options['facebook_fan_pages']);
									$this->options['facebook_fan_pages'] = array();
								} else {
									 foreach($this->options['facebook_fan_pages'] as $fan_page_idx => $fan_page_info) {
										 if (in_array($fan_page_idx, $_POST['pwal']['facebook_fan_page_urls_current']) === false) {
											unset($this->options['facebook_fan_pages'][$fan_page_idx]);
										}
									}
								}
								//echo "facebook_fan_pages<pre>"; print_r($this->options['facebook_fan_pages']); echo "</pre>";
								//die();

								if ((!empty($this->options['facebook_api_key'])) && (!empty($this->options['facebook_api_secret']))
								 && (isset($_POST['pwal']['facebook_fan_page_urls_new'])) && (!empty($_POST['pwal']['facebook_fan_page_urls_new']))) {

									//unset($this->options['facebook_fan_pages']);
									if (!isset($this->options['facebook_fan_pages']))
										$this->options['facebook_fan_pages'] = array();
						
									//echo "facebook_fan_page_urls<pre>"; print_r( $_POST['pwal']['facebookfan_page_urls'] ); echo "</pre>";
									//include_once( dirname(__FILE__) .'/lib/facebook-php-sdk/facebook.php');
	
									if (!class_exists('PWALBaseFacebook')) {
										//error_log('loading PWALBaseFacebook');
										include_once( dirname(__FILE__) .'/lib/facebook-php-sdk/base_facebook.php');
									} 
									
									if (!class_exists('PWALFacebook')) {
										//error_log('loading PWALFacebook');
										include_once( dirname(__FILE__) .'/lib/facebook-php-sdk/facebook.php');
									} 
	
									$facebook = new PWALFacebook(array(
										'appId'  => $this->options['facebook_api_key'],
										'secret' => $this->options['facebook_api_secret'],
										'cookie' => true, 
									));

									$this->_fb_api_acctoken = $this->options['facebook_api_key'] .'|'. $this->options['facebook_api_secret'] ;
									
									$fb_resp_array = array() ; 
									
									foreach($_POST['pwal']['facebook_fan_page_urls_new'] as $url) {
										$url = esc_url(trim($url));
										//echo "url[". $url ."]<br />";
										if (empty($url)) continue;

										$url_path = parse_url($url, PHP_URL_PATH);
										if (!empty($url_path)) {
											$url_path = basename($url_path);
								
                                       		$fb_requrl  = $this->_fb_api_ep . $this->_fb_api_ver .'/'. $url_path;
											$fb_requrl .= '?access_token=' . $this->_fb_api_acctoken ; 
                                       		$fb_requrl .= '&fields=picture,name,about,link';


                                       		$fb_resp = wp_remote_get( $fb_requrl ) ;

                                       		if( wp_remote_retrieve_response_code($fb_resp) == 200 &&  (!is_wp_error($fb_resp)) )
                                       			$fb_resp_array = json_decode( $fb_resp['body'] , true );
										}

										if ((!empty($fb_resp_array))) {
	                                        foreach( $fb_resp_array as $result => $value) {
		                                        if( $result == 'id' ) : 

		                                        	$fb_resp_array['page_id']     = $value ; 
		                                            $fb_resp_array['page_url']    = $fb_resp_array['link'] ; 
		                                            $fb_resp_array['pic_square']  = $fb_resp_array['picture']['data']['url'] ; 
		                                        	$this->options['facebook_fan_pages'][$value] = $fb_resp_array;

		                                        endif ; 
		                                    }
	                                    }

								 	}
	
								}

								break;
				
							case 'linkedin':
								if (isset($_POST['pwal']['linkedin_layout_style']))
									$this->options['linkedin_layout_style']				= esc_attr($_POST['pwal']['linkedin_layout_style']);
								else
									$this->options['linkedin_layout_style']				= '';

								if (isset($_POST['pwal']['linkedin_button_lang']))
									$this->options['linkedin_button_lang']				= esc_attr($_POST['pwal']['linkedin_button_lang']);
								else
									$this->options['linkedin_button_lang']				= '';

								break;

							case 'twitter':
								if (isset($_POST['pwal']['twitter_layout_style']))
									$this->options['twitter_layout_style']				= esc_attr($_POST['pwal']['twitter_layout_style']);
								else
									$this->options['twitter_layout_style']				= '';

								//if (isset($_POST['pwal']['twitter_button_size']))
								//	$this->options['twitter_button_size']				= esc_attr($_POST['pwal']['twitter_button_size']);
								//else
								//	$this->options['twitter_button_size']				= '';

								if (isset($_POST['pwal']['twitter_message']))
									$this->options['twitter_message']					= esc_attr($_POST['pwal']['twitter_message']);
								else
									$this->options['twitter_message']					= '';
				
								if (isset($_POST['pwal']['twitter_button_lang']))
									$this->options['twitter_button_lang']				= esc_attr($_POST['pwal']['twitter_button_lang']);
								else
									$this->options['twitter_button_lang']				= '';
					
								break;
				
							case 'google':
								//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
								//die();
								
								if (isset($_POST['pwal']['google_layout_style']))
									$this->options['google_layout_style']				= esc_attr($_POST['pwal']['google_layout_style']);
								else
									$this->options['google_layout_style']				= '';
					
								if (isset($_POST['pwal']['google_button_lang']))
									$this->options['google_button_lang']				= esc_attr($_POST['pwal']['google_button_lang']);
								else
									$this->options['google_button_lang']				= '';


								break;
						}
					}
					break;
					
	    		case 'pay-with-a-like-statistics':
					break;
			
	    		case 'pay-with-a-like-customization':
					break;

				case 'pay-with-a-like': 

					//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
					//die();
				
					if (isset($_POST['pwal']['post_types']))
						$this->options['post_types']						= $_POST['pwal']['post_types'];
					else
						$this->options['post_types']						= array();

					
					if (isset($_POST['pwal']['show_metabox']))
						$this->options['show_metabox']						= $_POST['pwal']['show_metabox'];
					else
						$this->options['show_metabox']						= array();
					

					if (isset($_POST['pwal']['method']))
						$this->options['method']							= $_POST['pwal']['method'];
					else
						$this->options['method']							= '';

					if (isset($_POST['pwal']['excerpt']))
						$this->options['excerpt']							= esc_attr($_POST['pwal']['excerpt']);
					else
						$this->options['excerpt']							= '';


					if (isset($_POST['pwal']['description']))
						$this->options['description']						= esc_attr($_POST['pwal']['description']);
					else
						$this->options['description']						= '';


					if (isset($_POST['pwal']['container_width']))
						$this->options['container_width']					= esc_attr($_POST['pwal']['container_width']);
					else
						$this->options['container_width']					= '';


					if (isset($_POST['pwal']['content_reload']))
						$this->options['content_reload']					= $_POST['pwal']['content_reload'];
					else
						$this->options['content_reload']					= 'refresh';

					
					if (isset($_POST['pwal']['home']))
						$this->options['home']								= esc_attr($_POST['pwal']['home']);


					if (isset($_POST['pwal']['multi']))
						$this->options['multi']								= esc_attr($_POST['pwal']['multi']);


					if (isset($_POST['pwal']['admin']))
						$this->options['admin']								= esc_attr($_POST['pwal']['admin']);


					if (isset($_POST['pwal']['authorized']))
						$this->options['authorized']						= esc_attr($_POST['pwal']['authorized']);


					if (isset($_POST['pwal']['level']))
						$this->options['level']								= esc_attr($_POST['pwal']['level']);


					if (isset($_POST['pwal']['bot']))
						$this->options['bot']								= esc_attr($_POST['pwal']['bot']);


					if (isset($_POST['pwal']['cookie']))
						$this->options['cookie']							= intval($_POST['pwal']['cookie']);

					if (isset($_POST['pwal']['usermeta']))
						$this->options['usermeta']							= esc_attr($_POST['pwal']['usermeta']);

					
					if (isset($_POST['pwal']['sitewide']))
						$this->options['sitewide']							= esc_attr($_POST['pwal']['sitewide']);
					else
						$this->options['sitewide']							= '';

					if (isset($_POST['pwal']['url_to_like']))
						$this->options['url_to_like']						= esc_url($_POST['pwal']['url_to_like']);
					else
						$this->options['url_to_like']						= '';

					if (isset($_POST['pwal']['random']))
						$this->options['random']							= $_POST['pwal']['random'];
					else
						$this->options['random']							= '';
					
					
					break;
				
				default:
					break;
			}
			//echo "options<pre>"; print_r($this->options); echo "</pre>";
			
			$this->options = apply_filters("pwal_before_save_options", $this->options);
			//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
			//echo "options<pre>"; print_r($this->options); echo "</pre>";
			//die();
			update_option( 'pwal_options', $this->options );
			add_action( 'admin_notices', array ( &$this, 'saved' ) );
		} else if ((isset($_GET['page'])) && ($_GET['page'] == 'pay-with-a-like-statistics')) {
			if ((isset($_GET['action'])) && ($_GET['action'] == 'pwal_delete_stats')) {
				if ((isset($_GET['pwal_nonce'])) || (wp_verify_nonce($_GET['pwal_nonce'], 'pwal_delete_stats'))) {
					//echo "_GET<pre>"; print_r($_GET); echo "</pre>";
					delete_option( 'pwal_statistics' );
					add_action( 'admin_notices', array ( &$this, 'saved' ) );

				} else {
					echo "pwal_nonce failed<br />";
				}
			}
		}
	}
	
	/**
	 *	Prints "saved" message on top of Admin page 
	 */
	function saved( ) {
		echo '<div class="updated fade"><p>'.__("<b>[Pay With a Like]</b> Settings saved","pwal").'</p></div>';
	}

	/**
	 *	Prints warning message on top of Admin page 
	 */
	function warning( ) {
		echo '<div class="error"><p>'.__("<b>[Pay With a Like]</b> You are not authorised to do this.","pwal").'</b></p></div>';
	}

	/**
	 * Warn admin if no button is activated
	 */	
	function no_button () {
		if ( $this->button_count() == 0 ) {
			echo '<div class="updated fade"><p>' .
				__("<b>[Pay With a Like]</b> You didn't select any buttons. Plugin will not function as expected.", 'pwal') .
			'</p></div>';
		}
	}
	
	/**
	 * Warn admin to make some settings
	 */	
	function notice_settings () {
		global $pwal_options_page;
		$screen = get_current_screen();
		
		// If admin visits setting page, remove this annoying message :P
		if ( $screen->id == $pwal_options_page && !$this->options["no_visit"] ) {
			$this->options["no_visit"] = "true";
			update_option( "pwal_options", $this->options );
		}
		// If this is localhost, warn admin
		if ( stripos( home_url(), 'localhost' ) !== false )
			echo '<div class="error"><p>'.__("<b>[Pay With a Like]</b> As Social Networking scripts cannot access your local pages, plugin will not function properly in localhost.","pwal").'</b></p></div>';

		// Warn wrong language setting
		$locale = preg_replace('/-/', '_', get_locale());
		if ( 'en' == strtolower( $locale ) && $this->options["use_facebook"] && $this->options["load_facebook"] )
			echo '<div class="error"><p>'.__("<b>[Pay With a Like]</b> Your WPLANG setting in wp-config.php is wrong. Facebook button will not work.","pwal").'</b></p></div>';

	}

	/**
	 *	Get saved postbox states
	 */
	function postbox_classes( $css_id ) {
		if ( function_exists( 'postbox_classes' ) )
			return postbox_classes( $css_id, $this->page );
		else
			return "";

	}
	
	function is_content_enabled($post_id = 0) {
		global $post;

		if ((!$post_id) || (empty($post_id))) {
			if (!isset($post->ID))
				return;
			$post_id = $post->ID;
		}
		$pwal_enable =  get_post_meta( $post_id, 'pwal_enable', true );
		//echo "pwal_enable[". $pwal_enable ."]<br />";
		if (empty($pwal_enable)) {
			if (isset($this->options['post_types'][$post->post_type])) 
				$pwal_enable = 'enable';
			else
				$pwal_enable = 'disable';
		}		
		return $pwal_enable;
	}
	
	/**
	 *	Admin settings HTML code 
	 */
	function settings() {

		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.', 'pwal') );
		}

		$pwal_page = esc_attr($_GET['page']);
		if (empty($pwal_page)) return;

		require_once dirname(__FILE__) . '/lib/pwal_admin_panels.php';
		?>
		<div class="wrap pwal_wrap">
    		<?php
				switch($pwal_page) {
					case 'pay-with-a-like':
					
			        	?>
						<form id="pwal-settings-form-<?php echo $pwal_page; ?>" class="pwal-settings-form" method="post" action="<?php admin_url( '/options-general.php?page='. $pwal_page ); ?>">
							<?php wp_nonce_field( 'pwal_update_settings', 'pwal_nonce' ); ?>
							<input type="hidden" name="pwal_action" value="update_pwal" />
							<input type="hidden" name="page" value="<?php echo $pwal_page; ?>" />

							<h2><?php _e('Pay With a Like Settings', 'pwal') ?></h2>
							<div id="poststuff" class="pwal-settings">
								<div id="post-body" class="metabox-holder columns-1">
									<div id="post-body-content">
										<?php pwal_admin_panels_global(); ?>
										<?php pwal_admin_panels_defaults(); ?>
										<?php pwal_admin_panels_container(); ?>
										<?php pwal_admin_panels_visibility(); ?>
									</div>
								</div>
							</div>
							<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes', 'pwal') ?>" /></p>
						</form>
						<?php
						break;
	
					case 'pay-with-a-like-buttons':
					    $tabs = array( 	
				    		'buttons'		=> __('Social Buttons', 'pwal'),
				    		'facebook'		=> __('Facebook', 'pwal'),
				    		'linkedin'		=> __('LinkedIn', 'pwal'),
				    		'twitter'		=> __('Twitter', 'pwal'),
				    		'google'		=> __('Google', 'pwal'),
						);
						?>
						<form id="pwal-settings-form-<?php echo $pwal_page; ?>" class="pwal-settings-form" method="post" action="<?php admin_url( '/options-general.php?page='. $pwal_page ); ?>">
							<?php wp_nonce_field( 'pwal_update_settings', 'pwal_nonce' ); ?>
							<input type="hidden" name="pwal_action" value="update_pwal" />
							<input type="hidden" name="page" value="<?php echo $pwal_page; ?>" />
							<h2><?php _e('Pay With a Like Social Buttons','pwal') ?></h2>
							
							<h2 class="nav-tab-wrapper"><?php
								if ( isset( $_GET['tab'] )) {
									$current_tab = $_GET['tab']; 						
									if (!isset($tabs[$current_tab])) {
										$current_tab = 'buttons';
									}
								} else {
									$current_tab = 'buttons'; 
								}
								?><input type="hidden" name="tab" value="<?php echo $current_tab; ?>" /><?php
							    foreach( $tabs as $tab => $name ) {
									if (($tab == "facebook") && ($this->options['use_facebook'] != "true")) continue;
									if (($tab == "linkedin") && ($this->options['use_linkedin'] != "true")) continue;
									if (($tab == "twitter") && ($this->options['use_twitter'] != "true")) continue;
									if (($tab == "google") && ($this->options['use_google'] != "true")) continue;
	
									$class = ( $tab == $current_tab ) ? ' nav-tab-active' : '';
					        		echo "<a class='nav-tab$class' href='?page=$pwal_page&tab=$tab'>$name</a>";
					    		} 
								?></h2>
								<div id="poststuff" class="pwal-settings">
									<div id="post-body" class="metabox-holder columns-1">
										<div id="post-body-content">
										<?php
											switch($current_tab) {
												case 'facebook':
													pwal_admin_panels_facebook();
													break;

												case 'linkedin':
													pwal_admin_panels_linkedin();
													break;

												case 'twitter':
													pwal_admin_panels_twitter();
													break;
			
												case 'google':
													pwal_admin_panels_google();
													break;

									    		case 'buttons':
													pwal_admin_panels_social();
													break;
											}
										?>
										</div>
									</div>
								</div>
								<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes', 'pwal') ?>" /></p>
								
							</form>
						<?php
						break;
			
					case 'pay-with-a-like-statistics':
						//echo "_GET<pre>"; print_r($_GET); echo "</pre>";
					
						?>
						<h2><?php _e('Pay With a Like Statistics', 'pwal') ?><?php
						if (isset($_GET['post'])) {
							?> - <a href="<?php echo get_edit_post_link( intval($_GET['post']) ); ?>"><?php echo get_the_title(intval($_GET['post'])) ?> </a><?php
						}
						?></h2>
						<div id="poststuff" class="pwal-settings">
							<div id="post-body" class="metabox-holder columns-2">
								<div id="post-body-content">
									<?php pwal_admin_panels_statistic_chart(); ?>
									<?php //pwal_admin_panels_statistics(); ?>
								</div>
								<div id="postbox-container-1" class="postbox-container">
									<div id="side-sortables" class="meta-box-sortables ui-sortable">
										<?php pwal_admin_panels_statistic_actions(); ?>
										<?php pwal_admin_panels_statistic_summary(); ?>
										<?php pwal_admin_panels_statistic_top_pages(); ?>
										<?php pwal_admin_panels_statistic_top_ipaddress(); ?>
									
									</div>
								</div>
							</div>
						</div>
						<?php
						break;
			
					case 'pay-with-a-like-customization':
						?>
						<h2><?php _e('Pay With a Like Customization', 'pwal') ?></h2>
						<div id="poststuff" class="pwal-settings">
							<div id="post-body" class="metabox-holder columns-1">
								<div id="post-body-content">
									<?php pwal_admin_panels_customization(); ?>
								</div>
							</div>
						</div>
						<?php
						break;

					default:
					break;
		
				}
			?>
		</div>
		<?php 
	}
	
	function delete_stats(){
		check_ajax_referer( $this->options["salt"], 'nonce' );
		if ( !delete_option( 'pwal_statistics' ) )
			die( json_encode( array('error' => __('Statistics could not be deleted','pwal'))));
	}
	
	function export_stats(){
		//check_ajax_referer( $this->options["salt"], 'nonce' );
		
		$stats = get_option( "pwal_statistics" );
		if ( !is_array( $stats ) )
			die(__('Nothing to download!','pwal'));
		
		$file = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
		fputcsv( $file, array("Blog ID","Post ID","Content ID","Social Button","User IP","Date","Time") );
		
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');
		
		foreach ( $stats as $stat_key => $stat_value ) {
			if ( is_array( $stat_value ) ) {
				foreach ( $stat_value as $key => $value ) {
					if ( "t" == $key ) {
						$stats[$stat_key]["t"] = date_i18n( $date_format, $value );
						$stats[$stat_key]["time"] = date_i18n( $time_format, $value );
					}
				}	
			}
			else 
				die(__('Nothing to download!','pwal'));
		}
		
		foreach ( $stats as $stat ) {
			fputcsv( $file, $stat );
		}
		
		$filename = "stats_".date('F')."_".date('d')."_".date('Y').".csv";

		//serve the file
		rewind($file);
		ob_end_clean(); //kills any buffers set by other plugins
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		$output = stream_get_contents($file);
		//$output = $output . "\xEF\xBB\xBF"; // UTF-8 BOM
		header('Content-Length: ' . strlen($output));
		fclose($file);
		die($output);
	}
	
	/**
	 *	Adds tinyMCE editor to the post editor
	 *  Modified from Password Protect Selected Content by Aaron Edwards 
	 */
	function load_tinymce() {

		if (isset($_GET['post_type'])) {
			$post_type = esc_attr($_GET['post_type']);
		} else {
			if (isset($_GET['post'])) {
				$post_id = intval($_GET['post']);
				$post = get_post($post_id);
				
				if (($post) && ($post->post_type)) {
					$post_type = $post->post_type;
				}
			} else {
				$post_type = 'post';
			}	
		}
		if (isset($this->options['show_metabox'][$post_type])) {
			if ( current_user_can('edit_posts') && (get_user_option('rich_editing') == 'true') ) {
	   			add_filter( 'mce_external_plugins', array(&$this, 'tinymce_add_plugin') );
				add_filter( 'mce_buttons', array(&$this,'tinymce_register_button') );
				add_filter( 'mce_external_languages', array(&$this,'tinymce_load_langs') );
			}
		}
	}
	
	/**
	 * TinyMCE dialog content
	 */
	function tinymce_options() {
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<script type="text/javascript" src="../wp-includes/js/tinymce/tiny_mce_popup.js?ver=327-1235"></script>
				<script type="text/javascript" src="../wp-includes/js/tinymce/utils/form_utils.js?ver=327-1235"></script>
				<script type="text/javascript" src="../wp-includes/js/tinymce/utils/editable_selects.js?ver=327-1235"></script>

				<script type="text/javascript" src="../wp-includes/js/jquery/jquery.js"></script>

				<script type="text/javascript">
				
					tinyMCEPopup.storeSelection();
          
					var insertPayWithaLike = function (ed) {
						var description = jQuery.trim(jQuery('#pwal-description').val());
						var id = Math.round((new Date()).getTime() / 1000) -1330955000;
						tinyMCEPopup.restoreSelection();
						output = '[pwal id="'+id+'" description="'+description+'"]'+tinyMCEPopup.editor.selection.getContent()+'[/pwal]';

						tinyMCEPopup.execCommand('mceInsertContent', 0, output);
						tinyMCEPopup.editor.execCommand('mceRepaint');
						tinyMCEPopup.editor.focus();
						// Return
						tinyMCEPopup.close();
					};
				</script>
				<style type="text/css">
				td.info {
					vertical-align: top;
					color: #777;
				}
				</style>

				<title><?php _e("Pay With a Like", 'pwal'); ?></title>
			</head>
			<body style="display: none">
			
				<form onsubmit="insertPayWithaLike();return false;" action="#">

					<div id="general_panel" class="panel current">
						<div id="pwal-error" style="display: none;color:#C00;padding: 2px 0;"><?php _e("Please enter a value!", 'pwal'); ?></div>
							<fieldset>
						  <table border="0" cellpadding="4" cellspacing="0">
								<tr>
									<td><label for="chat_width"><?php _e("Description", 'pwal'); ?></label></td>
									<td>
										<input type="text" id="pwal-description" name="pwal-description" value="" class="size" size="30" />
									</td>
									<td class="info"><?php _e("Description for this selection.", 'pwal'); ?></td>
								</tr>
							</table>
						</fieldset>
					</div>

					<div class="mceActionPanel">
						<div style="float: left">
							<input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", 'pwal'); ?>" onclick="tinyMCEPopup.close();" />
						</div>

						<div style="float: right">
							<input type="submit" id="insert" name="insert" value="<?php _e("Insert", 'pwal'); ?>" />
						</div>
					</div>
				</form>
			</body>
		</html>
		<?php
		exit(0);
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_register_button($buttons) {
		array_push($buttons, "separator", "paywithalike");
		return $buttons;
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_load_langs($langs) {
		$langs["paywithalike"] =  plugins_url('pay-with-a-like/tinymce/langs/langs.php');
		return $langs;
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_add_plugin($plugin_array) {
		$plugin_array['paywithalike'] = plugins_url('pay-with-a-like/tinymce/editor_plugin.js');
		return $plugin_array;
	}
	
	/**
	 *	check if visitor is a bot 
	 *
	 */
	function is_bot(){
		
		$botlist = array("Teoma", "alexa", "froogle", "Gigabot", "inktomi",
		"looksmart", "URL_Spider_SQL", "Firefly", "NationalDirectory",
		"Ask Jeeves", "TECNOSEEK", "InfoSeek", "WebFindBot", "girafabot",
		"crawler", "www.galaxy.com", "Googlebot", "Scooter", "Slurp",
		"msnbot", "appie", "FAST", "WebBug", "Spade", "ZyBorg", "rabaz",
		"Baiduspider", "Feedfetcher-Google", "TechnoratiSnoop", "Rankivabot",
		"Mediapartners-Google", "Sogou web spider", "WebAlta Crawler","TweetmemeBot",
		"Butterfly","Twitturls","Me.dium","Twiceler");
	 
		if ($this->pwal_js_data['debug'] == 'true') {
			echo "PWAL_DEBUG: ". __FUNCTION__ .": HTTP_USER_AGENT: [". $_SERVER['HTTP_USER_AGENT'] ."]<br >";
		}
		
		foreach($botlist as $bot){
			if ( strpos($_SERVER['HTTP_USER_AGENT'], $bot) !== false ) {
				if ($this->pwal_js_data['debug'] == 'true') {
					echo "PWAL_DEBUG: ". __FUNCTION__ .": found bot match: [". $bot ."]<br >";
				}
				
				return true;	// Is a bot
			}
		}
	 
		return false;	// Not a bot
	}
	
	function check_size_qualifier($size_str = '', $size_qualifiers = array('px', 'pt', 'em', '%')) {
		if (empty($size_str)) $size_str = "0"; //return $size_str;

		if (count($size_qualifiers)) {
			foreach($size_qualifiers as $size_qualifier) {
				if (empty($size_qualifier)) continue;

				if ( substr($size_str, strlen($size_qualifier) * -1, strlen($size_qualifier)) === $size_qualifier)
					return $size_str;
			}
			return intval($size_str) ."px";
		}
	}
	
/*
	function closetags ( $html )
	{
	    #put all opened tags into an array
	    preg_match_all ( "#<([a-z]+)( .*)?(?!/)>#iU", $html, $result );
	    $openedtags = $result[1];

	    #put all closed tags into an array
	    preg_match_all ( "#</([a-z]+)>#iU", $html, $result );
	    $closedtags = $result[1];
	    $len_opened = count ( $openedtags );
	    # all tags are closed
	    if( count ( $closedtags ) == $len_opened )
	    {
	        return $html;
	    }
	    $openedtags = array_reverse ( $openedtags );
	    # close tags
	    for( $i = 0; $i < $len_opened; $i++ )
	    {
	        if ( !in_array ( $openedtags[$i], $closedtags ) )
	        {
	            $html .= "</" . $openedtags[$i] . ">";
	        }
	        else
	        {
	            unset ( $closedtags[array_search ( $openedtags[$i], $closedtags)] );
	        }
	    }
	    return $html;
	}
*/	
}
}

$pwal = new PayWithaLike();
global $pwal;

if ( !function_exists( 'wpmudev_pwal' ) ) {
	function wpmudev_pwal( $content='', $force=false ) {
		global $pwal, $post;
		if ( $content ) 
			return $pwal->content( $content, $force );
		else
			return $pwal->content( $post->post_content, $force );
	}
}

if ( !function_exists( 'wpmudev_pwal_html' ) ) {
	// since 1.1.2
	function wpmudev_pwal_html( $html='', $id=1, $description='' ) {
		global $pwal, $post;
		
		if ( !empty($html) )
			$content = $html;
		else if ( is_object( $post ) )
			$content = $post->content;
		else
			return 'No html code or post content found';
			
		//return $pwal->content( '[pwal id="'.$id.'" description="'.$description.'"]'. $content . '[/pwal]', true, 'tool' );
		return do_shortcode( '[pwal id="'. $id .'" content_reload="refresh" description="'. $description .'"]'. $content . '[/pwal]');
	}
}

if ( !function_exists( 'wpmudev_pwal_check_liked' ) ) {
	// since 1.1.5.1
	// Checks if a certain ID has been liked. 
	// $pwal_id is the post ID to check
	// Returns true if liked else false
	function wpmudev_pwal_check_liked($pwal_id, $pwal_id_type='post_id') {
		global $pwal;
	
		// Check that $pwal is available
		if (!$pwal) return false;
	
		$pwal->load_cookie_likes();
		if (empty($pwal->cookie_likes['data'])) return false;
		
		// "sitewide like" is selected
		if ( $pwal->options["sitewide"] )
			$post_id = $this->sitewide_id;
		else
			$post_id = $pwal_id;
		
		// Check if this post is liked or sitewide like is selected
		foreach ( $pwal->cookie_likes['data'] as $like ) {
			// Cookie is already encrypted, so we are looking if post_id matches to the encryption 
			if ($pwal_id_type == "post_id") {
				if ( $like["post_id"] == md5( $post_id . $pwal->options["salt"] ) ) { 
					return true;
				}
			} else if ($pwal_id_type == "content_id") {
				if ($like['content_id'] == $post_id)
					return true;
			}
		}
		if ( $pwal->options["admin"] == 'true' && current_user_can('administrator') )
			return true;
	}
}

//force_balance_tags
