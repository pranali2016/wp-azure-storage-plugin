<?php 
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\PublicAccessType;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Blob\Models\CreateBlobOptions;

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
		add_action( 'wp_ajax_get-container-list', array($this, 'ajax_get_containers') );
		add_action( 'wp_ajax_container-exist', array($this, 'ajax_container_exist') );
		
		// Rewriting URLs
		add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url' ), 99, 2 );
		
		// upload media to the azure storage
		add_filter( 'wp_update_attachment_metadata', array( $this, 'wp_update_attachment_metadata' ), 110, 2 );

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
					'get_container_error'           => __( 'Error fetching containers', 'azure-storage-and-cdn' ),
					'get_url_preview_error'       => __( 'Error getting URL preview: ', 'azure-storage-and-cdn' ),
					'save_alert'                  => __( 'The changes you made will be lost if you navigate away from this page', 'azure-storage-and-cdn' ),					
				),				
			)
		);
		
		$this->handle_post_request();
	}

	function handle_post_request(){
		if ( empty( $_POST['action'] ) || 'save' != sanitize_key( $_POST['action'] ) ) { // input var okay
			return;
		}
		// Make sure $this->settings has been loaded
		$this->get_settings();

		$post_vars = array( 'copy-to-azure','serve-from-azure' );
		foreach ( $post_vars as $var ) {
			$this->remove_setting( $var );
			if ( ! isset( $_POST[ $var ] ) ) { // input var okay
				continue;
			}

			$value = sanitize_text_field( $_POST[ $var ] ); // input var okay
			$this->set_setting( $var, $value );
		}
		$this->save_settings();
	}
	
	function render_page() {
		$this->azure->render_view( 'header', array( 'page_title' => 'Azure Storage') );
		$this->render_view( 'container-setting-tabs' );
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
		$container_list = $this->get_container_list();
		if(is_wp_error($container_list)){
			return $container_list;
		}
		else{
			if(in_array($container,$container_list)){
				return true;
			}else{
				$error_msg = "No such Container exist";
				return new WP_Error( 'exception', $error_msg );
			}
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
			$out = array(
				'success' => '1',
				'container' => $container_name,
			);			
			$this->end_ajax( $out );
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

	// get container list
	function get_container_list(){
		$containers = array();
		try{
			$container_list = $this->blobClient->listContainers()->getContainers();
			foreach($container_list as $cl){
				$containers[] = $cl->getName();
			}
		}catch (Exception $e){
			$error_msg = $e->getMessage();
			return new WP_Error( 'exception', $error_msg );
		}
		return $containers;
	}
	
	//ajax-get-containers
	function ajax_get_containers(){
		$this->verify_ajax_request();
		$containers = $this->get_container_list();
		if( is_wp_error($containers)){
			$out = $this->prepare_container_error( $containers, false );
		} else {
			$out = array(
				'success' => '1',
				'containers' => $containers,
			);
		}
		$this->end_ajax( $out );
	}
	
	// container exist in database
	function ajax_container_exist(){
		$out = array();
		$container = $this->get_setting('container');
		if(isset($container)){
			$out['success'] ='1';
			$out['container'] = $container;
		}
		$this->end_ajax( $out );
	}
	
	public function get_copy_to_azure_setting(){
		return $this->get_setting('copy-to-azure');
	}
	
	public function get_serve_from_azure_setting(){
		return $this->get_setting('serve-from-azure');
	}
	
	//Handles the upload of the attachment to Azure storage when an attachment is updated
	public function wp_update_attachment_metadata($data, $post_id){

		$data = $this->upload_attachment_to_azure_storage( $post_id, $data );

		return $data;
	}
	//Get mime type
	protected function get_mime_type( $file_path ) {
		$file_type = wp_check_filetype_and_ext( $file_path, basename( $file_path ) );

		return $file_type['type'];
	}
	
	// uploading error
	protected function return_upload_error($error,$return = null){
		if(is_null($return)){
			return new WP_Error('exception',$error);
		}
		return $return;
	}
	
	// to upload blob to azure storage
	public function upload_attachment_to_azure_storage( $post_id, $data = null, $file_path = null ){
		$return_metadata = null;
		if(is_null($data)){
			$data = wp_get_attachment_metadata($post_id,true);
		}else{
			$return_metadata = $data;
		}
		
		if(is_wp_error($data)){
			return $data;
		}
		
		$file_path = get_attached_file( $post_id, true );
		if(! file_exists($file_path)){
			$error_msg = sprintf(__('File does not exist','azure-storage-and-cdn'),$file_path);
			return $this->return_upload_error($error_msg, $return_metadata);
		}
		
		$type = $this->get_mime_type($file_path);
		$option = new CreateBlobOptions();
		$option->setBlobContentType($type);
		$blob_name = basename($file_path);
		$container = $this->get_container_name();
		
		$azureStorageObject = array(
			'container' => $container,
			'key'    => $blob_name,
		);
		
		$files_to_remove = array();
		if( file_exists($file_path)){		
			$content = file_get_contents($file_path);
			try{		
				$this->blobClient->createBlockBlob($container, $blob_name, $content,$option);
				$files_to_remove[] = $blob_name;
			}catch(Exception $e){
				$error_msg = sprintf( __( 'Error uploading %s to Azure Storage: %s', 'azure-storage-and-cdn' ), $file_path, $e->getMessage() );
				return $this->return_upload_error( $error_msg, $return_metadata );
			}
		}
		delete_post_meta( $post_id, 'azurestorage_info' );
		add_post_meta( $post_id, 'azurestorage_info', $azureStorageObject );
		$file_paths        = $this->get_attachment_file_paths( $post_id, true, $data );
		
		$additional_images = array();		
		
		foreach($file_paths as $size => $path){
			if(! in_array($path, $files_to_remove)){
				$additional_images[] = $path;				
			}
		}
		
		foreach($additional_images as $blob_path){
			$type = $this->get_mime_type($blob_path);
			$opt = new CreateBlobOptions();
			$opt->setBlobContentType($type);
			$blob_name = basename($blob_path);
			try{		
				$this->blobClient->createBlockBlob($container, $blob_name, $content,$opt);
				$files_to_remove[] = $blob_name;
			}catch(Exception $e){
				$error_msg = sprintf( __( 'Error uploading %s to Azure Storage: %s', 'azure-storage-and-cdn' ), $file_path, $e->getMessage() );
				return $this->return_upload_error( $error_msg, $return_metadata );
			}
		}
		
		$azureStorageObject['sizes'] = $data['sizes'];
		update_post_meta( $post_id, 'azurestorage_info', $azureStorageObject );
		
		if ( ! is_null( $return_metadata ) ) {
			// If the attachment metadata is supplied, return it
			return $data;
		}

		return $azureStorageObject;
	}
	
	// upload images in different sizes
	public function get_attachment_file_paths( $attachment_id, $exists_locally = true, $meta = false, $include_backups = true ) {
		$file_path = get_attached_file( $attachment_id, true );
		$paths     = array(
			'original' => $file_path,
		);

		if ( ! $meta ) {
			$meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		}

		if ( is_wp_error( $meta ) ) {
			return $paths;
		}

		$file_name = basename( $file_path );

		// Thumb
		if ( isset( $meta['thumb'] ) ) {
			$paths['thumb'] = str_replace( $file_name, $meta['thumb'], $file_path );
		}

		// Sizes
		if ( isset( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size => $file ) {
				if ( isset( $file['file'] ) ) {
					$paths[ $size ] = str_replace( $file_name, $file['file'], $file_path );
				}
			}
		}

		$backups = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

		// Backups
		if ( $include_backups && is_array( $backups ) ) {
			foreach ( $backups as $size => $file ) {
				if ( isset( $file['file'] ) ) {
					$paths[ $size ] = str_replace( $file_name, $file['file'], $file_path );
				}
			}
		}

		// Allow other processes to add files to be uploaded
		$paths = apply_filters( 'as3cf_attachment_file_paths', $paths, $attachment_id, $meta );

		// Remove duplicates
		$paths = array_unique( $paths );

		// Remove paths that don't exist
		if ( $exists_locally ) {
			foreach ( $paths as $key => $path ) {
				if ( ! file_exists( $path ) ) {
					unset( $paths[ $key ] );
				}
			}
		}

		return $paths;
	}
		
	public function wp_get_attachment_url($url, $post_id){
		$new_url = $this->get_attachment_url( $post_id );
		
		if(false === $new_url ){
			return $url;
		}
		return $new_url;
	}
	
	// azure storage info from db
	public function get_attachment_azure_info($post_id){
		return get_post_meta($post_id,'azurestorage_info', true);
	}
	
	// whether to server from azure storage or local server
	public function is_attachment_served_by_azure($post_id){
		if(! $this->get_setting('serve-from-azure')){
			return false;
		}
		if(! $azureStorageObject = $this->get_attachment_azure_info($post_id)){
			return false;
		}
		
		return $azureStorageObject;
	}
	
	public function get_attachment_url($post_id){
		if( ! $azureStorageObject = $this->is_attachment_served_by_azure($post_id)){
			return false;
		}
		$url = $this->get_attachment_azure_storage_url($post_id,$azureStorageObject);
		return $url;
	}
	
	// to fetch blob from the azure storage
	public function get_attachment_azure_storage_url($post_id,$azureStorageObject){
		$container = $azureStorageObject['container'];
		$blob_name = $azureStorageObject['key'];
		$azure  = new Azure_Web_Services();
		$endprotocol = $azure->get_access_end_prorocol();
		$accountName = $azure->get_access_account_name();
		$url = $endprotocol."://".$accountName."blob.core.windows.net/".$container."/".$blob_name;		
		return $url;
	}
	
}
