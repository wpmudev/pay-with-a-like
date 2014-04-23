<?php
function pwal_admin_panels_global() {
	global $pwal;
	
	//echo "options<pre>"; print_r($pwal->options); echo "</pre>";
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Where to show Pay With a Like Buttons', 'pwal') ?></span></h3>
   		<div class="inside">		
         	<p class="description"><?php _e('Pay With a Like allows protecting posts or parts of posts until the visitor clicks the action button to show the protected content. These settings provide a quick way to set Pay With a Like for your posts and pages. They can be overridden on a per post basis using the post editor metabox.', 'pwal') ?></p>

         	<p class="description"><?php _e('<strong>Enabled for All</strong> - When checked will include all of the selected posts when displaying the Pay With a Like buttons. If unchecked you can still enable individual posts via the Pay With a Like metabox shown on the post editor screen.', 'pwal') ?></p>
         	<p class="description"><?php _e('<strong>Show Metabox</strong> - When checked will display Pay With a Like metabox on the post editor screen.', 'pwal') ?></p>

			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><?php _e('Activation for post types', 'pwal')?></th>
				<td colspan="2">
					<?php
						//echo "post_types<pre>"; print_r($pwal->options['post_types']); echo "</pre>";
						$post_types = get_post_types(array('public' => true), 'objects');
						if (!empty($post_types)) {
							?><ul id="pwal-post-types"><?php
							foreach($post_types as $slug => $post_type) {
								if ($slug == 'attachment')
									continue;
								
								if (isset($pwal->options['post_types'][$slug])) { 
									$checked_post_type = ' checked="checked" '; 
								} else {
									$checked_post_type = '';
								}
								if (isset($pwal->options['show_metabox'][$slug])){ 
									$checked_metabox = ' checked="checked" '; 
								} else {
									$checked_metabox = '';
								}
								
								?><li>
									<span class="pwal-post-type-label"><?php echo $post_type->label ?><span>
									<ul id="pwal-post-types-<?php echo $slug; ?>" class="pwal-post-types-item">
										<li><input type="checkbox" value="<?php echo $slug; ?>" id="pwal-post-type-<?php echo $slug ?>" 
											class="pwal-post-type-item" value="enable" name="pwal[post_types][<?php echo $slug ?>]" <?php 
											echo $checked_post_type ?> /> <label class="label-text" for="pwal-post-type-<?php echo $slug ?>"><?php 
												_e('Enable for all', 'pwal') ?> <?php echo $post_type->label ?></label></li>
										<li><input type="checkbox" value="<?php echo $slug; ?>" id="pwal-show-meta-<?php echo $slug ?>" 
											class="pwal-show-meta-item" value="enable" name="pwal[show_metabox][<?php echo $slug ?>]" <?php  
											echo $checked_metabox ?>  /> <label class="label-text" 
											for="pwal-post-type-<?php echo $slug ?>"><?php _e('Show Metabox', 'pwal') ?></label></li>
									</ul>
								</li><?php
							}
							?></ul><?php
						}
					?>
				</td>
			</tr>
			</table>
		</div>
	</div>
	<?php
}


function pwal_admin_panels_defaults() {
	global $pwal;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Default settings for Pay With a Like content protection', 'pwal') ?></span></h3>
   		<div class="inside">		
			<p class="description"><?php _e('This section lets you define the default content handling for all post types. You will be able to override these settings on individual posts via the editor screen if you have checked Show Metabox above for that post type.', 'pwal') ?></p>
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_method"><?php _e('Revealed content selection method', 'pwal')?></label></th>
				<td colspan="2">
					<ul id="pwal_method_options">
						<li><input type="radio" name="pwal[method]" id="pwal_method_automatic" value="automatic" <?php checked( $pwal->options['method'], 'automatic' ) ?> /> <label for="pwal_method_automatic"><?php _e('<strong>Automatic Excerpt</strong> - This option will create an automatic excertp from the content to show users. You can control the excerpt length.', 'pwal')?></label></li>
						<li><input type="radio" name="pwal[method]" id="pwal_method_manual" value="manual" <?php checked( $pwal->options['method'], 'manual' )?> /> <label for="pwal_method_manual"><?php _e('<strong>Manual Excerpt</strong> - This option tells the plugin to use the excerpt field from the post. Note the post type must support the Excerpt field. Some post types like Pages do not. This option will default to automatic if the post types do not support the Excerpt field.', 'pwal')?></label></li>
						<li><input type="radio" name="pwal[method]" id="pwal_method_tool" value="tool" <?php checked( $pwal->options['method'], 'tool' )?> /> <label for="pwal_method_tool"><?php _e('<strong>Select Excerpt</strong> - This option allows you to manually select text within your post content for the <strong>hidden content</strong>. Using this tool you will see the button on the Visual editor toolbar. The hidden content can be anywhere within your content. You are not limited to the beginning or end of the content like with excerpts.', 'pwal')?></label></li>
					</ul>					
				</td>
			</tr>

			<tr valign="top" id="excerpt_length" <?php if ( $pwal->options['method'] != 'automatic' ) echo 'style="display:none"'?>>
				<th scope="row" ><label for="pwal_excerpt"><?php _e('Excerpt length (words)', 'pwal')?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('Number of words of the post content that will be displayed publicly. Only effective if Automatic excerpt is selected.', 'pwal') ?></p>
					<input type="text" class="regular-text" id="pwal_excerpt" name="pwal[excerpt]" value="<?php echo $pwal->options["excerpt"] ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_description"><?php _e('Description above the buttons', 'pwal') ?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('You may want to write something here that will encourage the visitor to click a button. You may customize this message on the individual post editor metabox.', 'pwal') ?></p>
					<input type="text" class="regular-text" id="pwal_description" name="pwal[description]" value="<?php echo $pwal->options["description"] ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_content_reload"><?php _e('Reload Content on Like', 'pwal')?></label></th>
				<td colspan="2">
					<p class="description"><?php 
						_e("When a user clicks the Pay With a Like buttons the content the hidden content can be revealed by full page refresh or AJAX load. For AJAX the Pay With a Like button will be removed and replaced with the new content. Note this option is still experimental and depending on the complexity of the content being shown may not work. You will be able to change this setting at the individual content level.", 'pwal'); 
					?></p>
				<select id="pwal_content_reload" name="pwal[content_reload]">
					<option value="refresh" <?php selected($pwal->options['content_reload'], 'refresh' ) ?>><?php _e('Page refresh', 'pwal')?></option>
					<option value="ajax" <?php selected( $pwal->options['content_reload'], 'ajax' ) ?>><?php _e('AJAX', 'pwal')?></option>
				</select>
				</td>
			</tr>
			</table>
		</div>
	</div>
	<?php
}

