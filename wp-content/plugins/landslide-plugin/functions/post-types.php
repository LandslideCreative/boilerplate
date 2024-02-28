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