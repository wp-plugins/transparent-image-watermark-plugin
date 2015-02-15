<?php


 
class Transparent_Watermark_Plugin{

	//plugin version number
	private $version = "2.3.15";
	
	private $debug = false;
	
	
	
	//holds settings page class
	private $settings_page;
	
	//holds a link to the plugin settings menu
	private $page_menu;
	
	//holds watermark tools
	private $tools;


	
	///options are: edit, upload, link-manager, pages, comments, themes, plugins, users, tools, options-general
	private $page_icon = "options-general"; 	
	
	//settings page title, to be displayed in menu and page headline
	private $plugin_title = "Transparent Watermark";
	
	//page name, also will be used as option name to save all options
	private $plugin_name = "transparent-watermark";
	
	//will be used as option name to save all options
	private $setting_name = "transparent-watermark-settings";	
	
	private $youtube_id = "fEhZK1U8W94";
	
	
	
	//holds plugin options
	private $opt = array();
	
	public $plugin_path;
	public $plugin_dir;
	public $plugin_url;
	
	
	//initialize the plugin class
	public function __construct() {
		
		$this->plugin_path = DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), null, plugin_basename(__FILE__));
		$this->plugin_dir = WP_PLUGIN_DIR . $this->plugin_path;
		$this->plugin_url = WP_PLUGIN_URL . $this->plugin_path;
		
		$this->opt = get_option($this->setting_name);
		
		$this->tools = new Transparent_Watermark_Tools;
		$this->tools->opt = $this->opt;
		
		
		if(isset($_GET['action']) && isset($_GET['page']) && "watermark_preview" == $_GET['action'] && "transparent-watermark-settings" == $_GET['page'] ){
			
			add_action('admin_init', array($this->tools, 'do_watermark_preview'));
			//die();
		}
		
		
		// check if post_id is "-1", meaning we're uploading watermark image
		if(!(array_key_exists('post_id', $_REQUEST) && $_REQUEST['post_id'] == -1)) {
		
			// add filter for watermarking images
			add_filter('wp_generate_attachment_metadata', array(&$this->tools, 'apply_watermark'), 10, 2);
			
		}
		
		
		$show_on_upload_screen = $this->opt['watermark_settings']['show_on_upload_screen'];			 
		if($show_on_upload_screen === "true"){	
		
			add_action( 'admin_enqueue_scripts', array($this, 'add_watermark_js') );
			
			add_filter('attachment_fields_to_edit', array(&$this, 'attachment_field_add_watermark'), 10, 2);
				
		}
		
		add_action('wp_ajax_revert_watermarks', array(&$this, 'revert_watermarks'));
				
				
		//hook on delete_attachment action to delete all of the backups created before watermarks were applied
		add_action('delete_attachment', array($this, 'delete_attachment_watermark_backups'));
		
	
		//check pluign settings and display alert to configure and save plugin settings
		add_action( 'admin_init', array(&$this, 'check_plugin_settings') );
		
		//initialize plugin settings
        add_action( 'admin_init', array(&$this, 'settings_page_init') );
		
		//create menu in wp admin menu
        add_action( 'admin_menu', array(&$this, 'admin_menu') );
		
		//add help menu to settings page
		//add_filter( 'contextual_help', array(&$this,'admin_help'), 10, 3);	
		
		// add plugin "Settings" action on plugin list
		add_action('plugin_action_links_' . plugin_basename(TW_LOADER), array(&$this, 'add_plugin_actions'));
		
		// add links for plugin help, donations,...
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
	
		//setup javascript files	
		//add_action('admin_enqueue_scripts', array($this, 'setup_watermark_scripts'));
		

		
	}
	
	
	
	

	
	
	
	public function settings_page_init() {

		 $this->settings_page  = new Transparent_Watermark_Settings_Page( $this->setting_name );
		 
        //set the settings
        $this->settings_page->set_sections( $this->get_settings_sections() );
        $this->settings_page->set_fields( $this->get_settings_fields() );
		$this->settings_page->set_sidebar( $this->get_settings_sidebar() );

		$this->build_optional_tabs();
		
        //initialize settings
        $this->settings_page->init();
    }
	
	
	
	
	public function check_plugin_settings(){
		if( isset($_GET['page']) ){
			if ($_GET['page'] == "transparent-watermark"  ){
				if(false === get_option($this->setting_name)){
					
					$link = admin_url()."options-general.php?page=transparent-watermark-settings&tab=watermark_settings";
					$message = '<div class="error"><p>Welcome!<br>This plugin needs to be configured before you watermark your images.';
					$message .= '<br>Please Configure and Save the <a href="%1$s">Plugin Settings</a> before you continue!!</p></div>';
					echo sprintf($message, $link);
					
				}
			}
		}
	}

	
	

    /**
     * Returns all of the settings sections
     *
     * @return array settings sections
     */
    function get_settings_sections() {
	
		$settings_sections = array(
			array(
				'id' => 'watermark_settings',
				'title' => __( 'Watermark Settings', $this->plugin_name )
			)
			
		);
		
		
		
		$text_watermark_section = array(
				'id' => 'text_watermark_settings',
				'title' => __( 'Text Watermark', $this->plugin_name )
			);

		$image_watermark_section = array(
				'id' => 'image_watermark_settings',
				'title' => __( 'Image Watermark', $this->plugin_name )
			);
			


		if(isset($this->opt['watermark_settings']['watermark_type'])){
			switch( $this->opt['watermark_settings']['watermark_type']){
				
				case "text-only":
					$settings_sections[] = $text_watermark_section;
					break;
				case "image-only":
					$settings_sections[] = $image_watermark_section;
					break;	
				
			}
		}

								
        return $settings_sections;
    }


	
	

    /**
     * Returns all of the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
		
		$pwd = getcwd()."/";
		$target = $this->plugin_dir."watermark-logo.png";
		$default_watermark_path  =   $this->tools->get_relative_path($pwd, $target);
		
		$image_watermark_fields = array(
			array(
				'name' => 'watermark_image_url',
				'label' => __( 'Watermark Image URL', $this->plugin_name ),
				'type' => 'url',
				'default' => $default_watermark_path,
				'desc' => 'Configure the Watermark Image URL or Relative Path.<p>If you have <b>"allow_url_fopen" : disabled</b>, you can use a relative path to the watermark image location such as: <br><b>' . $default_watermark_path . '</b></p>',
			),
			array(
				'name' => 'watermark_image_width',
				'label' => __( 'Watermark Image Width', $this->plugin_name ),
				'desc' => 'Configure the Watermark Image Width (Percentage)',
				'type' => 'percentage',
				'default' => "50"
			),
			array(
				'name' => 'watermark_image_v_pos',
				'label' => __( 'Watermark Image Vertical Position', $this->plugin_name ),
			 	'desc' => __( "Enable Image Watermark Vertical Position Adjustnment.<br>(Feature Available in Ultra Version Only, <a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
                'action' => 'Enable',
				'type' => 'checkbox',
                'enabled' => 'false'
			),
			array(
				'name' => 'watermark_image_h_pos',
				'label' => __( 'Watermark Image Horizontal Position', $this->plugin_name ),
			 	'desc' => __( "Enable Image Watermark Horizontal Position Adjustnment.<br>(Feature Available in Ultra Version Only, <a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
                'action' => 'Enable',
				'type' => 'checkbox',
                'enabled' => 'false'
			),
			array(
				'name' => 'enable_hq_watermarks',
				'label' => __( 'High Quality Watermarks', $this->plugin_name ),
				'desc' => __( "Enable Watermark Resampling which will result in Higher Quality watermarks.<br>(Feature Available in Ultra Version Only, <a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
				'action' => 'Enable',
				'type' => 'checkbox',
				'enabled' => 'false'
			)
			
		);
			
			
			
		$fonts = $this->get_font_list();
		
		
		
		$fonts_select = array(
			'name' => 'watermark_font',
			'label' => __( 'Watermark Font', $this->plugin_name ),
			'desc' => 'Select a Watermark Text Font',
			'type' => 'select',
			'options' => $fonts
		);
		
			
		$text_watermark_fields = array(
			
			array(
				'name' => 'watermark_text',
				'label' => __( 'Watermark Text', $this->plugin_name ),
				'desc' => 'Configure the Watermark Text',
				'type' => 'text',
				'default' => "&copy; MyWebsiteAdvisor.com"
			),
			$fonts_select,
			array(
				'name' => 'watermark_text_width',
				'label' => __( 'Watermark Text Width', $this->plugin_name ),
				'desc' => 'Configure the Watermark Text Width (Percentage)',
				'type' => 'percentage',
				'default' => "50"
			),
			array(
				'name' => 'watermark_text_color',
				'label' => __( 'Watermark Text Color', $this->plugin_name ),
				'desc' => 'Configure the Watermark Text Color (FFFFFF is White)',
				'type' => 'text',
				'default' => "FFFFFF"
			),
			array(
				'name' => 'watermark_text_transparency',
				'label' => __( 'Watermark Text Transparency', $this->plugin_name ),
				'desc' => 'Configure the Watermark Text Transparency (Percentage)',
				'type' => 'percentage',
				'default' => "70"
			), 
			array(
				'name' => 'watermark_text_v_pos',
				'label' => __( 'Watermark Text Vertical Position', $this->plugin_name ),
				'desc' => 'Configure the Watermark Text Vertical Position (Percentage)',
			 	'desc' => __( "Enable Text Watermark Vertical Position Adjustnment.<br>(Feature Available in Ultra Version Only, <a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
                'action' => 'Enable',
				'type' => 'checkbox',
                'enabled' => 'false'
			),
			array(
				'name' => 'watermark_text_h_pos',
				'label' => __( 'Watermark Text Horizontal Position', $this->plugin_name ),
			 	'desc' => __( "Enable Text Watermark Horizontal Position Adjustnment.<br>(Feature Available in Ultra Version Only, <a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
                'action' => 'Enable',
				'type' => 'checkbox',
                'enabled' => 'false'
			)
			
			
		);






		$settings_fields = array(
			'watermark_settings' => array(
			array(
                    'name' => 'watermark_type',
                    'label' => __( 'Watermark Type', $this->plugin_name ),
					'desc' => __( "Select a Watermark Type.<br>(Upgrade to <a href='http://mywebsiteadvisor.com/plugins/signature-watermark/' target='_blank'>Signature Watermark Ultra</a> for <b>Text and Image Watermarks!</b>)", $this->plugin_name ),
                    'type' => 'radio',
                    'options' => array(
						'image-only' => 'Image Only',
                        'text-only' => 'Text Only' 
                    )
                ),
			    array(
                    'name' => 'image_sizes',
                    'label' => __( 'Image Sizes', $this->plugin_name ),
                    'desc' => __( 'Enable Automatic Watermarks for the selected Image Sizes', $this->plugin_name ),
                    'type' => 'multicheck',
					'options' => $this->get_image_sizes()
                ),
				array(
                    'name' => 'image_types',
                    'label' => __( 'Image Types', $this->plugin_name ),
                    'desc' => __( 'Enable Automatic Watermarks for the selected Image Types', $this->plugin_name ),
                    'type' => 'multicheck',
                    'options' => array(
                        'jpg' => '.JPG',
						'jpeg' => '.JPEG',
                        'png' => '.PNG',
                        'gif' => '.GIF'
                    )
                ),
				array(
                    'name' 		=> 'watermark_backup',
                    'label' 		=> __( 'Watermark Backup', $this->plugin_name ),
					'desc' 		=> __( "Create a Backup of each image before it is watermarked so watermarks can be removed.", $this->plugin_name ),
                    'type' 		=> 'radio',
					'default' 	=> 'backup-enabled',
                    'options' 	=> array(
						'backup-enabled'	 	=> 'Backup Enabled',
                        'backup-disabled' 	=> 'Backup Disabled' 
                    )
                ),
                array(
                    'name' => 'show_on_upload_screen',
                    'label' => __( 'Show Advanced Features', $this->plugin_name ),
                    'desc' => __( "Show Advanced Watermark Features on Upload Screen<br><b>Must Be Enabled to Remove Watermarks</b><br>(Some Features Available in Ultra Version Only, <a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
                    'type' => 'radio',
                    'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
                    )
                ),
				array(
					'name' => 'jpeg_quality',
					'label' => __( 'JPEG Quality Adjustment', $this->plugin_name ),
					'desc' => __( "Adjustable JPEG image output quality can adjust the size and quality of the finished images.<br>(Feature Available in Ultra Version Only, <a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
					'type' => 'checkbox',
					'action' => 'Enable',
					'enabled' => 'false'
				)
			)
		);
		
		
		if(isset($this->opt['watermark_settings']['watermark_type'])){
			switch( $this->opt['watermark_settings']['watermark_type']){

				case "text-only":
					$settings_fields['text_watermark_settings'] = $text_watermark_fields;
					break;
					
				case "image-only":
					$settings_fields['image_watermark_settings'] = $image_watermark_fields;
					break;	
				
			}
		}
			
			
        return $settings_fields;
    }




	private function do_diagnostic_sidebar(){
	
		ob_start();
		
			echo "<p>Plugin Version: $this->version</p>";
				
			echo "<p>Server OS: ".PHP_OS." (" . strlen(decbin(~0)) . " bit)</p>";
			
			echo "<p>Required PHP Version: 5.0+<br>";
			echo "Current PHP Version: " . phpversion() . "</p>";
			

			$gdinfo = gd_info();
		
			if($gdinfo){
				echo '<p>GD Support Enabled!<br>';
				if($gdinfo['FreeType Support']){
					 echo 'FreeType Support Enabled!</p>';
				}else{
					echo "Please Configure FreeType!</p>";
				}
			}else{
				echo "<p>Please Configure GD!</p>";
			}
			
			
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
			
			
			echo "<p>Memory Use: " . number_format(memory_get_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
			
			echo "<p>Peak Memory Use: " . number_format(memory_get_peak_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
			
			if(function_exists('sys_getloadavg')){
				$lav = sys_getloadavg();
				echo "<p>Server Load Average: ".$lav[0].", ".$lav[1].", ".$lav[2]."</p>";
			}	
		
			
	
		return ob_get_clean();
				
	}
	
	


	
	private function get_settings_sidebar(){
	
		$plugin_resources = "<p><a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Plugin Homepage</a></p>
			<p><a href='http://mywebsiteadvisor.com/learning/video-tutorials/transparent-image-watermark-tutorial/'  target='_blank'>Plugin Tutorial</a></p>
			<p><a href='http://mywebsiteadvisor.com/support/'  target='_blank'>Plugin Support</a></p>
			<p><b><a href='http://wordpress.org/support/view/plugin-reviews/transparent-image-watermark-plugin?rate=5#postform'  target='_blank'>Rate and Review This Plugin</a></b></p>";
	
	
		$enabled = get_option('mywebsiteadvisor_pluigin_installer_menu_disable');
		if(!isset($enabled) || $enabled == 'true'){
			$more_plugins = "<p><b><a href='".admin_url()."plugins.php?page=MyWebsiteAdvisor' target='_blank' title='Install More Free Plugins from MyWebsiteAdvisor.com!'>Install More Free Plugins!</a></b></p>";
		}else{
			$more_plugins = "<p><b><a href='".admin_url()."plugin-install.php?tab=search&type=author&s=MyWebsiteAdvisor' target='_blank' title='Install More Free Plugins from MyWebsiteAdvisor.com!'>Install More Free Plugins!</a></b></p>";
		}
			
		$more_plugins .= "<p><a href='http://mywebsiteadvisor.com/plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
			<p><a href='http://mywebsiteadvisor.com/plugins/'  target='_blank'>Developer WordPress Plugins!</a></p>
			<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
			<p><a href='http://mywebsiteadvisor.com/plugins/'  target='_blank'>Free Plugins on MyWebsiteAdvisor.com!</a></p>";
					
							
		$follow_us = "<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
			<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
			<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
			<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>";
	
		$upgrade = "	<p>
			<b><a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/'  target='_blank'>Upgrade to Transparent Watermark Ultra!</a></b><br />
			<br />
			<b>Features:</b><br />
				-Manually Add Watermarks<br />
	 			-Change Watermark Position<br />
	 			-Add High Quality Watermarks<br />
	 			-And Much More!<br />
			 </p>
			<p>Click Here for <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/watermark-plugins-for-wordpress/' target='_blank'>More Watermark Plugins</a></p>
			<p>-<a href='http://mywebsiteadvisor.com/plugins/bulk-watermark/' target='_blank'>Bulk Watermark</a></p>
			<p>-<a href='http://mywebsiteadvisor.com/plugins/signature-watermark/' target='_blank'>Signature Watermark</a></p>
			<p>-<a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Transparent Image Watermark</a></p>
			</p>";
	
		$sidebar_info = array(
			array(
				'id' => 'diagnostic',
				'title' => 'Plugin Diagnostic Check',
				'content' => $this->do_diagnostic_sidebar()		
			),
			array(
				'id' => 'resources',
				'title' => 'Plugin Resources',
				'content' => $plugin_resources	
			),
			array(
				'id' => 'upgrade',
				'title' => 'Plugin Upgrades',
				'content' => $upgrade	
			),
			array(
				'id' => 'more_plugins',
				'title' => 'More Plugins',
				'content' => $more_plugins	
			),
			array(
				'id' => 'follow_us',
				'title' => 'Follow MyWebsiteAdvisor',
				'content' => $follow_us	
			)
		);
		
		return $sidebar_info;

	}



	//plugin settings page template
    function plugin_settings_page(){
	
		echo "<style> 
		.form-table{ clear:left; } 
		.nav-tab-wrapper{ margin-bottom:0px; }
		</style>";
		
		echo $this->display_social_media(); 
		
        echo '<div class="wrap" >';
		
			echo '<div id="icon-'.$this->page_icon.'" class="icon32"><br /></div>';
			
			echo "<h2>".$this->plugin_title." Plugin Settings</h2>";
			
			$this->settings_page->show_tab_nav();
			
			echo '<div id="poststuff" class="metabox-holder has-right-sidebar">';
			
				echo '<div class="inner-sidebar">';
					echo '<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">';
					
						$this->settings_page->show_sidebar();
					
					echo '</div>';
				echo '</div>';
			
				echo '<div class="has-sidebar" >';			
					echo '<div id="post-body-content" class="has-sidebar-content">';
						
						$this->settings_page->show_settings_forms();
						
					echo '</div>';
				echo '</div>';
				
			echo '</div>';
			
        echo '</div>';
		
    }










   	public function admin_menu() {
		$this->page_menu = add_options_page( $this->plugin_title, $this->plugin_title, 'manage_options',  $this->setting_name, array($this, 'plugin_settings_page') );
 	
		global $wp_version;

   		if($this->page_menu && version_compare($wp_version, '3.3', '>=')){
			add_action("load-". $this->page_menu, array($this, 'admin_help'));	
		}
    }




	//public function admin_help($contextual_help, $screen_id, $screen){
	public function admin_help(){
			
		 $screen = get_current_screen();
		 
		//if ($screen_id == $this->page_menu) {
				
			$support_the_dev = $this->display_support_us();
			$screen->add_help_tab(array(
				'id' => 'developer-support',
				'title' => "Support the Developer",
				'content' => "<h2>Support the Developer</h2><p>".$support_the_dev."</p>"
			));
				
				
		$video_code = "<style>
		.videoWrapper {
			position: relative;
			padding-bottom: 56.25%; /* 16:9 */
			padding-top: 25px;
			height: 0;
		}
		.videoWrapper iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}
		</style>";
		
			$video_id = $this->youtube_id;
			$video_code .= '<div class="videoWrapper"><iframe width="640" height="360" src="http://www.youtube.com/embed/'.$video_id.'?rel=0&vq=hd720" frameborder="0" allowfullscreen></iframe></div>';

			$screen->add_help_tab(array(
				'id' => 'tutorial-video',
				'title' => "Tutorial Video",
				'content' => "<h2>{$this->plugin_title} Tutorial Video</h2><p>$video_code</p>"
			));
			
			
			
			$faqs = "<p><b>How can I remove the watermarks?</b><br>";
			$faqs .= "This plugin permenantly alters the images to contain the watermarks, so the watermarks can not be removed. <br>";
			$faqs .= "If you want to simply test this plugin, or think you may want to remove the watermarks, you need to make a backup of your images before you use the plugin to add watermarks.<br>";
			$faqs .= "<b><a href='http://wordpress.org/extend/plugins/simple-backup/' target='_blank'>Try Simple Backup Plugin</a></b></p>";
						
			$faqs .= "<p><b>How do I generate the Highest Quality Watermarks?</b><br>";
			$faqs .= "We recommend that your watermark image be roughly the same width as the largest images you plan to watermark.<br>";
			$faqs .= "That way the watermark image will be scaled down, which will work better than making the watermark image larger in order to fit.<br>";
			$faqs .= "We also have a premium version of this plugin that adds the capability to Re-Sample the watermark image, rather than simply Re-Size it, which results in significantly better looking watermarks!<br>";
			$faqs .= "<b><a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Upgrade to Transparent Watermark Ultra</a></b>";
			$faqs .= "</p>";
			
			$faqs .= "<p><b>How can I Adjust the Location of the Watermarks?</b><br>";
			$faqs .= "We have a premium version of this plugin that adds the capability to adjust the location of the watermarks.<br>";
			$faqs .= "The position can be adjusted both vertically and horizontally.<br>";
			$faqs .= "<b><a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Upgrade to Transparent Watermark Ultra</a></b>";
			$faqs .= "</p>";
			
			$faqs .= "<p><b>How can I Add Watermarks to images that were uploaded before the plugin was installed?</b><br>";
			$faqs .= "We have a premium version of this plugin that adds the capability to manually add watermarks to images in the WordPress Media Library.<br>";
			$faqs .= "<b><a href='http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/' target='_blank'>Upgrade to Transparent Watermark Ultra</a></b>";
			$faqs .= "</p>";


			$screen->add_help_tab(array(
				'id' => 'plugin-faq',
				'title' => "Plugin FAQ's",
				'content' => "<h2>Frequently Asked Questions</h2>".$faqs
			));
					
					
			$screen->add_help_tab(array(
				'id' => 'plugin-support',
				'title' => "Plugin Support",
				'content' => "<h2>Support</h2><p>For Plugin Support please visit <a href='http://mywebsiteadvisor.com/support/' target='_blank'>MyWebsiteAdvisor.com</a></p>"
			));
			
			
			$screen->add_help_tab(array(
				'id' => 'upgrade_plugin',
				'title' => __( 'Plugin Upgrades', $this->plugin_name ),
				'content' => $this->get_plugin_upgrades()		
			));		
			
				
					
			
			$disable_plugin_installer_nonce = wp_create_nonce("mywebsiteadvisor-plugin-installer-menu-disable");	
		
			$plugin_installer_ajax = " <script>
				function update_mwa_display_plugin_installer_options(){
					  
						jQuery('#display_mwa_plugin_installer_label').text('Updating...');
						
						var option_checked = jQuery('#display_mywebsiteadvisor_plugin_installer_menu:checked').length > 0;
					  
						var ajax_data = {
							'checked': option_checked,
							'action': 'update_mwa_plugin_installer_menu_option', 
							'security': '$disable_plugin_installer_nonce'
						};
						  
						jQuery.ajax({
							type: 'POST',
							url:  ajaxurl,
							data: ajax_data,
							success: function(data){
								if(data == 'true'){
									jQuery('#display_mwa_plugin_installer_label').text(' MyWebsiteAdvisor Plugin Installer Menu Enabled!');
								}
								if(data == 'false'){
									jQuery('#display_mwa_plugin_installer_label').text(' MyWebsiteAdvisor Plugin Installer Menu Disabled!');
								}
								//alert(data);
								//location.reload();
							}
						});  
				  }</script>";



			$checked = "";
			$enabled = get_option('mywebsiteadvisor_pluigin_installer_menu_disable');
			if(!isset($enabled) || $enabled == 'true'){
				$checked = "checked='checked'";
				$content = "<h2>More Free Plugins from MyWebsiteAdvisor.com</h2><p>Install More Free Plugins from MyWebsiteAdvisor.com <a href='".admin_url()."plugins.php?page=MyWebsiteAdvisor' target='_blank'>Click here</a></p>";
			}else{
					$checked = "";
				$content = "<h2>More Free Plugins from MyWebsiteAdvisor.com</h2><p>Install More Free Plugins from MyWebsiteAdvisor.com  <a href='".admin_url()."plugin-install.php?tab=search&type=author&s=MyWebsiteAdvisor' target='_blank'>Click here</a></p>";
			}
			
			$content .=  $plugin_installer_ajax . "
       	<p><input type='checkbox' $checked id='display_mywebsiteadvisor_plugin_installer_menu' name='display_mywebsiteadvisor_plugin_installer_menu' onclick='update_mwa_display_plugin_installer_options()' /> <label id='display_mwa_plugin_installer_label' for='display_mywebsiteadvisor_plugin_installer_menu' > Check here to display the MyWebsiteAdvisor Plugin Installer page in the Plugins menu.</label></p>";
			
			$screen->add_help_tab(array(
				'id' => 'more-free-plugins',
				'title' => "More Free Plugins",
				'content' => $content
			));
			
			
			
			
			$help_sidebar = "<p>Please Visit us online for more Free WordPress Plugins!</p>";
			$help_sidebar .= "<p><a href='http://mywebsiteadvisor.com/plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p>";
			$help_sidebar .= "<br>";
			$help_sidebar .= "<p>Install more FREE WordPress Plugins from MyWebsiteAdvisor.com </p>";
			
			$enabled = get_option('mywebsiteadvisor_pluigin_installer_menu_disable');
			if(!isset($enabled) || $enabled == 'true'){
				$help_sidebar .= "<p><a href='".admin_url()."plugins.php?page=MyWebsiteAdvisor' target='_blank'>Click here</a></p>";
			}else{
				$help_sidebar .= "<p><a href='".admin_url()."plugin-install.php?tab=search&type=author&s=MyWebsiteAdvisor' target='_blank'>Click here</a></p>";
			}
			
			$screen->set_help_sidebar($help_sidebar);
		//}
	}
	
	
	






	private function get_image_sizes(){
	
		$default_image_sizes = array('fullsize');
		$tmp_image_sizes = array_unique(array_merge(get_intermediate_image_sizes(), $default_image_sizes));
		$image_sizes = array();
		
		foreach($tmp_image_sizes as $image_size){
			$image_sizes[$image_size] = ucfirst($image_size);
		}	
		
		return $image_sizes;
				
	}

  

	/**
	 * Add "Settings" action on installed plugin list
	 */
	public function add_plugin_actions($links) {
		array_unshift($links, '<a href="options-general.php?page=' . $this->setting_name . '">' . __('Settings') . '</a>');
		
		return $links;
	}
	
	
	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename(TW_LOADER)) {
			$upgrade_url = 'http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/';
			$links[] = '<a href="'.$upgrade_url.'" target="_blank" title="Click Here to Upgrade this Plugin!">Upgrade Plugin</a>';
			
			$install_url = admin_url()."plugin-install.php?tab=search&type=author&s=MyWebsiteAdvisor";
			$links[] = '<a href="'.$install_url.'" target="_blank" title="Click Here to Install More Free Plugins!">More Plugins</a>';
			
			$tutorial_url = 'http://mywebsiteadvisor.com/learning/video-tutorials/transparent-image-watermark-tutorial/';
			$links[] = '<a href="'.$tutorial_url.'" target="_blank" title="Click Here to View the Plugin Video Tutorial!">Tutorial Video</a>';
			
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
			$links[] = '<a href="'.$rate_url.'" target="_blank" title="Click Here to Rate and Review this Plugin on WordPress.org">Rate This Plugin</a>';
		}
		
		return $links;
	}
	
	
	public function display_support_us(){
				
		$string = '<p><b>Thank You for using the '.$this->plugin_title.' Plugin for WordPress!</b></p>';
		$string .= "<p>Please take a moment to <b>Support the Developer</b> by doing some of the following items:</p>";
		
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$string .= "<li><a href='$rate_url' target='_blank' title='Click Here to Rate and Review this Plugin on WordPress.org'>Click Here</a> to Rate and Review this Plugin on WordPress.org!</li>";

		$string .= "<li><a href='http://www.youtube.com/subscription_center?add_user=MyWebsiteAdvisor' target='_blank' title='Click Here to Subscribe to our YouTube Channel'>Click Here</a> to Subscribe to our YouTube Channel!</li>";
		
		$string .= "<li><a href='http://facebook.com/MyWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Facebook'>Click Here</a> to Follow MyWebsiteAdvisor on Facebook!</li>";
		$string .= "<li><a href='http://twitter.com/MWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Twitter'>Click Here</a> to Follow MyWebsiteAdvisor on Twitter!</li>";
		$string .= "<li><a href='http://mywebsiteadvisor.com/plugins/' target='_blank' title='Click Here to Purchase one of our Premium WordPress Plugins'>Click Here</a> to Purchase Premium WordPress Plugins!</li>";
	
		return $string;
	}  
  
  
  
  
  	public function display_social_media(){
	
		$social = '<style>
	
		.fb_edge_widget_with_comment {
			position: absolute;
			top: 0px;
			right: 200px;
		}
		
		</style>
		
		<div  style="height:20px; vertical-align:top; width:45%; float:right; text-align:right; margin-top:5px; padding-right:16px; position:relative;">
		
			<div id="fb-root"></div>
			<script>(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=253053091425708";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, "script", "facebook-jssdk"));</script>
			
			<div class="fb-like" data-href="http://www.facebook.com/MyWebsiteAdvisor" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false"></div>
			
			
			<a href="https://twitter.com/MWebsiteAdvisor" class="twitter-follow-button" data-show-count="false"  >Follow @MWebsiteAdvisor</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		
		
		</div>';
		
		return $social;

	}	








	//build optional tabs, using debug tools class worker methods as callbacks
	private function build_optional_tabs(){
		
		
		$watermark_preview = array(
			'id' => 'watermark_preview',
			'title' => __( 'Watermark Preview', $this->plugin_name ),
			'callback' => array(&$this, 'show_watermark_preview')
		);
		$this->settings_page->add_section( $watermark_preview );
		
			
		
		$plugin_tutorial = array(
			'id' => 'plugin_tutorial',
			'title' => __( 'Tutorial Video', $this->plugin_name ),
			'callback' => array(&$this, 'show_plugin_tutorual')
		);
		$this->settings_page->add_section( $plugin_tutorial );
		
		
		
		if(true === $this->debug){
			//general debug settings
			$plugin_debug = array(
				'id' => 'plugin_debug',
				'title' => __( 'Settings Debug', $this->plugin_name ),
				'callback' => array(&$this, 'show_plugin_settings')
			);
			
			$this->settings_page->add_section( $plugin_debug );
		}	
		
		
				
		$upgrade_plugin = array(
			'id' => 'upgrade_plugin',
			'title' => __( 'Upgrades', $this->plugin_name ),
			'callback' => array(&$this, 'show_plugin_upgrades')
		);
		$this->settings_page->add_section( $upgrade_plugin );
	
	}
	
	
	
	
	
	
	
	
	
		
	public function get_plugin_upgrades(){
		ob_start();
		$this->show_plugin_upgrades();
		return ob_get_clean();	
	}
	
	
	public function show_plugin_upgrades(){
		
		$html = "<style>
			ul.upgrade_features li { list-style-type: disc; }
			ul.upgrade_features  { margin-left:30px;}
		</style>";
		
		$html .= "<script>
		
			function  trans_watermark_upgrade(){
        		window.open('http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/');
        		return false;
			}
			
			
			function  try_sig_watermark(){
        		window.open('http://wordpress.org/extend/plugins/signature-watermark/');
        		return false;
			}
		
			function  try_bulk_watermark(){
        		window.open('http://wordpress.org/extend/plugins/bulk-watermark/');
        		return false;
			}
			

			
	
			function  trans_watermark_learn_more(){
        		window.open('http://mywebsiteadvisor.com/plugins/transparent-image-watermark/');
        		return false;
			}
			
			function  bulk_watermark_learn_more(){
        		window.open('http://mywebsiteadvisor.com/plugins/bulk-watermark/');
        		return false;
			}
			
			function  sig_watermark_learn_more(){
        		window.open('http://mywebsiteadvisor.com/plugins/signature-watermark/');
        		return false;
			}
			
			
			
			
			function compare_watermark_plugins(){
        		window.open('http://mywebsiteadvisor.com/tools/wordpress-plugins/watermark-plugins-for-wordpress/');
        		return false				
			}
			
		</script>";
		
		//transparent watermark ultra
		$html .= "</form><h2>Upgrade to Transparent Watermark Ultra Today!</h2>";
		
		$html .= "<p><b>Premium Features include:</b></p>";
		
		$html .= "<ul class='upgrade_features'>";
		$html .= "<li>Fully Adjustable Watermark Position</li>";
		$html .= "<li>Manually watermark images using the WordPress Media Library</li>";	
		$html .= "<li>Highest Quality Watermarks</li>";
		$html .= "<li>Lifetime Priority Support and Update License</li>";
		$html .= "</ul>";
		
		$html .=  '<div style="padding-left: 1.5em; margin-left:5px;">';
		$html .= "<p class='submit'>";
		$html .= "<input type='submit' class='button-primary' value='Upgrade to Transparent Watermark Ultra &raquo;' onclick='return trans_watermark_upgrade()'> &nbsp;";
		$html .= "<input type='submit' class='button-secondary' value='Learn More &raquo;' onclick='return trans_watermark_learn_more()'>";
		$html .= "</p>";		
		$html .=  "</div>";


		$html .=  "<hr/>";
		
		
		//signature watermark 
		$html .= "<h2>Also Try Signature Watermark!</h2>";
		$html .= "Signature Watermark Plugin adds text and/or image watermarks to each new image as they are uploaded.";
		
		$html .=  '<div style="padding-left: 1.5em; margin-left:5px;">';
		$html .= "<p class='submit'>";
		$html .= "<input type='submit' class='button-primary' value='Try Signature Watermark &raquo;' onclick='return try_sig_watermark()'> &nbsp;";
		$html .= "<input type='submit' class='button-secondary' value='Learn More &raquo;' onclick='return sig_watermark_learn_more()'>";
		$html .= "</p>";
		$html .=  "</div>";
		
		
		$html .=  "<hr/>";


		//bulk watermark
		$html .= "<h2>Also Try Bulk Watermark!</h2>";
		$html .= "Bulk Watermark Plugin adds text and/or image watermarks to images which have already been uploaded to your Media Library.";
		
		$html .=  '<div style="padding-left: 1.5em; margin-left:5px;">';
		$html .= "<p class='submit'>";
		$html .= "<input type='submit' class='button-primary' value='Try Bulk Watermark &raquo;' onclick='return try_bulk_watermark()'> &nbsp;";
		$html .= "<input type='submit' class='button-secondary' value='Learn More &raquo;' onclick='return bulk_watermark_learn_more()'>";
		$html .= "</p>";
		$html .=  "</div>";
		
		
		$html .=  "<hr/>";
		

		$html .=  '<div style="padding-left: 1.5em; margin-left:5px;">';
		$html .= "<p class='submit'><input type='submit' class='button-primary' value='Click Here to Compare All of Our Watermark Plugins &raquo;' onclick='return compare_watermark_plugins()'></p>";
		$html .=  "</div>";
		
		echo $html;
	}

	
	
	
	
	
	
	
	
	
	
	
	

	public function show_watermark_preview(){
		$img_url = admin_url()."options-general.php?page=".$this->setting_name."&action=watermark_preview";
		echo "<img src=$img_url width='100%'>";
		echo "<p><strong>You can customize the preview image by replacing the image named ";
		echo " <a href='".$this->plugin_url."example.jpg' target='_blank'>'example.jpg'</a> in the plugin directory.</strong></p>";
	}
	
	
 

	// displays the plugin options array
	public function show_plugin_settings(){
				
		echo "<pre>";
			print_r($this->opt);
		echo "</pre>";
			
	}
	
	
	
	
	public function show_plugin_tutorual(){
	
		echo "<style>
		.videoWrapper {
			position: relative;
			padding-bottom: 56.25%; /* 16:9 */
			padding-top: 25px;
			height: 0;
		}
		.videoWrapper iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}
		</style>";

		$video_id = $this->youtube_id;
		echo sprintf( '<div class="videoWrapper"><iframe width="640" height="360" src="http://www.youtube.com/embed/%1$s?rel=0&vq=hd720" frameborder="0" allowfullscreen ></iframe></div>', $video_id);
		
	
	}






	public function attachment_field_add_watermark($form_fields, $post){
    		if ($post->post_mime_type == 'image/jpeg' || $post->post_mime_type == 'image/gif' || $post->post_mime_type == 'image/png') {
				
            	$show_on_upload_screen = $this->opt['watermark_settings']['show_on_upload_screen'];
				if($show_on_upload_screen === "true"){	 
				                           
					$form_js = "<style>
						#watermark_preview {
							position:absolute;
							border:1px solid #ccc;
							background:#333;
							padding:5px;
							display:none;		 
							color:#fff;
						}
						#watermark_preview img {
							max-width:300px;  
							max-height:300px;     
							z-index:200000;                                    
						} 
						
						p#watermark_preview {
							z-index:200000; 
						}   
														 
					</style>";    

				  
				  
				  		$image_url = $post->guid;             
						                 							
                       $attachment_info =  wp_get_attachment_metadata($post->ID);  
						
						$bk_meta = get_post_meta($post->ID, '_watermark_backups', true);   
						  
                       $sizes = array();                           
                       if(isset($attachment_info) && isset($attachment_info['sizes']) ){                           
						  foreach($attachment_info['sizes'] as $name => $size){
								$sizes[$name] = $size;
						  }
					   }
                  	krsort($sizes);
                  
				  
    				//get the filename with extension (stip url path) for the upload
                  	$path_info = pathinfo($image_url);
                  	$base_filename = $path_info['basename'];		
 
					
					//get uploads sub dir
					$uploads_subdir = "/" . str_replace($base_filename, "", $attachment_info['file']);
  
				  
					//basic uloads location info				  
                  	$upload_dir   = wp_upload_dir();
					$base_path = $upload_dir['basedir'];
					$base_url = $upload_dir['baseurl'];

				  
					$file_path = $base_path  . $uploads_subdir . $base_filename;
					$file_url = $base_url   . $uploads_subdir . $base_filename;
					
					
                  
				  	$form_fields['image-watermark-header']  = array(
            			'label'      => __('<h3>Transparent Watermark Settings</h3>', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => '<input type="hidden">');
						
					
					 $time = file_exists($file_path) ? filemtime($file_path) : rand(10000,500000);
					 
					$checked = "";
					$disabled = "";
						
					if(isset($bk_meta) && is_array($bk_meta)){	
						foreach($bk_meta as $key => $bk){
							if($bk['original_path'] == $file_path){
								$checked = 'checked="checked" ';	
								$disabled = 'disabled="disabled" ';	
							}
						}
					}
					
  				$form_html = "<p><input type='checkbox' name='attachment_size[]' value='".$post->guid."' style='width:auto;' ".$checked." " . $disabled . "   class='attachment_sizes'> ";
                $form_html .= " <a class='watermark_preview' href='".$post->guid."?". $time ."' title='$base_filename Preview' target='_blank'>" . $base_filename . "</a></p>";
                  $form_html .= $form_js;
				  
				  $form_fields['image-watermark-fullsize']  = array(
            			'label'      => __('Fullsize', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);
				  
				  
                   foreach($sizes as $name => $size){
              
						$image_link = $base_url . $uploads_subdir .$size['file'];
						$current_filepath = $base_path  . $uploads_subdir . $size['file'];
						$time = file_exists($current_filepath) ? filemtime($current_filepath) : rand(10000,500000);
						
                    
						$checked = "";
						$disabled = "";
							
						if( isset($bk_meta) && is_array($bk_meta) ){	
							foreach($bk_meta as $key => $bk){
								if($bk['original_path'] == $current_filepath){
									$checked = 'checked="checked" ';	
									$disabled = 'disabled="disabled" ';	
								}
							}
						}
						
						$form_html = "<p><input type='checkbox' name='attachment_size[]' value='".$base_path.$size['file']."' style='width:auto;' ".$checked." " . $disabled . "  class='attachment_sizes'> ";
						$form_html .= " <a class='watermark_preview' title='".$size['file']." Preview'  href='".$image_link."?". $time ."' target='_blank'>" . $size['file'] . "</a></p>";
					
						$id = 'image-watermark-' . $size['width'] . "x" . $size['height'];
					
					
						 $form_fields[ $id ]  = array(
            			'label'      => __(ucwords($name), 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);
					
                  }

                  
                  	$form_html = "<input type='button' class='button-primary' name='Add Watermark's value='Add Watermarks' onclick='image_add_watermark();'> ";
                 
				 	if($bk_meta != ''){
				 		$form_html .= "<input type='button' class='button' name='Remove Watermarks' value='Remove All Watermarks' onclick='image_revert_watermarks();'>";
					}
					
					
					
					$revert_watermarks_nonce = wp_create_nonce("revert-watermarks");
					
					$attachment_id = $post->ID;
					
				  $form_html .= "<script type='text/javascript'>
                  
				  				var el = jQuery('.compat-attachment-fields');
				                 jQuery(el).ready(function(){
                                              imagePreview();
                                      });       
                  		
									function image_add_watermark(){
										var upgrade = confirm('Sorry, This feature is only available in the Ultra Version!   Press Ok if you would like to Learn More!');
										
										 if (upgrade == true) window.open('http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/');
									 }
									 
									 
									 function image_revert_watermarks(){
										 
										 var allVals = [];
                                                 jQuery('.attachment_sizes:checked').each(function() {
                                                   allVals.push(jQuery(this).val());
                                                 });
                  
										 
										 var ajax_data = {
											 'post_id' : $attachment_id,
											 'images_url_list': allVals, 
											 'revert_watermarks':'revert_watermarks', 
											 'action': 'revert_watermarks', 
											 'security': '$revert_watermarks_nonce'
										 };
                                                  
                                                  jQuery.ajax({
                                                    type: 'POST',
                                                    url:  ajaxurl,
                                                    data: ajax_data,
                                                    success: function(data){
                                                  	alert(data);
                                                      	location.reload();
                                                    }
                                                  });
										 
									 }
									 
									 
								 
								setTimeout(imagePreview, 100);
                                                                                        
                                      </script>";        
                       
                         $form_fields['image-watermark']  = array(
            			'label'      => __('', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html);      
							   
							   	
							   
					}
                }
				
			return $form_fields;   

    	}     
		
		
	
	
	
	
	
		/**
	 * List all fonts from the fonts dir
	 *
	 * @return array
	 */
	private function get_font_list() {
		$plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), null, plugin_basename(__FILE__));
		$fonts_dir =  $plugin_dir . DIRECTORY_SEPARATOR . "fonts";

		$fonts = array();
		try {
			$dir = new DirectoryIterator($fonts_dir);

			foreach($dir as $file) {
				if($file->isFile()) {
					$font = pathinfo($file->getFilename());

					if(strtolower($font['extension']) == 'ttf') {
						if(!$file->isReadable()) {
							$this->_messages['unreadable-font'] = sprintf('Some fonts are not readable, please try chmoding the contents of the folder <strong>%s</string> to writable and refresh this page.', $this->_plugin_dir . $this->_fonts_dir);
						}

						$fonts[$font['basename']] = str_replace('_', ' ', $font['filename']);
					}
				}
			}

			ksort($fonts);
		} catch(Exception $e) {}

		return $fonts;
	}

			
			
			
			
			

	// add plugin js file
	public function add_watermark_js(){
		
		wp_enqueue_script('transparent-watermark-script', $this->plugin_url . "watermark.js");
		
	}
	
	
	
	
	// deletes backup image files when the main image attachment is deleted
	function delete_attachment_watermark_backups($attachment_id){
		
		$bk_meta = get_post_meta($attachment_id, '_watermark_backups', true);
		
		if(isset($bk_meta) && is_array($bk_meta)){
			foreach($bk_meta as $key => $info){
				@unlink( $info['bk_path'] );
			}
		}
		
	}



	// overwrite the watermarked copy with the backup copy
	function revert_watermarks(){
		
		check_ajax_referer( 'revert-watermarks', 'security' );
		
		$attachment_id = $_POST['post_id'];
		
		$bk_meta = get_post_meta($attachment_id, '_watermark_backups', true);
		
		if(isset($bk_meta)){
			foreach($bk_meta as $key => $info){
				
				@unlink( $info['original_path'] );
				copy( $info['bk_path'] , $info['original_path'] );
				@unlink( $info['bk_path'] );
				echo "Removed Watermark: " . $info['original_path'] . "\r\n";
				
			}
		}
		
		echo "Done!!!";
		
		delete_post_meta($attachment_id, '_watermark_backups');
		
		die();
		
	}
	
	
	
		
}
 
 
?>
