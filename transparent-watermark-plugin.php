<?php


 
class Transparent_Watermark_Plugin{

	//plugin version number
	private $version = "2.3.3";
	
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
			$this->tools->do_watermark_preview();
			die();
		}
		
		// check if post_id is "-1", meaning we're uploading watermark image
		if(!(array_key_exists('post_id', $_REQUEST) && $_REQUEST['post_id'] == -1)) {
		
			// add filter for watermarking images
			add_filter('wp_generate_attachment_metadata', array(&$this->tools, 'apply_watermark'));
			
		}
		
		
		$show_on_upload_screen = $this->opt['watermark_settings']['show_on_upload_screen'];			 
		if($show_on_upload_screen === "true"){	
		
			add_filter('attachment_fields_to_edit', array(&$this, 'attachment_field_add_watermark'), 10, 2);
			
		}
				
	
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
			
				//$this->update_plugin_settings();
				
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

								
        return $settings_sections;
    }


	
	

    /**
     * Returns all of the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
		

		$settings_fields = array(
			'watermark_settings' => array(
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
                        'png' => '.PNG',
                        'gif' => '.GIF'
                    )
                ),
				array(
					'name' => 'watermark_image_url',
					'label' => __( 'Watermark Image URL', $this->plugin_name ),
					'desc' => 'Configure the Watermark Image URL',
					'type' => 'url',
					'value' => ""
				),
				array(
					'name' => 'watermark_image_width',
					'label' => __( 'Watermark Image Width', $this->plugin_name ),
					'desc' => 'Configure the Watermark Image Width (Percentage)',
					'type' => 'percentage',
					'default' => "50"
				),
                array(
                    'name' => 'show_on_upload_screen',
                    'label' => __( 'Show Advanced Preview', $this->plugin_name ),
                    'desc' => __( "Show Preview of Advanced Watermark Features on Upload Screen<br>(Feature Available in Ultra Version Only, <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
                    'type' => 'radio',
                    'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
                    )
                ),
                array(
                    'name' => 'enable_hq_watermarks',
                    'label' => __( 'High Quality Watermarks', $this->plugin_name ),
                    'desc' => __( "Enable Watermark Resampling which will result in Higher Quality watermarks.<br>(Feature Available in Ultra Version Only, <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Click Here for More Information!</a>)", $this->plugin_name ),
                    'action' => 'Enable',
					'type' => 'checkbox',
                    'enabled' => 'false'
                )
			)
		);
		
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
			

			if( ini_get('disable_functions') !== '' ){
				echo "<p><font color='red'>Disabled PHP Functions Detected!<br><b>Please enable these functions in php.ini!</b></font></p>";
			}else{
				echo "<p>Disabled PHP Functions: None Found!</p>";
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
	
		$plugin_resources = "<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Plugin Homepage</a></p>
			<p><a href='http://mywebsiteadvisor.com/learning/video-tutorials/transparent-image-watermark-tutorial/'  target='_blank'>Plugin Tutorial</a></p>
			<p><a href='http://mywebsiteadvisor.com/support/'  target='_blank'>Plugin Support</a></p>
			<p><a href='http://wordpress.org/support/view/plugin-reviews/transparent-image-watermark-plugin?rate=5#postform'  target='_blank'>Rate and Review This Plugin</a></p>";
	
		$more_plugins = "<p><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
			<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
			<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/'  target='_blank'>Free Plugins on MyWebsiteAdvisor.com!</a></p>";
	
		$follow_us = "<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
			<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
			<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
			<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>";
	
		$upgrade = "	<p>
			<a href='http://mywebsiteadvisor.com/products-page/premium-wordpress-plugin/transparent-watermark-plugin-ultra-version/'  target='_blank'>Upgrade to Transparent Watermark Ultra!</a><br />
			<br />
			<b>Features:</b><br />
				-Manually Add Watermarks<br />
	 			-Change Watermark Position<br />
	 			-Add High Quality Watermarks<br />
	 			-And Much More!<br />
			 </p>
			<p>Click Here for <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/watermark-plugins-for-wordpress/' target='_blank'>More Watermark Plugins</a></p>
			<p>-<a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/bulk-watermark/' target='_blank'>Bulk Watermark</a></p>
			<p>-<a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/signature-watermark/' target='_blank'>Signature Watermark</a></p>
			<p>-<a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>Transparent Image Watermark</a></p>
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
			
				
			$faqs = "<p><b>Question: Why am I getting low quality watermarks?</b><br>Answer: The plugin needs to change the size of your watermark image, according to the size of your original image.  You should use a watermark image that is roughly the same width as your largest images intended to be watermarked.  That way the plugin will scale them down, resulting in no loss of quality.  When the plugin is forced to do the opposite and increase the size of a small watermark image, low quality watermarks may occur.</p>";
			$faqs .= "<p><b>Question: How can I remove the watermarks?</b><br>Answer: This plugin permenantly alters the images to contain the watermarks, so the watermarks can not be removed.  If you want to simply test this plugin, or think you may want to remove the watermarks, you need to make a backup of your images before you run the plugin to add watermarks.</p>";

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
			
			/**
			$screen->add_help_tab(array(
				'id' => 'plugin-upgrades',
				'title' => "Plugin Upgrades",
				'content' => "<h2>Plugin Upgrades</h2><p>We also offer a premium version of this pluign with extended features!<br>You can learn more about it here: <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/transparent-image-watermark/' target='_blank'>MyWebsiteAdvisor.com</a></p><p>Learn more about our different watermark plugins for WordPress here: <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/watermark-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p><p>Learn about all of our free plugins for WordPress here: <a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p>"
			));
			**/
			
			$screen->add_help_tab(array(
				'id' => 'upgrade_plugin',
				'title' => __( 'Plugin Upgrades', $this->plugin_name ),
				'content' => $this->get_plugin_upgrades()		
			));	
			
			$screen->set_help_sidebar("<p>Please Visit us online for more Free WordPress Plugins!</p><p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p><br>");
			
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
			$upgrade_url = 'http://mywebsiteadvisor.com/products-page/premium-wordpress-plugin/transparent-watermark-plugin-ultra-version/';
			$links[] = '<a href="'.$upgrade_url.'" target="_blank" title="Click Here to Upgrade this Plugin!">Upgrade Plugin</a>';
			
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
		
		$string .= "<li><a href='http://facebook.com/MyWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Facebook'>Click Here</a> to Follow MyWebsiteAdvisor on Facebook!</li>";
		$string .= "<li><a href='http://twitter.com/MWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Twitter'>Click Here</a> to Follow MyWebsiteAdvisor on Twitter!</li>";
		$string .= "<li><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/' target='_blank' title='Click Here to Purchase one of our Premium WordPress Plugins'>Click Here</a> to Purchase Premium WordPress Plugins!</li>";
	
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
			'title' => __( 'Plugin Tutorial Video', $this->plugin_name ),
			'callback' => array(&$this, 'show_plugin_tutorual')
		);
		$this->settings_page->add_section( $plugin_tutorial );
		
		
		
		if(true === $this->debug){
			//general debug settings
			$plugin_debug = array(
				'id' => 'plugin_debug',
				'title' => __( 'Plugin Settings Debug', $this->plugin_name ),
				'callback' => array(&$this, 'show_plugin_settings')
			);
			
			$this->settings_page->add_section( $plugin_debug );
		}	
		
		
				
		$upgrade_plugin = array(
			'id' => 'upgrade_plugin',
			'title' => __( 'Plugin Upgrades', $this->plugin_name ),
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
        		window.open('http://mywebsiteadvisor.com/products-page/premium-wordpress-plugin/transparent-watermark-plugin-ultra-version/');
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
        		window.open('http://mywebsiteadvisor.com/tools/wordpress-plugin/transparent-image-watermark/');
        		return false;
			}
			
			function  bulk_watermark_learn_more(){
        		window.open('http://mywebsiteadvisor.com/tools/wordpress-plugin/bulk-watermark/');
        		return false;
			}
			
			function  sig_watermark_learn_more(){
        		window.open('http://mywebsiteadvisor.com/tools/wordpress-plugin/signature-watermark/');
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
		$html .= "<li>Higher Quality Watermarks</li>";
		$html .= "<li>Priority Support</li>";
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
                       
				//$ajax_url = "../".PLUGINDIR . "/". dirname(plugin_basename (__FILE__))."/watermark_ajax.php";     
				$image_url = $post->guid;                          
                                                  
                                                  
					$form_js = "<style>
					
						#watermark_preview{
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
                  $watermark_image = $this->opt['watermark_settings']['watermark_image_url'];
                  $watermark_width = $this->opt['watermark_settings']['watermark_image_width'];
                  
				  	$form_fields['image-watermark-header']  = array(
            			'label'      => __('<h3>Transparent Watermark Settings</h3>', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => '<input type="hidden">');
						
						
				  
				  
				  	$tooltip = "50 means that the watermark will be 50% of the width of the image being watermarked.";
					$form_html = "<input id='watermark_width' value='$watermark_width' type='text' size='5' style='width:50px !important;'  title='$tooltip' />%";
 					
					
					$form_fields['image-watermark-width']  = array(
            			'label'      => __('Width', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html
					);
				       
					
					$tooltip = "50 means that the image is centered vertically, 10 means it is 10% from the top.";	              
					$form_html = "<input id='watermark_vertical_location' value='$watermark_vertical_location' type='text'  size='5' style='width:50px !important;' title='$tooltip' />%";
					
					$form_html .= $form_js;
					  
					  $form_fields['image-watermark-vertical-location']  = array(
							'label'      => __('Vertical', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html
					);
						
					$tooltip = "50 means that the image is centered horizontally, 10 means it is 10% from the left.";
					$form_html = "<input id='watermark_horizontal_location' value='$watermark_horizontal_location' type='text' size='5' style='width:50px !important;' title='$tooltip' />%";
					
					 $form_fields['image-watermark-horizontal-location']  = array(
            			'label'      => __('Horizontal', 'transparent-watermark'),
            			'input'      => 'html',
            			'html'       => $form_html
					);
						
					  
                  
                 
                  
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
							   
							   
					$show_on_upload_screen = $this->opt['watermark_settings']['show_on_upload_screen'];
							 
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
