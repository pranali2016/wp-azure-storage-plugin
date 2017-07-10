<?php
/*
Plugin Name: WP Offload Azure - Assets Addon
Description: Automatically copies media uploads to Azure storage for storage and delivery.
Author: Promact
Version: 1.0
Author URI: http://promactinfo.com
Network: True
Text Domain: wp-offload-azure-assets
*/

require_once ABSPATH . 'wp-admin/includes/plugin.php';
if(is_plugin_active('azure-cdn/azure-cdn.php')){
	add_action('azure_init','azure_assets_init');
}else{
	$exit_msg = printf( 'The WP Offload Azure - Assets Addon plugin has been disabled as it requires the WP Offload Azure plugin.');
	exit($exit_msg);
}
function azure_assets_init(  $azure_storage_service) {
	$abspath = dirname( __FILE__ );
	require_once $abspath . '/classes/asset-storage-manage.php';
	
	$assets = new Asset_Storage_Manage(__FILE__ ,$azure_storage_service);
}

?>
