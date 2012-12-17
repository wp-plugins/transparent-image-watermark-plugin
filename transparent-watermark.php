<?php

class Transparent_Watermark {
	/**
	 * Transparent Watermark version
	 *
	 * @var string
	 */
	public $version                 = '2.1';
	
	/**
	 * Array with default options
	 *
	 * @var array
	 */
	protected $_options             = array(
		'show_on_upload_screen' => true,
		'watermark_on'       => array(),
		'watermark_type' =>	'image',
		'watermark_image'	=> array(
			'url' => null,
			'width' => 80
		)
	);
	
	/**
	 * Plugin work path
	 *
	 * @var string
	 */
	protected $_plugin_dir          = null;
	
	/**
	 * Settings url
	 *
	 * @var string
	 */
	protected $_settings_url        = null;

	
	/**
	 * Get option by setting name with default value if option is unexistent
	 *
	 * @param string $setting
	 * @return mixed
	 */
	protected function get_option($setting) {
	    if(is_array($this->_options[$setting])) {
	        $options = array_merge($this->_options[$setting], get_option($setting));
	    } else {
	        $options = get_option($setting, $this->_options[$setting]);
	    }

	    return $options;
	}
	
	/**
	 * Get array with options
	 *
	 * @return array
	 */
	private function get_options() {
		$options = array();
		
		// loop through default options and get user defined options
		foreach($this->_options as $option => $value) {
			$options[$option] = $this->get_option($option);
		}
		
		return $options;
	}
	
	/**
	 * Merge configuration array with the default one
	 *
	 * @param array $default
	 * @param array $opt
	 * @return array
	 */
	private function merge_conf_array($default, $opt) {
		foreach($default as $option => $values)	{
			if(!empty($opt[$option])) {
				$default[$option] = is_array($values) ? array_merge($values, $opt[$option]) : $opt[$option];
				$default[$option] = is_array($values) ? array_intersect_key($default[$option], $values) : $opt[$option];
			}
		}

		return $default;
    }
	
	/**
	 * Plugin installation method
	 */
	public function activate_watermark() {
		// record install time
		add_option('watermark_installed', time(), null, 'no');
				
		// loop through default options and add them into DB
		foreach($this->_options as $option => $value) {
			add_option($option, $value, null, 'no');	
		}
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
		$watermark_on = $this->get_option('watermark_on');
			
		if(isset($data['file'])){
			$mime_type = wp_check_filetype($upload_dir['basedir'] . DIRECTORY_SEPARATOR . $data['file']);
			$allowed_types = array('jpg', 'png', 'gif');
			
			if(in_array($mime_type['ext'], $allowed_types)){
			
				// loop through image sizes ...
				foreach($watermark_on as $image_size => $on) {
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
	
	
	/**
	 * Apply watermark to certain image
	 *
	 * @param string $filepath
	 * @return boolean
	 */
	public function do_watermark($filepath) {
		// get image mime type
		$mime_type = wp_check_filetype($filepath);
		
		$allowed_types = array('jpg', 'png', 'gif');
		
		if(in_array($mime_type['ext'], $allowed_types)){
			$mime_type = $mime_type['type'];
			
			// get watermark settings
			$options = $this->get_options();
	
			// get image resource
			$image = $this->get_image_resource($filepath, $mime_type);
	
			if($options['watermark_type'] == "image"){
				// add watermark image to image
				$this->image_add_watermark_image($image, $options);
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
	private function image_add_watermark_image($image, array $opt) {
		// get size and url of watermark
		$size  =  $opt['watermark_image']['width'] / 100;
		$url  =  $opt['watermark_image']['url'];
		
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