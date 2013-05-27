<?php



class Transparent_Watermark_Tools{
	
	
	//holds plugin options
	public $opt;
	
	//holds basic plugin config locations
	public $plugin_path;
	public $plugin_dir;
	public $plugin_url;
	
	
	
	//initialize plugin
	public function __construct(){

		$this->plugin_path = DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), null, plugin_basename(__FILE__));
		$this->plugin_dir = WP_PLUGIN_DIR . $this->plugin_path;
		$this->plugin_url = WP_PLUGIN_URL . $this->plugin_path;
		
	}

	
	
	
	/**
	 * Apply watermark to selected image sizes
	 *
	 * @param array $data
	 * @return array
	 */
	public function apply_watermark( $metadata, $attachment_id) {
		// get settings for watermarking
		$upload_dir   = wp_upload_dir();
		$options = $this->opt;
		$watermark_sizes = $options['watermark_settings']['image_sizes'];
		$watermark_types = $options['watermark_settings']['image_types'];
			
		if(isset($metadata['file'])){
			$mime_type = wp_check_filetype($upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file']);
			
			//$allowed_types = array('jpg', 'png', 'gif');
			$allowed_types = array_keys( $watermark_types );
			
			if(in_array($mime_type['ext'], $allowed_types)){
			
				// loop through image sizes ...
				foreach($watermark_sizes as $image_size => $on) {
					if($on == true) {
						switch($image_size) {
							case 'fullsize':
								$filepath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file'];
								break;
							default:
								if(!empty($metadata['sizes']) && array_key_exists($image_size, $metadata['sizes'])) {
									$filepath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . dirname($metadata['file']) . DIRECTORY_SEPARATOR . $metadata['sizes'][$image_size]['file'];
								} else {
									// early getaway
									continue 2;
								}	
						}
						
						// ... and apply watermark
						$this->do_watermark($filepath, $attachment_id);
					}
				}
			}
		}
		
		// pass forward attachment metadata
		return $metadata;
	}
	
	
	
	
	public function do_watermark_preview(){

		$options = $this->opt;
	
		$filepath = $this->plugin_dir . "/example.jpg";
	
		$mime_type = wp_check_filetype($filepath);
		$mime_type = $mime_type['type'];

		// get image resource
		$image = $this->get_image_resource($filepath, $mime_type);
		

		
		// add watermark image to image
		if($options['watermark_settings']['watermark_type'] == "text-only"){
		
			$this->apply_watermark_text($image, $options);
			
		}elseif($options['watermark_settings']['watermark_type'] == "image-only"){
		
			$this->apply_watermark_image($image, $options);
			
		}	
		
		
		// Set the content-type
		header('Content-type: image/jpg');

		// Output the image using imagejpg()
		imagejpeg($image, null, 100);
		imagedestroy($image);
	}






	/**
	 * Apply watermark to certain image
	 *
	 * @param string $filepath
	 * @return boolean
	 */
	public function do_watermark($filepath, $attachment_id) {
		
		//get plugin options
		$options = $this->opt;
		
		// get image mime type
		$mime_type = wp_check_filetype($filepath);
		
		$watermark_types = $options['watermark_settings']['image_types'];
		$allowed_types = array_keys( $watermark_types );
		
		if(in_array($mime_type['ext'], $allowed_types)){
			$mime_type = $mime_type['type'];
	
			// get image resource
			$image = $this->get_image_resource($filepath, $mime_type);
			
			
			//create backup image if not disabled
			if($this->opt['watermark_settings']['watermark_backup'] !== 'backup-disabled'){
				
				$this->save_image_backup_file($image, $mime_type, $filepath, $attachment_id);
				
			}
		
		
			// add watermarks  to image
			if($options['watermark_settings']['watermark_type'] == "text-only"){
			
				$this->apply_watermark_text($image, $options);
				
			}elseif($options['watermark_settings']['watermark_type'] == "image-only"){
			
				$this->apply_watermark_image($image, $options);
				
			}	
		
			
			// save watermarked image
			return $this->save_image_file($image, $mime_type, $filepath);
		}
	}
	


	
	
	/**
	 * Add watermark image to image
	 *
	 * @param resource $image
	 * @param array $opt
	 * @return resource
	 */
	private function apply_watermark_image($image, array $opt) {
		// get size and url of watermark
		$size  =  $opt['image_watermark_settings']['watermark_image_width'] / 100;
		$url  =  $opt['image_watermark_settings']['watermark_image_url'];
		
		$watermark = imagecreatefrompng("$url"); 
		$watermark_width = imagesx($watermark);
		$watermark_height = imagesy($watermark);
				
		$img_width = imagesx($image);
		$img_height = imagesy($image);
					
		$ratio = (($img_width * $size) / $watermark_width);
			
		$w =($watermark_width * $ratio);
		$h = ($watermark_height * $ratio);
		
		$dest_x = ($img_width/2) - ($w/2);
		$dest_y = ($img_height/2) - ($h/2);
		

		imagecopyresized($image, $watermark, $dest_x, $dest_y, 0, 0, $w, $h, $watermark_width, $watermark_height);
		
		return $image;
	}
	
	



	private function apply_watermark_text($image, array $opt) {
		
		$text  =  $opt['text_watermark_settings']['watermark_text'];
		$text_size = $opt['text_watermark_settings']['watermark_text_width'] / 100;
		$text_color  =  $opt['text_watermark_settings']['watermark_text_color'];
		$text_transparency  =  $opt['text_watermark_settings']['watermark_text_transparency'];
		
		$v_pos = .5;
		$h_pos = .5;
		
		//get size of image watermark will be applied to.
		$img_width = imagesx($image);
		$img_height = imagesy($image);
		
		//fix font path
		$opt    = $this->get_full_font_path($opt);
		
		//calculate font size as well as the size of the text
		$font_size = $this->calculate_font_size($opt, $img_width);
		$text_size = $this->calculate_text_box_size($opt, $font_size);

		//calculate where to position the text
		$txt_dest_x = ($img_width * $h_pos) - ($text_size['width']/2);
		$txt_dest_y = ($img_height * $v_pos ) + ($text_size['height']/2);
			
		// allocate text color
		$text_transparency =  (int) (($text_transparency/100) * 127);
		$text_color  = $this->image_transparent_color_allocate_hex($image, $text_color, $text_transparency);

		
		// Add the text to image
		imagettftext($image, $font_size, 0, $txt_dest_x, $txt_dest_y, $text_color, $opt['text_watermark_settings']['watermark_font'], html_entity_decode($text));

		return $image;
	}
	
	




	/**
	 * Get fullpath of font
	 *
	 * @param array $opt
	 * @return unknown
	 */
	private function get_full_font_path(array $opt) {
		$opt['text_watermark_settings']['watermark_font'] = $this->plugin_dir . "/fonts/" . $opt['text_watermark_settings']['watermark_font'];

		return $opt;
	}



	/**
	 * Allocate a color for an image from HEX code
	 *
	 * @param resource $image
	 * @param string $hexstr
	 * @return int
	 */
	private function image_transparent_color_allocate_hex($image, $hexstr, $transparency) {
		return imagecolorallocatealpha($image,
			hexdec(substr($hexstr,0,2)),
			hexdec(substr($hexstr,2,2)),
			hexdec(substr($hexstr,4,2)),
			$transparency
		);
	}
	


	/**
	 * Calculate text bounting box size
	 *
	 * @param array $opt
	 * @param int $font_size
	 * @return array $size
	 */
	private function calculate_text_box_size(array $opt, $font_size){
	
		$bbox = imagettfbbox(
			$font_size,
			0,
			$opt['text_watermark_settings']['watermark_font'],
			html_entity_decode($opt['text_watermark_settings']['watermark_text'])
		);

		//calculate height and width of text
		$size['width'] = $bbox[4] - $bbox[0];
		$size['height'] = $bbox[1] - $bbox[7];

		return $size;
	
	}
	
	
	
	

	/**
	 * Calculate font size
	 *
	 * @param array $opt
	 * @param int $width
	 * @return int $font_size
	 */
	private function calculate_font_size(array $opt, $width) {

		$font_size = 72;
		$size = $this->calculate_text_box_size($opt, $font_size);

		//calculate font size needed to fill the desired watermark text width, based on size of original image
		$font_size_ratio = (($opt['text_watermark_settings']['watermark_text_width'] / 100) * $width)  / $size['width'];
		
		$font_size = $font_size * $font_size_ratio;
			

		
		return $font_size;
	}





	
	
	
	
	/**
	 * Get array with image size
	 *
	 * @param resource $image
	 * @return array
	 */
	private function get_image_size($image) {
		return array(
			'x' => imagesx($image),
			'y' => imagesy($image)
		);
	}
	

	
	/**
	 * Get image resource accordingly to mimetype
	 *
	 * @param string $filepath
	 * @param string $mime_type
	 * @return resource
	 */
	private function get_image_resource($filepath, $mime_type) {
		switch ( $mime_type ) {
			case 'image/jpeg':
				return imagecreatefromjpeg($filepath);
			case 'image/png':
				$image = imagecreatefrompng($filepath);
				imagealphablending($image, true);
				imagesavealpha($image, true);
				return $image;
			case 'image/gif':
				return imagecreatefromgif($filepath);
			default:
				return false;
		}
	}
	
	
	/**
	 * Save image from image resource
	 *
	 * @param resource $image
	 * @param string $mime_type
	 * @param string $filepath
	 * @return boolean
	 */
	private function save_image_file($image, $mime_type, $filepath) {
		switch ( $mime_type ) {
			case 'image/jpeg':
				return imagejpeg($image, $filepath, 90);
			case 'image/png':
				return imagepng($image, $filepath);
			case 'image/gif':
				return imagegif($image, $filepath);
			default:
				return false;
		}
	}
	
	
	
	
	/**
	 * Save image backup file from image resource
	 *
	 * @param resource $image
	 * @param string $mime_type
	 * @param string $filepath
	 * @return boolean
	 */
	private function save_image_backup_file($image, $mime_type, $filepath, $attachment_id) {
		
		$path_info = pathinfo($filepath);
		$suffix = time() . rand(100, 999);
		$backup_file_path = $path_info['dirname'] ."/". $path_info['filename'] . "-" . $suffix . "." . $path_info['extension'];
		
		$key = $path_info['filename'];
		
		if( get_post_meta($attachment_id, '_watermark_backups', true) ){
			$bk_meta = get_post_meta($attachment_id, '_watermark_backups', true);
		}else{
			$bk_meta = array();
		}
		
		

		if(!array_key_exists($key, $bk_meta)){
			
			$bk_meta[$key]['bk_path']	 		= $backup_file_path;
			$bk_meta[$key]['original_path'] 	= $filepath;
		
		
			update_post_meta( $attachment_id, '_watermark_backups', $bk_meta);
			
			
			switch ( $mime_type ) {
				case 'image/jpeg':
					return imagejpeg($image, $backup_file_path, 90);
				case 'image/png':
					return imagepng($image, $backup_file_path);
				case 'image/gif':
					return imagegif($image, $backup_file_path);
				default:
					return false;
			}
			
		}
		
	}
	
	
	
		
	function get_relative_path($from, $to){
		$from     = explode('/', $from);
		$to       = explode('/', $to);
		$relPath  = $to;
	
		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}
	
	
}


?>