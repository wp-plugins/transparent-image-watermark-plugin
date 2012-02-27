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
	private $_image_sizes         = array('thumbnail', 'medium', 'large', 'fullsize');
	
	
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
			
			
			add_filter('attachment_fields_to_edit', array(&$this, 'attachment_field_add_watermark'), 10, 2);
			
			
			// add plugin "Settings" action on plugin list
			add_action('plugin_action_links_' . plugin_basename(TW_LOADER), array(&$this, 'add_plugin_actions'));
			
			// add links for plugin help, donations,...
			add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
			
			// push options page link, when generating admin menu
			add_action('admin_menu', array(&$this, 'adminMenu'));
	
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
			$links[] = '<a href="http://MyWebsiteAdvisor.com">Visit Us Online</a>';
		}
		
		return $links;
	}
	
	/**
	 * Add menu entry for Transparent Watermark settings and attach style and script include methods
	 */
	public function adminMenu() {		
		// add option in admin menu, for setting details on watermarking
		$plugin_page = add_options_page('Transparent Watermark Plugin Options', 'Transparent Watermark', 8, __FILE__, array(&$this, 'optionsPage'));

		add_action('admin_print_styles-' . $plugin_page,     array(&$this, 'installStyles'));
	}
	
	/**
	 * Include styles used by Transparent Watermark Plugin
	 */
	public function installStyles() {
		wp_enqueue_style('transparent-watermark', WP_PLUGIN_URL . $this->_plugin_dir . 'style.css');
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
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>Transparent Watermark Plugin Settings</h2>
		<form method="post" action="">
			<table class="form-table">

				<tr valign="top">
					<th scope="row">Enable watermark for</th>
					<td>
						<fieldset>
						<legend class="screen-reader-text"><span>Enable watermark for</span></legend>
						
						<?php $watermark_on = array_keys($this->get_option('watermark_on')); ?>
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
									echo "We offer Premium versions of this plugin which support other image types! <a href='' target='_blank'>Click Here for More Info.</a>";
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
					

					
				</table>
			</div>


			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
			</p>

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
                         
                                               
                         return $form_fields;                            
                                                  
                                                  
                } else {
                 	return false; 
                }
    	}                    

}


?>