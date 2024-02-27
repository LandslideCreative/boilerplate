<?php 

// Save site variables field to plugin
function ls_site_variables_acf_json_save_path( $path ) {
    
    $path = LS_PLUGIN_PATH . '/assets/acf-json';
    
    return $path;
    
}
add_filter('acf/settings/save_json/name=Site Variables', 'ls_site_variables_acf_json_save_path');

// Load site variables field from plugin
function ls_site_variables_acf_json_load_path( $paths ) {

    $paths[] = LS_PLUGIN_PATH . '/assets/acf-json';

    return $paths;    
}
add_filter( 'acf/settings/load_json', 'ls_site_variables_acf_json_load_path' );

// Add Site variables ACF page
function ls_site_variables_acf() {
	
	if( function_exists('acf_add_options_page') ) {
	    acf_add_options_sub_page(array(
	        'page_title'    => 'Site Variables',
	        'menu_title'    => 'Site Variables',
	        'parent_slug'   => 'theme-settings',
	    ));
	}

}
add_action('acf/init', 'ls_site_variables_acf', 99);

// Site variables shortcode
function ls_site_variables_shortcode($atts, $content = null) {
    $output = '';
    $variable = $atts[0];

    $site_variables = get_field('site_variables', 'options');

    foreach( $site_variables as $site_variable ) {
    	if( $site_variable['name']==$variable ) {
    		$output = $site_variable['value'];
    	}
    }
    
    return $output;
}

add_shortcode('site-variable', 'ls_site_variables_shortcode');