function pwal_admin_panels_container() {
	global $pwal, $wp_roles;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Buttons Container Settings', 'pwal') ?></span></h3>
		<div class="inside">		
			<p class="description"><?php _e('This section give you some control over the container box wrapped around the social buttons.', 'pwal') ?></p>
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_container_width"><?php _e('Width', 'pwal') ?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('By default the Pay With a Like button container is added below or within the post content. The width defaults to the theme content_width variable if defined. If not it is set to 100%. If for some reason the width does not work well for your theme you can specify the alternate container width here. You may also override this on the individual posts.', 'pwal') ?></p>
					<input type="text" class="regular-text" id="pwal_container_width" name="pwal[container_width]" value="<?php echo $pwal->options["container_width"] ?>" />
				</td>
			</tr>
<?php /* ?>			
			<tr valign="top">
				<th scope="row" ><label for="pwal_container_height"><?php _e('Height', 'pwal') ?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('Controls the height of the Pay With a Like button container. Default if left blank is auto.', 'pwal') ?></p>
					<input type="text" class="regular-text" id="pwal_container_height" name="pwal[container_height]" value="<?php echo $pwal->options["container_width"] ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" ><label for="pwal_container_border_width"><?php _e('Border', 'pwal') ?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('The Pay With a Like button container will show a border around the section. This is used to hightlight the container. This settings lets you override the border width, border style and border color values. Default values a border width: 1, border style: solid and border color: #E3E3E3', 'pwal') ?></p>
					<input type="text" class="regular-text" size="2" style="width: 100px;" id="pwal_container_border_width" name="pwal[container_border_width]" value="<?php echo $pwal->options["container_border_width"] ?>" /> <select id="pwal_container_border_style" name="pwal[container_border_style]">
					<option value="none" <?php selected( $pwal->options['container_border_style'], 'none' ) ?> ><?php _e('No border','pwal')?></option>
					<option value="solid" <?php selected( $pwal->options['container_border_style'], 'solid' ) ?>><?php _e('solid','pwal')?></option>
					<option value="dotted" <?php selected( $pwal->options['container_border_style'], 'dotted' ) ?>><?php _e('dotted','pwal')?></option>
					<option value="dashed" <?php selected( $pwal->options['container_border_style'], 'dashed' ) ?>><?php _e('dashed','pwal')?></option>
					<option value="double" <?php selected( $pwal->options['container_border_style'], 'double' ) ?>><?php _e('double','pwal')?></option>
					<option value="groove" <?php selected( $pwal->options['container_border_style'], 'groove' ) ?>><?php _e('groove','pwal')?></option>
					<option value="ridge" <?php selected( $pwal->options['container_border_style'], 'ridge' ) ?>><?php _e('ridge','pwal')?></option>
					<option value="outset" <?php selected( $pwal->options['container_border_style'], 'outset' ) ?>><?php _e('outset','pwal')?></option>
					<option value="initial" <?php selected( $pwal->options['container_border_style'], 'initial' ) ?>><?php _e('initial','pwal')?></option>
					<option value="inherit" <?php selected( $pwal->options['container_border_style'], 'inherit' ) ?>><?php _e('inherit','pwal')?></option>
				</select> <input type="text" class="regular-text" size="2" style="width: 100px;" id="pwal_container_border_color" name="pwal[container_border_color]" value="<?php echo $pwal->options["container_border_color"] ?>" />
				</td>
			</tr>
<?php */ ?>
			</table>
		</div>
	</div>
	<?php
}

function pwal_admin_panels_visibility() {
	global $pwal, $wp_roles;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Buttons Visibility Settings', 'pwal') ?></span></h3>
   		<div class="inside">		
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_home"><?php _e('Enable on the home page', 'pwal') ?></label></th>
				<td colspan="2">
				<p class="description"><?php _e('Enables the plugin for the home page. If you are displaying latest posts or some similar category archive enabling this will show the buttons on each post. If this option is not enabled the full content will be shown. If you are instead showing a static page you will control the buttons via that page form. Some themes use excerpts here so enabling plugin for these pages may cause strange output.', 'pwal')?></p>
				<select id="pwal_home" name="pwal[home]">
					<option value="true" <?php selected( $pwal->options['home'], 'true' ) ?> ><?php _e('Yes, show buttons','pwal')?></option>
					<option value="" <?php selected( $pwal->options['home'], '' ) ?>><?php _e('No, show full content','pwal')?></option>
				</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_multi"><?php _e('Enable for multiple post pages', 'pwal') ?></label></th>
				<td colspan="2">
				<p class="description"><?php _e('Enables the plugin for pages (except the home page) which contain content for more that one post/page, e.g. archive, category pages. Some themes use excerpts here so enabling plugin for these pages may cause strange output. ', 'pwal')?></p>
				<select id="pwal_multi" name="pwal[multi]">
					<option value="true" <?php selected( $pwal->options['multi'], 'true' ) ?> ><?php _e('Yes, show buttons','pwal')?></option>
					<option value="" <?php selected( $pwal->options['multi'], '' ) ?>><?php _e('No, show full content','pwal')?></option>
				</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_admin"><?php _e('Admin sees full content','pwal')?></label></th>
				<td colspan="2">
				<p class="description"><?php _e('You may want to select No for test purposes.','pwal')?></p>
				<select id="pwal_admin" name="pwal[admin]">
					<option value="true" <?php selected( $pwal->options['admin'], 'true' ) ?>><?php _e('Yes','pwal')?></option>
					<option value="" <?php selected( $pwal->options['admin'], '' ) ?> ><?php _e('No','pwal')?></option>
				</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_authorized"><?php _e('Authorized users see full content','pwal')?></label></th>
				<td colspan="2">
				<p class="description"><?php _e('If Yes, authorized users will see the full content without the need to like a content. Authorization level will be revealed after you select yes. Admin setting is independent of this one.','pwal')?></p>
				<select name="pwal[authorized]" id="pwal_authorized">
					<option value="true" <?php selected( $pwal->options['authorized'], 'true' ) ?> ><?php _e('Yes','pwal')?></option>
					<option value="" <?php selected( $pwal->options['authorized'], '' ) ?>><?php _e('No','pwal')?></option>
				</select>			
				</td>
			</tr>

			<tr valign="top" id="pwal_level_section" <?php if ( $pwal->options['authorized'] != 'true' ) echo 'style="display:none"'?>>
				<th scope="row" ><label for="pwal_level"><?php _e('User level where authorization starts','pwal')?></label></th>
				<td colspan="2">
				<p class="description"><?php _e('If the above field is selected as yes, users having a higher level than this selection will see the full content.','pwal')?></p>
				<select id="pwal_level" name="pwal[level]">
					<?php
						if (count($wp_roles)) {
							foreach ($wp_roles->roles as $role_slug => $role) {
								$role_level = $pwal->get_user_role_highest_level($role['capabilities']);
								if (!isset($log_display_levels['level_'.$role_level])) {
									$log_display_levels['level_'.$role_level] = "Level ". $role_level .": ". $role['name'];
								} else {
									$log_display_levels['level_'.$role_level] .= ", ". $role['name'];
								}
							}
						}
						if (count($log_display_levels)) {
							ksort($log_display_levels, SORT_NUMERIC);
							krsort($log_display_levels, SORT_NUMERIC);
							foreach ($log_display_levels as $role_level_key => $role_level_display) {

								?><option value="<?php echo $role_level_key; ?>" <?php 
									selected ( $pwal->options['level'], $role_level_key ) ?>><?php echo $role_level_display; ?></option><?php
							}
						}
					?>
				</select>
			
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_bot"><?php _e('Search bots see full content','pwal')?></label></th>
				<td colspan="2">
				<p class="description"><?php _e('You may want to enable this for SEO purposes. Warning: Your full content may be visible in search engine results.','pwal')?></p>
				<select id="pwal_bot" name="pwal[bot]">
					<option value="true" <?php selected( $pwal->options['bot'], 'true' ) ?> ><?php _e('Yes','pwal')?></option>
					<option value="" <?php selected( $pwal->options['bot'], '' ) ?>><?php _e('No','pwal')?></option>
				</select>
			
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_cookie"><?php _e('Cookie validity time (hours)', 'pwal')?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('Validity time of the cookie which lets visitor to be exempt from the protection after he/she liked. Tip: If you want the cookie to expire at the end of the session (when the browser is closed), enter zero here.', 'pwal') ?></p>
					<input type="text" style="width:50px" id="pwal_cookie" name="pwal[cookie]" value="<?php echo $pwal->options["cookie"] ?>" />
				</td>
			</tr>
			</table>
		</div>
	</div>
	<?php
}

