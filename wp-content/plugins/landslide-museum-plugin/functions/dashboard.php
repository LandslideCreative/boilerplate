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
    remove_meta_box('tribe_dashboard_widget', 'dashboard', 'normal');
}
add_action('admin_init', 'ls_remove_dashboard_widgets');

// Remove metaboxes on post pages
function ls_remove_meta_boxes() {
    // Yoast SEO
    remove_meta_box( 'wpseo_meta', 'post-type', 'normal' );
}
// add_action('add_meta_boxes', 'ls_remove_meta_boxes', 100);

// Remove comments column
function ls_remove_comments_column($columns){
    unset($columns['author']);
    unset($columns['comments']);

    return $columns;
}
add_filter('manage_edit-page_columns', 'ls_remove_comments_column');
add_filter('manage_edit-post_columns', 'ls_remove_comments_column');
add_filter('manage_edit-tribe_events_columns', 'ls_remove_comments_column');

// Add dashboard styles
function ls_add_dashboard_scripts() {

    // LS dashboard styles
    wp_register_style('ls-dashboard', LS_PLUGIN_URL . '/assets/css/ls-dashboard.css', array(), LS_BUILD_VERSION);
    wp_enqueue_style('ls-dashboard');

    // LS dashboard javascript
    wp_register_script('ls-dashboard', LS_PLUGIN_URL . '/assets/js/ls-dashboard.js', array(), LS_BUILD_VERSION, true);
    wp_enqueue_script('ls-dashboard');

}
add_action('admin_enqueue_scripts', 'ls_add_dashboard_scripts');

// WP Rocket - Remove Rocket Insights column from posts/pages list page
function ls_remove_rocket_insights($columns) {
    unset($columns['rocket_insights']);
    return $columns;
}
add_filter('manage_posts_columns', 'ls_remove_rocket_insights', 99);

// Yoast SEO - Move SEO metabox lower
function ls_yoast_to_bottom() {
    return 'low';
}
add_filter( 'wpseo_metabox_prio', 'ls_yoast_to_bottom');

/* DASHBOARD WIDGET */

// Add Landslide dashboard widget
function ls_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'dashboard_landslide_creative',
        'Need Help?',
        'ls_dashboard_widget_content',
        NULL,
        NULL,
        'normal',
        'high'
    );
}
add_action( 'wp_dashboard_setup', 'ls_add_dashboard_widget' );

// Dashboard widget content
function ls_dashboard_widget_content() {
    $output = '<div class="main">
        <p><a href="https://landslidecreative.com"><img src="'.LS_PLUGIN_URL.'/assets/img/landslide-creative-logo.png'.'" width="220" /></a></p>'.
        get_field('dashboard_widget', 'options').
    '</div>
    <div class="sub">
        <p>If you have questions or need help managing your site, please email us at <strong><a href="mailto:help@landslidecreative.com">help@landslidecreative.com</a></strong>.</p>
    </div>';

    echo $output;
}

// Move At a Glance widget
function ls_move_dashboard_right_now() {
    global $wp_meta_boxes;

    $at_a_glance = $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'];

    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);

    $wp_meta_boxes['dashboard']['side']['core']['dashboard_right_now'] = $at_a_glance;
}

add_action('wp_dashboard_setup', 'ls_move_dashboard_right_now');

// Save dashboard widget field to plugin
function ls_dashboard_widget_acf_json_save_path( $path ) {
    
    $path = LS_PLUGIN_PATH . '/assets/acf-json';
    
    return $path;
    
}
add_filter('acf/settings/save_json/name=Dashboard Widget', 'ls_dashboard_widget_acf_json_save_path');



