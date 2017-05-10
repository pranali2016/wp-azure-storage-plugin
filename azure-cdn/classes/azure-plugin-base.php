<?php

class Azure_Plugin_Base {

	protected $plugin_file_path;
	protected $plugin_dir_path;
	protected $plugin_slug;
	protected $plugin_basename;
	protected $plugin_version;
	private $settings;
	private $defined_settings;
	
	function __construct($plugin_file_path){
		$this->plugin_file_path = $plugin_file_path;
		$this->plugin_dir_path  = rtrim( plugin_dir_path( $plugin_file_path ), '/' );
		$this->plugin_basename  = plugin_basename( $plugin_file_path );		
	}
	
	public function get_asset_version() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;
	}
	
	/**
	 * Get the filename suffix used for script enqueuing
	 *
	 * @return mixed
	 */
	public function get_asset_suffix() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}
	
	/**
	 * Get the plugin's settings array
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	function get_settings( $force = false ) {
		if ( is_null( $this->settings ) || $force ) {
			$this->settings = $this->filter_settings( get_site_option( static::SETTINGS_KEY ) );
		}

		return $this->settings;
	}
	
	/**
	 * Set a setting
	 *
	 * @param $key
	 * @param $value
	 */
	function set_setting( $key, $value ) {
		$this->settings[ $key ] = $value;
	}
	
	/**
	 * Save the settings to the database
	 */
	public function save_settings() {
		$this->update_site_option( static::SETTINGS_KEY, $this->settings );
	}
	
	/**
	 * Update site option.
	 *
	 * @param string $option
	 * @param mixed  $value
	 * @param bool   $autoload
	 *
	 * @return bool
	 */
	public function update_site_option( $option, $value, $autoload = true ) {
		if ( is_multisite() ) {
			return update_site_option( $option, $value );
		}		
		return update_option( $option, $value, $autoload );
	}
	
	/**
	 * Filter the plugin settings array
	 *
	 * @param array $settings
	 *
	 * @return array $settings
	 */
	function filter_settings( $settings ) {
		$defined_settings = $this->get_defined_settings();
		
		// Bail early if there are no defined settings
		if ( empty( $defined_settings ) ) {
			return $settings;
		}

		foreach ( $defined_settings as $key => $value ) {
			$settings[ $key ] = $value;
		}

		return $settings;
	}
	
	function get_defined_settings( $force = false ) {
		if ( is_null( $this->defined_settings ) || $force ) {
			$this->defined_settings = array();
			$unserialized           = array();
			$class                  = get_class( $this );

			if ( defined( "$class::SETTINGS_CONSTANT" ) ) {
				$constant = static::SETTINGS_CONSTANT;
				if ( defined( $constant ) ) {
					$unserialized = maybe_unserialize( constant( $constant ) );
				}
			}

			$unserialized = is_array( $unserialized ) ? $unserialized : array();

			foreach ( $unserialized as $key => $value ) {
				if ( ! in_array( $key, $this->get_settings_whitelist() ) ) {
					continue;
				}

				if ( is_bool( $value ) || is_null( $value ) ) {
					$value = (int) $value;
				}

				if ( is_numeric( $value ) ) {
					$value = strval( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				$this->defined_settings[ $key ] = $value;
			}
		}

		return $this->defined_settings;
	}
	
	/**
	 * Render a view template file
	 *
	 * @param       $view View filename without the extension
	 * @param array $args Arguments to pass to the view
	 */
	function render_view( $view, $args = array() ) {
		extract( $args );
		include $this->plugin_dir_path . '/view/' . $view . '.php';
	}
	
	/**
	 * get specific setting from database
	 */
	function get_setting($key, $default = ''){
		$this->get_settings();
		if(isset($this->settings[$key])){
			$settings = $this->settings[$key];
		}else{
			$setrtings = $default;
		}
		return apply_filters("azure_web_services",	$settings, $key);
	}
	
	function remove_setting( $key ) {
		$this->get_settings();

		if ( isset( $this->settings[ $key ] ) ) {
			unset( $this->settings[ $key ] );
		}
	}
	
	function plugin_actions_settings_link( $links, $file ) {
		$url  = $this->get_plugin_page_url();
		$text = $this->get_plugin_action_settings_text();

		$settings_link = '<a href="' . $url . '">' . esc_html( $text ) . '</a>';

		if ( $file == $this->plugin_basename ) {
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
	
	
	/* create connection string*/
	function cerate_connection_string($azure){
		$access_protocol = $azure->get_access_end_prorocol();
		$account_name = $azure->get_access_account_name();
		$account_key = $azure->get_access_account_key();
		$connection_string = "DefaultEndpointsProtocol=".$access_protocol.";AccountName=".$account_name.";AccountKey=".$account_key;
		return $connection_string;
	}
	
}