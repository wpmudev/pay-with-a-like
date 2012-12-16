<?php
/*
Plugin Name: Pay With a Like
Description: Allows protecting posts/pages until visitor likes the page or parts of the page with Facebook, Linkedin, Twitter or Google +1.
Plugin URI: http://premium.wpmudev.org/project/pay-with-a-like
Version: 1.1.2
Author: Hakan Evin (Incsub)
Author URI: http://premium.wpmudev.org/
TextDomain: pwal
Domain Path: /languages/
WDP ID: 7330
*/

/* 
Copyright 2007-2012 Incsub (http://incsub.com)

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

include_once 'pwal-uninstall.php';
register_uninstall_hook(  __FILE__ , "pwal_uninstall" );
register_activation_hook( __FILE__, array('PayWithaLike', 'install') );

if ( !class_exists( 'PayWithaLike' ) ) {

class PayWithaLike {

	var $version="1.1.2";

	/**
     * Constructor
     */
	function PayWithaLike() {
		$this->__construct();
	}
	function __construct() {
		// Constants
		$this->plugin_name = "pay-with-a-like";
		$this->plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_name;
		$this->plugin_url = plugins_url( '/' . $this->plugin_name );
		$this->page = 'settings_page_' . $this->plugin_name;

		// Read all options at once
		$this->options = get_option( 'pwal_options' );
		$this->options['salt'] = get_option( 'pay_with_a_like_salt' );
		
		add_action( 'template_redirect', array(&$this, 'cachable'), 1 );		// Check if page can be cached
		add_action( 'plugins_loaded', array(&$this, 'localization') );			// Localize the plugin
		add_action( 'save_post', array( &$this, 'add_postmeta' ) ); 			// Calls post meta addition function on each save
		add_filter( 'the_content', array( &$this, 'content' ), 8 ); 			// Manipulate the content. 
		add_filter( 'the_content', array($this, 'clear'), 130 );				// Clear if a shortcode is left
		add_action( 'wp_ajax_nopriv_pwal_action', array(&$this, 'set_cookie') );// Ajax request after a button is clicked
		add_action( 'wp_ajax_pwal_action', array(&$this, 'set_cookie') ); 		// Ajax request after a button is clicked
		add_action( 'wp_footer', array(&$this, 'footer') );
		
		// tinyMCE stuff
		add_action( 'wp_ajax_pwalTinymceOptions', array(&$this, 'tinymce_options') );
		add_action( 'admin_init', array(&$this, 'load_tinymce') );
	
		// Admin side actions
		add_action( 'admin_notices', array($this, 'no_button') );				// Warn admin if no Social button is selected
		add_action( 'admin_notices', array($this, 'notice_settings') );			// Notice admin to make some settings
		add_filter( 'plugin_row_meta', array(&$this,'set_plugin_meta'), 10, 2 );// Add settings link on plugin page
		add_action( 'admin_menu', array( &$this, 'admin_init' ) ); 				// Creates admin settings window
		add_action( 'add_meta_boxes', array( &$this, 'add_custom_box' ) ); 		// Add meta box to posts
		add_action( 'wp_ajax_delete_stats', array( &$this, 'delete_stats' ) ); // Clear statistics
		add_action( 'wp_ajax_pwal_export_stats', array( &$this, 'export_stats' ) ); // Export statistics
		add_action( 'admin_print_scripts-'. $this->page , array(&$this,'admin_scripts'));
		
		
		// By default assume that pages are cachable (Cache plugins are allowed)
		$this->is_cachable = true;
		$this->script_added = false;
		$this->footer_script = "";
	}

	/**
	* Make it possible to save status of postboxes
	*/
	function admin_scripts() {
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
	function load_scripts_styles() {
		
		if ( $this->options["use_facebook"] && $this->options["load_facebook"] ) {
				$locale = preg_replace('/-/', '_', get_locale());
				// Fix for de_DE_Sie type locale
				if ( substr_count( $locale, '_' ) > 1 ) {
					$l = explode( '_', $locale );
					$locale = $l[0] . '_' . $l[1];
				}
				$locale = apply_filters( 'pwal_fb_locale', $locale );
				wp_enqueue_script('facebook-all', 'http://connect.facebook.net/' . $locale . '/all.js', array( 'jquery' ) );
				add_action('wp_footer', array(&$this, 'init_fb_script'));
			}
		if ( $this->options["use_linkedin"] && $this->options["load_linkedin"] )
			wp_enqueue_script( 'linkedin', 'http://platform.linkedin.com/in.js', array( 'jquery' ) );
		
		if ( $this->options["use_twitter"] && $this->options["load_twitter"] )
			wp_enqueue_script( 'twitter', 'http://platform.twitter.com/widgets.js', array( 'jquery' ) );
			
		if ( $this->options["use_google"] && $this->options["load_google"] )
			wp_enqueue_script( 'google-plusone', 'https://apis.google.com/js/plusone.js', array( 'jquery' ) );
			
		do_action("pwal_additional_button_scripts");
		
		if ( current_theme_supports( 'pay_with_a_like_style' ) )
			return;
		
		$uploads = wp_upload_dir();
		if ( !$uploads['error'] && file_exists( $uploads['basedir'] . "/". $this->plugin_name .".css" ) )
			wp_enqueue_style( $this->plugin_name, $uploads['baseurl']. "/". $this->plugin_name .".css", array(), $this->version );
		else if ( file_exists( $this->plugin_dir. "/css/front.css" ) )
			wp_enqueue_style( $this->plugin_name, $this->plugin_url. "/css/front.css", array(), $this->version );
    }

	/**
     * Localize the plugin
     */
	function localization() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's "languages" folder and name it "pwal-[value in wp-config].mo"
		load_plugin_textdomain( 'pwal', false, '/pay-with-a-like/languages/' );
	}
	
	/**
	 * Check if user is authorised by the admin
	 */	
	function is_authorised() {
	
		if ( $this->options['authorized'] == 'true' && is_user_logged_in() && !current_user_can('administrator') ) {
			if ( $this->options['level'] == 'subscriber' && current_user_can( 'read' ) )
				return true;
			else if ( $this->options['level'] == 'contributor' && current_user_can( 'edit_posts' ) )
				return true;
			else if ( $this->options['level'] == 'author' && current_user_can( 'edit_published_posts' ) )
				return true;		
			else if ( $this->options['level'] == 'editor' && current_user_can( 'edit_others_posts' ) )
				return true;		
		}
		return false;
	}

	/**
	 * Check if page can be cached or not
	 *	
	 */
	function cachable() {
		
		global $post;
		// If plugin is enabled for this post/page, it is not cachable
		if ( is_object( $post ) && is_singular() ) {
			$post_meta = get_post_meta( $post->ID, 'pwal_enable', true );
			
			if ( $post->post_type == 'page' ) 
				$default = $this->options["page_default"];
			else if ( $post->post_type == 'post' ) 
				$default = $this->options["post_default"];
			else if ( $post->post_type != 'attachment' ) 
				$default = $this->options["custom_default"];
			else
				$default = '';
			if ( $post_meta == 'enable' || ( $default == 'enable' && $post_meta != 'disable' ) )
				$this->is_cachable = false;
		}
		else if ( $this->options["multi"] && !is_home() )
			$this->is_cachable = false;
			
		if ( is_home() && $this->options["home"] )
			$this->is_cachable = false;
		
		// Prevent cache plugins, i.e. W3T, WP Super Cache, Quick Cache
		if ( !$this->is_cachable ) {
			if ( !defined( 'DONOTCACHEPAGE' ) )
				define( 'DONOTCACHEPAGE', true );
				
			// We will add SB buttons and codes to only uncachable pages, i.e those that plugin is enabled
			$this->load_scripts_styles();
		}
	}
	/**
	 * Facebook init  
	 */	
	function init_fb_script ( ) {
		echo <<<EOFb
<div id="fb-root"></div>
<script>
FB.init({
	status: true,
	cookie: true,
	xfbml: true
});
</script>
EOFb;
	}

	/**
	 * Changes the content according to selected settings
	 *	
	 */
	function content( $content, $force=false ) {

		global $post;
		// Unsupported post type
		if ( !is_object( $post ) && !$content )
			return;
	
		// If caching is allowed no need to continue
		if ( $this->is_cachable && !$force )
			return $this->clear($content);
				
		// Show the admin full content, if selected so
		if ( $this->options["admin"] == 'true' && current_user_can('administrator') )
			return $this->clear($content);

		// Show the bot full content, if selected so
		if ( $this->options["bot"] == 'true' && $this->is_bot() )
			return $this->clear($content);
		
		// Check if current user has been authorized to see full content
		if ( $this->is_authorised() )
			return $this->clear($content);
		
		// Find method
		$method = get_post_meta( $post->ID, 'pwal_method', true );
		if ( $method == "" )
			$method = $this->options["method"]; // Apply default method, if there is none
			
		// If user liked, show content. 'Tool' option has its own logic
		if ( isset( $_COOKIE["pay_with_a_like"] ) && $method != 'tool' ) {
			// In some installations slashes are added while serializing. So get rid of them.
			$likes = unserialize( stripslashes( $_COOKIE["pay_with_a_like"] ) );
			if ( is_array( $likes ) ) {
				// "sitewide like" is selected
				if ( $this->options["sitewide"] )
					$post_id = 123456789;
				else
					$post_id = $post->ID;
				
				// Check if this post is liked or sitewide like is selected
				foreach ( $likes as $like ) {
					// Cookie is already encrypted, so we are looking if post_id matches to the encryption 
					if ( $like["post_id"] == md5( $post_id . $this->options["salt"] ) ) { 
						return $this->clear($content);
						break; 
					}
				}
			}
		}
		// If we are here, it means content will be restricted.
		// Now prepare the restricted output
		if ( $method == "automatic" ) {
			$content = preg_replace( '%\[pwal(.*?)\](.*?)\[( *)\/pwal( *)\]%is', '$2', $content ); // Clean shortcode
			$temp_arr = explode( " ", $content );
			
			// If the article is shorter than excerpt, show full content
			// Zero value is also valid
			$excerpt_len = get_post_meta( $post->ID, 'pwal_excerpt', true );
			if ( '' == $excerpt_len )
				$excerpt_len = $this->options["excerpt"];
			
			if ( count( $temp_arr ) <= $excerpt_len )
				return $this->clear($content);
				
			// Otherwise prepare excerpt
			$e = ""; 
			for ( $n=0; $n<$excerpt_len; $n++ ) {
				$e .= $temp_arr[$n] . " ";
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
				
			return $e . $this->render_buttons( );
		}
		else if ( $method == "manual" ) {
			return $post->post_excerpt . $this->render_buttons( );
		}
		else if ( $method == "tool" ) {
			$contents = array();
			if ( preg_match_all( '%\[pwal( +)id="(.*?)"( +)description="(.*?)"(.*?)\](.*?)\[( *)\/pwal( *)\]%is', $content, $matches, PREG_SET_ORDER ) ){
				if ( isset( $_COOKIE["pay_with_a_like"] ) ) {
					$likes = unserialize( stripslashes( $_COOKIE["pay_with_a_like"] ) );
					if ( is_array( $likes ) ) {
						// If "Sitewide Like" is selected
						if ( $this->options["sitewide"] ) {
							foreach ( $likes as $like ) {
								if ( $like["post_id"] == md5( 123456789 . $this->options["salt"] ) )
									return $this->clear($content);
							}
						}
						else {
							foreach ( $likes as $like ) {
								if ( $like["post_id"] == md5( $post->ID . $this->options["salt"] ) )
									$contents[] = $like["content_id"]; // Take only values related to this post
							}
						}
					}
				}
				// Prepare the content
				foreach ( $matches as $m ) {
					if ( in_array( $m[2], $contents ) ) { // Means this was already liked
						$content = str_replace( $m[0], $m[6] , $content );
						$content = apply_filters( 'pwal_revealed_content', $content );
					}
					else {
						$content = str_replace( $m[0], $this->render_buttons( $m[2],$m[4] ), $content );
						$content = apply_filters( 'pwal_hidden_content', $content ); 
					}
				}
			}
			return $this->clear($content);
		}
		return $this->clear($content); // Script cannot come to this point, but just in case.
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
	
	/**
	 *	Add button html codes and handle post based embedded scripts
	 */	
	function render_buttons( $id=0, $description='') {
		global $post;
		
		$n = $this->button_count();
		if ( $n == 0 )
			return; // No button selected. Nothing to do.
			
		// If url to like is left empty and Random Like is not selected, take existing post's url
		if ( !$url_to_like = trim( $this->options["url_to_like"] ) )
			$url_to_like = get_permalink( $post->ID );
		
		// If Random Like is selected, find a random, published post/page
		if ( $this->options["random"] || apply_filters( 'pwal_force_random', false ) ) {
			global $wpdb;
			$result = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE (post_type='post' OR post_type='page') AND post_status='publish' ORDER BY RAND()");
			if ( $result != null )
				$url_to_like = get_permalink( $result->ID );
			else
				$url_to_like = get_permalink( $post->ID );
		}
		if ( trim( $url_to_like ) == '' )
			$url_to_like = home_url(); // Never let an empty url, just in case
			
		$url_to_like = apply_filters( 'pwal_url_to_like', $url_to_like );
		
		if ( trim( $description ) == '' )
			$description = $this->options["description"]; // Use default description, if one is not specifically set
		
		$content  = "<div class='pwal_container'>";
		if ( $description )
			$content .= "<div class='pwal_description'>". $description . "</div>";
		$content .= "<ul>";
		$script   = "";
		if ( !$this->script_added )
			$script  .= "var pwal_data={'ajax_url': '".admin_url('admin-ajax.php')."'};
						jQuery(document).bind('pwal_button_action', function (e, service,cid) {
							jQuery.post(pwal_data.ajax_url, {
								'action': 'pwal_action',
								'post_id':".$post->ID.",
								'content_id':cid,
								'service': service,
								'nonce': '".wp_create_nonce("ajax-nonce")."'
							}, function (data) {
								if ( data && data.error ) {alert(data.error);}
								else{window.location.href = window.location.href;}
							},
							'json'
							);
						});
					";
					
		if ( $this->options["use_facebook"] ) {
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_facebook_button'><fb:like id='pwal_".$id."' layout='box_count' href='{$url_to_like}'></fb:like></div></li>";
			if ( !$this->script_added )
				$script  .= "function pwal_facebook_callback(){
							jQuery(document).trigger('pwal_button_action',['facebook',cid]);
						}
						function pwal_facebook_register(){
							if (typeof FB !='undefined') {
								FB.Event.subscribe('edge.create',function(href,widget){
									var did=widget.dom.id;
									var cid=did.replace('pwal_','');
									jQuery(document).trigger('pwal_button_action',['facebook',cid]);
								});
							}
							else setTimeout(pwal_facebook_register,200);
						}
						pwal_facebook_register();
						";
		}
		if ( $this->options["use_linkedin"] ) {
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_linkedin_button'><script type='IN/Share' data-counter='top' data-url='{$url_to_like}' data-onsuccess='pwal_linkedin_callback_".$id."'></script></div></li>";
			$script  .= "function pwal_linkedin_callback_".$id."() {
							jQuery(document).trigger('pwal_button_action',['linkedin',".$id."]);
						}
						";
		}
		if ( $this->options["use_twitter"] ) {
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_twitter_button' id='pwal_".$id."' ><a href='https://twitter.com/share' class='twitter-share-button' data-count='vertical' data-url='{$url_to_like}'>Tweet</a></div></li>";
			if ( !$this->script_added )
			$script  .= "twttr.ready(function(twttr) {
							twttr.events.bind('tweet',function(event){
							var did=event.target.parentNode.id;
							var cid=did.replace('pwal_','');
							jQuery(document).trigger('pwal_button_action',['twitter',cid]);
						});
						});
						";
		}
		if ( $this->options["use_google"] ) {
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_google_button'><g:plusone size='tall' href='{$url_to_like}' callback='pwal_google_callback_".$id."'></g:plusone></div></li>";
			$script  .= "function pwal_google_callback_".$id."(data){
							if (data.state == 'off'){return false;}
							jQuery(document).trigger('pwal_button_action',['google',".$id."]);
						}";
		}
		$content = apply_filters( "pwal_render_button_html", $content, $post->ID );
		$script  = apply_filters( "pwal_render_button_script", $script, $post->ID );
		$content .= "</ul></div>";
		
		// Flag that we have already included some common scripts
		$this->script_added = true;
		// Save scripts to be added to the footer 
		$this->footer_script = $this->footer_script . $script;
		
		return $content;
	}

	/**
	 *	Add embedded scripts to the footer and compress them
	 */	
	function footer() {
		echo "<script type='text/javascript'>". str_replace( array("\r","\n","\t","<br>","<br />"), "", apply_filters( 'pwal_footer_scripts', $this->footer_script ) ). "</script>";
	}
	
	/**
	 *	Set a cookie upon Ajax request
	 */	
	function set_cookie( ) {
		// Check if request is coming from the right place
		if ( !$_POST["post_id"] || !check_ajax_referer( 'ajax-nonce', 'nonce', false ) ) {
			die( json_encode( array( "error" => __('Something went wrong. Please refresh the page and try again.', 'pwal') ) ) );
		}
			
		$new_like = array(
			'content_id' => $_POST["content_id"],
			'post_id'	=> md5( $_POST["post_id"] . $this->options["salt"] )
		);
		
		// Always save the "sitewide like" thing
		$new_like_sitewide = array(
			'content_id' => $_POST["content_id"],
			'post_id'	=> md5( 123456789 . $this->options["salt"] )
		);
		
		// Check if user has got likes saved in the cookie
		if ( isset( $_COOKIE["pay_with_a_like"] ) )
			$likes = unserialize( stripslashes( $_COOKIE["pay_with_a_like"] ) );
		else
			$likes = '';

		if ( !is_array( $likes ) )
			$likes = array();
			
		// Prevent cookie growing with duplicate entries	
		$duplicate = false;
		foreach ( $likes as $like ) {
			if ( isset( $like["post_id"] ) && $like["post_id"] == $new_like ) {
				$duplicate = true;
				break; // One match is enough
			}
		}
		if ( !$duplicate )
			$likes[] = $new_like;
		
		$duplicate_sitewide = false;
		foreach ( $likes as $like ) {
			if ( isset( $like["post_id"] ) && $like["post_id"] == $new_like_sitewide ) {
				$duplicate_sitewide = true;
				break;
			}
		}
		if ( !$duplicate_sitewide )
			$likes[] = $new_like_sitewide;
		
		// Clear empty entries, just in case
		$likes = array_filter( $likes );
		
		// Let admin set cookie expire at the end of session
		if ( $this->options["cookie"] == 0  || trim( $this->options["cookie"] ) == '' )
			$expire = 0; 
		else
			$expire = time() + 3600 * $this->options["cookie"]; 
		
		if ( defined('COOKIEPATH') ) $cookiepath = COOKIEPATH;
		else $cookiepath = "/";
		if ( defined('COOKIEDOMAIN') ) $cookiedomain = COOKIEDOMAIN;
		else $cookiedomain = '';
		
		// Setting cookie works in ajax request!!
		@setcookie("pay_with_a_like", serialize($likes), $expire, $cookiepath, $cookiedomain);
		
		// We can handle statistics here, as this is ajax request and it will not affect performance
		$statistics = get_option( "pwal_statistics" );
		if ( !is_array( $statistics  ) )
			$statistics = array();
		
		global $blog_id;
		
		// Let's try to be economical in key usage
		$statistics[] = array(
						'b'		=> $blog_id,
						'p'		=> $_POST["post_id"],
						'c'		=> $_POST["content_id"],
						's'		=> $_POST["service"],
						'i'		=> $_SERVER["REMOTE_ADDR"],
						't'		=> current_time('timestamp')
						);
						
		$statistics = apply_filters( "pwal_ajax_statistics", $statistics, $_POST["post_id"], $_POST["service"] );
						
		update_option( "pwal_statistics", $statistics );
		
		do_action("pwal_ajax_request", $_POST["post_id"], $_POST["content_id"]);
		
		// It looks like FF cannot write the cookie immediately. Let's put a delay.
		sleep(1);
		
		die();
	}
	/**
	 *	Custom box create call
	 *
	 */
	function add_custom_box( ) {
		$pwal_name = __('Pay With a Like', 'pwal');
		add_meta_box( 'pwal_metabox', $pwal_name, array( &$this, 'custom_box' ), 'post', 'side', 'high' );
		add_meta_box( 'pwal_metabox', $pwal_name, array( &$this, 'custom_box' ), 'page', 'side', 'high' );

		$args = array(
			'public'   => true,
			'_builtin' => false
		); 
	
		$post_types = get_post_types( $args );
		if ( is_array( $post_types ) ) {
			foreach ($post_types as $post_type )
				add_meta_box( 'pwal_metabox', $pwal_name, array( &$this, 'custom_box' ), $post_type, 'side', 'high' );
		}
	}

	/**
	 *	Custom box html codes
	 *
	 */
	function custom_box(  ) {

		global $post;

		// Some wordings and vars that will be used
		$enabled_wording = __('Enabled','pwal');
		$disabled_wording = __('Disabled','pwal');
		$automatic_wording = __("Automatic excerpt","pwal");
		$manual_wording = __("Manual excerpt","pwal");
		$tool_wording = __("Use selection tool","pwal");

		if ( is_page() )
			$pp = __('page','pwal');
		else 
			$pp = __('post','pwal');
			
		if ( $post->post_type == 'page' ) 
			$default = $this->options["page_default"];
		else if ( $post->post_type == 'post' ) 
			$default = $this->options["post_default"];
		else if ( $post->post_type != 'attachment' ) 
			$default = $this->options["custom_default"];
		else
			$default = '';
			
		$e = get_post_meta( $post->ID, 'pwal_enable', true );
		$eselect = $dselect = '';
		if ( $e == 'enable' ) 
			$eselect = ' selected="selected"';
		else if ( $e == 'disable' ) 
			$dselect = ' selected="selected"';

		$saved_method = get_post_meta( $post->ID, 'pwal_method', true );
		switch ( $saved_method ) {
			case "automatic":	$aselect = 'selected="selected"'; break;
			case "manual":		$mselect = 'selected="selected"'; break;
			case "tool":		$tselect = 'selected="selected"'; break;
			default:			$aselect = $mselect = $tselect = ''; break;
		}

		if ( $saved_method == "" )
			$method = $this->options["method"]; // Apply default method, if there is none
		else
			$method = $saved_method;
		switch ( $method ) {
			case 'automatic':	$eff_method = $automatic_wording; break;
			case 'manual':		$eff_method = $manual_wording; break;
			case 'tool':		$eff_method = $tool_wording; break;
		}
			
		if ( $e == 'enable' || ( $default == 'enable' && $e != 'disable' ) )
			$eff_status = "<span class='pwal_span green' id='pwal_eff_status'>&nbsp;" . $enabled_wording . "</span>";
		else
			$eff_status = "<span class='pwal_span red' id='pwal_eff_status'>&nbsp;" . $disabled_wording . "</span>";
		
		// Use nonce for verification
		wp_nonce_field( plugin_basename(__FILE__), 'pwal_nonce' );
		?>
		<style>
		<!--
		#pwal_metabox label{
		float: left;
		padding-top:5px;
		}
		#pwal_metabox select{
		float: right;
		}
		#pwal_metabox input{
		float: right;
		width: 20%;
		text-align:right;
		}
		.pwal_clear{
		clear:both;
		margin:10px 0 10px 0;
		}
		.pwal_info{
		padding-top:5px;
		}
		.pwal_info span.wpmudev-help{
		margin-top:10px;
		}
		.pwal_span{float:right;font-weight:bold;padding-top:5px;padding-right:3px;}
		.red{color:red}
		.green{color:green}
		.pwal_border{border-top-color:white;border-bottom-color: #DFDFDF;border-style:solid;border-width:1px 0;}
		
		-->
		<?php if ( 'automatic' != $method ) echo '#pwal_excerpt{opacity:0.2}';?>
		</style>
		<?php
		echo '<select name="pwal_enable" id="pwal_enable">';
		echo '<option value="" >'. __("Follow global setting","pwal"). '</option>';
		echo '<option value="enable" '.$eselect.'>' . __("Always enabled","pwal"). '</option>';
		echo '<option value="disable" '.$dselect.'>' . __("Always disabled","pwal") . '</option>';
		echo '</select>';

		echo '<label for="pwal_enable">';
		_e('Enabled?', 'pwal');
		echo '</label>';
		/* translators: Both %s refer to post or page */
		echo '<div class="pwal_info">';
		echo $this->tips->add_tip( sprintf(__('Selects if Pay With a Like is enabled for this %s or not. If Follow global setting is selected, General Setting page selection will be valid. Always enabled and Always disabled selections will enable or disable Pay With a Like for this %s, respectively, overriding general setting.','pwal'),$pp,$pp));
		echo '</div>';
		echo '<div class="pwal_clear"></div>';

		echo "<label for='effective_status'>". __('Effective status','pwal'). "</label>";
		echo $eff_status;
		echo '<div class="pwal_info">';
		/* translators: %s refer to post or page */
		echo $this->tips->add_tip(sprintf(__('Effective status dynamically shows the final result of the setting that will be applied to this %s. Disabled means Pay With a Like will not work for this %s. It takes global settings into account and helps you to check if your intention will be correctly reflected to the settings after you save.','pwal'),$pp,$pp));
		echo '</div>';
		echo '<div class="pwal_clear pwal_border"></div>';
		
		echo '<select name="pwal_method" id="pwal_method">';
		echo '<option value="" >'. __("Follow global setting","pwal"). '</option>';
		echo '<option value="automatic" '.$aselect.'>'. $automatic_wording . '</option>';
		echo '<option value="manual" '.$mselect.'>' . $manual_wording . '</option>';
		echo '<option value="tool" '.$tselect.'>' . $tool_wording . '</option>';
		echo '</select>';
		echo '<label for="pwal_method">';
		_e('Method', 'pwal');
		echo '</label>';
		echo '<div class="pwal_info">';
		/* translators: First %s refer to post or page. Second %s is the url address of the icon */
		echo $this->tips->add_tip(sprintf(__('Selects the content protection method for this %s. If Follow Global Setting is selected, method selected in General Settings page will be applied. If you want to override general settings, select one of the other methods. With Use Selection Tool you need to select each content using the icon %s on the editor tool bar. For other methods refer to the settings page.','pwal'),$pp,"<img src='".$this->plugin_url."/images/menu_icon.png"."' />" ) );
		echo '</div>';
		echo '<div class="pwal_clear"></div>';

		echo "<label for='effective_method'>". __('Effective method','pwal'). ":</label>";
		echo "<span class='pwal_span' id='pwal_eff_method'>&nbsp;" . $eff_method . "</span>";
		echo '<div class="pwal_info">';
		/* translators: %s refer to post or page */
		echo $this->tips->add_tip(sprintf(__('Effective method dynamically shows the final result of the setting that will be applied to this %s. It takes global settings into account and helps you to check if your intention will be correctly reflected to the settings after you save.','pwal'),$pp));
		echo '</div>';
		echo '<div class="pwal_clear pwal_border"></div>';
		
		echo '<input type="text" name="pwal_excerpt" id="pwal_excerpt" value="'.get_post_meta( $post->ID, 'pwal_excerpt', true ).'" />';
		echo '<label for="pwal_excerpt">';
		_e('Excerpt length', 'pwal');
		echo '</label>';
		echo '<div class="pwal_info">';
		/* translators: %s refer to post or page */
		echo $this->tips->add_tip(sprintf(__('If you want to override the number of words that will be used as an excerpt for the unprotected content, enter it here. Please note that this value is only used when Automatic Excerpt method is applied to the %s.','pwal'),$pp ));
		echo '</div>';
		echo '<div class="pwal_clear"></div>';

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			var def = '<?php echo $default ?>';
			var def_method = '<?php echo $this->options["method"] ?>';
			$(document).bind('DOMSubtreeModified',function(){
				if ('<?php echo $method?>' != 'tool'){$('#content_paywithalike').css('opacity','0.2');}
			});
			$("select#pwal_enable").change(function() {
				var e = $('select#pwal_enable').val();
				if ( e == 'enable' || ( def == 'enable' && e != 'disable' ) ){
					$('#pwal_eff_status').html('&nbsp;<?php echo $enabled_wording?>').addClass('green').removeClass('red');
				}
				else { $('#pwal_eff_status').html('&nbsp;<?php echo $disabled_wording?>').addClass('red').removeClass('green'); }
			});
			
			
			$("select#pwal_method").change(function() {
				var m = $('select#pwal_method').val();
				if ( m == '' ) {m = def_method;}
				switch(m){
					case 'automatic':	$('#pwal_eff_method').html('&nbsp;<?php echo $automatic_wording?>');$('#content_paywithalike,#pwal_excerpt').css('opacity','0.2');$('#pwal_excerpt').css('opacity','1');break;
					case 'manual':		$('#pwal_eff_method').html('&nbsp;<?php echo $manual_wording?>');$('#content_paywithalike,#pwal_excerpt').css('opacity','0.2');break;
					case 'tool':		$('#pwal_eff_method').html('&nbsp;<?php echo $tool_wording?>');$('#content_paywithalike').css('opacity','1');$('#pwal_excerpt').css('opacity','0.2');break;
				}
			});

		});
		</script>
		<?php
	}

	/**
	 *	Saves post meta values
	 *
	 */
	function add_postmeta( $post_id ) {

		global $post;
		if ( !$post_id )
			$post_id = $post->ID;

		if ( !wp_verify_nonce( @$_POST['pwal_nonce'], plugin_basename(__FILE__) ) ) 
			return $post_id;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return $post_id;

		// Check permissions
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) ) 
				return $post_id;
		}
		elseif ( !current_user_can( 'edit_post', $post_id ) ) 
			return $post_id;

		// Auth ok
		if ( isset( $_POST['pwal_enable'] ) ) {
			if ( $_POST['pwal_enable'] != '' )
				update_post_meta( $post_id, 'pwal_enable', $_POST['pwal_enable'] );
			else
				delete_post_meta( $post_id, 'pwal_enable' );
		}
		if ( isset( $_POST['pwal_method'] ) ) {
			if ( $_POST['pwal_method'] != '' )
				update_post_meta( $post_id, 'pwal_method', $_POST['pwal_method'] );
			else
				delete_post_meta( $post_id, 'pwal_method' );
		}
		if ( isset( $_POST['pwal_excerpt'] ) ) {
			if ( $_POST['pwal_excerpt'] != '' && is_numeric( $_POST['pwal_excerpt'] ) )
				update_post_meta( $post_id, 'pwal_excerpt', $_POST['pwal_excerpt'] );
			else
				delete_post_meta( $post_id, 'pwal_excerpt' );
		}
	}
	

	/**
	 *	Set some default settings
	 *
	 */
	function install() {
		// Create a salt, if it doesn't exist from the previous installation
		if ( !$salt = get_option( "pay_with_a_like_salt" ) ) {
			$salt = mt_rand();
			add_option( "pay_with_a_like_salt", $salt ); // Save it to be used until it is cleared manually
		}
		
		add_option( 'pwal_options', array(
										'post_default'				=> 'enable',
										'page_default'				=> '',
										'custom_default'			=> '',
										'method'					=> 'automatic',
										'excerpt'					=> 100,
										'admin'						=> 'true',
										'home'						=> '',
										'multi'						=> 'true',
										'authorized'				=> '',
										'level'						=> 'editor',
										'bot'						=> '',
										'cookie'					=> 24,
										'use_facebook'				=> 'true',
										'use_linkedin'				=> 'true',
										'use_twitter'				=> 'true',
										'use_google'				=> 'true',
										'url_to_like'				=> '',
										'salt'						=> $salt,
										'sitewide'					=> '',
										'description'				=> __('To see the full content, share this page by clicking one of the buttons below','pwal'),
										'random'					=> '',
										'no_visit'					=> '',
										'load_facebook'				=> 'true',
										'load_linkedin'				=> 'true',
										'load_twitter'				=> 'true',
										'load_google'				=> 'true'
										)
		);
		

	}
	
	function checkbox_value($name) {
		return (isset($_POST[$name]) ? "true" : "");
	}

	/**
	 *	Handles settings form data
	 *
	 */
	function admin_init() {
	
		if (!class_exists('WpmuDev_HelpTooltips')) 
			require_once dirname(__FILE__) . '/includes/class_wd_help_tooltips.php';
		$this->tips = new WpmuDev_HelpTooltips();
		$this->tips->set_icon_url(plugins_url('pay-with-a-like/images/information.png'));
	
		global $pwal_options_page;
		$pwal_options_page = add_options_page(__('Pay With a Like Settings','pwal'), __('Pay With a Like','pwal'), 'manage_options',  'pay-with-a-like', array(&$this,'settings') );
		
		if ( isset($_POST["action_pwal"]) && !wp_verify_nonce($_POST['pwal_nonce'],'update_pwal_settings') ) {
			add_action( 'admin_notices', array( &$this, 'warning' ) );
			return;
		}
		
		if ( isset($_POST["action_pwal"]) ) {
			$this->options["post_default"]			= $_POST["post_default"];
			$this->options["page_default"]			= $_POST["page_default"];
			$this->options["custom_default"]		= $_POST["custom_default"];
			$this->options["method"]				= $_POST["pwal_method"];
			$this->options["excerpt"]				= $_POST["excerpt"];
			$this->options["home"]					= $_POST["home"];
			$this->options["multi"]					= $_POST["multi"];
			$this->options["admin"]					= $_POST["admin"];
			$this->options["authorized"]			= $_POST["authorized"];
			$this->options["level"]					= $_POST["level"];
			$this->options["bot"]					= $_POST["bot"];
			$this->options["cookie"]				= $_POST["cookie"];
			$this->options['use_facebook']			= $this->checkbox_value('use_facebook');
			$this->options['use_linkedin']			= $this->checkbox_value('use_linkedin');
			$this->options['use_twitter']			= $this->checkbox_value('use_twitter');
			$this->options['use_google']			= $this->checkbox_value('use_google');
			$this->options["sitewide"]				= $_POST["sitewide"];
			$this->options["description"]			= stripslashes( $_POST["description"] );
			$this->options["url_to_like"]			= $_POST["url_to_like"];
			$this->options["random"]				= $_POST["random"];
			$this->options['load_facebook']			= $this->checkbox_value('load_facebook');
			$this->options['load_linkedin']			= $this->checkbox_value('load_linkedin');
			$this->options['load_twitter']			= $this->checkbox_value('load_twitter');
			$this->options['load_google']			= $this->checkbox_value('load_google');
			
			$this->options = apply_filters("pwal_before_save_options", $this->options);
			
			if ( update_option( 'pwal_options', $this->options ) )
				add_action( 'admin_notices', array ( &$this, 'saved' ) );
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
		
		if ( !$this->options["no_visit"] && $screen->id != $pwal_options_page ) {
			/* translators: %s means settings here */
			echo '<div class="updated fade"><p>' .
				sprintf(__("<b>[Pay With a Like]</b> It looks like you have just installed the plugin. You may want to adjust some %s. If you are using a cache plugin, please clear your cache.", 'pwal'),"<a href='".admin_url('options-general.php?page=pay-with-a-like')."'>".__("settings","pwal")."</a>") .
				'</p></div>';
		}
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
	
	/**
	 *	Admin settings HTML code 
	 */
	function settings() {

		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.', 'pwal') );
		}
	?>
		<div class="wrap">
		<div class="icon32" style="margin:8px 0 0 8px"><img src="<?php echo $this->plugin_url . '/images/general.png'; ?>" /></div>
        <h2><?php _e('Pay With a Like Settings', 'pwal'); ?></h2>
        <div id="poststuff" class="metabox-holder pwal-settings">
				
		<form method="post" action="" >
		<?php wp_nonce_field( 'update_pwal_settings', 'pwal_nonce' ); ?>
		
		<div class="postbox <?php echo $this->postbox_classes('pwal_global_postbox') ?>" id="pwal_global_postbox"><div class="handlediv" title="<?php echo esc_attr__('Click to toggle') ?>"><br /></div>
            <h3 class='hndle'><span><?php _e('Global Settings', 'pwal') ?></span></h3>
            <div class="inside">
              <span class="description"><?php _e('Pay With a Like allows protecting posts/pages or parts of posts/pages until visitor likes the protected content with Facebook, Linkedin, Twitter or Google +1. These settings provide a quick way to set Pay With a Like for your posts and pages. They can be overridden per post basis using post editor page.', 'pwal') ?></span>
				
				<table class="form-table">
				
					<tr valign="top">
						<th scope="row" ><?php _e('Activation for posts', 'pwal')?></th>
						<td colspan="2">
						<select name="post_default">
						<option value="" <?php if ( $this->options['post_default'] <> 'enable' ) echo "selected='selected'"?>><?php _e('Disabled for all posts', 'pwal')?></option>
						<option value="enable" <?php if ( $this->options['post_default'] == 'enable' ) echo "selected='selected'"?>><?php _e('Enabled for all posts', 'pwal')?></option>
						</select>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Activation for pages', 'pwal')?></th>
						<td colspan="2">
						<select name="page_default">
						<option value="" <?php if ( $this->options['page_default'] <> 'enable' ) echo "selected='selected'"?>><?php _e('Disabled for all pages', 'pwal')?></option>
						<option value="enable" <?php if ( $this->options['page_default'] == 'enable' ) echo "selected='selected'"?>><?php _e('Enabled for all pages', 'pwal')?></option>
						</select>
						</td>
					</tr>
					<?php
					$args = array(
						'public'   => true,
						'_builtin' => false
					); 
					$post_types = get_post_types( $args, 'objects' );

					if ( is_array( $post_types ) && count( $post_types ) > 0 ) {
						$note = __("You have the following custom post type(s): ","pwal");
							foreach ( $post_types as $post_type )
								$note .= $post_type->labels->name . ", ";
						$note = rtrim( $note, ", " );
						$note .= __(' Note: See the below customization section for details.','pwal');
					}
					else $note = __("You don't have any custom post types. Changing this setting will have no effect.","pwal");
					?>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Activation for custom post types', 'pwal')?></th>
						<td colspan="2">
						<select name="custom_default">
						<option value="" <?php if ( $this->options['custom_default'] <> 'enable' ) echo "selected='selected'"?>><?php _e('Disabled for all custom post types', 'pwal')?></option>
						<option value="enable" <?php if ( $this->options['custom_default'] == 'enable' ) echo "selected='selected'"?>><?php _e('Enabled for all custom post types', 'pwal')?></option>
						</select>
						<span class="description"><?php echo $note ?></span>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Revealed content selection method', 'pwal')?></th>
						<td colspan="2">
						<select name="pwal_method" id="pwal_method">
						<option value="automatic" <?php if ( $this->options['method'] == 'automatic' ) echo "selected='selected'"?>><?php _e('Automatic excerpt from the content', 'pwal')?></option>
						<option value="manual" <?php if ( $this->options['method'] == 'manual' ) echo "selected='selected'"?>><?php _e('Manual excerpt from post excerpt field', 'pwal')?></option>
						<option value="tool" <?php if ( $this->options['method'] == 'tool' ) echo "selected='selected'"?>><?php _e('Use selection tool', 'pwal')?></option>
						</select>
						<span class="description"><?php 
							printf(__('With this setting, you can select the method that reveals the part of your contents *before* a Like is clicked. Automatic excerpt selects the first %d words, number being adjustable from "excerpt length" field. Manual excerpt displays whatever included in the post excerpt field of the post. With selection tool, you can freely select part(s) of the content to be protected. Using the latter one may be a little bit sophisticated, but enables more than one Likes on the same page.', 'pwal'),$this->options["excerpt"]); 
						?></span>
						</td>
					</tr>
					<script type="text/javascript">
					jQuery(document).ready(function($){
						$("select#pwal_method").change(function() {
							if ( $('select#pwal_method').val() == "automatic" ) { $("#excerpt_length").show(); }
							else { $("#excerpt_length").hide(); }
						});
					});
					</script>					
					<tr valign="top" id="excerpt_length" <?php if ( $this->options['method'] != 'automatic' ) echo 'style="display:none"'?>>
						<th scope="row" ><?php _e('Excerpt length (words)', 'pwal')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="excerpt" value="<?php echo $this->options["excerpt"] ?>" />
						<span class="description"><?php _e('Number of words of the post content that will be displayed publicly. Only effective if Automatic excerpt is selected.', 'pwal') ?></span></td>
					</tr>
					
				</table>
			</div>
			</div>
				
		<div class="postbox <?php echo $this->postbox_classes('pwal_accessibility_postbox') ?>" id="pwal_accessibility_postbox"><div class="handlediv" title="<?php echo esc_attr__('Click to toggle') ?>"><br /></div>
            <h3 class='hndle'><span><?php _e('Accessibility Settings', 'pwal'); ?></span></h3>
            <div class="inside">
			
				<table class="form-table">
					
					<tr valign="top">
						<th scope="row" ><?php _e('Enable on the home page', 'pwal') ?></th>
						<td colspan="2">
						<select name="home">
						<option value="true" <?php if ( $this->options['home'] == 'true' ) echo "selected='selected'"?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php if ( $this->options['home'] <> 'true' ) echo "selected='selected'"?>><?php _e('No','pwal')?></option>
						</select>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Enable for multiple post pages', 'pwal') ?></th>
						<td colspan="2">
						<select name="multi">
						<option value="true" <?php if ( $this->options['multi'] == 'true' ) echo "selected='selected'"?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php if ( $this->options['multi'] <> 'true' ) echo "selected='selected'"?>><?php _e('No','pwal')?></option>
						</select>
						<span class="description"><?php _e('Enables the plugin for pages (except the home page) which contain content for more that one post/page, e.g. archive, category pages. Some themes use excerpts here so enabling plugin for these pages may cause strange output. ', 'pwal')?></span>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Admin sees full content','pwal')?></th>
						<td colspan="2">
						<select name="admin">
						<option value="true" <?php if ( $this->options['admin'] == 'true' ) echo "selected='selected'"?>><?php _e('Yes','pwal')?></option>
						<option value="" <?php if ( $this->options['admin'] <> 'true' ) echo "selected='selected'"?> ><?php _e('No','pwal')?></option>
						</select>
						<span class="description"><?php _e('You may want to select No for test purposes.','pwal')?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Authorized users see full content','pwal')?></th>
						<td colspan="2">
						<select name="authorized" id="authorized">
						<option value="true" <?php if ( $this->options['authorized'] == 'true' ) echo "selected='selected'"?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php if ( $this->options['authorized'] <> 'true' ) echo "selected='selected'"?>><?php _e('No','pwal')?></option>
						</select>
						<span class="description"><?php _e('If Yes, authorized users will see the full content without the need to like a content. Authorization level will be revealed after you select yes. Admin setting is independent of this one.','pwal')?></span>
						</td>
					</tr>
					<script type="text/javascript">
					jQuery(document).ready(function($){
						$("select#authorized").change(function() {
							if ( $('select#authorized').val() == "true" ) { $("#level").show(); }
							else { $("#level").hide(); }
						});
					});
					</script>					
					<tr valign="top" id="level" <?php if ( $this->options['authorized'] != 'true' ) echo 'style="display:none"'?>>
						<th scope="row" ><?php _e('User level where authorization starts','pwal')?></th>
						<td colspan="2">
						<select name="level">
						<option value="editor" <?php if ( $this->options['level'] == 'editor' ) echo "selected='selected'"?>><?php _e('Editor','pwal')?></option>
						<option value="author" <?php if ( $this->options['level'] == 'author' ) echo "selected='selected'"?>><?php _e('Author','pwal')?></option>
						<option value="contributor" <?php if ( $this->options['level'] == 'contributor' ) echo "selected='selected'"?>><?php _e('Contributor','pwal')?></option>
						<option value="subscriber" <?php if ( $this->options['level'] == 'subscriber' ) echo "selected='selected'"?>><?php _e('Subscriber','pwal')?></option>
						</select>
						<span class="description"><?php _e('If the above field is selected as yes, users having a higher level than this selection will see the full content.','pwal')?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Search bots see full content','pwal')?></th>
						<td colspan="2">
						<select name="bot">
						<option value="true" <?php if ( $this->options['bot'] == 'true' ) echo "selected='selected'"?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php if ( $this->options['bot'] <> 'true' ) echo "selected='selected'"?>><?php _e('No','pwal')?></option>
						</select>
						<span class="description"><?php _e('You may want to enable this for SEO purposes. Warning: Your full content may be visible in search engine results.','pwal')?></span>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Cookie validity time (hours)', 'pwal')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="cookie" value="<?php echo $this->options["cookie"] ?>" />
						<span class="description"><?php _e('Validity time of the cookie which lets visitor to be exempt from the protection after he/she liked. Tip: If you want the cookie to expire at the end of the session (when the browser is closed), enter zero here.', 'pwal') ?></span></td>
					</tr>
				</table>
			</div>
			</div>					

			
			<div class="postbox <?php echo $this->postbox_classes('pwal_social_button_postbox') ?>" id="pwal_social_button_postbox"><div class="handlediv" title="<?php echo esc_attr__('Click to toggle') ?>"><br /></div>
            <h3 class='hndle'><span><?php _e('Social Button Settings', 'pwal') ?></span></h3>
            <div class="inside">
			
				<table class="form-table">
				
					<tr valign="top">
						<th scope="row" ><?php _e('Buttons to use','pwal')?></th>
						<td colspan="2">
						<input type="checkbox" name="use_facebook" value="true" <?php if ($this->options["use_facebook"]) echo "checked='checked'"?>>&nbspFacebook&nbsp&nbsp&nbsp
						<input type="checkbox" name="use_linkedin" value="true" <?php if ($this->options["use_linkedin"]) echo "checked='checked'"?>>&nbspLinkedin&nbsp&nbsp&nbsp
						<input type="checkbox" name="use_twitter" value="true" <?php if ($this->options["use_twitter"]) echo "checked='checked'"?>>&nbspTwitter&nbsp&nbsp&nbsp
						<input type="checkbox" name="use_google" value="true" <?php if ($this->options["use_google"]) echo "checked='checked'"?>>&nbspGoogle+1&nbsp&nbsp&nbsp
						<?php
						do_action("pwal_additional_button_settings");
						?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" ><?php _e('Load scripts for','pwal')?></th>
						<td colspan="2">
						<input type="checkbox" name="load_facebook" value="true" <?php if ($this->options["load_facebook"]) echo "checked='checked'"?>>&nbspFacebook&nbsp&nbsp&nbsp
						<input type="checkbox" name="load_linkedin" value="true" <?php if ($this->options["load_linkedin"]) echo "checked='checked'"?>>&nbspLinkedin&nbsp&nbsp&nbsp
						<input type="checkbox" name="load_twitter" value="true" <?php if ($this->options["load_twitter"]) echo "checked='checked'"?>>&nbspTwitter&nbsp&nbsp&nbsp
						<input type="checkbox" name="load_google" value="true" <?php if ($this->options["load_google"]) echo "checked='checked'"?>>&nbspGoogle+1&nbsp&nbsp&nbsp
						<br /><span class="description"><?php _e('If you have other plugins which already use these scripts, duplicate load of them may create conflict. In that case uncheck related checkbox. If you are unsure and not having any issues, keep this settings checked.', 'pwal') ?></span>
						<?php
						do_action("pwal_additional_script_settings");
						?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" ><?php _e('Description above the buttons', 'pwal') ?></th>
						<td colspan="2"><input type="text" style="width:500px" name="description" value="<?php echo $this->options["description"] ?>" />
						<br /><span class="description"><?php _e('You may want to write something here that will encourage the visitor to click a button. If you want individual descriptions on post basis, use Selection Tool method and write description inside the tool.', 'pwal') ?></span></td>
					</tr>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Sitewide Like','pwal')?></th>
						<td colspan="2">
						<select name="sitewide">
						<option value="true" <?php if ( $this->options['sitewide'] == 'true' ) echo "selected='selected'"?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php if ( $this->options['sitewide'] <> 'true' ) echo "selected='selected'"?>><?php _e('No','pwal')?></option>
						</select>
						<span class="description"><?php _e('If selected yes, when visitor likes a single content, all protected content on the website will be revealed to him/her.','pwal')?></span>
						</td>
					</tr>
						
					<tr valign="top" id="url_to_like" <?php if ( $this->options['random'] == 'true' ) echo 'style="display:none"'?>>
						<th scope="row" ><?php _e('URL to be liked', 'pwal') ?></th>
						<td colspan="2"><input type="text" style="width:500px" name="url_to_like" value="<?php echo $this->options["url_to_like"] ?>" />
						<br /><span class="description"><?php
						/* translators: Here, %s is the home page url. */
						printf(__('You can enter a single URL to be liked, e.g. your home page, %s. NOT your page on the Social Networking Website, e.g. Facebook. If left empty, the page that button is clicked will be liked.', 'pwal'), home_url() ); 
						?></span></td>
					</tr>
					
					<tr valign="top">
						<th scope="row" ><?php _e('Like Random Page','pwal')?></th>
						<td colspan="2">
						<select name="random" id="random">
						<option value="true" <?php if ( $this->options['random'] == 'true' ) echo "selected='selected'"?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php if ( $this->options['random'] <> 'true' ) echo "selected='selected'"?>><?php _e('No','pwal')?></option>
						</select>
						<span class="description"><?php _e('If selected yes, a random published page or post on your website will be selected to be liked. This disables "URL to be liked" setting.','pwal')?></span>
						</td>
					</tr>
					<script type="text/javascript">
					jQuery(document).ready(function($){
						$("select#random").change(function() {
							if ( $('select#random').val() == "true" ) { $("#url_to_like").hide(); }
							else { $("#url_to_like").show(); }
						});
					});
					</script>	
				</table>
			</div>
			</div>

					<input type="hidden" name="action_pwal" value="update_pwal" />
					<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'pwal') ?>" />
					</p>
			</form>

			<?php
			
			?>
			
			<div class="postbox <?php echo $this->postbox_classes('pwal_statistics_postbox') ?>" id="pwal_statistics_postbox"><div class="handlediv" title="<?php echo esc_attr__('Click to toggle') ?>"><br /></div>
			 <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<h3 class='hndle'><span><?php _e('Basic Statistics', 'pwal'); ?></span></h3>
				<div class="inside" id="pwal_stats">
					<?php
					$stats = get_option( "pwal_statistics" );
					if ( !is_array( $stats ) )
						$stats = array();
					else	
						$stats = array_filter( $stats );
						
					$total_likes = apply_filters( "pwal_total_likes", count( $stats ) );
						
					if ( !$total_likes )
						echo "There is no data yet";
					else {
						$fb = $lin = $tw = $gp = 0; // Set like counts to zero
						$lposts = array(); // Most liked posts
						foreach ( $stats as $stat ) {
							foreach ( $stat as $key=>$value ) {
								if ( 's' == $key ) {
									switch( $value ) {
										case 'facebook':	$fb++; break;
										case 'linkedin':	$lin++; break;
										case 'twitter':		$tw++; break;
										case 'google':		$gp++; break;
									}
								}
								else if ( 'p' == $key ) {
									if ( array_key_exists( $value, $lposts ) ) // Value is post_id here
										$lposts[$value]++;
									else
										$lposts[$value] = 1;
								}
							}
						}
						arsort( $lposts ); // Sort posts acc to the most likes
						
						$like_stats = array( 
									'Facebook'	=> $fb,
									'LinkedIn'	=> $lin,
									'Twitter'	=> $tw,
									'Google+1'	=> $gp
									);
						$like_stats = apply_filters( "pwal_like_stats", $like_stats );
						
						?>
							<table class="form-table">
								<tr valign="top">
									<th scope="row" ><?php _e('Total likes','pwal') ?></th>
									<td colspan="2">
									<?php echo $total_likes ?>
									</td>
								</tr>
						<?php
						$like_wording = __('likes','pwal');
						foreach ( $like_stats as $social=>$count ) {
							if ( $count > 0 ) {
							?>
								<tr valign="top">
									<th scope="row" ><?php echo $social ." " . $like_wording ?></th>
									<td colspan="2">
									<?php echo $count ?>
									</td>
								</tr>
							<?php
							}
						}
						?>
								<tr valign="top">
									<th scope="row" ><?php _e('Most liked posts','pwal')?></th>
									<td colspan="2">
						<?php
						$n = 1;
						$popular = "";
						foreach ( $lposts as $lpost_id => $lcount ) {
							if ( $n >= 10 ) break; // 10 popular posts are enough
							$ppost = get_post( $lpost_id );
							if ( is_object( $ppost ) ) {
								$popular .= "<a href='".get_permalink( $lpost_id )."' title='". wp_trim_words( $ppost->post_title, 4 )."' >" . $ppost->post_title . "</a> (". $lcount . "), ";
								$n++;
							}
						}
						echo rtrim( $popular, ", ") ;
						?></td></tr></table>

						<table class="form-table">
						<tr valign="top">
							<th scope="row" >
							<input type="button" id="pwal_clear_button" class="button-secondary" value="<?php _e('Clear Statistics') ?>" title="<?php _e('Clicking this button deletes statistics saved on the server') ?>" />
							</th>
						
							<td colspan="2">
							<form action="<?php echo admin_url('admin-ajax.php?action=pwal_export_stats'); ?>" method="post">
								<input type="submit" class="button-secondary" value="<?php _e('Export Statistics') ?>" title="<?php _e('If you click this button a CSV file including statistics will be saved on your PC') ?>" />
							</form>
							</td>
						</tr>
						</table>
						

						<?php
						
					}
					?>
				</div>
			</div>
			
			<div class="postbox <?php echo $this->postbox_classes('pwal_instructions') ?>" id="pwal_instructions"><div class="handlediv" title="<?php echo esc_attr__('Click to toggle') ?>"><br /></div>
				<h3 class='hndle'><span><?php _e('Customization Instructions', 'pwal'); ?></span></h3>
				<div class="inside">
					<?php
					_e('For protecting html codes that you cannot add to post content, there is a template function <b>wpmudev_pwal_html</b>. This function replaces all such codes with like buttons and reveal them when payment is done. Add the following codes to the page template where you want the html codes to be displayed and modify as required. Also you need to use the bottom action function.', 'pwal');
					?>
					<br />
					<code>
					&lt;?php<br /> 
					if ( function_exists( 'wpmudev_pwal_html' ) ) {<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$html = '&lt;iframe width="560" height="315" src="http://www.youtube.com/embed/-uiN9z5tqhg" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;'; // html code to be protected (required)<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$id = 1; // An optional unique id if you are using the function more than once on a single page<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$description = 'video'; // Optional description of the protected content<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo wpmudev_pwal_html( $html, $id, $description );<br />
					}<br />
					?&gt;
					</code>
					<br />
					<?php
					_e('Some custom post types use templates which take the post content directly from the database. For such applications you may need to use <b>wpmudev_pwal</b> function to manage the content.', 'pwal');
					?>
					<br />
					<?php
					_e('Example: Suppose that the content of a post type is displayed like this: <code>&lt;?php echo custom_description(); ?&gt;</code>. Then edit that part of the template like this:','pwal');
					?>
					<br />
					<code>
					&lt;?php<br /> 
					if ( function_exists( 'wpmudev_pwal' ) )<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo wpmudev_pwal( custom_description() );<br />
					else<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo custom_description();<br />
					?&gt;
					</code>
					<br />
					<?php
					_e( 'For both of the above usages you <b>must</b> create a function in your functions.php to call necessary css and js files. Here is an example:', 'pwal');
					?>
					<br />
					<code>
					&lt;?php<br /> 
					function my_pwal_customization( ) {<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;global $pwal; <br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;if ( !is_object( $pwal ) ) return;<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$pwal->load_scripts_styles();<br />
					}<br />
					add_action( 'template_redirect', 'my_pwal_customization', 2 );<br />
					?&gt;
					</code>
					<br />
					<br />
					<?php 
					$uploads = wp_upload_dir();
					$default_css = "/wp-content/plugins/pay-with-a-like/css/front.css";
					$custom_css = "/wp-content/uploads/pay-with-a-like.css";
					printf(__('If you want to apply your own styles copy contents of front.css to your theme css file and add this code inside functions.php of your theme:<code>add_theme_support( "pay_with_a_like_style" )</code> OR copy and rename the default css file <b>%s</b> as <b>%s</b> and edit this latter file. Then, your edited styles will not be affected from plugin updates.', 'pwal'), $default_css, $custom_css); 
					?>
					<br />
				</div>
			</div>
					
		</div>
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#pwal_clear_button').click(function() {
				if ( !confirm('<?php _e("Are you sure to clear statistics?","pwal") ?>') ) {return false;}
				else{
					var data = {action: 'delete_stats', nonce: '<?php echo wp_create_nonce($this->options["salt"]) ?>'};
					$.post(ajaxurl, data, function(response) {
						if ( response && response.error )
							alert(response.error);
						else{
							$("#pwal_stats").html('<?php _e("Statistics cleared...","pwal") ?>');
						}
					},'json');							
				}
			});
			
			postboxes.add_postbox_toggles('<?php echo $this->page?>');
			
		});
		</script>

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
    if ( (current_user_can('edit_posts') || current_user_can('edit_pages')) && get_user_option('rich_editing') == 'true') {
   		add_filter( 'mce_external_plugins', array(&$this, 'tinymce_add_plugin') );
			add_filter( 'mce_buttons', array(&$this,'tinymce_register_button') );
			add_filter( 'mce_external_languages', array(&$this,'tinymce_load_langs') );
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
	 
		foreach($botlist as $bot){
			if( strpos($_SERVER['HTTP_USER_AGENT'],$bot)!== false )
			return true;	// Is a bot
		}
	 
		return false;	// Not a bot
	}
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
	function wpmudev_pwal_html( $html, $id=1, $description='' ) {
		global $pwal, $post;
		
		if ( $html )
			$content = $html;
		else if ( is_object( $post ) )
			$content = $post->content;
		else
			return 'No html code or post content found';
			
		return $pwal->content( '[pwal id="'.$id.'" description="'.$description.'"]'. $content . '[/pwal]', true, 'tool' );
	}
}


