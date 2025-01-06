<?php

/*
Plugin Name: CHURCH NAME Plugin
Plugin URI: https://CHURCH.DOMAIN
Description: Plugin that adds necessary content options for the CHURCH NAME theme
Version: 1.0.0
Author: Landslide Creative
Author URI: https://landslidecreative.com
*/

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

define('LS_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('LS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));

// Check if plugin class already exists
if( !class_exists('landslide_plugin') ) {

	class landslide_plugin {	
		
		function __construct() {		
			
			include_once('functions/admin-menu.php');
			include_once('functions/dashboard.php');
			include_once('functions/ls-page-builder.php');
			include_once('functions/ls-site-variables.php');							
			include_once('functions/post-types.php');
			include_once('functions/queries.php');
			// include_once('functions/shortcodes.php');

		}
		
	}

	new landslide_plugin();

}