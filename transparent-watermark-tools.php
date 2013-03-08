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
	public function apply_watermark($data) {
		// get settings for watermarking
		$upload_dir   = wp_upload_dir();
		$options = $this->opt;
		$watermark_sizes = $options['watermark_settings']['image_sizes'];
		$watermark_types = $options['watermark_settings']['image_types'];
			
		if(isset($data['file'])){
			$mime_type = wp_check_filetype($upload_dir['basedir'] . DIRECTORY_SEPARATOR . $data['file']);
			
			//$allowed_types = array('jpg', 'png', 'gif');
			$allowed_types = array_keys( $watermark_types );
			
			if(in_array($mime_type['ext'], $allowed_types)){
			
				// loop through image sizes ...
				foreach($watermark_sizes as $image_size => $on) {
					if($on == true) {
						switch($image_size) {
							case 'fullsize':
								$filepath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $data['file'];
								break;
							default:
								if(!empty($data['sizes']) && array_key_exists($image_size, $data['sizes'])) {
									$filepath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . dirname($data['file']) . DIRECTORY_SEPARATOR . $data['sizes'][$image_size]['file'];
								} else {
									// early getaway
									continue 2;
								}	
						}
						
						// ... and apply watermark
						$this->do_watermark($filepath);
					}
				}
			}
		}
		
		// pass forward attachment metadata
		return $data;
	}
	
	
	
	
	public function do_watermark_preview(){

		$options = $this->opt;
	
		$filepath = $this->plugin_dir . "/example.jpg";
	
		$mime_type = wp_check_filetype($filepath);
		$mime_type = $mime_type['type'];

		// get image resource
		$image = $this->get_image_resource($filepath, $mime_type);
		

		$this->apply_watermark_image($image, $options);
		
		
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
	public function do_watermark($filepath) {
		
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
	
			
			// add watermark image to image
			$this->apply_watermark_image($image, $options);
		
			
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
		$size  =  $opt['watermark_settings']['watermark_image_width'] / 100;
		$url  =  $opt['watermark_settings']['watermark_image_url'];
		
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
				return imagecreatefrompng($filepath);
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
				return imagejpeg($image, $filepath, 100);
			case 'image/png':
				return imagepng($image, $filepath);
			case 'image/gif':
				return imagegif($image, $filepath);
			default:
				return false;
		}
	}

	
}


?>