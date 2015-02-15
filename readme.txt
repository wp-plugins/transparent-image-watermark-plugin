=== Transparent Image Watermark ===
Name: Transparent Image Watermark
Contributors: MyWebsiteAdvisor, ChrisHurst
Tags: Watermark, Images, Image, Picture, Pictures, Photo, Photos, Upload, Post, posts, Plugin, Page, Admin, Security, administration, automatic, media
Requires at least: 3.3
Tested up to: 4.1
Stable tag: 2.3.15
Donate link: http://MyWebsiteAdvisor.com/donations/


Automatically watermark images as they are uploaded to the WordPress Media Library.



== Description ==
This plugin allows you to Automatically add a watermark to all images as they are uploaded to the WordPress Media Library.
The plugin uses PNG watermark images with transparency for precise control over the appearance of the watermarks.
This plugin also supports simple text watermarks with adjustable color, size and transparency.
The user friendly settings page allows for control over the appearance of your watermark.  
The watermark preview feature allows for easy testing of the plugin settings.
The watermark size is controlled as a percentage of the target image, 50% means the watermark will be half the width of the target image. 
Watermarks are now removable with the new backup system, any images watermarked while the new backup system is enabled are able to be restored to the original image.


<a href="http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/">**Upgrade to Transparent Watermark Ultra**</a> for advanced
watermark features including:

* Manually Apply Watermarks to Images Previously Uploaded
* Fully Adjustable Image Watermark Position
* Fully Adjustable Text Watermark Position
* Adjustable JPEG Image Output Quality
* Highest Quality Watermarks using Image Re-sampling rather than Re-sizing
* Lifetime Priority Support and Update License