function pwal_admin_panels_social() {
	global $pwal;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Social Buttons to display', 'pwal') ?></span></h3>
   		<div class="inside">		
			<table class="form-table">

			<tr valign="top">
				<th scope="row" ><?php _e('Buttons to use','pwal')?></th>
				<td colspan="2">
					<p class="description"><?php _e('Once you have set a button as Visible and save this page, you will see a new tab specific to the  button. On that tab you will be able to customize the style of the button. Drag the buttons into the order you want them displayed on the post. ', 'pwal') ?></p>
					<?php
						if (!isset($pwal->options['social_button_sort'])) {
							$pwal->options['social_button_sort'] = array();
						}
						if (empty($pwal->options['social_button_sort'])) {
							$pwal->options['social_button_sort'] = array_keys($pwal->options['social_buttons']);
						}
					?>
					<input type="hidden" name="pwal[social_button_sort]" id="pwal-social-button-sort" 
						value="<?php echo implode(',', $pwal->options['social_button_sort']) ?>" />
					<ul id="pwal-social-buttons">
						<?php
							// Here we need to figure out which items we have in the sort and which might be missing...
							$pwal_buttons_show = array();

							if (!empty($pwal->options['social_button_sort'])) {
								foreach($pwal->options['social_button_sort'] as $button_key) {

									if (isset($pwal->options['social_buttons'][$button_key])) {
										$pwal_buttons_show[$button_key] = $pwal->options['social_buttons'][$button_key];
										unset($pwal->options['social_buttons'][$button_key]);
									}
								}
							}
							
							// Append any missing ones to the end of the show array.
							if (!empty($pwal->options['social_buttons'])) {
								foreach($pwal->options['social_buttons'] as $button_key => $button_label) {
									$pwal_buttons_show[$button_key] = $pwal->options['social_buttons'][$button_key];
								}
							}
							
							// Now show the buttons. 
							foreach($pwal_buttons_show as $button_key => $button_label) {

								if (checked($pwal->options["use_". $button_key], 'true', false)) { 
									$checked_use_button = ' checked="checked" '; 
								} else {
									$checked_use_button = '';
								}
								if (checked($pwal->options["load_". $button_key], 'true', false)) { 
									$checked_load_button = ' checked="checked" '; 
								} else {
									$checked_load_button = '';
								}
								
								if (empty($checked_use_button)) {
									$disabled_load = ' disabled="disabled" ';
								} else {
									$disabled_load = '';
								}

								?>
								<li id="pwal-social-button-<?php echo $button_key ?>" class="ui-state-default pwal-social-button">
									<span id="pwal-social-button-title-<?php echo $button_key ?>" class="pwal-social-button-title"><?php echo $button_label ?></span>
									<span id="pwal-social-button-image-<?php echo $button_key ?>" class="pwal-social-button-image"></span>
									<ul id="pwal-social-button-sections-<?php echo $button_key ?>" class="pwal-social-button-sections">
										<li><span class="pwal-social-button-section">
											<input class="pwal-use-button" type="checkbox" <?php echo $checked_use_button; ?> 
												id="pwal-use-button-<?php echo $button_key ?>" value="true"
												name="pwal[use_<?php echo $button_key; ?>]" /> <label 
												for="pwal-use-button-<?php echo $button_key ?>"><?php _e('Visible', 'pwal'); ?></label>
											</span></li>
										<li><span class="pwal-social-button-section">
											<input class="pwal-load-button" type="checkbox" <?php echo $checked_load_button ?> <?php echo $disabled_load; ?>
												id="pwal-load-button-<?php echo $button_key ?>" value="true"
												name="pwal[load_<?php echo $button_key; ?>]" /> <label 
												for="pwal-load-button-<?php echo $button_key ?>"><?php _e('Load JS', 'pwal'); ?></label>
											</span></li>
										</ul>
								</li>
								<?php
							}
						?>
					</ul>
					<p class="description"><?php _e('<strong>Visible</strong> - If checked means the social button will be displayed within the Pay With a Like box on the post', 'pwal'); ?></p>
					<p class="description"><?php _e('<strong>Load JS</strong> - If checked means the plugin will load the needed JavaScript libraries from the social network in order to display the button. If you have other plugins which already use these scripts it mean there are potential issues because the JavaScript libraries will be loaded more than once. In that case uncheck related checkbox. If you are unsure and not having any issues, keep this settings checked.', 'pwal'); ?></p>
					
					<?php
				
				do_action("pwal_additional_button_settings");
				?>				
				</td>
			</tr>
		</table>
		</div>
	</div>
			
<?php /* ?>	
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('How the Social Buttons are displayed', 'pwal') ?></span></h3>
   		<div class="inside">		
			<table class="form-table">
				<tr valign="top">
				<th scope="row" ><?php _e('Description above the buttons', 'pwal') ?></th>
				<td colspan="2">
					<p class="description"><?php _e('You may want to write something here that will encourage the visitor to click a button. If you want individual descriptions on post basis, use Selection Tool method and write description inside the tool.', 'pwal') ?></p>
					<input type="text" class="regular-text" name="pwal[description]" value="<?php echo $pwal->options["description"] ?>" />
				</td>
			</tr>
			</table>
		</div>
	</div>
<?php */ ?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Sitewide and Random Likes', 'pwal') ?></span></h3>
   		<div class="inside">		
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_sitewide"><?php _e('Sitewide Like','pwal')?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('If selected yes, when visitor likes a single content, all protected content on the website will be revealed to him/her.','pwal')?></p>
					<select id="pwal_sitewide" name="pwal[sitewide]">
						<option value="true" <?php selected( $pwal->options['sitewide'], 'true' ) ?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php selected( $pwal->options['sitewide'], '' ) ?>><?php _e('No','pwal')?></option>
					</select>
			
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" ><label for="pwal_random"><?php _e('Like Random Page','pwal')?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('If selected yes, a random published page or post on your website will be selected to be liked. This disables "URL to be liked" setting.','pwal')?></p>
					<select name="pwal[random]" id="pwal_random">
						<option value="true" <?php selected( $pwal->options['random'], 'true' ) ?> ><?php _e('Yes','pwal')?></option>
						<option value="" <?php selected( $pwal->options['random'], '' ) ?>><?php _e('No','pwal')?></option>
					</select>
				</td>
			</tr>

			<tr valign="top" id="url_to_like_section" <?php if ( $pwal->options['random'] == 'true' ) echo 'style="display:none"'?>>
				<th scope="row" ><label for="pwal_url_to_like"><?php _e('URL to be liked', 'pwal') ?></label></th>
				<td colspan="2">
					<p class="description"><?php
					/* translators: Here, %s is the home page url. */
					printf(__('You can enter a single URL to be liked, e.g. your home page, %s. NOT your page on the Social Networking Website, e.g. Facebook. If left empty, the page that button is clicked will be liked.', 'pwal'), home_url() ); 
					?></p>
					<input type="text" class="regular-text" id="pwal_url_to_like" name="pwal[url_to_like]" value="<?php echo $pwal->options["url_to_like"] ?>" />
				</td>
			</tr>
			</table>
		</div>
	</div>	
	<?php
}

