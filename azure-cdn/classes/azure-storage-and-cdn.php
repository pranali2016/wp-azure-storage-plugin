<?php 
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\PublicAccessType;
use WindowsAzure\Common\ServiceException;

class Azure_Storage_And_Cdn extends Azure_Plugin_Base{
	
	const SETTINGS_KEY = 'wordpress_azure_storage_container';
	const SETTINGS_CONSTANT = 'WPAZURE_SETTINGS';
	function __construct( $plugin_file_path, $azure, $slug = null ) {
		$this->plugin_slug = ( is_null( $slug ) ) ? 'azure-storage-and-cdn' : $slug;

		parent::__construct( $plugin_file_path );
		$this->azure = $azure;			
		$connection_string = $this->cerate_connection_string($azure);
		$this->blobClient = ServicesBuilder::getInstance()->createBlobService($connection_string);
		$this->init( $plugin_file_path , $blobClient);
	}
	
	function init($plugin_file_path, $blobClient){
		add_action( 'azure_admin_menu', array( $this, 'admin_menu' ) );		
		add_action( 'azure_plugin_load', $this );		
		
		add_action( 'wp_ajax_manual-save-container', array( $this, 'ajax_save_container' ) );
		add_action( 'wp_ajax_azure-container-create', array( $this, 'ajax_create_container' ) );		
	}
	
	function admin_menu( $azure ) {
		$hook_suffix = $azure->add_page( 'Azure Storage', 'Storage And Cdn', 'manage_options', 'azure-storage-and-cdn', array( $this, 'render_page' ) );
		if ( false !== $hook_suffix ) {
			$this->hook_suffix = $hook_suffix;
			add_action( 'load-' . $this->hook_suffix, array( $this, 'plugin_load' ) );
		}
	}
	
	function plugin_load() {
		$version = $this->get_asset_version();
		$suffix  = $this->get_asset_suffix();

		$src = plugins_url( 'assets/css/styles.css', $this->plugin_file_path );
		wp_enqueue_style( 'aws-styles', $src, array(), $version );

		$src = plugins_url( 'assets/js/script.js', $this->plugin_file_path );
		wp_enqueue_script( 'azure-script', $src, array( 'jquery' ), $version, true );

		wp_localize_script( 'azure-script',
			'azure',
			array(
				'strings'         => array(
					'create_container_error'      => __( 'Error creating container', 'azure-storage-and-cdn' ),
					'save_container_error'        => __( 'Error saving bucket', 'azure-storage-and-cdn' ),
					'get_buckets_error'           => __( 'Error fetching buckets', 'azure-storage-and-cdn' ),
					'get_url_preview_error'       => __( 'Error getting URL preview: ', 'azure-storage-and-cdn' ),
					'save_alert'                  => __( 'The changes you made will be lost if you navigate away from this page', 'azure-storage-and-cdn' ),					
				),				
			)
		);
	}
	
	function render_page() {
		$this->azure->render_view( 'header', array( 'page_title' => 'Azure Storage') );
		$this->render_view( 'container-setting' );
		$this->azure->render_view( 'footer' );
	}
	
	function get_settings_tabs(){
		$tabs = array(
			'media'   => _x( 'Media Library', 'Show the media library tab', 'azure-storage-and-cdn' ),
			'support' => _x( 'Support', 'Show the support tab', 'azure-storage-and-cdn' )
		);
		return  $tabs ;
	}
	
	// ajax request to save container
	function ajax_save_container() {
		$this->verify_ajax_request();

		$container = $this->ajax_check_container();

		$manual = false;
		// are we inputting the bucket manually?
		if ( isset( $_POST['action'] ) && false !== strpos( $_POST['action'], 'manual-save-container' ) ) {
			$manual = true;
		}

		$this->save_container_for_ajax( $container, $manual );
	}
	
