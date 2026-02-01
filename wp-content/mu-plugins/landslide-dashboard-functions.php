<?php
/*
Plugin Name: Landslide Creative Dashboard
Plugin URI: https://landslidecreative.com
Description: Plugin to simplify the dashboard for end users
Author: Landslide Creative
Version: 1.0
Author URI: https://landslidecreative.com
*/

defined( 'ABSPATH' ) || exit;

if ( ! version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	return;
}

// Remove pages from admin menu for end users
function ls_mu_remove_pages_from_menu() {
    
    $current_user = wp_get_current_user();
    
    if( strpos($current_user->user_login, 'landslide')!==0 ) {
        remove_menu_page( 'edit.php?post_type=acf-field-group');
        remove_menu_page( 'plugins.php');
        remove_submenu_page( 'options-general.php', 'wprocket' );
    }

}
add_action( 'admin_menu', 'ls_mu_remove_pages_from_menu' );

// Hide Wordpress update nag
function ls_mu_hide_update_nag() {
    remove_action('admin_notices', 'update_nag', 3);
}
add_action('admin_menu', 'ls_mu_hide_update_nag');

// ACF - Remove editing rights for admin users
function ls_mu_acf_show_admin($show) {

    $current_user = wp_get_current_user();
    
    if( strpos($current_user->user_login, 'landslide')!==0 ) {
        $show_acf = 0;
    } else {
        $show_acf = 1;
    }

    return $show_acf;

}
add_filter('acf/settings/show_admin', 'ls_mu_acf_show_admin');

// ACF - Remove unsafe html notice
add_filter( 'acf/admin/prevent_escaped_html_notice', '__return_true' );

// ACF - Allow unsafe html in WYSIWYG fields
add_filter( 'acf/the_field/allow_unsafe_html', function() { return true; }, 10, 2);

// Gravity Forms - Hide update notice
function ls_mu_remove_gf_update_notice() {
    
    echo '<style>
        #gf_dashboard_message.updated {
            display: none !important;
        }
    </style>';

}
add_action('admin_head', 'ls_mu_remove_gf_update_notice');

// The Events Calendar - Remove Tribe Events slug notice
function ls_mu_remove_tribe_events_notice() {
    
    echo '<style>
        .tribe-notice-archive-slug-conflict {
            display: none !important;
        }
    </style>';

}
add_action('admin_head', 'ls_mu_remove_tribe_events_notice');

// WP Rocket - Remove options from admin bar for end users
function ls_mu_remove_wp_rocket_admin_bar() {

    $current_user = wp_get_current_user();
    
    if( strpos($current_user->user_login, 'landslide')!==0 ) {
        echo '<style>
            #wp-admin-bar-wp-rocket #wp-admin-bar-rocket-settings,
            #wp-admin-bar-wp-rocket #wp-admin-bar-docs,
            #wp-admin-bar-wp-rocket #wp-admin-bar-faq,
            #wp-admin-bar-wp-rocket #wp-admin-bar-support {
                display: none !important;
            }
        </style>';
    }
}
add_action('admin_head', 'ls_mu_remove_wp_rocket_admin_bar');