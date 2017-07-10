<?php
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Blob\Models\PublicAccessType;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Blob\Models\CreateBlobOptions;

class Asset_Storage_Manage extends Azure_Plugin_Base{

    const ASSET_FILES_BY_LOCATION = 'azure_assets_files';
    const SETTINGS_KEY = 'wp_azure_assets_details';
    const SETTINGS_CONSTANT = 'WPAZURE_ASSETS_SETTINGS';

    function __construct($plugin_file_path, $azure){
        if ( ! function_exists( 'get_plugins' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

            parent::__construct( $plugin_file_path, $azure );
            $this->azure = $azure;			
            $connection_string = $this->cerate_connection_string($azure);

            if(($connection_string != null)){
                    $this->blobClient = ServicesBuilder::getInstance()->createBlobService($connection_string);
                    $src = plugins_url( 'assets/js/asset.js', $plugin_file_path );
                    wp_enqueue_script( 'azure-asset', $src, array( 'jquery' ));
            }	

            if ( is_multisite() ) {
                    $this->plugin_permission = 'manage_network_options';
            } else {
                    $this->plugin_permission = 'manage_options';
            }

            add_action( 'azure_admin_menu', array( $this, 'admin_menu' ) );		
            add_action( 'azure_plugin_load', $this );

            $this->start();
    }

    function admin_menu( $azure ) {
            $hook_suffix = $azure->add_page( 'Azure Storage', 'Asset Addon', $this->plugin_permission, 'wp-offload-azure-assets', array( $this, 'display_page' ) );
            if ( false !== $hook_suffix ) {
                    $this->hook_suffix = $hook_suffix;
                    add_action( 'load-' . $this->hook_suffix, array( $this, 'plugin_page_load' ) );
            }
    }

    function plugin_page_load(){
        $version = $this->get_version();

        $src = plugins_url( 'assets/css/styles.css', $this->plugin_file_path );
        wp_enqueue_style( 'azure-assets-styles',$src);

        $src = plugins_url( 'assets/js/script.js', $this->plugin_file_path );
        wp_register_script( 'azure-assets-script', $src, array( 'jquery' ), $version, true );
        wp_enqueue_script( 'azure-assets-script');

        wp_localize_script( 'azure-assets-script',
            'azure',
            array(
                'strings'         => array(
                    'create_container_error'      => __( 'Error creating container', 'wp-offload-azure-assets' ),
                    'save_container_error'        => __( 'Error saving container', 'wp-offload-azure-assets' ),
                    'get_container_error'           => __( 'Error fetching containers', 'wp-offload-azure-assets' ),
                    'get_url_preview_error'       => __( 'Error getting URL preview: ', 'wp-offload-azure-assets' ),
                    'save_alert'                  => __( 'The changes you made will be lost if you navigate away from this page', 'wp-offload-azure-assets' ),					
                ),				
            )
        );
        $this->handle_request();
    }

    function handle_request(){
        if ( empty( $_POST['action'] ) || 'save' != sanitize_key( $_POST['action'] ) ) { // input var okay
                return;
        }
        // Make sure $this->settings has been loaded
        $this->get_settings();

        $post_vars = array( 'enable-assets-addon', 'automatic-scan', 'asset-extenstions', 'asset-cdn-endpoint' );
        foreach ( $post_vars as $var ) {
                $this->unset_setting( $var );
                if ( ! isset( $_POST[ $var ] ) ) { // input var okay
                        continue;
                }

                $value = sanitize_text_field( $_POST[ $var ] ); // input var okay
                $this->set_setting( $var, $value );
        }
        $this->save_settings();
    }

    function display_page() {
            $this->azure->display_view( 'header', array( 'page_title' => 'WP Offload Azure - Asset Addon') );
            $this->display_view( 'asset-setting' );
            $this->azure->display_view( 'footer' );
    }

    public function start(){		
            //ajax request for containers
            add_action( 'wp_ajax_manual-save-asset-container', array( $this, 'ajax_save_container' ) );
            add_action( 'wp_ajax_asset-container-create', array( $this, 'ajax_create_container' ) );
            add_action( 'wp_ajax_get-container-list', array($this, 'ajax_get_containers') );
            add_action( 'wp_ajax_container-exist', array($this, 'ajax_container_exist') );

            //ajax request for assets manual operations
            add_action('wp_ajax_copy-assets-manually',array($this,'ajax_copy_assets_manually'));
            add_action('wp_ajax_remove-assets-manually',array($this,'ajax_remove_assets_manually'));

            add_action( 'switch_theme', array( $this, 'automatic_process_assets' ) );
            add_action( 'activated_plugin', array( $this, 'automatic_process_assets' ));
            add_action( 'deactivated_plugin', array( $this, 'automatic_remove_assets' ));

            //cron schedule
            add_filter( 'cron_schedules', array($this, 'new_schedule'));
            if ( ! wp_next_scheduled( 'my_cron' ) ) {
                    wp_schedule_event(time(),'new_min','my_cron');
            }
            add_action( 'my_cron', array($this,'scan_files_for_azure') );
            add_action( 'upgrader_process_complete', array( $this, 'scan_files_for_azure' ) );

            // Serve assets using CDN
            add_action('style_loader_src', array($this,'serve_css_from_storage'));
            add_action('script_loader_src', array($this,'serve_js_from_storage'));
    }

    public function is_enable_assets_addon(){
            return $this->get_setting('enable-assets-addon');
    }

    public function get_asset_extensions(){
            return $this->get_setting('asset-extenstions');
    }

    public function automatic_scan_assets(){
            return $this->get_setting('automatic-scan');
    }

    public function get_asset_container_name(){
        return $this->get_setting('asset-container');
    }
    
    // get cdn endpoint to display file
    public function get_cdn_endpoint(){
            return $this->get_setting('asset-cdn-endpoint');
    }
    /*
     *Create an interval for cron to run
     */
    public function new_schedule($interval){
            $interval['new_min'] = array('interval'=>1200,'display'   => __( 'Every Minutes', 'textdomain' ));
            return $interval;
    }

    public function scan_files_for_azure(){
            $this->automatic_process_assets();
    }

    /* ajax request
     * start copying assets on a button click
     */
    function ajax_copy_assets_manually(){
            if( !$this->is_enable_assets_addon() ){
                    $out = new WP_Error('exception','Make sure Asset addon is Enabled.');
                    $this->end_ajax( $out );
            }

            $this->scan_files();		
    }

    /*
     * scan locations and copy assets to azure storage
     */
    function automatic_process_assets(){
            if( !$this->is_enable_assets_addon() || !$this->automatic_scan_assets() ){
                    return;
            }

            $this->scan_files();
    }

    /*
     * Assets file key
     */
    private function file_key( $location, $name ){
            return self::ASSET_FILES_BY_LOCATION . '_' . ( trim( $location ) ).( trim( $name ) );
    }

    /*
     * Scan files to copy to storage
     */
    public function scan_files(){
            $locations = $this->file_local_places();
            foreach($locations as $location){
                    $this->scan_files_by_location( $location );
            }
    }

    /*
     * Scan files by its place 
     */
    protected function scan_files_by_location( $location ){
            if( !isset($location['path'],$location['type'],$location['url']) ){
                    return;
            }

            $already_saved = $this->get_file_list_by_location($location);
            $files = $this->find_files_by_location( $location, $already_saved);
            $files_to_process = $this->process_files_by_location($location, $already_saved, $files);

            $this->save_files($files);

            if(!empty($files_to_process) ){			
                    $this->process_assets($files_to_process, $location);
            }
    }

    /*
     * Process for assets files 
     */
    public function process_assets($files_to_copy, $place){
            $files_copy = 0;
            $already_saved = $this->get_file_list_by_location($place);

            foreach( $files_to_copy as $location => $file ){			
                    foreach( $file as $details){				
                            if( $details['action'] == "copy" ){
                                    $storage_info = $this->copy_file_to_storage($location,$details);

                                    if(is_wp_error( $storage_info )){
                                            continue;					
                                    }else{				
                                            $already_saved[$details['path']]['storage_info'] = $storage_info;
                                            $files_copy++;
                                    }
                            }

                            else if( $details['action'] == "remove" ){
                                    $response = $this->remove_file_from_storage($details['container'],$details['name']);
                                    if( is_wp_error($response)){
                                            continue;
                                    }else{
                                            unset($already_saved[$details['path']]);
                                    }
                            }				
                    }
            }

            if($files_copy > 0){
                    $this->save_files( $already_saved );			
            }	
    }	

    /*
     * Upload files to storage
     */
    public function copy_file_to_storage($location,$file){
        if( !file_exists($file['path']) ){
            $err_msg = "File does not exist";
            return new WP_Error('exception',$err_msg);
        }

        if($file['type'] == null){
            if($location == "themes"){
                    $file_name = $location."/".get_stylesheet().$file['name'];
            }else{
                    $file_name = $location.$file['name'];
            }
        }else{
            $file_name = $location."/".$file['type'].$file['name'];
        }

        $type = $this->get_mime_type($file['path']);
        $option = new CreateBlobOptions();
        $option->setBlobContentType($type);
        $container = $this->get_asset_container_name();
        $content = file_get_contents($file['path']);

        try{
            $this->blobClient->createBlockBlob($container, $file_name, $content,$option);
        }catch( Exception $e){
            $err_msg = "Error uploading file to azure storage";
            return new WP_Error('exception',$err_msg);
        }

        $azureStorageObject = array(
            'container' => $container,
            'key'    => $file_name,
            'path' => $file['path'],
        );
        return $azureStorageObject;
    }

    /*
     * Save files to db
     */
    public function save_files( $files ){
        if(!empty( $files )){
            $files_tobe_saved = array();
            foreach ($files as $file => $details){
                if( !isset($details['place'])){
                        continue;
                }

                $files_tobe_saved[$this->file_key($details['place'],$details['name'])][$file] = $details;
            }

            foreach($files_tobe_saved as $place_key =>$file_details){
                $this->save_site_option( $place_key, $file_details );
            }
        }		
    }

    /*
     * Find files by it location
     */
    protected function find_files_by_location( $location, $already_saved){
        $path           =  $location['path'];
        $url            =  $location['url'];
        $extensions     = $this->get_allowed_extensions();
        $location_files = $this->files_in_path( $path, $extensions );		
        $found_files    = array();

        foreach($location_files as $file=>$object){		
            $details = array(
                'url'           => str_replace( $path, $url, $file ),
                'base'          => str_replace( $path, '', $file ),
                'local_version' => filemtime($file),
                'name'        => $location['name'],
                'extension'     => pathinfo( $file, PATHINFO_EXTENSION ),
                'place'      => $location['type'],
            );

            if(isset($already_saved[$file])){
                if( isset($already_saved[$file]['storage_info'])){
                        $details['storage_info'] = $already_saved[$file]['storage_info'];
                }
            }

            $found_files[$file] = $details;
        }

        return $found_files;
    }

    /*
     * Get file list by location
     */
    function get_file_list_by_location($location){
        if(empty($location['type'])){
                return false;
        }

        return get_site_option($this->file_key($location['type'],$location['name']));
    }

    /*
     * Allowed extensions
     */
    private function get_allowed_extensions(){
        $extensions = $this->get_asset_extensions();
        $extensions = explode(",",$extensions);	
        if(count($extensions) == 0){
                $extensions = array('css','js');
        }

        return $extensions;
    }

    /*
     * Get all assets location within word press
     */
    protected function file_local_places(){
        $all_places = array('admin','core','themes','plugins');
        $locations = array();

        /*if ( in_array( 'admin', $all_places ) ) {
                $locations[] = array(
                        'path'    => ABSPATH . 'wp-admin',
                        'url'     => site_url( '/wp-admin' ),
                        'type'    => 'admin',
                        'name'    => '',
                );
        }*/

        if ( in_array( 'core', $all_places ) ){
                $locations[] = array(
                        'path'    => ABSPATH . WPINC,
                        'url'     => site_url( '/' . WPINC ),
                        'type'    => 'core',
                        'name'	  => '',
                );
        }

        if ( in_array( 'themes', $all_places ) ){
                $themes    = $this->get_active_theme($themes = array());
                $locations = array_merge( $locations, $themes );
        }

        if ( in_array( 'plugins', $all_places ) ) {
                $plugins   = $this->get_all_plugins();
                $locations = array_merge( $locations, $plugins );
        }
        return  $locations;
    }

    /*
     * Returns files in given path
     */
    protected function files_in_path( $path, $extensions = array() ){		
        $validation = function ( $file, $key, $file_iterator ) {
            $filename = $file->getFilename();

            if( $file->isDir() && '.' === $filename[0] ) {
                    return false;
            }
            if ( ! $file->isReadable() ) {
                    return false;
            }
            if ( $file_iterator->hasChildren() ) {
                    return true;
            }
            return $file->isFile();
        };

        $directory = new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
        $file_iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator( $directory, $validation ),RecursiveIteratorIterator::SELF_FIRST);

        $ext  = implode( '|', $extensions );
        $files = new RegexIterator( $file_iterator, '/^.*\.(' . $ext . ')$/i', RecursiveRegexIterator::GET_MATCH );

        return $files;
    }

    /*
     * Get active theme detail
     */
    function get_active_theme( $themes, $is_parent_theme = false ){
        $theme = array(
                'path'    => $is_parent_theme ? get_template_directory() : get_stylesheet_directory(),
                'url'     => $is_parent_theme ? get_template_directory_uri() : get_stylesheet_directory_uri(),
                'type'    => 'themes',
        );

        $name = $is_parent_theme ? get_template() : get_stylesheet();
        //$theme['name'] = $name;

        if ( isset( $themes[ $name ] ) ) {
                return $themes;
        }

        if ( ! file_exists( $theme['path'] ) ) {
                return $themes;
        }

        $themes[ $name ] = $theme;

        if ( ! $is_parent_theme && get_template_directory() !== get_stylesheet_directory() ) {
                $themes = $this->get_active_theme( $themes, true );
        }

        return array_values($themes);
    }

    /*
     * Get all installed plug in details
     */
    function get_all_plugins(){
        $installed = get_option( 'active_plugins' );
        //$installed = array_keys( get_plugins() );
        $plugins           = array();
        $plugins_url       = plugins_url();

        foreach ( $installed as $plugin ) {
            $dir = dirname( $plugin );

            if ( '.' === $dir ) {				
                    continue;
            }

            $plugins[ $plugin ] = array(
                    'type'    => 'plugins',
                    'path'    => trailingslashit( WP_PLUGIN_DIR ) . $dir,
                    'url'     => trailingslashit( $plugins_url ) . $dir,
                    'name'  => basename( $plugin, '.php' ),
            );
        }

        return  array_values($plugins );
    }

    /*
     * Get files to copy to storage by its location
     */
    public function process_files_by_location($location, $already_saved, $files_found){
        if(!empty( $files_found )){

            foreach( $files_found as $file=>$details){
                    if( !isset($details['storage_info'])){
                            $files_to_process[$location['type']][] = $this->add_to_queue( 'copy', $file, $details);
                    }
            }

            $files_to_process = $this->remove_old_files($location, $files_to_process, $already_saved, $files_found);

            return $files_to_process;
        }
    }

    private function remove_old_files($location, $files_to_process, $already_saved, $files_found){
        if( empty($already_saved) ){
                return $files_to_process;
        }

        foreach( $already_saved as $file=>$details ){
                if( !isset($files_found[$file]) ){
                        $files_to_process[$location['type']][] = $this->add_to_queue( 'remove', $file, $details);
                }
        }
        return $files_to_process;
    }

    /*
     * Add files to queue
     */
    public function add_to_queue($action, $file, $details){
        switch($action){
            case 'copy':
                    $files_in_queue = array(
                        'action' => 'copy',
                        'path' => $file,
                        'name' => $details['base'],
                        'type' => $details['name'],
                    );
                    break;
            case 'remove':
                    $files_in_queue = array(
                        'action' => 'remove',
                        'path' => $file,
                        'url'    => $details['url'],
                        'name' => $details['storage_info']['key'],
                        'container' => $details['storage_info']['container']
                    );
                    break;
        }

        return $files_in_queue;
    }

    /*
     * Returns an array of saved files
     */
    public function get_files(){
        $locations = $this->file_local_places();
        foreach($locations as $location){
            $location_files = $this->get_file_list_by_location( $location );
            if($location_files === false){
                    continue;
            }
            $location = $location['type'].$location['name'];
            $files[$location] = $location_files;
        }

        $this->files = $files;
        return $files;
    }

    /*
     * Serve Js files from azure Storage
     */
    public function serve_js_from_storage($src){
        $src = $this->serve_assets_from_storage($src, 'js');
        return $src;
    }

    /*
     * Serve CSS files from azure storage
     */
    public function serve_css_from_storage($src){
        $src = $this->serve_assets_from_storage($src,'css');
        return $src;
    }

    /*
     * Replace loaded url with CDN url
     */
    public function serve_assets_from_storage($src,$type){
        if( is_admin() || !$this->is_enable_assets_addon() ){
                return $src;
        }	
        $files = $this->get_files();
        $path = $this->get_file_path($src);
        if($ver = strpos($path,"?")){
                $path = substr($path,0,$ver);
        }

        if ( !$files ) {
                return $src;
        }

        foreach( $files as $location=>$file ){
                if( array_key_exists($path,$file)){
                        $script = $file[$path];
                        break;
                }
        }

        if(!$script || !isset($script['storage_info'])){
                return $src;
        }
        $name = $script['storage_info']['key'];
        $container = $script['storage_info']['container'];
        $azure  = new Azure_Storage_Services(__FILE__);
        $endprotocol = $azure->get_access_end_prorocol();
        $cdnEndPoint = $this->get_cdn_endpoint();
        $url = $endprotocol."://".$cdnEndPoint."/".$container."/".$name;
        $src = substr( $url, strpos( $url, '?' ) );
        return $src;
    }

    /*
     * Returns file's directory path from its URL
     */
    public function get_file_path( $url ) {
        global $wp_styles;
        $content_url =  $wp_styles->content_url ;
        $base = $wp_styles->base_url;
        $base_path = untrailingslashit( WP_CONTENT_DIR );

        if ( 0 === strpos( $url, $content_url ) ) {
                $base_path = untrailingslashit( WP_CONTENT_DIR );
                $base_url  = untrailingslashit( $content_url );
        } else {
                $base_path = untrailingslashit( ABSPATH );
                if( strpos($url, $base) === false ){
                        $base_url  = untrailingslashit( $this->replace_without_scheme_url( $base ) );
                }else{
                        $base_url  = untrailingslashit( $base );
                }
        }
        return str_replace( $base_url, $base_path, $url );
    }

    /*
     * Return url without schemes 
     */
    function replace_without_scheme_url( $url ) {
        $url = preg_replace( '(https?:)', '', $url );
        return $url;
    }

    /*
     * Manually remove assets files
     */
    function ajax_remove_assets_manually(){
        if( !$this->is_enable_assets_addon() ){
                return;
        }

        $locations = $this->file_local_places();
        foreach($locations as $location){
                $this->scan_to_remove_assets($location);
        }
    }

    /*
     * Automatic remove assets while plugin deactivation
     */
    function automatic_remove_assets($plugin){
        $location = array(	
                'type'  => 'plugins',
                'path'  => trailingslashit( WP_PLUGIN_DIR ) . $plugin,
                'url'   => plugins_url($plugin),
                'name'  => basename( $plugin, '.php' ),
        );

        $this->scan_to_remove_assets($location);
    }

    /*
     * Scan location to remove assets
     */
    function scan_to_remove_assets($location){
        $files = $this->get_file_list_by_location($location);

        if(!$files){
                return;
        }

        foreach ( $files as $path => $details){				
            if(!isset($details['storage_info'])){
                    continue;
            }
            $container = $details['storage_info']['container'];
            $key = $details['storage_info']['key'];
            $response = $this->remove_file_from_storage($container,$key);

            if( !is_wp_error($response)){
                    unset($files[$path]);
            }				
        }

        $place_key = $this->file_key($location['type'],$location['name']);
        if ( !empty($files)){
                $this->save_site_option( $place_key, $files );
        }else{
                delete_option( $place_key);
        }
    }

    /*
     * Remove files from storage
     */
    public function remove_file_from_storage($container,$name){
        try{
                $this->blobClient->deleteBlob($container, $name);
        }catch( Exception $e){
                $err_msg = "Error uploading file to azure storage";
                return new WP_Error('exception',$err_msg);
        }
    }
}