Check out the [Transparent Image Watermark for WordPress video](http://www.youtube.com/watch?v=fEhZK1U8W94):

http://www.youtube.com/watch?v=fEhZK1U8W94&hd=1



Developer Website: http://MyWebsiteAdvisor.com/

Plugin Support: http://MyWebsiteAdvisor.com/support/

Plugin Page: http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/

Compare Watermark Plugins: http://MyWebsiteAdvisor.com/tools/wordpress-plugins/watermark-plugins-for-wordpress/

Video Tutorial: http://mywebsiteadvisor.com/learning/video-tutorials/transparent-image-watermark-tutorial/



Requirements:

* PHP v5.0+
* WordPress v3.3+
* GD extension for PHP
* FreeType extension for PHP

To-do:





== Installation ==

1. Upload `transparent-watermark/` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Transparent Watermark settings and enable Transparent Watermark Plugin.


Check out the [Transparent Image Watermark for WordPress video](http://www.youtube.com/watch?v=fEhZK1U8W94):

http://www.youtube.com/watch?v=fEhZK1U8W94&hd=1

Video Tutorial: http://mywebsiteadvisor.com/learning/video-tutorials/transparent-image-watermark-tutorial/




== Frequently Asked Questions ==

= Plugin doesn't work ... =

Please specify as much information as you can to help us debug the problem. 
Check in your error log if you can. 
Please send screenshots as well as a detailed description of the problem.



= Error message says that I don't have GD extension installed =

Contact your hosting provider and ask them to enable GD extension for your host,  GD extension is required for watermarking.



= Error message says that I need to enable the allow_url_fopen option =

Contact your hosting provider and ask them to enable allow_url_fopen, most likely in your php.ini  
It may be necessary to create a php.ini file inside of the wp-admin directory to enable the allow_url_fopen option.

You can also use a relative url path as a workaround, an example is provided on the settings page.


= How do I Remove Watermarks? =

This plugin permanently alters the images to contain the watermarks, so the watermarks can not be removed. 
If you want to simply test this plugin, or think you may want to remove the watermarks, you need to make a backup of your images before you use the plugin to add watermarks.
<a href="http://wordpress.org/extend/plugins/simple-backup/">**Try Simple Backup Plugin**</a>



= How can I Add Watermarks to images that were uploaded before the plugin was installed? = 

We have a premium version of this plugin that adds the capability to manually add watermarks to images in the WordPress Media Library.

<a href="http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/">**Upgrade to Transparent Watermark Ultra**</a> for advanced
watermark features including:

* Manually Apply Watermarks to Images Previously Uploaded
* Fully Adjustable Image Watermark Position
* Fully Adjustable Text Watermark Position
* Adjustable JPEG Image Output Quality
* Highest Quality Watermarks using Image Re-sampling rather than Re-sizing
* Lifetime Priority Support and Update License



= How can I Adjust the Location of the Watermarks? = 

We have a premium version of this plugin that adds the capability to adjust the location of the watermarks.
The position can be adjusted both vertically and horizontally.

<a href="http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/">**Upgrade to Transparent Watermark Ultra**</a> for advanced
watermark features including:

* Manually Apply Watermarks to Images Previously Uploaded
* Fully Adjustable Image Watermark Position
* Fully Adjustable Text Watermark Position
* Adjustable JPEG Image Output Quality
* Highest Quality Watermarks using Image Re-sampling rather than Re-sizing
* Lifetime Priority Support and Update License




= How do I generate the Highest Quality Watermarks? = 

We recommend that your watermark image be roughly the same width as the largest images you plan to watermark.
That way the watermark image will be scaled down, which will work better than making the watermark image larger in order to fit.

We also have a premium version of this plugin that adds the capability to resample the watermark image, rather than simply resize it.
This results in significantly better looking watermarks.

<a href="http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/">**Upgrade to Transparent Watermark Ultra**</a> for advanced
watermark features including:

* Manually Apply Watermarks to Images Previously Uploaded
* Fully Adjustable Image Watermark Position
* Fully Adjustable Text Watermark Position
* Adjustable JPEG Image Output Quality
* Highest Quality Watermarks using Image Re-sampling rather than Re-sizing
* Lifetime Priority Support and Update License



Check out the [Transparent Image Watermark for WordPress video](http://www.youtube.com/watch?v=fEhZK1U8W94):

http://www.youtube.com/watch?v=fEhZK1U8W94&hd=1




Developer Website: http://MyWebsiteAdvisor.com/

Plugin Support: http://MyWebsiteAdvisor.com/support/

Plugin Page: http://MyWebsiteAdvisor.com/plugins/transparent-image-watermark/

Compare Watermark Plugins: http://MyWebsiteAdvisor.com/tools/wordpress-plugins/watermark-plugins-for-wordpress/

Video Tutorial: http://mywebsiteadvisor.com/learning/video-tutorials/transparent-image-watermark-tutorial/







== Screenshots ==

1. General Watermark Settings Page
2. Text Watermark Settings Page
3. Image Watermark Settings Page
4. Watermark Preview Page
6. Finished Example Image




== Changelog ==



= 2.3.15 =
* tested for compatibility with WP 4.1
* fixed issue with watermark settings image preview function
* updated links in readme and plugin for support, updates, etc.


= 2.3.14 =
* Fixed .jpeg file extension issue, Added checkbox option to automatically watermark .jpeg images


= 2.3.13 =
* improved support for adding watermarks to PNG images


= 2.3.12 =
* updated the MyWebsiteAdvisor Plugin Installer Page to include the option to remove the installer page and menu.
* updated links to the plugin installer to use the search by author feature when the plugin installer is disabled.
* added option to the help menu, 'More Free Plugins' section to enable and disable the 'MyWebsiteAdvisor' Plugins installer menu. 


= 2.3.11 =
* fixed attachment_field_add_watermark() so it always returns the $form_fields
* updated ui on edit-media page to further condense the form display


= 2.3.10 =
* more improvements to the edit-media page, condensed form to display better on the insert media sidebar
* fixed typos in readme


= 2.3.9 =
* fixed issue causing error message when deleting non-watermarked image.
* fixed ereg_replace depricated message
* updated the edit media page to display the image size names, rather than the image dimensions



= 2.3.8 =
* fixed text watermark system.
* added image backup system option.
* added remove watermarks button to the edit media screen, will only show up when backups are available.


= 2.3.7 =
* fixed text watermark size adjustment system.


= 2.3.6 =
* updated plugin settings system
* added simple text watermark option
* added info about workaround for users who have 'allow_url_fopen' disabled, using relative path to watermark image.
* updated default JPEG output quality from 100 to 90 to reduce file bloat, added option to ultra version to adjust output quality.
* updated plugin screenshots


= 2.3.5.1 =
* updated links to plugin page

= 2.3.5 =
* updated links to plugin page


= 2.3.4 =
* updated plugin FAQs
* updated readme file


= 2.3.3 =
* updated contextual help, removed deprecated filter and updated to preferred method
* added uninstall and deactivation functions to clear plugin settings
* updated plugin upgrades tab on plugin settings page
* update readme file
* updated broken links


= 2.3.2 =
* added plugin upgrades tab on plugin settings page
* update readme file


= 2.3.1 =
* fixed several bad links
* update readme file


= 2.3 =
* updated plugin to use WordPress settings API
* added tabbed navigation on settings page
* added watermark preview system (preview tab)
* added watermark tutorial video to the plugin admin interface (tutorial video tab)
* updated screenshots
* updated readme, required WP version is 3.3



= 2.2.2 =
* added label elements around checkboxes to make the label text clickable.
* added function exists check for the sys_getloadavg function so it does not cause fatal errors on MS Windows Servers


= 2.2.1 =
* fixed readme file stable tag


= 2.2 =
* updated plugin settings screen
* updated and improved image preview on edit media screen
* added ability to select type of images to auto watermark, for example jpg only


= 2.1 =
* updated readme file.
* fixed several issues causing warnings and notices in debug.log
* added plugin version to plugin diagnostic screen.


= 2.0.7 =
* fixed typo in help menu


= 2.0.6 =
* fixed issues with displaying cached version of images, and not displaying newly watermarked images properly.
* verified compatibility with WordPress v3.5


= 2.0.5 =
* fixed several improper opening php tags


= 2.0.4 =
* added rate this plugin link in plugin row meta links on plugin screen
* added upgrade plugin link in plugin row meta links on plugin screen
* added check for image types before attempting to apply watermark to eliminate error messages.
* resolved warnings about ereg_replace deprecated function
* resolved other notices and warnings about undefined index


= 2.0.3 =
* added link to rate and review this plugin on WordPress.org.


= 2.0.2 =
* cleaned up a leftover/unused debug function.


= 2.0.1 =
* updated plugin activation php version check which was causing out of place errors.


= 2.0 =
* added contextual help menu with faqs and support links
* fixed broken links


= 1.9 =
* added to debug info panel, minor cleanup, fixed broken links


= 1.8 =
* updated image size selection option to resolve issue with non-standard or custom size images not having watermarks applied.


= 1.7 =
* fixed some broken links, other minor cleanup.


= 1.6 =
* improved appearance of plugin settings page, increased output quality of final watermarked image, added plugin diagnostic on settings page.


= 1.5 =
* added option to turn off advanced features preview on upload screen.

= 1.4 =
* added image preview similar to ultra version.


= 1.3 =
* added better error checking on image upload types


= 1.2 =
* Minor cleanup and optimization.


= 1.1 =
* Fixed minor bug causing warning message about "installScripts" function.


= 1.0 =
* Initial release

