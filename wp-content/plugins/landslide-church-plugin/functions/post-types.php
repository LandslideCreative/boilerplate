<?php 

/*------------------------------------*\
    
    Custom Post Types

    register_post_type - https://developer.wordpress.org/reference/functions/register_post_type/
    register_taxonomy - https://developer.wordpress.org/reference/functions/register_taxonomy/

\*------------------------------------*/

// Staff
function ls_create_staff_post_type()
{
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
        'rewrite'           => array( 'slug' => 'staff/department', 'with_front' => false ),
    );

    register_taxonomy( 'department', array( 'staff' ), $args );

    // Staff Post Type
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

    register_post_type('staff',
        array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'has_archive' => false,
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
        'rewrite' => array( 'slug' => 'staff', 'with_front' => false)
    ));
}

add_action('init', 'ls_create_staff_post_type');

// Sermons
function ls_create_sermon_post_type()
{
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
        'show_admin_column' => false,
        'meta_box_cb'       => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'sermons/speaker', 'with_front' => false ),
    );

    register_taxonomy( 'speaker', array( 'sermon' ), $args );

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
        'show_admin_column' => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'sermons/topic', 'with_front' => false ),
    );

    register_taxonomy( 'topic', array( 'sermon' ), $args );

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
        'show_admin_column' => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'sermons/bible', 'with_front' => false ),
    );

    register_taxonomy( 'bible', array( 'sermon' ), $args );

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
        'show_admin_column' => false,
        'meta_box_cb'       => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'sermons/series', 'with_front' => false ),
    );

    register_taxonomy( 'series', array( 'sermon' ), $args );

    // Sermons Post Type
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
        'rewrite' => array( 'slug' => 'sermons', 'with_front' => false)
    ));

    // Rewrite rule for sermon pagination
    add_rewrite_rule('^sermons/page/([0-9]{1,})/?$','index.php?pagename=sermons&paged=$matches[1]', 'top');
}

add_action('init', 'ls_create_sermon_post_type');