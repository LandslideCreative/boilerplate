<?php

/*
Returns an array of pages associated with post type archives.

To add archives, use this format:
$ls_archive_pages['post-type'] = 'page_slug'

*/
function ls_archive_pages() {
    $ls_archive_pages = array();

    $ls_archive_pages['post'] = 'blog';
    $ls_archive_pages['staff'] = 'staff';
    if( function_exists('tribe_is_event') ) {
        $ls_archive_pages['tribe_events'] = 'events';
    }
    $ls_archive_pages['sermon'] = 'sermons';

    return $ls_archive_pages;
}


// Get page associated with a post type archive
function ls_get_archive_page_slug( $post_type ) {
    $page_slug = false;
    $ls_archive_pages = ls_archive_pages();    

    if( array_key_exists($post_type, $ls_archive_pages) ) {
        $page_slug = $ls_archive_pages[$post_type];
    }

    return $page_slug;
}

// Disable slug changes on pages associated with a post type archive
function ls_disable_archive_permalink_changes( $html ) {
    global $post;
    $ls_archive_pages = ls_archive_pages();

    if( is_admin() && $post->post_type=='page' && in_array($post->post_name, $ls_archive_pages) ) {
        return '<p><strong>Permalink:</strong> <a href="'.get_permalink($post).'">'.get_permalink($post).'</a><br />Changes to the permalink are disabled because this is an archive page.</p>';
    } else {
        return $html;
    }
    
}
add_filter( 'get_sample_permalink_html', 'ls_disable_archive_permalink_changes');

// Disable Yoast settings on archive pages
function ls_disable_archive_yoast() {
    global $post;
    $ls_archive_pages = ls_archive_pages();

    if( is_admin() && $post->post_type=='page' && in_array($post->post_name, $ls_archive_pages) ) {
        remove_meta_box( 'wpseo_meta', 'page', 'normal' );
    }
    
}
add_action( 'add_meta_boxes', 'ls_disable_archive_yoast', 99 );

/*------------------------------------*\
    
    Custom Post Types

    register_post_type - https://developer.wordpress.org/reference/functions/register_post_type/
    register_taxonomy - https://developer.wordpress.org/reference/functions/register_taxonomy/

\*------------------------------------*/

// Staff
function ls_create_staff_post_type()
{

    // Staff Post Type
    $name = 'staff';
    $singular = 'Staff Member';
    $plural = 'Staff Members';
    $menu = 'Staff';

    $labels = array(
        'name' => $menu,
        'singular_name' => $singular,
        'add_new' => 'Add '.$singular,
        'add_new_item' => 'Add New '.$singular,
        'edit_item' => 'Edit '.$singular,
        'new_item' => 'New '.$singular,
        'view_item' => 'View '.$singular,
        'view_items' => 'View '.$plural,
        'search_items' => 'Search '.$plural,
        'not_found' => 'No '.$plural.' found',
        'not_found_in_trash' => 'No '.$plural.' found in Trash',
        'featured_image' => $singular.' Headshot',
        'set_featured_image' => 'Set '.$singular.' Headshot', 
        'remove_featured_image' => 'Remove '.$singular.' Headshot',
        'use_featured_image' => 'Use '.$singular.' Headshot',
    );

    register_post_type($name,
        array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-businessperson',
        'supports' => array(
            'title',
            'editor',
            'thumbnail'
        ),
        'taxonomies' => array(
            'department',
        ),
        'can_export' => true,
        'rewrite' => array( 'slug' => ls_get_archive_page_slug( $name ), 'with_front' => false)
    ));

    // Department Taxonomy
    $singular = 'Department';
    $plural = 'Departments';
    $menu = $plural;
    
    $labels = array(
        'name'              => $plural,
        'singular_name'     => $singular,
        'search_items'      => 'Search '.$plural,
        'all_items'         => 'All '.$plural,
        'parent_item'       => 'Parent '.$singular,
        'parent_item_colon' => 'Parent '.$singular.':',
        'edit_item'         => 'Edit '.$singular,
        'update_item'       => 'Update '.$singular,
        'add_new_item'      => 'Add New '.$singular,
        'new_item_name'     => 'New '.$singular.' Name',
        'menu_name'         => $menu,
        'not_found'         => 'No '.$plural.' found.'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => ls_get_archive_page_slug( $name ).'/department', 'with_front' => false ),
    );

    register_taxonomy( 'department', array( $name ), $args );

}

add_action('init', 'ls_create_staff_post_type');

