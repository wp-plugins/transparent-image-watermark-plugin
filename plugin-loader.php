<?php
/*
Plugin Name: Transparent Image Watermark
Plugin URI: http://MyWebsiteAdvisor.com/tools/wordpress-plugins/transparent-image-watermark/
Description: Add transparent PNG image watermark to your uploaded images.
Version: 1.7
Author: MyWebsiteAdvisor
Author URI: http://MyWebsiteAdvisor.com
*/

register_activation_hook(__FILE__, 'transparent_watermark_activate');

// display error message to users
if ($_GET['action'] == 'error_scrape') {                                                                                                   
    die("Sorry, Transparent Watermark Plugin requires PHP 5.0 or higher. Please deactivate Transparent Watermark Plugin.");                                 
}

function transparent_watermark_activate() {
	if ( version_compare( phpversion(), '5.0', '<' ) ) {
		trigger_error('', E_USER_ERROR);
	}
}

// require Transparent Watermark Plugin if PHP 5 installed
if ( version_compare( phpversion(), '5.0', '>=') ) {
	define('TW_LOADER', __FILE__);

	require_once(dirname(__FILE__) . '/transparent-watermark.php');
	require_once(dirname(__FILE__) . '/plugin-admin.php');
	
	$watermark = new Transparent_Watermark_Admin();

}
?>