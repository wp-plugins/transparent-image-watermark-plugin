<?php

class Transparent_Watermark_Admin extends Transparent_Watermark {
	/**
	 * Error messages to diplay
	 *
	 * @var array
	 */
	private $_messages = array();
	
	/**
	 * List of available image sizes
	 *
	 * @var array
	 */
	private $_image_sizes         = array('fullsize');
	
	
	/**
	 * Class constructor
	 *
	 */
	public function __construct() {
		$this->_plugin_dir   = DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), null, plugin_basename(__FILE__));
		$this->_settings_url = 'options-general.php?page=' . plugin_basename(__FILE__);;
		
		$allowed_options = array(
			
		);
		
		// set watermark options
		if(array_key_exists('option_name', $_GET) && array_key_exists('option_value', $_GET)
			&& in_array($_GET['option_name'], $allowed_options)) {
			update_option($_GET['option_name'], $_GET['option_value']);
			
			header("Location: " . $this->_settings_url);
			die();	

		} else {
			// register installer function
			register_activation_hook(TW_LOADER, array(&$this, 'activate_watermark'));
			

			$show_on_upload_screen = $this->get_option('show_on_upload_screen');			 
			if($show_on_upload_screen === "true"){	
			
				add_filter('attachment_fields_to_edit', array(&$this, 'attachment_field_add_watermark'), 10, 2);
				
			}
			
			// add plugin "Settings" action on plugin list
			add_action('plugin_action_links_' . plugin_basename(TW_LOADER), array(&$this, 'add_plugin_actions'));
			
			// add links for plugin help, donations,...
			add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
			
			// push options page link, when generating admin menu
			add_action('admin_menu', array(&$this, 'admin_menu'));
	
			//add help menu
			add_filter('contextual_help', array(&$this,'admin_help'), 10, 3);
			
			
			// check if post_id is "-1", meaning we're uploading watermark image
			if(!(array_key_exists('post_id', $_REQUEST) && $_REQUEST['post_id'] == -1)) {
			
				// add filter for watermarking images
				add_filter('wp_generate_attachment_metadata', array(&$this, 'apply_watermark'));
				
			}
		}
	}
	
	/**
	 * Add "Settings" action on installed plugin list
	 */
	public function add_plugin_actions($links) {
		array_unshift($links, '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __('Settings') . '</a>');
		
		return $links;
	}
	
	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename(TW_LOADER)) {
			$upgrade_url = 'http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/';
			$links[] = '<a href="'.$upgrade_url.'" target="_blank" title="Click Here to Upgrade this Plugin!">Upgrade Plugin</a>';
		
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
			$links[] = '<a href="'.$rate_url.'" target="_blank" title="Click Here to Rate and Review this Plugin on WordPress.org">Rate This Plugin</a>';
			
		}
		
		return $links;
	}
	

	
	/**
	 * Add menu entry for Transparent Watermark settings and attach style and script include methods
	 */
	public function admin_menu() {		
		// add option in admin menu, for setting details on watermarking
		global $transparent_watermark_admin_page;
		$transparent_watermark_admin_page = add_options_page('Transparent Watermark Plugin Options', 'Transparent Watermark', 'manage_options', __FILE__, array(&$this, 'options_page'));

		//add_action('admin_print_styles-' . $transparent_watermark_admin_page,     array(&$this, 'install_styles'));
	}
	
	
	public function admin_help($contextual_help, $screen_id, $screen){
	
		global $transparent_watermark_admin_page;
		
		if ($screen_id == $transparent_watermark_admin_page) {
			
			
			$support_the_dev = $this->display_support_us();
			$screen->add_help_tab(array(
				'id' => 'developer-support',
				'title' => "Support the Developer",
				'content' => "<h2>Support the Developer</h2><p>".$support_the_dev."</p>"
			));
			
			$screen->add_help_tab(array(
				'id' => 'plugin-support',
				'title' => "Plugin Support",
				'content' => "<h2>Support</h2><p>For Plugin Support please visit <a href='http://mywebsiteadvisor.com/support/' target='_blank'>MyWebsiteAdvisor.com</a></p>"
			));
			
			$faqs = "<p><b>Question: Why am I getting low quality watermarks?</b><br>Answer: The plugin needs to change the size of your watermark image, according to the size of your original image.  You should use a watermark image that is roughly the same width as your largest images intended to be watermarked.  That way the plugin will scale them down, resulting in no loss of quality.  When the plugin is forced to do the opposite and increase the size of a small watermark image, low quality watermarks may occur.</p>";
			
			$faqs .= "<p><b>Question: How can I remove the watermarks?</b><br>Answer: This plugin permenantly alters the images to contain the watermarks, so the watermarks can not be removed.  If you want to simply test this plugin, or think you may want to remove the watermarks, you need to make a backup of your images before you run the plugin to add watermarks.</p>";

			
			
			$screen->add_help_tab(array(
				'id' => 'plugin-faq',
				'title' => "Plugin FAQ's",
				'content' => "<h2>Frequently Asked Questions</h2>".$faqs
			));
			
			
			$screen->add_help_tab(array(
				'id' => 'plugin-upgrades',
				'title' => "Plugin Upgrades",
				'content' => "<h2>Plugin Upgrades</h2><p>We also offer a premium version of this pluign with extended features!<br>You can learn more about it here: <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>MyWebsiteAdvisor.com</a></p><p>Learn more about our different watermark plugins for WordPress here: <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/watermark-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p><p>Learn about all of our free plugins for WordPress here: <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p>"
			));
			
			
	
			$screen->set_help_sidebar("<p>Please Visit us online for more Free WordPress Plugins!</p><p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p><br>");
			//$contextual_help = 'HELP!';
		}
			
		//return $contextual_help;

	}	
	
	
	
	public function display_support_us(){
				
		$string = '<p><b>Thank You for using the Transparent Image Watermark Plugin for WordPress!</b></p>';
		$string .= "<p>Please take a moment to <b>Support the Developer</b> by doing some of the following items:</p>";
		
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$string .= "<li><a href='$rate_url' target='_blank' title='Click Here to Rate and Review this Plugin on WordPress.org'>Click Here</a> to Rate and Review this Plugin on WordPress.org!</li>";
		
		$string .= "<li><a href='http://facebook.com/MyWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Facebook'>Click Here</a> to Follow MyWebsiteAdvisor on Facebook!</li>";
		$string .= "<li><a href='http://twitter.com/MWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Twitter'>Click Here</a> to Follow MyWebsiteAdvisor on Twitter!</li>";
		$string .= "<li><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/' target='_blank' title='Click Here to Purchase one of our Premium WordPress Plugins'>Click Here</a> to Purchase Premium WordPress Plugins!</li>";
	
		return $string;
	}

	
	
	
	
	/**
	 * Include styles used by Transparent Watermark Plugin
	 */
	public function install_styles() {
		//wp_enqueue_style('transparent-watermark', WP_PLUGIN_URL . $this->_plugin_dir . 'style.css');
	}
	




	function html_print_box_header($id, $title, $right = false) {
		
		?>
		<div id="<?php echo $id; ?>" class="postbox">
			<h3 class="hndle"><span><?php echo $title ?></span></h3>
			<div class="inside">
		<?php
		
		
	}
	
	function html_print_box_footer( $right = false) {
		?>
			</div>
		</div>
		<?php
		
	}




	
	/**
	 * Display options page
	 */
	public function options_page() {
		// if user clicked "Save Changes" save them
		if(isset($_POST['Submit'])) {
			foreach($this->_options as $option => $value) {
				if(array_key_exists($option, $_POST)) {
					update_option($option, $_POST[$option]);
				} else {
					update_option($option, $value);
				}
			}

			$this->_messages['updated'][] = 'Options updated!';
		}


		if( !extension_loaded( 'gd' ) ) {
			$this->_messages['error'][] = 'Transparent Watermark Plugin will not work without PHP extension GD.';
		}
		
	
		foreach($this->_messages as $namespace => $messages) {
			foreach($messages as $message) {
?>
<div class="<?php echo $namespace; ?>">
	<p>
		<strong><?php echo $message; ?></strong>
	</p>
</div>
<?php
			}
		}
?>
<script type="text/javascript">var wpurl = "<?php bloginfo('wpurl'); ?>";</script>




<style>

.form-table{clear:left;}

.fb_edge_widget_with_comment {
	position: absolute;
	top: 0px;
	right: 200px;
}

</style>

<div  style="height:20px; vertical-align:top; width:50%; float:right; text-align:right; margin-top:5px; padding-right:16px; position:relative;">

	<div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=253053091425708";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));</script>
	
	<div class="fb-like" data-href="http://www.facebook.com/MyWebsiteAdvisor" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false"></div>
	
	
	<a href="https://twitter.com/MWebsiteAdvisor" class="twitter-follow-button" data-show-count="false"  >Follow @MWebsiteAdvisor</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>


</div>




<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>Transparent Watermark Plugin Settings</h2>
		<form method="post" action="">
		
		
			<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">
			
<?php $this->html_print_box_header('pl_diag',__('Plugin Diagnostic Check','diagnostic'),true); ?>

				<?php
				
				echo "<p>Plugin Version: $this->version</p>";
				
				echo "<p>Server OS: ".PHP_OS."</p>";
						
				echo "<p>Required PHP Version: 5.0+<br>";
				echo "Current PHP Version: " . phpversion() . "</p>";
				
				
				if( ini_get('safe_mode') ){
					echo "<p><font color='red'>PHP Safe Mode is enabled!<br><b>Disable Safe Mode in php.ini!</b></font></p>";
				}else{
					echo "<p>PHP Safe Mode: is disabled!</p>";
				}
				
				if( ini_get('allow_url_fopen')){
					echo "<p>PHP allow_url_fopen: is enabled!</p>";
				}else{
					echo "<p><font color='red'>PHP allow_url_fopen: is disabled!<br><b>Enable allow_url_fopen in php.ini!</b></font></p>";
				}
				

			
				$gdinfo = gd_info();
			
				if($gdinfo){
					echo '<p>GD Support Enabled!<br>';
				}else{
					echo "<p>Please Configure GD!</p>";
				}
				
				
				echo "<p>Memory Use: " . number_format(memory_get_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
				
				echo "<p>Peak Memory Use: " . number_format(memory_get_peak_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
		
				if(function_exists('sys_getloadavg')){
					$lav = sys_getloadavg();
					echo "<p>Server Load Average: ".$lav[0].", ".$lav[1].", ".$lav[2]."</p>";
				}	
				
				?>

<?php $this->html_print_box_footer(true); ?>



<?php $this->html_print_box_header('pl_resources',__('Plugin Resources','resources'),true); ?>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Plugin Homepage</a></p>
	<p><a href='http://mywebsiteadvisor.com/support/'  target='_blank'>Plugin Support</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Contact Us</a></p>
	<p><a href='http://wordpress.org/support/view/plugin-reviews/transparent-image-watermark-plugin?rate=5#postform'  target='_blank'>Rate and Review This Plugin</a></p>
<?php $this->html_print_box_footer(true); ?>


<?php $this->html_print_box_header('pl_upgrades',__('Plugin Upgrades','upgrades'),true); ?>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Upgrade to Transparent Watermark Ultra!</a></p>
	<p><b>Features:</b><br />
	 -Manually Add Watermarks<br />
	 -Change Watermark Position<br />
	 -Add High Quality Watermarks<br />
	 -And Much More!<br />
	 </p>
	<p>Click Here for <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/watermark-plugins-for-wordpress/' target='_blank'>More Watermark Plugins</a></p>
	<p>-<a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/bulk-watermark/' target='_blank'>Bulk Watermark</a></p>
	<p>-<a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/signature-watermark/' target='_blank'>Signature Watermark</a></p>
	<p>-<a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Transparent Image Watermark</a></p>
<?php $this->html_print_box_footer(true); ?>


<?php $this->html_print_box_header('more_plugins',__('More Plugins','more_plugins'),true); ?>
	
	<p><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
	<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/'  target='_blank'>Free Plugins on MyWebsiteAdvisor.com!</a></p>	
				
<?php $this->html_print_box_footer(true); ?>


<?php $this->html_print_box_header('follow',__('Follow MyWebsiteAdvisor','follow'),true); ?>

	<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
	<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
	<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
	<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>	
	
<?php $this->html_print_box_footer(true); ?>

</div>
</div>



	<div class="has-sidebar sm-padded" >			
		<div id="post-body-content" class="has-sidebar-content">
			<div class="meta-box-sortabless">
								
								
				
	
		
		<?php $this->html_print_box_header('wm_type',__('Enable Watermark','transparent-watermark'),false); ?>


			<table class="form-table">

				<tr valign="top">
					<th scope="row">Enable Automatic Watermark for Image Sizes:</th>
					<td>
						<fieldset>
						<legend class="screen-reader-text"><span>Enable Automatic Watermark for Image Sizes:</span></legend>
						
						
						
						<?php $watermark_on = array_keys($this->get_option('watermark_on')); ?>
						
						
						<?php $this->_image_sizes = array_unique(array_merge(get_intermediate_image_sizes(), $this->_image_sizes)); ?>
						
						<?php foreach($this->_image_sizes as $image_size) : ?>
							
							<?php $checked = in_array($image_size, $watermark_on); ?>
							<p>
							<label>
								<input name="watermark_on[<?php echo $image_size; ?>]" type="checkbox" id="watermark_on_<?php echo $image_size; ?>" value="1"<?php echo $checked ? ' checked="checked"' : null; ?> />
								<?php echo ucfirst($image_size); ?>
							</label>
							</p>
						<?php endforeach; ?>
						
							<span class="description">Select Image Sizes on which Automatic Watermark should appear.</span>						
						</fieldset>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Enable Automatic Watermark for Image Types:</th>
					<td>
						<fieldset>
						<legend class="screen-reader-text"><span>Enable Automatic Watermark for Image Types:</span></legend>
						
						
						
						<?php $watermark_type_on = get_option('watermark_type_on'); ?>
								
						
							<p>
							<label>
								<?php $checked = isset($watermark_type_on['jpg']) ? 'checked="checked"' : ''; ?>
								<input name="watermark_type_on[jpg]" type="checkbox" id="watermark_type_on_jpg" value="1" <?php echo $checked; ?> />
								<?php echo ".JPG"; ?>
							</label>
							</p>
							
							<p>
							<label>
								<?php $checked = isset($watermark_type_on['gif']) ? 'checked="checked"' : '' ; ?>
								<input name="watermark_type_on[gif]" type="checkbox" id="watermark_type_on_gif" value="1" <?php echo $checked; ?> />
								<?php echo ".GIF"; ?>
							</label>
							</p>
							
							<p>
							<label>
								<?php $checked = isset($watermark_type_on['png']) ? 'checked="checked"' : '' ; ?>
								<input name="watermark_type_on[png]" type="checkbox" id="watermark_type_on_png" value="1" <?php echo $checked; ?> />
								<?php echo ".PNG"; ?>
							</label>
							</p>
							
						
						
							<span class="description">Select Image Types on which Automatic Watermark should appear.</span>						
						</fieldset>
					</td>
				</tr>
				
			</table>
			
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
			</p>
			
		<?php $this->html_print_box_footer(true); ?>




		<?php $this->html_print_box_header('wm_type',__('Watermark Type','transparent-watermark'),false); ?>

				<table class="form-table">
					<?php $watermark_type = $this->get_option('watermark_type'); ?>
					
					<tr valign="top">
						<th scope="row">Watermark Type</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Watermark Type</span></legend>

								<label><input name="watermark_type" value="image" type="radio" <?php if($watermark_type == "image"){echo "checked='checked'";}  ?> /> Image   </label><br />
							 (<a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/watermark-plugins-for-wordpress/' target='_blank'>Click Here to Upgrade!</a>)
								
								
							</fieldset>
						</td>
						
					</tr>
					
				</table>


				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
				</p>
				
		<?php $this->html_print_box_footer(true); ?>




		<?php $this->html_print_box_header('wm_settings',__('Image Watermark Settings','transparent-watermark'),false); ?>

				<p>Configure Transparent Image Watermark. (Remember to use a .png file with transparency or translucency!)</p>
				<p>Also keep in mind that your watermark image should be about the same with as the images you plan to watermark.</p>

				<table class="form-table">
					<?php $watermark_image = $this->get_option('watermark_image'); ?>
					
					<tr valign="top">
						<th scope="row">Watermark Image URL</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Watermark Image URL</span></legend>
	
								<input name="watermark_image[url]" type="text" class='widefat' value="<?php echo $watermark_image['url']; ?>" />
								<?php if(substr($watermark_image['url'], -4, 4) != '.png'){ 
									echo "ERROR: Image should be a .png file!<br>";
									echo "We offer Premium versions of this plugin which support other image types! <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Click Here for More Info.</a>";
									} 
								?>
							</fieldset>
						</td>
						
					</tr>
					
					<tr valign="top">
						<th scope="row">Watermark Preview</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Watermark Preview</span></legend>
	
								<img id="previewImg_image" src="<?php echo $watermark_image['url']; ?>" alt="" width="300" />
							
							</fieldset>
						</td>
						
					</tr>

					<tr valign="top">
						<th scope="row">Image Width (Percentage)</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Width</span></legend>
	
								<input type="text" size="5"  name="watermark_image[width]" value="<?php echo $watermark_image['width']; ?>">%
							
							</fieldset>
						</td>
						
					</tr>
					
				
										
					<tr valign="top">
						<th scope="row">Enable Watermark Resampling (Higher Quality Watermark)</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Enable Watermark Resampling</span></legend>
	
								<label>Enable : <input name="watermark_resampling" type="checkbox" size="50" value="true"  disabled="disabled" /></label><br />
								<span class="description">(Feature Available in Ultra Version Only, <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)</span>
							</fieldset>
						</td>
						
					</tr>
					
					<tr valign="top">
						<th scope="row">Show Preview of Advanced Watermark Features on Upload Screen</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Enable Advanced Features Preview</span></legend>
								<?php $show_on_upload_screen = $this->get_option('show_on_upload_screen'); ?>
								
								<label>Enable :  <input name="show_on_upload_screen" type="checkbox" size="50" value='true'  <?php if($show_on_upload_screen === "true"){echo "checked='checked'";}  ?>  /></label><br />
								<span class="description">(Feature Available in Ultra Version Only, <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)</span>
							</fieldset>
						</td>
						
					</tr>
					
				</table>
			





				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
				</p>
				
			
			
			
			
			<?php $this->html_print_box_footer(true); ?>
			
			
			
			</div></div></div></div>
			

		</form>
</div>
<?php
	}
	
	
	
	
	
	

	public function attachment_field_add_watermark($form_fields, $post){
    		if ($post->post_mime_type == 'image/jpeg' || $post->post_mime_type == 'image/gif' || $post->post_mime_type == 'image/png') {
                       
                        $ajax_url = "../".PLUGINDIR . "/". dirname(plugin_basename (__FILE__))."/watermark_ajax.php";     
                        $image_url = $post->guid;                          
                                                  

                                                  
                         $form_js = "<style>#watermark_preview{
                          position:absolute;
                                      border:1px solid #ccc;
                                      background:#333;
                                      padding:5px;
                                      display:none;
                                                                             
                                      color:#fff;
                            }
                             #watermark_preview img{
                      
                                       max-width:300px;  
									  max-height:300px;     
									  z-index:200000;                                    
                              
                              } 
							  
							  p#watermark_preview{
								  z-index:200000; 
							  }                                        
                      </style>";    
                                   

                                                  
                          
                       $attachment_info =  wp_get_attachment_metadata($post->ID);        
                        
                       $sizes = array();                           
                                                  
                      foreach($attachment_info['sizes'] as $size){
                        
                        $sizes[$size['width']] = $size;
                        
                        
                      }
                                                  
                        //$sizes = array_unique($sizes);
                  	krsort($sizes);
                  


                  	$upload_dir   = wp_upload_dir();
                  
                  	$url_info = parse_url($post->guid);
  					$url_info['path'] = str_replace("/wp-content/uploads/", "/", $url_info['path']);
  
  					$filepath = $upload_dir['basedir']  . $url_info['path'];

                  
    
                  	$path_info = pathinfo($url_info['path']);
                  	
                  	$base_filename = $path_info['basename'];
                  	$base_path = str_replace($base_filename, "", $post->guid);
					
 			 
			 
                  	$url_info = parse_url($post->guid);
					$url_info['path'] = ereg_replace("/wp-content/uploads/", "/", $url_info['path']);

					$path_info = pathinfo($post->guid);
					$url_base = $path_info['dirname']."/".$path_info['filename'] . "." . $path_info['extension'];
					$filepath = ABSPATH . str_replace(get_option('siteurl'), "", $url_base);
					$filepath = str_replace("//", "/", $filepath);
                  
                  
                  $watermark_horizontal_location = 50;
                  $watermark_vertical_location = 50;
                  $watermark_image = $this->get_option('watermark_image');
                  $watermark_width = $watermark_image['width'];
                  
				  	$form_fields['image-watermark-header']  = array(
            			'label'      => __('<h3>Watermark Settings</h3>', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => '<input type="hidden">');
						
						
				  
				  
 					$form_html = "<p><input id='watermark_width' value='$watermark_width' type='text' size='5' style='width:50px !important;'  />%<br />";
					$form_html .= "(Example: 50 would mean that the watermark will be 50% of the width of the image being watermarked.)</p>";
					
					$form_fields['image-watermark-width']  = array(
            			'label'      => __('Watermark Width', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);
				       
					              
                  $form_html = "<p><input id='watermark_vertical_location' value='$watermark_vertical_location' type='text'  size='5' style='width:50px !important;' />%<br />";
				  $form_html .= "(Example: 50 would mean that the image is centered vertically, 10 would mean it is 10% from the top.)</p>";
                  $form_html .= $form_js;
				  
                  $form_fields['image-watermark-vertical-location']  = array(
            			'label'      => __('Vertical Position', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);
						
					
					$form_html = "<p><input id='watermark_horizontal_location' value='$watermark_horizontal_location' type='text' size='5' style='width:50px !important;'  />%<br />";
					$form_html .= "(Example: 50 would mean that the image is centered horizontally, 10 would mean it is 10% from the left.)</p>";
					
					 $form_fields['image-watermark-horizontal-location']  = array(
            			'label'      => __('Horizontal Position', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);
						
					  
                  
                 
                  
  				$form_html = "<p><input type='checkbox' name='attachment_size[]' value='".$post->guid."' style='width:auto;'> Original";
                $form_html .= " <a class='watermark_preview' href='".$post->guid."?".filemtime($filepath)."' title='$base_filename Preview' target='_blank'>" . $base_filename . "</a></p>";
                  
				  
				  $form_fields['image-watermark-fullsize']  = array(
            			'label'      => __('Fullsize', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);
				  
				  
                  foreach($sizes as $size){
              
						$image_link = $base_path.$size['file'];
						
						$filename = $path_info['filename'].".".$path_info['extension'];
						$current_filepath = str_replace($filename, $size['file'], $filepath);
						
                    
						$form_html = "<p><input type='checkbox' name='attachment_size[]' value='".$base_path.$size['file']."' style='width:auto;'> ".$size['width'] . "x" . $size['height'];
						$form_html .= " <a class='watermark_preview' title='".$size['file']." Preview'  href='".$image_link."?".filemtime($current_filepath)."' target='_blank'>" . $size['file'] . "</a></p>";
					
						$id = 'image-watermark-' . $size['width'] . "x" . $size['height'];
					
					
						 $form_fields[ $id ]  = array(
            			'label'      => __($size['width'] . "x" . $size['height'], 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);
					
                  }

                  
                  $form_html = "<input type='button' class='button-primary' name='Add Watermark' value='Add Watermark' onclick='image_add_watermark();'>";
                  $form_html .= "<script type='text/javascript' src='"."../".PLUGINDIR . "/". dirname(plugin_basename(__FILE__))."/watermark.js'></script>";  
				  $form_html .= "<script type='text/javascript'>
                  
				  				var el = jQuery('.compat-attachment-fields');
				                 jQuery(el).ready(function(){
                                              imagePreview();
                                      });       
                  		
                  		 function image_add_watermark(){
                                                  
                                                  alert('Sorry, This feature is only available in the Ultra Version!  Please Upgrade at http://MyWebsiteAdvisor.com');
						window.open('http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/');
                                           
										                                                              
                                                  
                                          }
										  
										  setTimeout(imagePreview, 100);
                                                                                        
                                      </script>";        
                       
                         $form_fields['image-watermark']  = array(
            			'label'      => __('', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);      
							   
							   
					$show_on_upload_screen = $this->get_option('show_on_upload_screen');
							 
						if($show_on_upload_screen === "true"){	   
							                   
                         	return $form_fields;   
							   
						}else{
						
							return "";
							
						}                      
                                                  
                                                  
                } else {
                 	return false; 
                }
    	}      
		
		
             

}


?>