// Sermons
function ls_create_sermon_post_type()
{

    $name = 'sermon';

    // Speaker Taxonomy
    $singular = 'Speaker';
    $plural = 'Speakers';
    $menu = $plural;
    
    $labels = array(
        'name'              => $plural,
        'singular_name'     => $singular,
        'search_items'      => 'Search '.$plural,
        'all_items'         => 'All '.$plural,
        'parent_item'       => 'Parent '.$singular,
        'parent_item_colon' => 'Parent '.$singular.':',
        'edit_item'         => 'Edit '.$singular,
        'update_item'       => 'Update '.$singular,
        'add_new_item'      => 'Add New '.$singular,
        'new_item_name'     => 'New '.$singular.' Name',
        'menu_name'         => $menu,
        'not_found'         => 'No '.$plural.' found.'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'meta_box_cb'       => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => ls_get_archive_page_slug( $name ).'/speaker', 'with_front' => false ),
    );

    register_taxonomy( 'speaker', array( $name ), $args );

    // Topic Taxonomy
    $singular = 'Topic';
    $plural = 'Topics';
    $menu = $plural;
    
    $labels = array(
        'name'              => $plural,
        'singular_name'     => $singular,
        'search_items'      => 'Search '.$plural,
        'all_items'         => 'All '.$plural,
        'parent_item'       => 'Parent '.$singular,
        'parent_item_colon' => 'Parent '.$singular.':',
        'edit_item'         => 'Edit '.$singular,
        'update_item'       => 'Update '.$singular,
        'add_new_item'      => 'Add New '.$singular,
        'new_item_name'     => 'New '.$singular.' Name',
        'menu_name'         => $menu,
        'not_found'         => 'No '.$plural.' found.'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'meta_box_cb'       => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => ls_get_archive_page_slug( $name ).'/topic', 'with_front' => false ),
    );

    register_taxonomy( 'topic', array( $name ), $args );

    // Book of the Bible Taxonomy
    $singular = 'Book of the Bible';
    $plural = 'Books of the Bible';
    $menu = $plural;
    
    $labels = array(
        'name'              => $plural,
        'singular_name'     => $singular,
        'search_items'      => 'Search '.$plural,
        'all_items'         => 'All '.$plural,
        'parent_item'       => 'Parent '.$singular,
        'parent_item_colon' => 'Parent '.$singular.':',
        'edit_item'         => 'Edit '.$singular,
        'update_item'       => 'Update '.$singular,
        'add_new_item'      => 'Add New '.$singular,
        'new_item_name'     => 'New '.$singular.' Name',
        'menu_name'         => $menu,
        'not_found'         => 'No '.$plural.' found.'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'meta_box_cb'       => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => ls_get_archive_page_slug( $name ).'/bible', 'with_front' => false ),
    );

    register_taxonomy( 'bible', array( $name ), $args );

    // Series Taxonomy
    $singular = 'Series';
    $plural = 'Series';
    $menu = $plural;
    
    $labels = array(
        'name'              => $plural,
        'singular_name'     => $singular,
        'search_items'      => 'Search '.$plural,
        'all_items'         => 'All '.$plural,
        'parent_item'       => 'Parent '.$singular,
        'parent_item_colon' => 'Parent '.$singular.':',
        'edit_item'         => 'Edit '.$singular,
        'update_item'       => 'Update '.$singular,
        'add_new_item'      => 'Add New '.$singular,
        'new_item_name'     => 'New '.$singular.' Name',
        'menu_name'         => $menu,
        'not_found'         => 'No '.$plural.' found.'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'meta_box_cb'       => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => ls_get_archive_page_slug( $name ).'/series', 'with_front' => false ),
    );

    register_taxonomy( 'series', array( $name ), $args );

    // Sermon Post Type
    $singular = 'Sermon';
    $plural = 'Sermons';
    $menu = 'Sermons';

    $labels = array(
        'name' => $menu,
        'singular_name' => $singular,
        'add_new' => 'Add '.$singular,
        'add_new_item' => 'Add New '.$singular,
        'edit_item' => 'Edit '.$singular,
        'new_item' => 'New '.$singular,
        'view_item' => 'View '.$singular,
        'view_items' => 'View '.$plural,
        'search_items' => 'Search '.$plural,
        'not_found' => 'No '.$plural.' found',
        'not_found_in_trash' => 'No '.$plural.' found in Trash',
    );

    register_post_type('sermon',
        array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-video-alt3',
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
            'excerpt'
        ),
        'taxonomies' => array(
            'speaker',
        ),
        'can_export' => true,
        'rewrite' => array( 'slug' => ls_get_archive_page_slug( $name ), 'with_front' => false)
    ));
}

add_action('init', 'ls_create_sermon_post_type');

// Announcements
function ls_create_announcement_post_type()
{

    $name = 'announcement';

    // Category
    $singular = 'Category';
    $plural = 'Categories';
    $menu = $plural;
    
    $labels = array(
        'name'              => $plural,
        'singular_name'     => $singular,
        'search_items'      => 'Search '.$plural,
        'all_items'         => 'All '.$plural,
        'parent_item'       => 'Parent '.$singular,
        'parent_item_colon' => 'Parent '.$singular.':',
        'edit_item'         => 'Edit '.$singular,
        'update_item'       => 'Update '.$singular,
        'add_new_item'      => 'Add New '.$singular,
        'new_item_name'     => 'New '.$singular.' Name',
        'menu_name'         => $menu,
        'not_found'         => 'No '.$plural.' found.'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => false,
        'query_var'         => true,
        'rewrite'           => false,
    );

    register_taxonomy( 'announcement-category', array( $name ), $args );

    // Serving Opportunity Post Type
    $singular = 'Announcement';
    $plural = 'Announcements';
    $menu = $plural;

    $labels = array(
        'name' => $menu,
        'singular_name' => $singular,
        'add_new' => 'Add '.$singular,
        'add_new_item' => 'Add New '.$singular,
        'edit_item' => 'Edit '.$singular,
        'new_item' => 'New '.$singular,
        'view_item' => 'View '.$singular,
        'view_items' => 'View '.$plural,
        'search_items' => 'Search '.$plural,
        'not_found' => 'No '.$plural.' found',
        'not_found_in_trash' => 'No '.$plural.' found in Trash'
    );

    register_post_type('announcement',
        array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'has_archive' => false,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => array(
            'title',
            'editor'
        ),
        'taxonomies' => array(
            'announcement-category'
        ),
        'can_export' => true,
        'rewrite' => array( 'slug' => 'announcement', 'with_front' => false)
    ));
}

add_action('init', 'ls_create_announcement_post_type');