///////////////////////////////////////////////////////////////////////////
/* -------------------- WPMU DEV Dashboard Notice -------------------- */
if ( !class_exists('WPMUDEV_Dashboard_Notice') ) {
	class WPMUDEV_Dashboard_Notice {
		
		var $version = '2.0';
		
		function WPMUDEV_Dashboard_Notice() {
			add_action( 'plugins_loaded', array( &$this, 'init' ) ); 
		}
		
		function init() {
			if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) && is_admin() ) {
				remove_action( 'admin_notices', 'wdp_un_check', 5 );
				remove_action( 'network_admin_notices', 'wdp_un_check', 5 );
				if ( file_exists(WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php') ) {
					add_action( 'all_admin_notices', array( &$this, 'activate_notice' ), 5 );
				} else {
					add_action( 'all_admin_notices', array( &$this, 'install_notice' ), 5 );
					add_filter( 'plugins_api', array( &$this, 'filter_plugin_info' ), 10, 3 );
				}
			}
		}
		
		function filter_plugin_info($res, $action, $args) {
			global $wp_version;
			$cur_wp_version = preg_replace('/-.*$/', '', $wp_version);
		
			if ( $action == 'plugin_information' && strpos($args->slug, 'install_wpmudev_dash') !== false ) {
				$res = new stdClass;
				$res->name = 'WPMU DEV Dashboard';
				$res->slug = 'wpmu-dev-dashboard';
				$res->version = '';
				$res->rating = 100;
				$res->homepage = 'http://premium.wpmudev.org/project/wpmu-dev-dashboard/';
				$res->download_link = "http://premium.wpmudev.org/wdp-un.php?action=install_wpmudev_dash";
				$res->tested = $cur_wp_version;
				
				return $res;
			}
	
			return false;
		}
	
		function auto_install_url() {
			$function = is_multisite() ? 'network_admin_url' : 'admin_url';
			return wp_nonce_url($function("update.php?action=install-plugin&plugin=install_wpmudev_dash"), "install-plugin_install_wpmudev_dash");
		}
		
		function activate_url() {
			$function = is_multisite() ? 'network_admin_url' : 'admin_url';
			return wp_nonce_url($function('plugins.php?action=activate&plugin=wpmudev-updates%2Fupdate-notifications.php'), 'activate-plugin_wpmudev-updates/update-notifications.php');
		}
		
		function install_notice() {
			echo '<div class="error fade"><p>' . sprintf(__('Easily get updates, support, and one-click WPMU DEV plugin/theme installations right from in your dashboard - <strong><a href="%s" title="Install Now &raquo;">install the free WPMU DEV Dashboard plugin</a></strong>. &nbsp;&nbsp;&nbsp;<small><a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">(find out more)</a></small>', 'wpmudev'), $this->auto_install_url()) . '</a></p></div>';
		}
		
		function activate_notice() {
			echo '<div class="updated fade"><p>' . sprintf(__('Updates, Support, Premium Plugins, Community - <strong><a href="%s" title="Activate Now &raquo;">activate the WPMU DEV Dashboard plugin now</a></strong>.', 'wpmudev'), $this->activate_url()) . '</a></p></div>';
		}
	
	}
	new WPMUDEV_Dashboard_Notice();
}