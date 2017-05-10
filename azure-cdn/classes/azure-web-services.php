<?php

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\PublicAccessType;
use WindowsAzure\Common\ServiceException;

class Azure_Web_Services extends Azure_Plugin_Base{

	protected $plugin_file_path;
	private $plugin_title;
	private $plugin_menu_title;
	private $plugin_permission;
	private $client;

	const SETTINGS_KEY = 'azure_settings';
	const SETTINGS_CONSTANT = 'AZURE_SETTINGS';

	function __construct($plugin_file_path) {
		$this->plugin_slug = 'azure-web-services';
		
		parent::__construct( $plugin_file_path );
		
		do_action( 'azure_init', $this );

		if ( is_admin() ) {
			do_action( 'azure_admin_init', $this );
		}
		
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			$this->plugin_permission = 'manage_network_options';
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			$this->plugin_permission = 'manage_options';
		}
	
		$this->plugin_file_path = $plugin_file_path;
		$this->plugin_dir_path  = rtrim( plugin_dir_path( $plugin_file_path ), '/' );
		$this->plugin_basename  = plugin_basename( $plugin_file_path );
		$this->plugin_title      = __( 'Azure Web Services', 'azure-web-services' );
		$this->plugin_menu_title = __( 'Azure', 'azure-web-services' );
		add_filter( 'plugin_action_links', array( $this, 'plugin_actions_settings_link' ), 10, 2 );
		

	}		

	function get_plugin_action_settings_text() {
		return __( 'Settings', 'azzure-web-services' );
	}
	function get_plugin_page_url() {
		return network_admin_url( 'admin.php?page=' . $this->plugin_slug );
	}
	
	/**
	 * Add the Azure menu item and sub pages
	 */
	function admin_menu() {	
		$hook_suffixes = array();
		$hook_suffixes[] = add_menu_page( $this->plugin_title, $this->plugin_menu_title, $this->plugin_permission, $this->plugin_slug, array(
				$this,
				'render_page',
			) );
		
		global $submenu;
		if ( isset( $submenu[ $this->plugin_slug ][0][0] ) ) {
			$submenu[ $this->plugin_slug ][0][0] = __( 'Settings', 'azure-web-services' );
		}

		do_action( 'azure_admin_menu', $this );
		
		foreach ( $hook_suffixes as $hook_suffix ) {			
			add_action( 'load-' . $hook_suffix, array( $this, 'plugin_load' ) );
		}
		
	}
	
	/**
	 * Render the output of a page
	 */
	function render_page() {
		$view       = 'settings';
		$page_title = __( 'Azure Web Services', 'azure-web-services' );

		if ( empty( $_GET['page'] ) ) { // input var okay
			// Not sure why we'd ever end up here, but just in case
			wp_die( 'What the heck are we doin here?' );
		}		

		$this->render_view( 'header', array( 'page' => $view, 'page_title' => $page_title ) );
		$this->render_view( $view );
		$this->render_view( 'footer' );
	}
	
	/**
	 * Add sub page to the Azure menu item
	 *
	 * @param string       $page_title
	 * @param string       $menu_title
	 * @param string       $capability
	 * @param string       $menu_slug
	 * @param string|array $function
	 *
	 * @return string|false*/
	 
	function add_page( $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
		return add_submenu_page( $this->plugin_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}
	
	/**
	 * Plugin loading enqueue scripts and styles
	 */
	function plugin_load() {
		$version = $this->get_asset_version();
		$suffix  = $this->get_asset_suffix();

		$src = plugins_url( 'assets/css/styles.css', $this->plugin_file_path );
		wp_enqueue_style( 'aws-styles', $src, array(), $version );

		$src = plugins_url( 'assets/js/script.js', $this->plugin_file_path );
		wp_enqueue_script( 'azure-script', $src, array( 'jquery' ), $version, true );

		if ( isset( $_GET['page'] ) ) { // input var okay
			add_filter( 'admin_body_class', array( $this, 'admin_plugin_body_class' ) );
			wp_enqueue_script( 'plugin-install' );
			add_thickbox();
		}

		$this->handle_post_request();

		//do_action( 'azure_plugin_load', $this );
	}
	
	/**
	 * Adds a class to admin page to style thickbox the same as the plugin directory pages.
	 *
	 * @param $classes
	 *
	 * @return string
	 */
	function admin_plugin_body_class( $classes ) {
		$classes .= 'plugin-install-php';	
		return $classes;
	}
	
	/**
	 * Process the saving of the settings form 
	 */
	function handle_post_request() {
		if ( empty( $_POST['action'] ) || 'save' != sanitize_key( $_POST['action'] ) ) { // input var okay
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'azure-save-settings' ) ) { // input var okay
			die( __( "Cheatin' eh?", 'azure-web-services' ) );
		}

		// Make sure $this->settings has been loaded
		$this->get_settings();

		$post_vars = array( 'access_end_prorocol','access_account_name', 'access_account_key' );
		foreach ( $post_vars as $var ) {
			if ( ! isset( $_POST[ $var ] ) ) { // input var okay
				continue;
			}

			$value = sanitize_text_field( $_POST[ $var ] ); // input var okay

			if ( 'access_account_key' == $var && '-- not shown --' == $value ) {
				continue;
			}

			$this->set_setting( $var, $value );
		}
		$this->save_settings();
	}
		
	
	/**
	 * get azure access account name
	 */
	function get_access_account_name(){
		if(defined("DBI_AZURE_ACCOUNT_NAME")){
			return DBI_AZURE_ACCOUNT_NAME; 
		}
		return $this->get_setting('access_account_name');
	}
	
	/**
	 * get azure access account key
	 */
	function get_access_account_key(){
		if(defined("DBI_AZURE_ACCOUNT_KEY")){
			return DBI_AZURE_ACCOUNT_KEY;
		}
		return $this->get_setting('access_account_key');
	}
	
	function get_access_end_prorocol(){
		return $this->get_setting('access_end_prorocol');
	}

}