<?php
require_once ABSPATH . 'wp-admin/includes/plugin.php';

class Asset_Storage_Manage extends Azure_Storage_And_Cdn{
	
	function __construct($plugin_file_path, $as3cf) {
		$this->blobClient = $as3cf->blobClient;		
		$src = plugins_url( 'assets/js/asset.js', $plugin_file_path );
		wp_enqueue_script( 'azure-asset', $src);
		$this->start($blobClient);
		// print script		
		if($this->get_copy_assets() == true){
			$sc = "https://promactwp.azureedge.net/mycontainer/promact/style.css";
			wp_enqueue_style( 'twentyseventeen-style', $sc );
			add_filter('script_loader_tag',array($this,'scripts'),10,3);
			add_action( 'wp_head', array($this,'scripts'));
			//add_action('wp_footer', array($this,'ajax_copy_assets_manually'));
			//add_action('admin_footer', array($this,'ajax_copy_assets_manually'));
			//add_action( 'wp_print_scripts', array($this,'scripts'));
		}
		/*add_action( 'after_switch_theme', array($this,'theme_switched'));
		echo "<pre>";
		echo WP_PLUGIN_URL;
		$path = WP_PLUGIN_DIR;
		$path = rtrim($path,"/");
		$files = $this->scandir($path,array('js'),3);
		//print_r(($files));
		//print_r(WP_PLUGIN_DIR);
				
				
		/*
		add_filter('cron_schedules', array($this,'new_interval'));
		if ( ! wp_next_scheduled( 'my_cron' ) ) {
			wp_schedule_event(time(),'new_min','my_cron');
		}
		add_action( 'my_cron', array($this,'scan') );
				
		/*print_r(wp_get_theme());print_r(get_template_directory_uri()); echo "<br/>";
		echo get_stylesheet_uri(); echo "<br/>"; echo get_stylesheet_directory_uri(); echo "<br/>";
		echo get_theme_file_uri(); 
		if ( ! is_admin() && ( $GLOBALS['pagenow'] != 'wp-login.php' )) {
			$sc = "https://promactwp.azureedge.net/mycontainer/promact/style.css";
			wp_enqueue_style( 'twentyseventeen-style', $sc );
		}
		$stylesheet = get_stylesheet();
		$theme = wp_get_theme( $stylesheet );
		echo "<pre>"; print_r($stylesheet); echo $theme->get('TextDomain');print_r($theme->get('Name')); 
		print_r($theme);
		print_r($theme->get_files('css', 3));
		print_r($theme->get_files('png', 3));
		print_r($theme->get_files('jpg', 3));
		print_r($theme->get_files('js', 3));exit;*/
		//echo "<pre>"; print_r(glob('D:/wamp/www/wordpress/wp-content/themes/twentyseventeen/*'));exit;
	}
	
	private static function scandir( $path, $extensions = null, $depth = 0, $relative_path = '' ) {
		if ( ! is_dir( $path ) )
			return false;

		if ( $extensions ) {
			$extensions = (array) $extensions;
			$_extensions = implode( '|', $extensions );
		}

		$relative_path = trailingslashit( $relative_path );
		if ( '/' == $relative_path )
			$relative_path = '';

		$results = scandir( $path );
		$files = array();
		foreach ( $results as $result ) {
			if ( '.' == $result[0] )
				continue;
			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth || 'CVS' == $result )
					continue;
				$found = self::scandir( $path . '/' . $result, $extensions, $depth - 1 , $relative_path . $result );
				$files = array_merge_recursive( $files, $found );
			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/'. $result;
			}
		}
		return $files;
	}
	
	function new_interval($interval){
		$interval['new_min'] = array('interval'=>180,'display'   => __( 'Every 3 Minutes', 'textdomain' ));
		return $interval;
	}
	
	/*function scan(){
		add_action( 'wp_print_scripts', array($this,'scripts'));
	}*/
	
	public function scripts($tag, $handle, $src){
		global $wp_scripts;
		global $wp_styles;
				
		//print_r(($wp_styles));exit;
		
		//print_r(($wp_scripts));exit;
		$content_url = WP_CONTENT_URL;
		$path = array();
		foreach( $wp_scripts->registered as $script ) {
			if(strpos($script->src, 'wp-content') !== false){
				$registered['script'][$script->handle] = $script->src;
				if(in_array($script->handle, $wp_scripts->queue)){
					$result['scripts'][$script->handle] = str_replace(WP_CONTENT_URL.'/','',$script->src);
					$path['scripts'][$script->handle] =  plugin_dir_path($script->src);
				}
			}
		}		
		foreach( $wp_styles->registered as $style ) {
			if(strpos($style->src, 'wp-content') !== false){
				$registered['styles'][$style->handle] = $style->src;
				if(in_array($style->handle, $wp_styles->queue)){
					$result['styles'][$style->handle] =  str_replace(WP_CONTENT_URL.'/','',$style->src);
					$path['styles'][$style->handle] =  plugin_dir_path($style->src);	
				}

			}
		}
		echo "<pre>";
		print_r($registered);
		//print_r($path);
		print_r($result); exit;
	}
	public function start($blobClient){
		//ajax request
		add_action('wp_ajax_copy-assets-manually',array($this,'ajax_copy_assets_manually'));
		
	}
	
	function ajax_copy_assets_manually(){
		$stylesheet = get_stylesheet();
		$theme = wp_get_theme( $stylesheet );
		$extension = explode(",",$this->get_asset_extensions());
		$files_to_copy = array();
		add_action( 'wp_print_scripts', array($this,'scripts'));
		global $wp_scripts;
		global $wp_styles;
		foreach( $wp_scripts->registered as $script ) {
			if(strpos($script->src, 'wp-content') !== false){
				$result['scripts'][$script->handle] = $script->src;
			}
		}		
		foreach( $wp_styles->registered as $style ) {
			if(strpos($style->src, 'wp-content') !== false){
				$result['styles'][$style->handle] =  $style->src;
			}
		}
		print_r($result);exit;
		$files = array();
		$themes = wp_get_themes();

		foreach ($themes as $name=>$theme){
			foreach($extension as $ext){				
				$files = $this->get_asset_files($theme,$ext);
				if(count($files) > 0){
					foreach($files as $path=>$url){							
						$files_to_copy[$name][$path] = $url;
					}
				}
			}				
		}
		echo "<pre>";
		print_r($files_to_copy);exit;
	}
	
	public function copy_theme_assets(){
		$stylesheet = get_stylesheet();
		$theme = wp_get_theme( $stylesheet );
	}
	
	public function theme_switched($old_theme_name){
		/*echo "<pre>"; print_r(glob('D:/wamp/www/wordpress/wp-content/themes/twentyseventeen/*'));
		print_r($old_theme_name);print_r(wp_get_theme($old_theme_name));print_r(wp_get_theme());echo get_stylesheet_uri();echo get_theme_file_uri(); exit;
		$sc = "https://promactwp.azureedge.net/mycontainer/promact/style.css";
		wp_enqueue_style( 'twentyseventeen-style', $sc );*/
	}
	
	
	public function get_asset_files($theme,$extension){
		return $theme->get_files($extension,5);
	}

}