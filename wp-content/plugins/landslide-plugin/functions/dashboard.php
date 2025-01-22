<?php 

// Remove social profile metadata
function ls_remove_user_social( $contactmethods ) {
    unset( $contactmethods['facebook'] );
    unset( $contactmethods['instagram'] );
    unset( $contactmethods['linkedin'] );
    unset( $contactmethods['myspace'] );
    unset( $contactmethods['pinterest'] );
    unset( $contactmethods['soundcloud'] );
    unset( $contactmethods['tumblr'] );
    unset( $contactmethods['twitter'] );
    unset( $contactmethods['youtube'] );
    unset( $contactmethods['wikipedia'] );

    return $contactmethods;
}
add_filter('user_contactmethods', 'ls_remove_user_social', 99, 1);

// Remove extraneous dashboard widgets
function ls_remove_dashboard_widgets() {
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'normal');
    remove_meta_box('wpseo-wincher-dashboard-overview', 'dashboard', 'normal');
}
add_action('admin_init', 'ls_remove_dashboard_widgets');

// Remove metaboxes on post pages
function ls_remove_meta_boxes() {
    //Yoast SEO
    remove_meta_box( 'wpseo_meta', 'post-type', 'normal' );
}
// add_action('add_meta_boxes', 'ls_remove_meta_boxes', 100);

// Move yoast priority lower
function ls_yoast_to_bottom() {
    return 'low';
}
add_filter( 'wpseo_metabox_prio', 'ls_yoast_to_bottom');

// Remove comments column
function ls_remove_comments_column($columns){
    unset($columns['author']);
    unset($columns['comments']);

    return $columns;
}
add_filter('manage_edit-page_columns', 'ls_remove_comments_column');
add_filter('manage_edit-post_columns', 'ls_remove_comments_column');
add_filter('manage_edit-tribe_events_columns', 'ls_remove_comments_column');