function pwal_admin_panels_facebook() {
	global $pwal;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Facebook Like button Display options', 'pwal') ?></span></h3>
   		<div class="inside">		
		
			<table class="form-table">		
			<tr valign="top">
				<th scope="row" ><label for=""><?php _e('Button Style', 'pwal') ?></label></th>
				<td colspan="2">
					<ul class="pwal-facebook-stlye-options">
						<li><input type="radio" name="pwal[facebook_layout_style]" id="pwal-facebook-layout-style-box_count" 
							value="box_count" <?php checked($pwal->options['facebook_layout_style'], 'box_count') ?>/> <label
							 for="pwal-facebook-layout-style-box-count"><?php _e('Vertical', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/fb_like_box_count.png'; ?>" /></div></li>

						<li><input type="radio" name="pwal[facebook_layout_style]" id="pwal-facebook-layout-style-button_count" 
							value="button_count" <?php checked($pwal->options['facebook_layout_style'], 'button_count') ?>/> <label
							 for="pwal-facebook-layout-style-button-count"><?php 
								_e('Horizontal', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/fb_like_button_count.png'; ?>" /></div></li>

						<li><input type="radio" name="pwal[facebook_layout_style]" id="pwal-facebook-layout-style-button" 
							value="button" <?php checked($pwal->options['facebook_layout_style'], 'button') ?>/> <label
							 for="pwal-facebook-layout-style-button"><?php 
								_e('No Count', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/fb_like_button.png'; ?>" /></div></li>

<?php /* ?>
						<li><input type="radio" name="pwal[facebook_layout_style]" id="pwal-facebook-layout-style-standard" 
							value="" <?php checked($pwal->options['facebook_layout_style'], '') ?> /> <label
							 for="pwal-facebook-layout-style-standard"><?php _e('Standard', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/fb_like_standard.png'; ?>" /></div></li>
<?php */ ?>
					</ul>
				</td>
			</tr>
<?php /* ?>			
			<tr valign="top">
				<th scope="row" ><?php _e('Color Scheme', 'pwal') ?></th>
				<td colspan="2">
					<select name="pwal[facebook_color_scheme]">
					<option value="" selected="selected"><?php _e('Light', 'pwal')?></option>
					<option value="dark" <?php if ( $pwal->options['facebook_color_scheme'] == 'dark' ) echo "selected='selected'"?>><?php _e('Dark', 'pwal')?></option>
					</select>
				</td>
			</tr>
<?php */ ?>
			<tr valign="top">
				<th scope="row" ><label for="pwal_facebook_verb"><?php _e('Verb to display', 'pwal') ?></label></th>
				<td colspan="2">
					<select id="pwal_facebook_verb" name="pwal[facebook_verb]">
					<option value="" <?php selected( $pwal->options['facebook_verb'], '' ) ?>><?php _e('Like', 'pwal')?></option>
					<option value="recommend" <?php selected( $pwal->options['facebook_verb'], 'recommend' ) ?>><?php _e('Recommend', 'pwal')?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" ><label for="pwal_facebook_include_share"><?php _e('Include Share button', 'pwal') ?></label></th>
				<td colspan="2">
					<select id="pwal_facebook_include_share" name="pwal[facebook_include_share]">
					<option value="" <?php selected( $pwal->options['facebook_include_share'], '' ); ?>><?php _e('No', 'pwal')?></option>
					<option value="yes" <?php selected( $pwal->options['facebook_include_share'], 'yes' ); ?>><?php _e('Yes', 'pwal')?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" ><label for="pwal_facebook_include_faces"><?php _e('Include Faces', 'pwal') ?></label></th>
				<td colspan="2">
					<select id="pwal_facebook_include_faces" name="pwal[facebook_include_faces]">
					<option value="" <?php selected( $pwal->options['facebook_include_faces'], '' ) ?>><?php _e('No', 'pwal')?></option>
					<option value="yes" <?php selected( $pwal->options['facebook_include_faces'], 'yes' ) ?>><?php _e('Yes', 'pwal')?></option>
					</select>
				</td>
			</tr>
					
			<tr valign="top">
				<th scope="row" ><label for="pwal_show_facebook_comment_popup"><?php _e('Facebook Comment popup', 'pwal') ?></label></th>
				<td colspan="2">
					<p class="description"><?php _e('If enabled, when the user clicks the like button Facebook will display a popup comment form. The PWAL process will wait until the user submits the form before reloading the page to show full content. If not enabled the form will submit after clicking the Facebook like button.', 'pwal') ?></p>
					<input type="checkbox" id="pwal_show_facebook_comment_popup" name="pwal[show_facebook_comment_popup]" value="true" <?php checked ($pwal->options["show_facebook_comment_popup"], 'true') ?>>&nbsp<?php _e('Wait for optional Facebook Comment popup on Facebook Like (Check for yes)', 'pwal'); ?>
				</td>
			</tr>
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Button Language', 'pwal') ?></span></h3>
   		<div class="inside">		
			<p class="description"><?php _e('In most cases the language your website is displayed in is acceptable as the language for the social buttons. But in some rare cases the social network API does not support your language. Here you can specify the alternate language to use instead of your default website language', 'pwal') ?></p>
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_facebook_button_lang"><?php _e('Button Language', 'pwal')?></label></th>
				<td colspan="2">
					<input type="text" class="regular-text" id="pwal_facebook_button_lang" name="pwal[facebook_button_lang]" value="<?php echo $pwal->options["facebook_button_lang"] ?>" />
					<?php $locale = get_locale(); ?>
					<p class="description"><?php echo sprintf(__("If left blank the default language as defined in your wp-config.php (<strong>%s</strong>) will be used. Please refer to the Facebook accepted %s codes.", 'pwal'), $locale, '<a href="https://developers.facebook.com/docs/internationalization/" target="_blank">'.__('Languages', 'pwal') .'</a>'); ?></p>
				</td>
			</tr>
		</table>
		</div>
	</div>
	
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Facebook App Setup', 'pwal'); ?></span></h3>
   		<div class="inside">		
			<p class="info"><?php _e('You can setup a Facebook App to allow deeper integration with the Facebook API. A Facebook App is required for options like check if a user has liked a page on Facebook.', 'pwal'); ?></p>
	
			<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="pwal_facebook_api_key"><?php _e('Facebook App API Key','pwal') ?></label></th>
				<td>
					<input type="text" style="width:90%" id="pwal_facebook_api_key" name="pwal[facebook_api_key]" value="<?php echo $pwal->options["facebook_api_key"] ?>" />
				</td>
				<td rowspan="2" class="info" style="vertical-align:top;">
					<ol style="margin-top:0px;">
						<li><?php print sprintf(__('Register this site as an application on Facebook\'s <a target="_blank" href="%s">app registration page</a>.', 'pwal'), 'https://developers.facebook.com/apps'); ?></li>
						<li><?php _e("Click the Create New App button. This will show a popup form where you will in the details of the App for your website.", 'pwal'); ?></li>
						<li><?php _e('The site URL should be', 'pwal'); ?> <b><?php print get_bloginfo('url'); ?></b></li>
						<li><?php _e('Once you have registered your site as an application, you will be provided with a App ID and a App secret.', 'pwal'); ?></li>
						<li><?php _e('Copy and paste them to the fields on the left', 'pwal'); ?></li>
					</ol>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" ><label for="pwal_facebook_api_secret"><?php _e('Facebook App API Secret','pwal')?></label></th>
				<td><input type="password" style="width:90%" id="pwal_facebook_api_secret" name="pwal[facebook_api_secret]" value="<?php echo $pwal->options["facebook_api_secret"] ?>" />
				</td>
			</tr>
			</table>
			
		</div>
	</div>

	<?php if ((!empty($pwal->options["facebook_api_key"])) && (!empty($pwal->options["facebook_api_secret"]))) { ?>

	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Facebook Authorization Polling', 'pwal'); ?></span></h3>
   		<div class="inside">	
			<p class="description"><?php _e('When a user arrives to your site the plugin checks if they are already logged into their Facebook account. If the user is not already logged in you can set the plugin to keep checking on a frequency interval define below. Or you can choose to only check once on the initial page load.', 'pwal') ?></p>
				
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_facebook_auth_polling"><?php _e('Enable Polling', 'pwal') ?></label></th>
				<td colspan="2">					
					<select id="pwal_facebook_auth_polling" name="pwal[facebook_auth_polling]">
						<option value="no" <?php selected( $pwal->options['facebook_auth_polling'], 'no' ) ?>><?php _e('No', 'pwal')?></option>
						<option value="yes" <?php selected( $pwal->options['facebook_auth_polling'], 'yes' ) ?>><?php _e('Yes', 'pwal')?></option>
					</select>
				</td>
			</tr>
			<tr valign="top" id="pwal_facebook_auth_polling_interval_section" <?php if ($pwal->options['facebook_auth_polling'] == 'no') { echo ' style="display:none;" '; } ?>>
				<th scope="row"><label for="pwal_facebook_auth_polling_interval"><?php _e('Polling Frequency (seconds)','pwal')?></label></th>
				<td><input id="pwal_facebook_auth_polling_interval" type="text" name="pwal[facebook_auth_polling_interval]" 
					value="<?php echo $pwal->options["facebook_auth_polling_interval"] ?>" />
				</td>
			</tr>
			
			</table>
			
		</div>
	</div>
	<?php } ?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Facebook Fan Pages', 'pwal'); ?></span></h3>
   		<div class="inside">		

			<p class="description"><?php _e('In addition to granting access when a user likes posts from this website, you can also grant access to users who may have liked your Facebook pages. Enter the Facebook page URL in the field below. Note at the moment these are treated globally. If the user viewing your website has previously liked any of the Facebook pages they are given full access to all Pay With a Like hidden content. This may change in the future.', 'pwal') ?></p>
			<?php
			
			if ((!empty($pwal->options["facebook_api_key"])) && (!empty($pwal->options["facebook_api_secret"]))) {
				?>
				<table id="pwal-facebook-fan-page-listing">
				<thead>
					<tr>
						<th class="column column-pwal-facebook-page-action">&nbsp;</th>
						<th class="column column-pwal-facebook-page-info"><?php _e('Facebook Page Info', 'pwal') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
			
				if (!defined('PWAL_FACEBOOK_FAN_EXAMPLE1'))
					define('PWAL_FACEBOOK_FAN_EXAMPLE1', 'http://www.facebook.com/wpmudev');
				if (!defined('PWAL_FACEBOOK_FAN_EXAMPLE2'))
					define('PWAL_FACEBOOK_FAN_EXAMPLE2', 'https://www.facebook.com/WordPress');
			
				$pwal_placeholder = __('Enter Facebook page URL', 'pwal');
				$pwal_placeholder_urls = '';
				if (PWAL_FACEBOOK_FAN_EXAMPLE1 != '') {
					$pwal_placeholder_urls .= PWAL_FACEBOOK_FAN_EXAMPLE1;
				}
			
				if (PWAL_FACEBOOK_FAN_EXAMPLE2 != '') {
					if (!empty($pwal_placeholder_urls))
						$pwal_placeholder_urls .= ' '. __('or') .' ';
					$pwal_placeholder_urls .= PWAL_FACEBOOK_FAN_EXAMPLE2;
				}
				//echo "pwal_placeholder_urls[". $pwal_placeholder_urls ."]<br />";
				if (!empty($pwal_placeholder_urls)) {
					$pwal_placeholder .= ' '. __('e.g.') .' '. $pwal_placeholder_urls;
				}
			
				if (($pwal->options['facebook_fan_pages']) 
					 && (is_array($pwal->options['facebook_fan_pages'])) 
					 && (count($pwal->options['facebook_fan_pages'])) ) {
						foreach($pwal->options['facebook_fan_pages'] as $fan_page_idx => $fan_page_info) {
							?>
							<tr id="row-pwal-facebook-page-action-<?php echo $fan_page_idx ?>">
								<td class="column column-pwal-facebook-page-action"><a href="#" 
									id="pwal-facebook-fan-page-remove-<?php echo $fan_page_idx ?>" 
									class="button-secondary pwal-facebook-fan-page-remove"><?php _e('X', 'pwal')?></a></td>
								<td class="column column-pwal-facebook-page-info"><?php 
									//echo "fan_page_info<pre>"; print_r($fan_page_info); echo "</pre>"; 
									if (isset($fan_page_info['pic_square'])) {
										?><div class="pwal-fan-page-image"><img src="<?php echo $fan_page_info['pic_square'] ?>" alt="" /></div><?php
									}
									?><div class="pwal-fan-page-info"><?php
										if (isset($fan_page_info['name'])) {
											?><div class="pwal-fan-page-name"><?php echo $fan_page_info['name'] ?></div><?php
										}
										if (isset($fan_page_info['page_url'])) {
											?><div class="pwal-fan-page-url"><span class="pwal-label"><?php _e('URL:', 'pwal') ?></label> <a target="_blank" href="<?php echo $fan_page_info['page_url'] ?>"><?php echo $fan_page_info['page_url'] ?></a></div>
											<?php
										}
										if (isset($fan_page_info['page_id'])) {
											?><div class="pwal-fan-page-id"><span class="pwal-label"><?php 
												_e('Page ID:', 'pwal') ?></span> <?php echo $fan_page_info['page_id'] ?><input type="hidden" name="pwal[facebook_fan_page_urls_current][]" value="<?php echo $fan_page_info['page_id'] ?>" /></div><?php
										}
									
									?></div><?php
								
								?></td>
							</tr>
							<?php
						}
					}
				?>
				<tr>
					<td class="column column-pwal-facebook-page-action">&nbsp;</td>
					<td colspan="4"><input placeholder="<?php echo $pwal_placeholder; ?>" name="pwal[facebook_fan_page_urls_new][]" type="text" /></td>
				</tr>
				</tbody>
				</table>
				<div><button id="pwal-facebook-fan-page-add" class="button-secondary pwal-facebook-fan-page-add"><?php _e('+', 'pwal') ?></button></div>
				<?php
			} else {
				?><p class="description"><?php _e('<strong>The Facebook API Key and API Secret are required to use Facebook Fan Pages integration.</strong>', 'pwal') ?></p><?php
				
			}
		?>
		</div>
	</div>
	<?php
	
}

function pwal_admin_panels_linkedin() {
	global $pwal;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('LinkedIn Like button Display options', 'pwal') ?></span></h3>
   		<div class="inside">		
		
			<table class="form-table">		
			<tr valign="top">
				<th scope="row" ><?php _e('Button Style', 'pwal') ?></th>
				<td colspan="2">
					<?php //echo "linkedin_layout_style[". $pwal->options['linkedin_layout_style'] ."]<br />"; ?>
					<ul class="pwal-linkedin-stlye-options">
						<li><input type="radio" name="pwal[linkedin_layout_style]" id="pwal-linkedin-layout-style-vertical" 
							value="top" <?php checked($pwal->options['linkedin_layout_style'], 'top') ?> /> <label
							 for="pwal-linkedin-layout-style-vertical"><?php _e('Vertical', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/linkedin_like_button_vertical.png'; ?>" /></div></li>
						<li><input type="radio" name="pwal[linkedin_layout_style]" id="pwal-linkedin-layout-style-right" 
							value="right" <?php checked($pwal->options['linkedin_layout_style'], 'right') ?>/> <label
							 for="pwal-linkedin-layout-style-right"><?php _e('Horizontal', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/linkedin_like_button_right.png'; ?>" /></div></li>
						<li><input type="radio" name="pwal[linkedin_layout_style]" id="pwal-linkedin-layout-style-none" 
							value="none" <?php checked($pwal->options['linkedin_layout_style'], 'none') ?>/> <label
							 for="pwal-linkedin-layout-style-no-count"><?php 
								_e('No Count', 'pwal'); ?></label><div class="pwal-linkedin-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/linkedin_like_button_none.png'; ?>" /></div></li>
					</ul>
				</td>
			</tr>
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Button Language', 'pwal') ?></span></h3>
   		<div class="inside">		
			<p class="description"><?php _e('In most cases the language your website is display in is acceptable as the language for the social buttons. But on some rare cases the social network API does not support your language. Here you can specificy the alternate language to use it not your default website language.', 'pwal') ?></p>
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_linkedin_button_lang"><?php _e('Button Language', 'pwal')?></label></th>
				<td colspan="2">
					<input type="text" class="regular-text" id="pwal_linkedin_button_lang" name="pwal[linkedin_button_lang]" value="<?php echo $pwal->options["linkedin_button_lang"] ?>" />
					<?php $locale = get_locale(); ?>
					<p class="description"><?php echo sprintf(__("If left blank the default language as defined in your wp-config.php (<strong>%s</strong>) will be used. Please refer to the LinkedIn accepted %s codes.", 'pwal'), $locale, '<a href="https://developer.linkedin.com/plugins/share-plugin-generator" target="_blank">'.__('Languages', 'pwal') .'</a>'); ?></p>
				</td>
			</tr>
		</table>
		</div>
	</div>
	
	<?php
}

function pwal_admin_panels_twitter() {
	global $pwal;
	
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Twitter Like button Display options', 'pwal') ?></span></h3>
   		<div class="inside">		
		
			<table class="form-table">		
			<tr valign="top">
				<th scope="row" ><label for="pwal-twitter-layout-style-vertical"><?php _e('Button Style', 'pwal') ?></label></th>
				<td colspan="2">
					<ul class="pwal-twitter-stlye-options">
						<li><input type="radio" name="pwal[twitter_layout_style]" id="pwal-twitter-layout-style-vertical" 
							value="vertical" <?php checked($pwal->options['twitter_layout_style'], 'vertical') ?> /> <label
							 for="pwal-twitter-layout-style-vertical"><?php _e('Vertical', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/twitter_like_button_vertical.png'; ?>" /></div></li>
						<li><input type="radio" name="pwal[twitter_layout_style]" id="pwal-twitter-layout-style-horizontal" 
							value="horizontal" <?php checked($pwal->options['twitter_layout_style'], 'horizontal') ?>/> <label
							 for="pwal-twitter-layout-style-horizontal"><?php _e('Horizontal', 'pwal'); ?></label><div
							  class="pwal-fb-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/twitter_like_button_right.png'; ?>" /></div></li>
						<li><input type="radio" name="pwal[twitter_layout_style]" id="pwal-twitter-layout-style-none" 
							value="none" <?php checked($pwal->options['twitter_layout_style'], 'none') ?>/> <label
							 for="pwal-linkedin-layout-style-none"><?php 
								_e('No Count', 'pwal'); ?></label><div class="pwal-linkedin-style-image"><img 
							src="<?php echo $pwal->plugin_url .'/images/twitter_like_button_none.png'; ?>" /></div></li>
					</ul>
				</td>
			</tr>
			<?php /* ?>
			<tr valign="top">
				<th scope="row" ><label for="pwal_twitter_button_size"><?php _e('Button Size', 'pwal') ?></label></th>
				<td colspan="2">
					<select name="pwal[twitter_button_size]" id="pwal_twitter_button_size">
					<option value="medium" <?php selected( $pwal->options['twitter_button_size'], 'medium' ) ?>><?php _e('Medium', 'pwal')?></option>
					<option value="large" <?php selected( $pwal->options['twitter_button_size'], 'large' ) ?>><?php _e('Large', 'pwal')?></option>
					</select>
				</td>
			</tr>
			<?php */ ?>
			<tr valign="top">
				<th scope="row" ><label for="pwal_twitter_message"><?php _e('Tweet Message', 'pwal') ?></label></th>
				<td colspan="2">
					<p><strong><?php _e("Do not include the post URL here. The post URL is automatically added by Twitter.")?></strong></p> 
					
					<textarea id="pwal_twitter_message" name="pwal[twitter_message]"><?php echo $pwal->options['twitter_message'] ?></textarea><br />
					<p><?php _e("You can use replaceable parameters in the tweet message. These will be replaced with the real content when the button is rendered.", 'pwal')?></p> 
					<p><?php _e("You can also setup a filter 'pwal_twitter_message' which will allow you to filter the message dynamically.", 'pwal')?></p>
					<ul>
						<li><strong>[POST_TITLE]</strong> - <?php _e('To represent the post title', 'pwal'); ?></li>
						<li><strong>[SITE_TITLE]</strong> - <?php _e('To represent the site title', 'pwal'); ?></li>
						<li><strong>[SITE_TAGLINE]</strong> - <?php _e('To represent the site tagline', 'pwal'); ?></li>
					</ul>
				</td>
			</tr>
			
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Button Language', 'pwal') ?></span></h3>
   		<div class="inside">		
			<p class="description"><?php _e('In most cases the language your website is display in is acceptable as the language for the social buttons. But on some rare cases the social network API does not support your language. Here you can specificy the alternate language to use it not your default website language.', 'pwal') ?></p>
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_twitter_button_lang"><?php _e('Button Language', 'pwal') ?></label></th>
				<td colspan="2">
					<input type="text" class="regular-text" id="pwal_twitter_button_lang" name="pwal[twitter_button_lang]" value="<?php echo $pwal->options["twitter_button_lang"] ?>" />
					<?php $locale = get_locale(); ?>
					<p class="description"><?php echo sprintf(__("If left blank the default language as defined in your wp-config.php (<strong>%s</strong>) will be used. Please refer to the Twitter accepted %s codes.", 'pwal'), $locale, '<a href="https://dev.twitter.com/docs/tweet-button/faq#languages" target="_blank">'.__('Languages', 'pwal') .'</a>'); ?></p>
				</td>
			</tr>
			</table>
		</div>
	</div>
	<?php
}

function pwal_admin_panels_google() {
	global $pwal;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Google +1 Like button Display options', 'pwal') ?></span></h3>
   		<div class="inside">		
		
			<table class="form-table">		
			<tr valign="top">
				<th scope="row" ><label for="pwal_google_button_layout"><?php _e('Button Style', 'pwal') ?></label></th>
				<td colspan="2">
					<ul class="pwal-google-stlye-options">
						<li><input type="radio" name="pwal[google_layout_style]" id="pwal-google-layout-style-tall-bubble" 
							value="tall-bubble" <?php checked($pwal->options['google_layout_style'], 'tall-bubble') ?> /> <label
							 for="pwal-twitter-layout-style-tall-bubble"><?php _e('Vertical', 'pwal'); ?></label><div class="pwal-fb-style-image"><img 
								 src="<?php echo $pwal->plugin_url .'/images/google_like_button_tall_bubble.png'; ?>" /></div></li>
							
						<li><input type="radio" name="pwal[google_layout_style]" id="pwal-google-layout-style-standard-bubble" 
							value="standard-bubble" <?php checked($pwal->options['google_layout_style'], 'standard-bubble') ?>/> <label
							 for="pwal-twitter-layout-style-standard-bubble"><?php _e('Horizontal', 'pwal'); ?></label><div
							  class="pwal-fb-style-image"><img 
								src="<?php echo $pwal->plugin_url .'/images/google_like_button_standard_bubble.png'; ?>" /></div></li>
							
						<li><input type="radio" name="pwal[google_layout_style]" id="pwal-google-layout-style-standard-none" 
							value="standard-none" <?php checked($pwal->options['google_layout_style'], 'standard-none') ?>/> <label
							 for="pwal-linkedin-layout-style-standard-none"><?php 
								_e('No Count', 'pwal'); ?></label><div class="pwal-linkedin-style-image"><img 
								src="<?php echo $pwal->plugin_url .'/images/google_like_button_standard_none.png'; ?>" /></div></li>
					</ul>
				</td>
			</tr>
		</table>
		</div>
	</div>
	
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Button Language', 'pwal') ?></span></h3>
   		<div class="inside">		
			<p class="description"><?php _e('In most cases the language your website is display in is acceptable as the language for the social buttons. But on some rare cases the social network API does not support your language. Here you can specificy the alternate language to use it not your default website language.', 'pwal') ?></p>
			<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="pwal_google_button_lang"><?php _e('Button Language', 'pwal')?></label></th>
				<td colspan="2">
					<input type="text" class="regular-text" id="pwal_google_button_lang" name="pwal[google_button_lang]" value="<?php echo $pwal->options["google_button_lang"] ?>" />
					<?php $locale = get_locale(); ?>
					<p class="description"><?php echo sprintf(__("If left blank the default language as defined in your wp-config.php (<strong>%s</strong>) will be used. Please refer to the Google +1 accepted %s codes.", 'pwal'), $locale, '<a href="https://developers.google.com/+/web/api/supported-languages" target="_blank">'.__('Languages', 'pwal') .'</a>'); ?></p>
				</td>
			</tr>
		</table>
		</div>
	</div>
	
	<?php
}

function pwal_admin_panels_statistics() {
	global $pwal;
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Like Statistics', 'pwal') ?></span></h3>
	
		<div class="inside" id="pwal_stats">
		<?php
			$stats = get_option( "pwal_statistics" );
			//echo "stats<pre>"; print_r($stats); echo "</pre>";
				
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
	<?php
}

function pwal_admin_panels_statistic_actions() {
	if (isset($_GET['post'])) return;
	
	$stats = get_option( "pwal_statistics" );
	if (!empty($stats)) {
		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e('Like Statistics Actions', 'pwal'); ?></span></h3>
			<div class="inside">
				<table class="form-table">
				<tr valign="top">
					<td scope="row" >
						<?php
						$action_url = add_query_arg('action', 'pwal_delete_stats');
						if (isset($_GET['post'])) {
							$action_url = add_query_arg('post', intval($_GET['post']), $action_url);
						}
						
						?>
					<a id="pwal_clear_button" href="<?php echo wp_nonce_url($action_url, 'pwal_delete_stats', 'pwal_nonce') ?>" class="button-secondary" title="<?php _e('Clicking this button deletes statistics saved on the server') ?>" ><?php _e('Clear Statistics') ?></a>
					</td>

					<td colspan="2">
						<?php
							$action_url = admin_url('admin-ajax.php?action=pwal_export_stats');
							if (isset($_GET['post'])) {
								$action_url = add_query_arg('post', intval($_GET['post']), $action_url);
							}
							
						?>
						<a id="pwal_export_button" href="<?php echo $action_url ?>" class="button-secondary" title="<?php _e('If you click this button a CSV file including statistics will be saved on your PC') ?>" ><?php _e('Export Statistics') ?></a>
					</td>
				</tr>
				</table>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						jQuery('#pwal_clear_button').click(function(event) {
							if ( !confirm('<?php _e("Are you sure to clear statistics?","pwal") ?>') ) {
								event.preventDefault();
							}
							//event.preventDefault();
						});
					});
				</script>
			</div>
		</div>
		<?php
	}
}

function pwal_admin_panels_statistic_chart() {
	$user = wp_get_current_user();
	$user_id = $user->ID;

	?>
	<div class="postbox">
		<h3 class="hndle"><span><?php _e('Like Statistics Chart', 'pwal'); ?></span></h3>
		<div class="inside">
			<div id="affdashgraph-wrapper">
				<form method="post" action="">
					<?php
					if (isset($_GET['post'])) {
						?>
						<input type="hidden" id="affiliate-post-id" name="affiliate-post-id" value="<?php echo intval($_GET['post']) ?>" />
						<?php
					}
					?>
				</form>
				<div id="affdashgraph"></div>
			</div>
		</div>
	</div>
	<?php
}

function pwal_admin_panels_statistic_summary() {
	global $pwal;
	
	if (isset($_GET['post'])) 
		$post_id = intval($_GET['post']);
	else
		$post_id = 0;
	
	// We make a copy of the buttons because we run an unset on the ones used.
	$social_buttons_tmp = $pwal->options['social_buttons'];
	
	if (!empty($pwal->options['social_button_sort'])) {
		foreach($pwal->options['social_button_sort'] as $button_key) {

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
		if ( !$pwal->options["use_".$button_key]) 
			unset($pwal_buttons_show[$button_key]);
	}
	ksort($pwal_buttons_show);
	//echo "pwal_buttons_show<pre>"; print_r($pwal_buttons_show); echo "</pre>";
	?>
	<div class="postbox">
		<h3 class="hndle"><span><?php _e('Like Statistics Summary', 'pwal'); ?></span></h3>
		<div class="inside">
			<?php
				$stats = get_option( "pwal_statistics" );
				if (!empty($stats)) {
					$stats_out = array();
				
					foreach($stats as $stat) {
						if (($post_id != 0) && ($stat['p'] != $post_id)) continue;
						
						$service = strtolower(trim($stat['s']));
						if (isset($pwal_buttons_show[$service])) {
					
							if (!isset($stats_out[$service]))
								$stats_out[$service] = intval(0);
							$stats_out[$service] += 1;
						}
					}
					?>
					<table id="pwal-statistics-summary">
					<thead>
						<tr>
							<th class="column column-service"><?php _e('Social', 'pwal') ?></th>
							<th class="column column-count"><?php _e('Count', 'pwal') ?></th>
						</tr>
					</thead>
					<tbody> 
					<?php
					$total_count = 0;
					foreach($pwal_buttons_show as $service_key => $service_label) {
						if (isset($stats_out[$service_key])) {
							$service_count = $stats_out[$service_key];
						} else {
							$service_count = 0;
						}	
						$total_count += $service_count;
						?>
						<tr>
							<td class="column column-service"><?php echo $service_label; ?></a></td>
							<td class="column column-count"><?php echo $service_count; ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>
					<tfoot>
						<tr>
							<th class="column column-title"><?php _e('Total', 'pwal') ?></th>
							<th class="column column-count"><?php echo $total_count ?></th>
						</tr>
					</tfoot>
					</table>
					<?php
				} else {
					?><p><?php _e('No Like Statistics to report.', 'pwal'); ?></p><?php
				}
			?>
		</div>
	</div>
	<?php
}

function pwal_admin_panels_statistic_top_pages() {
	global $pwal;
	
	if (isset($_GET['post'])) return;
	
	// We make a copy of the buttons because we run an unset on the ones used.
	$social_buttons_tmp = $pwal->options['social_buttons'];
	
	if (!empty($pwal->options['social_button_sort'])) {
		foreach($pwal->options['social_button_sort'] as $button_key) {

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
		if ( !$pwal->options["use_".$button_key]) 
			unset($pwal_buttons_show[$button_key]);
	}
	ksort($pwal_buttons_show);


	?>
	<div class="postbox">
		<h3 class="hndle"><span><?php _e('Top 10 Posts', 'pwal'); ?></span></h3>
		<div class="inside">
			<?php
				$stats = get_option( "pwal_statistics" );
				//echo "stats<pre>"; print_r($stats); echo "</pre>";
				/*
				'b'		=> $blog_id,
				'p'		=> $_POST["post_id"],
				'c'		=> $_POST["content_id"],
				's'		=> $_POST["service"],
				'i'		=> $_SERVER["REMOTE_ADDR"],
				't'		=> current_time('timestamp')
				*/
				if (!empty($stats)) {
					$post_ids = array();
					foreach($stats as $stat) {
						$service = strtolower(trim($stat['s']));
						if (isset($pwal_buttons_show[$service])) {
							if (!isset($post_ids[$stat['p']]))
								$post_ids[$stat['p']] = 1;
							else
								$post_ids[$stat['p']] = $post_ids[$stat['p']] + 1;
						}
					}
					//echo "post_ids<pre>"; print_r($post_ids); echo "</pre>";
					arsort($post_ids);
				
					$post_ids_tmp = array();
					foreach($post_ids as $post_id => $count) {
						$post = get_post($post_id);
						if (!$post) continue;
						$post_ids_tmp[$post_id] = $count;
						if (count($post_ids_tmp) >= 10) break;
					}
					$post_ids = $post_ids_tmp;	
				
					//echo "post_ids<pre>"; print_r($post_ids); echo "</pre>";
					if (count($post_ids) > 10) {
						$post_ids = array_slice($post_ids, 0, 10);
					}
					?>	
					<table id="pwal-statistics-toppages">
					<thead>
						<tr>
							<th class="column column-title"><?php _e('Title', 'pwal') ?></th>
							<th class="column column-count"><?php _e('Count', 'pwal') ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
						$total_count = 0;
						foreach($post_ids as $post_id => $post_count) {
							$total_count += $post_count;
							?>
							<tr>
								<td class="column column-title"><a href="<?php echo get_permalink($post_id) ?>"><?php echo get_the_title($post_id); ?></a></td>
								<td class="column column-count"><?php echo $post_count; ?></td>
							</tr>
							<?php
						}
					?>
					</tbody>
					<tfoot>
						<tr>
							<th class="column column-title"><?php _e('Total', 'pwal') ?></th>
							<th class="column column-count"><?php echo $total_count ?></th>
						</tr>
					</tfoot>
					</table>
					<?php
				} else {
					?><p><?php _e('No Like Statistics to report.', 'pwal'); ?></p><?php 
				}
			?>
		</div>
	</div>
	<?php
}

function pwal_admin_panels_statistic_top_ipaddress() {
	global $pwal;
	
	if (isset($_GET['post'])) 
		$post_id = intval($_GET['post']);
	else
		$post_id = 0;
	
	// We make a copy of the buttons because we run an unset on the ones used.
	$social_buttons_tmp = $pwal->options['social_buttons'];
	
	if (!empty($pwal->options['social_button_sort'])) {
		foreach($pwal->options['social_button_sort'] as $button_key) {

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
		if ( !$pwal->options["use_".$button_key]) 
			unset($pwal_buttons_show[$button_key]);
	}
	ksort($pwal_buttons_show);


	?>
	<div class="postbox">
		<h3 class="hndle"><span><?php _e('Top 10 IP Addresses', 'pwal'); ?></span></h3>
		<div class="inside">
			<?php
				$stats = get_option( "pwal_statistics" );
				//echo "stats<pre>"; print_r($stats); echo "</pre>";
				if (!empty($stats)) {
					$ip_addresses = array();
					foreach($stats as $stat) {
						
						if (($post_id != 0) && ($stat['p'] != $post_id)) continue;

						$service = strtolower(trim($stat['s']));
						if (isset($pwal_buttons_show[$service])) {
							if (!isset($ip_addresses[$stat['i']]))
								$ip_addresses[$stat['i']] = 1;
							else
								$ip_addresses[$stat['i']] = $ip_addresses[$stat['i']] + 1;
						}
					}
					arsort($ip_addresses);
					$ip_addresses = array_slice($ip_addresses, 0, 10);
					
					//echo "ip_addresses<pre>"; print_r($ip_addresses); echo "</pre>";
					?>	
					<table id="pwal-statistics-ip-addesses">
					<thead>
						<tr>
							<th class="column column-title"><?php _e('IP Address', 'pwal') ?></th>
							<th class="column column-count"><?php _e('Count', 'pwal') ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
						$total_count = 0;
						foreach($ip_addresses as $ip_address => $count) {
							$total_count += $count;
							?>
							<tr>
								<td class="column column-title"><?php echo $ip_address; ?></td>
								<td class="column column-count"><?php echo $count; ?></td>
							</tr>
							<?php
						}
					?>
					</tbody>
					<tfoot>
						<tr>
							<th class="column column-title"><?php _e('Total', 'pwal') ?></th>
							<th class="column column-count"><?php echo $total_count ?></th>
						</tr>
					</tfoot>
					</table>
					<?php
				} else {
					?><p><?php _e('No Like Statistics to report.', 'pwal'); ?></p><?php 
				}
			?>
		</div>
	</div>
	<?php
	
}

function pwal_admin_panels_customization() {
	global $pwal;
	?>	
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Using Pay With a Like as a shortcode.', 'pwal') ?></span></h3>
   		<div class="inside">		
		<p><?php _e('You can use Pay With a Like as a normal shortcode in WordPress. You just need to call the WordPress function do_shortcode() with the correct PWAL shortcode.', 'pwal')?></p>

		<p><?php _e('Below are the accpted shortcode parameters', 'pwal'); ?></p>
		<ul>
			<li><strong>id</strong> - <?php _e('This is the unque global number for the Pay With a Like element. You can make this a very large number like 987654321 to ensure it does not match an existing post ID.')?></li>
			<li><strong>description</strong> - <?php _e('This is the description shown above the Pay With a Like Buttons. If not provided the main Pay With a Like settings are used.', 'pwal'); ?></li>
			<li><strong>content_reload</strong> - <?php _e('Controls how the hidden content is revealed. Possible values are <strong>refresh</strong> or <strong>ajax</strong>. If not provided the main Pay With a Like settings are used.', 'pwal'); ?></li>
			<li><strong>container_width</strong> - <?php _e('Controls the width of the buttons container. Should be a normal value like you would user for CSS. For example 500px, 30%, etc. If not provided the main Pay With a Like settings are used.', 'pwal'); ?></li>
			<li><strong>wpautop</strong> - <?php _e('Controls if the shotcode process is to add paragraph tags around the hidden content. Default is <strong>yes</strong>.', 'pwal'); ?></li>
		</ul>

		<p><?php _e('In the example below note we are using a shortcode format <strong>[pwal]Hidden content goes here[/pwal]</strong>.', 'pwal')?></p>
		<code>
		&lt;?php<br /> 
		&nbsp;$content = do_shortcode(['pwal id="9999" description="Click to like to see my vacation video"]&lt;iframe width="560" height="315" src="http://www.youtube.com/embed/-uiN9z5tqhg" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;[/pwal]');<br /> 
		?&gt;
		</code>
		</div>
	</div>

	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Handling Pay With a Like via Template Function (legacy)', 'pwal') ?></span></h3>
   		<div class="inside">		
		<p><strong><?php _e('While the wpmudev_pwal_html() function is still supported it is consider depricated. You should use the shortcode method described above', 'pwal'); ?></strong></p>
		<p><?php _e('For protecting html codes that you cannot add to post content, there is a template function <b>wpmudev_pwal_html</b>. This function replaces all such codes with like buttons and reveal them when payment is done. Add the following codes to the page template where you want the html codes to be displayed and modify as required. Also you need to use the bottom action function.', 'pwal'); ?></p>

		<code>
		&lt;?php<br /> 
		if ( function_exists( 'wpmudev_pwal_html' ) ) {<br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$html = '&lt;iframe width="560" height="315" src="http://www.youtube.com/embed/-uiN9z5tqhg" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;'; // html code to be protected (required)<br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$id = 9999; // A global unuque number. Make sure this does not match an existing post ID<br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$description = 'video'; // Optional description of the protected content<br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo wpmudev_pwal_html( $html, $id, $description );<br />
		}<br />
		?&gt;
		</code>
		</div>
	</div>
<?php /* ?>	
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Customizations & Shortcodes', 'pwal') ?></span></h3>
   		<div class="inside">		
	
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
		</div>
	</div>
</php */ ?>
<?php /* ?>	
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Customizations & Shortcodes', 'pwal') ?></span></h3>
   		<div class="inside">		
	
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
		</div>
	</div>
<?php */ ?>

	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Customizing the CSS', 'pwal') ?></span></h3>
   		<div class="inside">		
		<?php 
		$uploads = wp_upload_dir();
		$default_css = "/wp-content/plugins/pay-with-a-like/css/front.css";
		$custom_css = "/wp-content/uploads/pay-with-a-like.css";
		printf(__('If you want to apply your own styles copy contents of front.css to your theme css file and add this code inside functions.php of your theme:<code>add_theme_support( "pay_with_a_like_style" )</code> OR copy and rename the default css file <b>%s</b> as <b>%s</b> and edit this latter file. Then, your edited styles will not be affected from plugin updates.', 'pwal'), $default_css, $custom_css); 
		?>
		</div>
	</div>
	
<?php /* ?>	
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('WordPress Filters/Actions', 'pwal') ?></span></h3>
   		<div class="inside">		
			<p><?php _e('Below is a list of the filters and actions available within the Pay With a Like plugin. These filters and actions allow you to customize the functionality of the plugin in certain instances where the settings do not function per your needs.', 'pwal'); ?></p>
			
			<h4><?php _e('Filters', 'pwal'); ?></h4>
			<p><?php _e('As with all filters witin WordPress they are designed to allow filtering of data passed into the filter function from the calling function. The data to be filtered is normally the first argument passed into the filtering function. The filtering function can then perform some localized logic and pass back an alternate value. A filter <strong>must always pass back a value</strong>.', 'pwal'); ?></p>
			<ul>
				<li><strong>pwal_fb_locale</strong> - This filter is called when adding the Facebook JavaScript to the page. This filter allows you to override the locale value determined from the plugin. The filter is passed one argument.</li>
				<li><strong>pwal_display_buttons</strong> - This filter is called just before the main logic to determine if a post should have the PWAL buttons displayed. This filters allows you to override the settings defined as part of the plugin. The filter will pass two arguments: $display_buttons - true/false is the current determined value to display the buttons, $post - is the post object of the item to be displayed. Maybe null. </li>
				<li><strong>pwal_active_button_count</strong> - This filter is called just before the button box if presented for a single post. This filter will pass one argument: $n - the current number of buttons to be displayed.</li>
				<li><strong>pwal_force_random</strong> - </li>
				<li><strong>pwal_url_to_like</strong> - </li>
				<li><strong>pwal_render_button_html</strong> - </li>
				<li><strong>pwal_ajax_statistics</strong> - </li>
				<li><strong>pwal_before_save_options</strong> - </li>
				
			</ul>
		</div>
	</div>
<?php */ ?>
	<?php
}