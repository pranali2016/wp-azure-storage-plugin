<?php
/*
Plugin Name: WP Offload Azure
Description: Automatically copies media uploads to Azure storage for storage and delivery.
Author: Promact
Version: 1
Author URI: http://promactinfo.com
Network: True
Text Domain: azzure-web-services

// Copyright (c) 2017 Delicious Brains. All rights reserved.

*/

/* Version check */
global $wp_version;
$exit_msg='WP Azure This requires WordPress 2.5 or newer.Please update!';
if (version_compare($wp_version,"2.5","<"))
{	
	exit($exit_msg);	
}

function azure_init( ) {
$abspath = dirname( __FILE__ );
	require_once $abspath . '/classes/azure-plugin-base.php';
	require_once $abspath . '/classes/azure-web-services.php';
	require_once $abspath . '/classes/azure-storage-and-cdn.php';
	require_once $abspath . '/vendor/autoload.php';
	global $azure_web_services;
	$azure_web_services = new Azure_Web_Services( __FILE__ );
	$as3cf = new Azure_Storage_And_Cdn( __FILE__, $azure_web_services );
}
add_action('init','azure_init' );


?>
