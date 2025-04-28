<?php // Admin menu functions

// Reorder admin menu
function ls_custom_menu_order($menu_ord) {
    
    if (!$menu_ord) return true;
    
    return array(
        'index.php', // Dashboard

        'separator1', // First separator
        'edit.php', // Posts
        'edit.php?post_type=page', // Pages
        'edit.php?post_type=tribe_events', // Events
        'edit.php?post_type=staff', // Staff
        'edit.php?post_type=exhibition', // Exhibitions
        'edit.php?post_type=artwork', // Collections
        'theme-settings', // Theme Settings
        
        'separator2', // Second separator
        'gf_edit_forms', // Forms
        'upload.php', // Media
        'users.php', // Users

        'separator-last', // Last separator
        
        'themes.php', // Appearance
        'wpseo_dashboard', // SEO
        'options-general.php', // Settings
        'plugins.php', // Plugins
        'tools.php', // Tools        
    );
    
}
add_filter('custom_menu_order', 'ls_custom_menu_order'); // Activate custom_menu_order
add_filter('menu_order', 'ls_custom_menu_order');

// Remove pages from admin menu
function ls_remove_pages_from_menu() {

    remove_menu_page( 'edit-comments.php');

}
add_action( 'admin_menu', 'ls_remove_pages_from_menu' );
