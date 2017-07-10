<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if already defined
if ( ! class_exists( 'WP_Asset_Compatibility_check' ) ) {

	class WP_Asset_Compatibility_check {
		
		protected $notice_class = 'error';

		function __construct( $plugin_name, $plugin_slug, $plugin_file_path, $parent_plugin_name = null, $parent_plugin_slug = null, $parent_plugin_filename = null, $deactivate_if_not_compatible = false) {
			$this->plugin_name                    = $plugin_name;
			$this->plugin_slug                    = $plugin_slug;
			$this->plugin_file_path               = $plugin_file_path;
			$this->parent_plugin_name             = $parent_plugin_name;
			$this->parent_plugin_slug             = $parent_plugin_slug;
			$this->parent_plugin_filename         = $parent_plugin_filename;
			$this->deactivate_if_not_compatible   = $deactivate_if_not_compatible;

			add_action( 'admin_notices', array( $this, 'notices' ) );
			add_action( 'network_admin_notices', array( $this, 'notices' ) );
		}

		function notices() {
			if ( ! $this->user_capabilities() ){
				return;
			}
			$this->admin_notice();
		}
		
		function user_capabilities() {
			if ( is_multisite() ) {
				if ( ! current_user_can( 'manage_network_plugins' ) ) {
					return false; 
				}
			}
			else {
				if ( ! current_user_can( 'activate_plugins' ) || 
						!current_user_can( 'update_plugins' ) ||
							! current_user_can( 'install_plugins' )) {
					return false;
				}
			}

			return true;
		}			
		
		function admin_notice() {	
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			if(is_plugin_active($this->parent_plugin_slug)){
				$this->deactivate_if_not_compatible= false;
			}
		
			if ( $this->deactivate_if_not_compatible ) {
				$deactivated_msg = sprintf( __( 'The %s plugin has been disabled as it requires the WP Offload Azure plugin.' ), $this->plugin_name );

				$error_msg = $deactivated_msg ;
				$this->display_notice( $error_msg );
				
				deactivate_plugins( $this->plugin_file_path );
			} 
		}

		function display_notice( $message ) {
			printf( '<div id="azure-compat-notice' . $this->plugin_slug . '" class="' . $this->notice_class . ' azure-compatibility-notice"><p>%s</p></div>', $message );
		}
		
	}
}