	// verify the request
	function verify_ajax_request() {		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'azure-storage-and-cdn' ) );
		}
	}
	
	//check whether field is empty or not
	function ajax_check_container() {
		if ( ! isset( $_POST['container_name'] ) || ! ( $container = sanitize_text_field( $_POST['container_name'] ) ) ) { // input var okay
			$out = array( 'error' => __( 'No bucket name provided.', 'azure-storage-and-cdn' ) );

			$this->end_ajax( $out );
		}

		return strtolower( $container );
	}
	
	// check container exists or not
	function save_container_for_ajax( $container, $manual_select = false, $defaults = array() ) {
		$res = $this->check_container_permission($container);
		if ( is_wp_error( $res ) ) {
			$out = $this->prepare_container_error( $res );
			$this->end_ajax( $out );
		}else{
			$this->save_container( $container, $manual_select );
			$this->end_ajax( $out );
		}
	}
	
	function end_ajax( $return = array() ) {
		echo json_encode( $return );
		exit;
	}
	
	// get list of containers from azure storage
	function check_container_permission( $container) {
		try {
			$containers = $this->blobClient->listContainers()->getContainers();			
			$matched = '';
			foreach($containers as $c){
				if($c->getName() == $container){
					$matched = true;
				}				
			}
			if(!$matched){
				$error_msg = "No such Container exist";
				return new WP_Error( 'exception', $error_msg );
			}
		} catch ( Exception $e ) {
			// if we encounter an error that isn't access denied, throw that error
				$error_msg = $e->getMessage();
				return new WP_Error( 'exception', $error_msg );
			}
	}
	
	// save container to the database
	function save_container( $container_name, $manual = false) {
		if ( $container_name ) {			
			$this->get_settings();
			$this->set_setting( 'container', $container_name );

			if ( $manual ) {
				// record that we have entered the container via the manual form
				$this->set_setting( 'manual_container', true );
			} else {
				$this->remove_setting( 'manual_container' );
			}

			$this->save_settings();
			$data = array("success"=>$container_name );
			$this->end_ajax( $data );
		}
	}
	
	//
	function prepare_container_error( $object, $single = true ) {
		if ( 'Access Denied' === $object->get_error_message() ) {
			// If the container error is access denied, show our notice message
			$out = array( 'error' => $this->get_access_denied_notice_message( $single ) );
		} else {
			$out = array( 'error' => $object->get_error_message() );
		}

		return $out;
	}
		
	function set_setting( $key, $value ) {
		$value = apply_filters( 'set_setting_' . $key, $value );
		parent::set_setting( $key, $value );
	}
	
	// ajax request to create new container
	function ajax_create_container(){
		$this->verify_ajax_request();
		$container = $this->ajax_check_container();
		$this->create_container_for_ajax($container);
	}
	
	// check whether the container exists if yes then throws error
	function create_container_for_ajax($container_name){
		$res = $this->check_container_permission($container_name);
		if(is_wp_error($res)){
			$out = $this->prepare_container_error( $res );
			if($out['error'] === "No such Container exist"){
				$res = $this->azure_container_create($container_name);
				if(is_wp_error($res)){
					$out = $this->prepare_container_error( $res );
					$this->end_ajax( $out );
				}else{
					$this->save_container( $container_name );
					$this->end_ajax( $out );
				}
			}else{
				$this->end_ajax( $out );
			}
		}else{
			$error_msg = array("error"=> "Container existed.");
			$this->end_ajax( $error_msg );
		}
	}
	
	// create a new container to the azure storage
	function azure_container_create($container_name){
		$createContainerOptions = new CreateContainerOptions();
		$createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);
		try{
			$this->blobClient->createContainer($container_name,$createContainerOptions);
		}
		catch (Exception $e){
			$error_msg = $e->getMessage();
			return new WP_Error( 'exception', $error_msg );
		}
	}
	
	// get container name from the database
	function get_container_name(){
		return $this->get_setting('container');
	}
}