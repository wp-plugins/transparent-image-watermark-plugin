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
			register_activation_hook(TW_LOADER, array(&$this, 'activateWatermark'));
			

			$show_on_upload_screen = $this->get_option('show_on_upload_screen');			 
			if($show_on_upload_screen === "true"){	
				add_filter('attachment_fields_to_edit', array(&$this, 'attachment_field_add_watermark'), 10, 2);
				
			}
			
			// add plugin "Settings" action on plugin list
			add_action('plugin_action_links_' . plugin_basename(TW_LOADER), array(&$this, 'add_plugin_actions'));
			
			// add links for plugin help, donations,...
			add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
			
			// push options page link, when generating admin menu
			add_action('admin_menu', array(&$this, 'adminMenu'));
	
			//add help menu
			add_filter('contextual_help', array(&$this,'adminHelp'), 10, 3);
			
			
			// check if post_id is "-1", meaning we're uploading watermark image
			if(!(array_key_exists('post_id', $_REQUEST) && $_REQUEST['post_id'] == -1)) {
				// add filter for watermarking images
				add_filter('wp_generate_attachment_metadata', array(&$this, 'applyWatermark'));
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
			$links[] = '<a href="http://MyWebsiteAdvisor.com/">Visit Us Online</a>';
		}
		
		return $links;
	}
	
	/**
	 * Add menu entry for Transparent Watermark settings and attach style and script include methods
	 */
	public function adminMenu() {		
		// add option in admin menu, for setting details on watermarking
		global $transparent_watermark_admin_page;
		$transparent_watermark_admin_page = add_options_page('Transparent Watermark Plugin Options', 'Transparent Watermark', 8, __FILE__, array(&$this, 'optionsPage'));

		add_action('admin_print_styles-' . $transparent_watermark_admin_page,     array(&$this, 'installStyles'));
	}
	
	
	public function adminHelp($contextual_help, $screen_id, $screen){
	
		global $transparent_watermark_admin_page;
		
		if ($screen_id == $transparent_watermark_admin_page) {
			
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
	
	
	
	
	
	
	
	
	/**
	 * Include styles used by Transparent Watermark Plugin
	 */
	public function installStyles() {
		wp_enqueue_style('transparent-watermark', WP_PLUGIN_URL . $this->_plugin_dir . 'style.css');
	}
	




	function HtmlPrintBoxHeader($id, $title, $right = false) {
		
		?>
		<div id="<?php echo $id; ?>" class="postbox">
			<h3 class="hndle"><span><?php echo $title ?></span></h3>
			<div class="inside">
		<?php
		
		
	}
	
	function HtmlPrintBoxFooter( $right = false) {
		?>
			</div>
		</div>
		<?php
		
	}




	
	/**
	 * Display options page
	 */
	public function optionsPage() {
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
			
<?php $this->HtmlPrintBoxHeader('pl_diag',__('Plugin Diagnostic Check','diagnostic'),true); ?>

				<?
				
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
		
				$lav = sys_getloadavg();
				echo "<p>Server Load Average: ".$lav[0].", ".$lav[1].", ".$lav[2]."</p>";

				
				
				?>

<?php $this->HtmlPrintBoxFooter(true); ?>



<?php $this->HtmlPrintBoxHeader('pl_resources',__('Plugin Resources','resources'),true); ?>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Plugin Homepage</a></p>
	<p><a href='http://mywebsiteadvisor.com/support/'  target='_blank'>Plugin Support</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Contact Us</a></p>
	<p><a href='http://wordpress.org/support/view/plugin-reviews/transparent-image-watermark-plugin?rate=5'  target='_blank'>Rate and Review This Plugin</a></p>
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('pl_upgrades',__('Plugin Upgrades','upgrades'),true); ?>
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
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('more_plugins',__('More Plugins','more_plugins'),true); ?>
	
	<p><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
	<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/'  target='_blank'>Free Plugins on MyWebsiteAdvisor.com!</a></p>	
				
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('follow',__('Follow MyWebsiteAdvisor','follow'),true); ?>

	<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
	<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
	<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
	<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>	
	
<?php $this->HtmlPrintBoxFooter(true); ?>

</div>
</div>



	<div class="has-sidebar sm-padded" >			
		<div id="post-body-content" class="has-sidebar-content">
			<div class="meta-box-sortabless">
								
								
				
	
		
		<?php $this->HtmlPrintBoxHeader('wm_type',__('Watermark Type','watermark-type'),false); ?>




			<table class="form-table">

				<tr valign="top">
					<th scope="row">Enable watermark for</th>
					<td>
						<fieldset>
						<legend class="screen-reader-text"><span>Enable watermark for</span></legend>
						
						
						
						<?php $watermark_on = array_keys($this->get_option('watermark_on')); ?>
						
						
						<?php $this->_image_sizes = array_unique(array_merge(get_intermediate_image_sizes(), $this->_image_sizes)); ?>
						
						<?php foreach($this->_image_sizes as $image_size) : ?>
							
							<?php $checked = in_array($image_size, $watermark_on); ?>
						
							<label>
								<input name="watermark_on[<?php echo $image_size; ?>]" type="checkbox" id="watermark_on_<?php echo $image_size; ?>" value="1"<?php echo $checked ? ' checked="checked"' : null; ?> />
								<?php echo ucfirst($image_size); ?>
							</label>
							<br />
						<?php endforeach; ?>
						
							<span class="description">Check image sizes on which watermark should appear.</span>						
						</fieldset>
					</td>
				</tr>
				
			</table>



			<a name="watermark_text"></a>
			<div id="watermark_text" class="watermark_type">
				<h3>Watermark Type</h3>
				<p>Choose a Watermark Type.</p>

				<table class="form-table">
					<?php $watermark_type = $this->get_option('watermark_type'); ?>
					
					<tr valign="top">
						<th scope="row">Watermark Type</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Watermark Type</span></legend>

								<input name="watermark_type" value="image" type="radio" <? if($watermark_type == "image"){echo "checked='checked'";}  ?> /> Image <br />
								
								
							</fieldset>
						</td>
						
					</tr>
				

					
				</table>
			</div>




			<a name="watermark_text"></a>
			<div id="watermark_text" class="watermark_type">
				<h3>Transparent Image Watermark</h3>
				<p>Configure Transparent Image Watermark. (Remember to use a .png file with transparency or translucency!)</p>
				<p>Also keep in mind that your watermark image should be about the same with as the images you plan to watermark.</p>

				<table class="form-table">
					<?php $watermark_image = $this->get_option('watermark_image'); ?>
					
					<tr valign="top">
						<th scope="row">Watermark Image URL</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Watermark Image URL</span></legend>
	
								<input name="watermark_image[url]" type="text" size="50" value="<?php echo $watermark_image['url']; ?>" />
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
	
								Enable : <input name="watermark_resampling" type="checkbox" size="50" value="true"  disabled="disabled" />
								(Feature Available in Ultra Version Only, <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)
							</fieldset>
						</td>
						
					</tr>
					
					<tr valign="top">
						<th scope="row">Show Preview of Advanced Watermark Features on Upload Screen</th>
						<td class="wr_width">
							<fieldset class="wr_width">
							<legend class="screen-reader-text"><span>Enable Advanced Features Preview</span></legend>
								<?php $show_on_upload_screen = $this->get_option('show_on_upload_screen'); ?>
								
								Enable :  <input name="show_on_upload_screen" type="checkbox" size="50" value='true'  <?php if($show_on_upload_screen === "true"){echo "checked='checked'";}  ?>  />
								(Feature Available in Ultra Version Only, <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)
							</fieldset>
						</td>
						
					</tr>
					
				</table>
			</div>


			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
			</p>
			
			
			<?php $this->HtmlPrintBoxFooter(true); ?>
			

		</form>
</div>
<?php
	}
	
	
	
	
	
	

	public function attachment_field_add_watermark($form_fields, $post){
    		if ($post->post_mime_type == 'image/jpeg' || $post->post_mime_type == 'image/gif' || $post->post_mime_type == 'image/png') {
                       
                        $ajax_url = "../".PLUGINDIR . "/". dirname(plugin_basename (__FILE__))."/watermark_ajax.php";     
                        $image_url = $post->guid;                          
                                                  
                       	$form_html = "<h3>Transparent Watermark</h3>"; 
                                                  
                         $form_html .= "<style>#watermark_preview{
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
                              
                              }                                         
                      </style>";    
                                                  
                        $form_html .= "<script type='text/javascript' src='"."../".PLUGINDIR . "/". dirname(plugin_basename (__FILE__))."/watermark.js'></script>";                        
                        $form_html .= "<script type='text/javascript'>
                                          function image_add_watermark(){
                                                  
                                                  alert('Sorry, This feature is only available in the Ultra Version!  Please Upgrade at http://MyWebsiteAdvisor.com');
						window.open('http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/');
                                                                                                      
                                                  
                                          }
                  
                  
                  			jQuery(document).ready(function(){
                                              imagePreview();
                                      });
                                                                                        
                                      </script>";                          
                                                
			
                                                  
                          
                       $attachment_info =  wp_get_attachment_metadata($post->ID);        
                        
                       $sizes = array();                           
                                                  
                      foreach($attachment_info['sizes'] as $size){
                        
                        $sizes[$size['width']] = $size;
                        
                        
                      }
                                                  
                        //$sizes = array_unique($sizes);
                  	krsort($sizes);
                  
                  
                  
                  
                  
                  	$upload_dir   = wp_upload_dir();
                  
                  	$url_info = parse_url($post->guid);
  			$url_info['path'] = ereg_replace("/wp-content/uploads/", "/", $url_info['path']);
  
  			$filepath = $upload_dir['basedir']  . $url_info['path'];

                  
                    	 //$url_info = parse_url($post->guid);
                  	$path_info = pathinfo($url_info['path']);
                  	
                  	$base_filename = $path_info['basename'];
                  	$base_path = ereg_replace($base_filename, "", $post->guid);
                  
 			 //$url_info['path'] = ereg_replace("/wp-content/uploads/", "/", $url_info['path']);
                  
                  
                  
                  $watermark_horizontal_location = $this->get_option('watermark_horizontal_location');
                  $watermark_vertical_location = $this->get_option('watermark_vertical_location');
                  $watermark_image = $this->get_option('watermark_image');
                  $watermark_width = $watermark_image['width'];
                  
                  
                  $form_html .= "<p>Vertical Position: ";
                  $form_html .= "<input id='watermark_vertical_location' value='$watermark_vertical_location' type='text'  size='5' style='width:50px !important;' />%<br />";
		  $form_html .= "(Example: 50 would mean that the image is centered vertically, 10 would mean it is 10% from the top.)</p>";
                  
                  
  		  $form_html .= "<p>Horizontal Position: ";
                  $form_html .= "<input id='watermark_horizontal_location' value='$watermark_horizontal_location' type='text' size='5' style='width:50px !important;'  />%<br />";
		  $form_html .= "(Example: 50 would mean that the image is centered horizontally, 10 would mean it is 10% from the left.)</p>";
                  
                  
  		  $form_html .= "<p>Watermark Width: ";
                  $form_html .= "<input id='watermark_width' value='$watermark_width' type='text' size='5' style='width:50px !important;'  />%<br />";
		  $form_html .= "(Example: 50 would mean that the watermark will be 50% of the width of the image being watermarked.)</p>";
                  
                  
                  $form_html .= "<div id='attachment_sizes'>";
                  
  		$form_html .= "<p><input type='checkbox' name='attachment_size[]' class='attachment_size' value='".$post->guid."'>";
                $form_html .= "Original - <a class='watermark_preview' href='".$post->guid."' title='$base_filename Preview' target='_blank'>" . $base_filename . "</a></p>";
                  
                  foreach($sizes as $size){
                    
                    	$form_html .= "<p><input type='checkbox' name='attachment_size[]' class='attachment_size' value='".$base_path.$size['file']."'>";
                    	$form_html .= $size['width'] . "x" . $size['height'] . " - <a class='watermark_preview' title='".$size['file']." Preview'  href='".$base_path.$size['file']."' target='_blank'>" . $size['file'] . "</a></p>";
                    
  
                  }
                  
                  $form_html .= "</div>";
                  
                  
                  
                  $form_html .= "<div id='watermark_button_container'><input type='button' class='button-primary' name='Add Watermark' value='Add Watermark' onclick='image_add_watermark();'></div>";
                  
                       //$form_html .= "<pre>" . print_r($sizes, true) . "</pre>";                           
                                                  
                                                  
                        $form_fields['image-watermark']  = array(
            			'label'      => __('Watermark', 'transparent_watermark_ultra'),
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