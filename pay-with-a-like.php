<?php
/*
Plugin Name: Pay With a Like
Description: Allows protecting posts/pages until visitor likes the page with Facebook, Linkedin, Twitter or Google +1.
Plugin URI: http://premium.wpmudev.org/project/pay-with-a-like
Version: 1.0.0
Author: Hakan Evin (Incsub)
Author URI: http://premium.wpmudev.org/
TextDomain: pwal
Domain Path: /languages/
WDP ID: 
*/

/* 
Copyright 2007-2011 Incsub (http://incsub.com)

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

	var $version="1.0.0";

	/**
     * Constructor
     */
	function PayWithaLike() {
		$this->__construct();
	}
	function __construct() {
		
		// Read all options at once
		$this->options = get_option( 'pwal_options' );
		$this->options['salt'] = get_option( 'pay_with_a_like_salt' );
		
		add_action( 'template_redirect', array(&$this, 'cachable'), 1 );		// Check if page can be cached
		add_action( 'plugins_loaded', array(&$this, 'localization') );			// Localize the plugin
		add_action( 'save_post', array( &$this, 'add_postmeta' ) ); 			// Calls post meta addition function on each save
		add_filter( 'the_content', array( &$this, 'content' ), 12 ); 			// Manipulate the content. 
		add_action( 'the_content', array($this, 'clear'), 130 );				// Clear if a shortcode is left
		add_action( 'wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts') );// Load css and javascript files
		add_action( 'wp_ajax_nopriv_pwal_action', array(&$this, 'set_cookie') );// Ajax request after a button is clicked
		add_action( 'wp_ajax_pwal_action', array(&$this, 'set_cookie') ); 		// Ajax request after a button is clicked
		
		// tinyMCE stuff
		add_action( 'wp_ajax_pwalTinymceOptions', array(&$this, 'tinymce_options') );
		add_action( 'admin_init', array(&$this, 'load_tinymce') );
	
		// Admin side actions
		add_action( 'admin_notices', array($this, 'no_button') );				// Warn admin if no Social button is selected
		add_action( 'admin_notices', array($this, 'notice_settings') );			// Notice admin to make some settings
		add_filter( 'plugin_row_meta', array(&$this,'set_plugin_meta'), 10, 2 );// Add settings link on plugin page
		add_action( 'admin_menu', array( &$this, 'admin_init' ) ); 				// Creates admin settings window
		add_action( 'add_meta_boxes', array( &$this, 'add_custom_box' ) ); 		// Add meta box to posts

		$this->plugin_dir = WP_PLUGIN_DIR . '/pay-with-a-like';
		$this->plugin_url = plugins_url( '/pay-with-a-like' );
		
		// By default assume that pages are cachable (Cache plugins are allowed)
		$this->is_cachable = true;
	}
	
	/**
	* Add Settings link to the plugin page
	* @ http://wpengineer.com/1295/meta-links-for-wordpress-plugins/
	*/
	function set_plugin_meta($links, $file) {
		$plugin = "pay-with-a-like";
		// create link
		if ($file == $plugin) {
			return array_merge(
				$links,
				array( sprintf( '<a href="options-general.php?page=%s">%s</a>', $plugin, __('Settings') ) )
			);
		}
		return $links;
	}
	
	/**
	 * Load css and javascript
	 */
	function wp_enqueue_scripts() {
		wp_enqueue_script( "jquery" );
		wp_enqueue_style( "paywithalike-css", $this->plugin_url. "/css/front.css", array(), $this->version );
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
	 * Checks if user is authorised by the admin
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
		if ( is_singular() ) {
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
		
		// Prevent cache plugins
		if ( !$this->is_cachable ) {
			if ( !defined( 'DONOTCACHEPAGE' ) )
				define( 'DONOTCACHEPAGE', true );
				
			// We will add SB buttons and codes to only uncachable pages, i.e those that plugin is enabled
			if ( $this->options["use_facebook"] && $this->options["load_facebook"] ) {
				$locale = preg_replace('/-/', '_', get_locale());
				wp_enqueue_script('facebook-all', 'http://connect.facebook.net/' . $locale . '/all.js');
				add_action('wp_footer', array(&$this, 'init_fb_script'));
			}
			if ( $this->options["use_linkedin"] && $this->options["load_linkedin"] )
				wp_enqueue_script( 'linkedin', 'http://platform.linkedin.com/in.js' );
			
			if ( $this->options["use_twitter"] && $this->options["load_twitter"] )
				wp_enqueue_script( 'twitter', 'http://platform.twitter.com/widgets.js' );
				
			if ( $this->options["use_google"] && $this->options["load_google"] )
				wp_enqueue_script( 'google-plusone', 'https://apis.google.com/js/plusone.js' );
				
			do_action("pwal_additional_button_scripts");
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
	function content( $content ) {
		
		// If caching is allowed no need to continue
		if ( $this->is_cachable )
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
		
		global $post;
		// Find method
		$method = get_post_meta( $post->ID, 'pwal_method', true );
		if ( $method == "" )
			$method = $this->options["method"]; // Apply default method, if there is none
			
		// If user paid, show content. 'Tool' option has its own logic
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
			if ( !$excerpt_len = get_post_meta( $post->ID, 'pwal_excerpt', true ) )
				$excerpt_len = $this->options["excerpt"];
			
			if ( count( $temp_arr ) <= $excerpt_len )
				return $this->clear($content);
				
			// Otherwise prepare excerpt
			$e = ""; 
			for ( $n=0; $n<$excerpt_len; $n++ ) {
				$e .= $temp_arr[$n] . " ";
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
					if ( in_array( $m[2], $contents ) ) // This is paid
						$content = str_replace( $m[0], $m[6] , $content );
					else
						$content = str_replace( $m[0], $this->render_buttons( $m[2],$m[4] ), $content );
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
	 *	Add button html codes and scripts
	 *
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
		if ( $this->options["random"] ) {
			global $wpdb;
			$result = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE (post_type='post' OR post_type='page') AND post_status='publish' ORDER BY RAND()");
			if ( $result != null )
				$url_to_like = get_permalink( $result->ID );
			else
				$url_to_like = get_permalink( $post->ID );
		}
		if ( trim( $url_to_like ) == '' )
			$url_to_like = home_url(); // Never let an empty url, just in case
		
		if ( trim( $description ) == '' )
			$description = $this->options["description"]; // Use default description, if one is not specifically set
		
		$content  = "<div class='pwal_container'>";
		if ( $description )
			$content .= "<div class='pwal_description'>". $description . "</div>";
		$content .= "<ul>";
		$script   = "<script type='text/javascript'>
						var pwal_data={'ajax_url': '".admin_url('admin-ajax.php')."'};
						jQuery(document).bind('pwal_button_action', function (e, service) {
							jQuery.post(pwal_data.ajax_url, {
								'action': 'pwal_action',
								'post_id':".$post->ID.",
								'content_id':".$id.",
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
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_facebook_button'><fb:like layout='box_count' href='{$url_to_like}'></fb:like></div></li>";
			$script  .= "function pwal_facebook_callback () {
							jQuery(document).trigger('pwal_button_action', ['facebook']);
						}
						function pwal_facebook_register () {
							if (typeof FB != 'undefined') FB.Event.subscribe('edge.create', pwal_facebook_callback);
							else setTimeout(pwal_facebook_register, 200);
						}
						pwal_facebook_register();
						";
		}
		if ( $this->options["use_linkedin"] ) {
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_linkedin_button'><script type='IN/Share' data-counter='top' data-url='{$url_to_like}' data-onsuccess='pwal_linkedin_callback' data-onerror='pwal_error'></script></div></li>";
			$script  .= "function pwal_linkedin_callback () {
							jQuery(document).trigger('pwal_button_action', ['linkedin']);
						}
						function pwal_error(){
							alert('Error');
						}
						";
		}
		if ( $this->options["use_twitter"] ) {
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_twitter_button'><a href='http://twitter.com/share' class='twitter-share-button' data-count='vertical' data-url='{$url_to_like}'>Tweet</a></div></li>";
			$script  .= "function pwal_twitter_callback () {
							jQuery(document).trigger('pwal_button_action', ['twitter']);
						}
						twttr.events.bind('tweet', pwal_twitter_callback);
						";
		}
		if ( $this->options["use_google"] ) {
			$content .= "<li class='pwal_list_item_".$n."'><div class='pwal_button pwal_google_button'><g:plusone size='tall' href='{$url_to_like}' callback='pwal_google_callback'></g:plusone></div></li>";
			$script  .= "function pwal_google_callback (data) {
							if (data.state == 'off') return false;
							jQuery(document).trigger('pwal_button_action', ['google']);
						}";
		}
		$content = apply_filters( "pwal_render_button_html", $content, $post->ID );
		$script  = apply_filters( "pwal_render_button_script", $script, $post->ID );
		$content .= "</ul></div>";
		$script  .= "</script>";
		
		// remove line breaks to prevent wpautop break the script
		$script = str_replace( array("\r","\n","\t","<br>","<br />"), "", $script );

		return $content . $script;
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
			$expire = ''; 
		else
			$expire = time() + 3600 * $this->options["cookie"]; 
		
		if ( defined('COOKIEPATH') ) $cookiepath = COOKIEPATH;
		else $cookiepath = "/";
		if ( defined('COOKIEDOMAIN') ) $cookiedomain = COOKIEDOMAIN;
		else $cookiedomain = '';
		
		// Setting cookie works in ajax request!!
		setcookie("pay_with_a_like", serialize($likes), $expire, $cookiepath, $cookiedomain);
		
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
		
		if ( is_page() )
			$pp = __('page','pwal');
		else 
			$pp = __('post','pwal');
		
		global $post;
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
		height:10px;
		}
		.pwal_info{
		padding-top:5px;
		}
		.pwal_info span.wpmudev-help{
		margin-top: 10px;
		}
		-->
		</style>
		<?php
		echo '<select name="pwal_enable" id="pwal_enable">';
		$e = get_post_meta( $post->ID, 'pwal_enable', true );
		$eselect = $dselect = '';
		if ( $e == 'enable' ) $eselect = ' selected="selected"';
		else if ( $e == 'disable' ) $dselect = ' selected="selected"';
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
		
		echo '<select name="pwal_method">';
		switch ( get_post_meta( $post->ID, 'pwal_method', true ) ) {
			case "automatic":	$aselect = 'selected="selected"'; break;
			case "manual":		$mselect = 'selected="selected"'; break;
			case "tool":		$tselect = 'selected="selected"'; break;
			default:			$aselect = $mselect = $tselect = ''; break;
		}
		echo '<option value="" >'. __("Follow global setting","pwal"). '</option>';
		echo '<option value="automatic" '.$aselect.'>'. __("Automatic excerpt","pwal"). '</option>';
		echo '<option value="manual" '.$mselect.'>' . __("Manual excerpt","pwal"). '</option>';
		echo '<option value="tool" '.$tselect.'>' . __("Use selection tool","pwal") . '</option>';
		echo '</select>';

		echo '<label for="pwal_method">';
		_e('Method', 'pwal');
		echo '</label>';
		echo '<div class="pwal_info">';
		/* translators: First %s refer to post or page. Second %s is the url address of the icon */
		echo $this->tips->add_tip(sprintf(__('Selects the content protection method for this %s. If Follow Global Setting is selected, method selected in General Settings page will be applied. If you want to override general settings, select one of the other methods. With Use Selection Tool you need to select each content using the icon %s on the editor tool bar. For other methods refer to the settings page.','pwal'),$pp,"<img src='".$this->plugin_url."/images/menu_icon.png"."' />" ) );
		echo '</div>';
		echo '<div class="pwal_clear"></div>';
		
		echo '<input type="text" name="pwal_excerpt" value="'.get_post_meta( $post->ID, 'pwal_excerpt', true ).'" />';
		echo '<label for="pwal_excerpt">';
		_e('Excerpt length', 'pwal');
		echo '</label>';
		
		echo '<div class="pwal_info">';
		/* translators: %s refer to post or page */
		echo $this->tips->add_tip(sprintf(__('If you want to override the number of words that will be used as an excerpt for the unprotected content, enter it here. Please note that this value is only used when Automatic Excerpt method is applied to the %s.','pwal'),$pp ));
		echo '</div>';
		echo '<div class="pwal_clear"></div>';

	}

	/**
	 *	Saves post meta values
	 *
	 */
	function add_postmeta( $post_id ) {

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
				sprintf(__("<b>[Pay With a Like]</b> It looks like you have just installed me. You may want to adjust some %s. If you are using a cache plugin, please clear your cache.", 'pwal'),"<a href='".admin_url('options-general.php?page=pay-with-a-like')."'>".__("settings","pwal")."</a>") .
				'</p></div>';
		}
		// If admin visits setting page, remove this annoying message :P
		if ( $screen->id == $pwal_options_page && !$this->options["no_visit"] ) {
			$this->options["no_visit"] = "true";
			update_option( "pwal_options", $this->options );
		}
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
		
			<div class="postbox">
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
				
			<div class="postbox">
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
						<span class="description"><?php _e('If Yes, authorized users will see the full content without the need to pay or subscribe. Admin setting is independent of this one.','pwal')?></span>
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
						<span class="description"><?php _e('Validity time of the cookie which lets visitor to be exempt from the protection after he/she liked. Tip: If you want the cookie to expire at the end of the session (when the browser closes), enter zero here.', 'pwal') ?></span></td>
					</tr>
				</table>
			</div>
			</div>					

			
			<div class="postbox">
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
						printf(__('You can enter a single url to be liked, e.g. your home page, %s. If left empty, the page that button is clicked will be liked.', 'pwal'), home_url() ); 
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
		</div>
		</div>

	<?php
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

$pwal = &new PayWithaLike() ;
global $pwal;

///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
  }
}
/* --------------------------------------------------------------------- */
