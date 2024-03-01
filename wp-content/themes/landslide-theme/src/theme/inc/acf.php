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

// Display event dates in relationship field
function ls_events_calendar_relationship_result( $text, $post, $field, $post_id ) {
    $args['post__in'] = array($post->ID);

    $events = tribe_get_events($args, true);

    if ( $events->have_posts() ) {
        $start_date = tribe_get_start_date($events->posts[0]->ID, false, 'm/d/y');
        $text .= ' ['.$start_date.']';
    }

    return $text;
}
add_filter('acf/fields/relationship/result/name=events', 'ls_events_calendar_relationship_result', 10, 4);

// Filter past events from relationship field
function ls_events_calendar_filter_relationship_result( $options, $field, $post_id ) {
    $timezone = wp_timezone_string();
    $now = new DateTime("now", new DateTimeZone($timezone) );

    $options['meta_query'] = array(
        array(
            'key' => '_EventEndDate',
            'compare' => '>=',
            'value' =>  $now->format('Y-m-d H:i:s'),
            'type' => 'DATETIME'
        )
    );

    $options['meta_key'] = '_EventStartDate';
    $options['meta_type'] = 'DATETIME';
    $options['orderby'] = 'meta_value';

    return $options;
}
add_filter('acf/fields/relationship/query/name=events', 'ls_events_calendar_filter_relationship_result', 10, 3);