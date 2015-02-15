<?php
/*
Plugin Name: Transparent Image Watermark
Plugin URI: http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/
Description: Add a Text or Image watermark to your uploaded images.
Version: 2.3.15
Author: MyWebsiteAdvisor
Author URI: http://MyWebsiteAdvisor.com
*/

register_activation_hook(__FILE__, 'transparent_watermark_activate');

register_uninstall_hook(__FILE__, "transparent_watermark_uninstall");



function transparent_watermark_uninstall(){
	delete_option('transparent-watermark-settings');
	delete_option('mywebsiteadvisor_pluigin_installer_menu_disable');
}



function transparent_watermark_activate() {

	// display error message to users
	if ($_GET['action'] == 'error_scrape') {
		die("Sorry, Transparent Watermark Plugin requires PHP 5.0 or higher. Please deactivate Transparent Watermark Plugin.");
	}

	if ( version_compare( phpversion(), '5.0', '<' ) ) {
		trigger_error('', E_USER_ERROR);
	}
	
}


// require Transparent Watermark Plugin if PHP 5 installed
if ( version_compare( phpversion(), '5.0', '>=') ) {

	define('TW_LOADER', __FILE__);

	include_once(dirname(__FILE__) . '/transparent-watermark-plugin-installer.php');
	
	require_once(dirname(__FILE__) . '/transparent-watermark-settings-page.php');
	require_once(dirname(__FILE__) . '/transparent-watermark-tools.php');
	require_once(dirname(__FILE__) . '/transparent-watermark-plugin.php');

	$transparent_watermark = new Transparent_Watermark_Plugin();

}

?>