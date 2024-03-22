<?php
/*------------------------------------*\
    ACF Functions
\*------------------------------------*/

// Set default ACF save path
function ls_acf_json_save_path( $path ) {
    
    $path = get_stylesheet_directory() . '/../acf-json';
    
    return $path;
    
}
add_filter('acf/settings/save_json', 'ls_acf_json_save_path');

// Load fields from default ACF save path
function ls_acf_json_load_path( $paths ) {
    
    unset($paths[0]);
    $paths[] = get_stylesheet_directory() . '/../acf-json';
    
    return $paths;
    
}
add_filter('acf/settings/load_json', 'ls_acf_json_load_path');

// ACF Google Maps API
/* function ls_acf_maps_key() {
    
    acf_update_setting('google_api_key', '');
}

add_action('acf/init', 'ls_acf_maps_key'); */

// Add ACF options page
if( function_exists('acf_add_options_page') ) {

    acf_add_options_page(array(
        'page_title'    => 'Theme Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));

    acf_add_options_sub_page(array(
        'page_title'    => 'General Information',
        'menu_title'    => 'General Info',
        'parent_slug'   => 'theme-settings',
    ));

    acf_add_options_sub_page(array(
        'page_title'    => 'Social Media',
        'menu_title'    => 'Social Media',
        'parent_slug'   => 'theme-settings',
    ));
}