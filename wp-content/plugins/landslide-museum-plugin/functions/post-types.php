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
    $ls_archive_pages['exhibition'] = 'exhibitions';
    $ls_archive_pages['artwork'] = 'artwork';
    $ls_archive_pages['artist'] = 'artists';

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
        return '';
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

// Add messages for pages with page specific content sections
function ls_add_page_specific_messages($field) {

    global $post;
    
    $archive_pages = ls_archive_pages();

    /*
        $page_specific_messages['page'] - Page to display message on
        $page_specific_messages['pb_message'] - Message to display above page builder
        $page_specific_messages['ps_message'] - Message to display in page specific section
    */

    $page_specific_messages = array();

    // Post archive message
    $page_specific_messages[] = array(
        'page' => $archive_pages['post'],
        'pb_message' => 'The <strong>Page Specific Content</strong> section will display an archive of blog posts.',
        'ps_message' => 'This section displays an archive of blog posts.',
    );

    // Staff archive message
    $page_specific_messages[] = array(
        'page' => $archive_pages['staff'],
        'pb_message' => 'The <strong>Page Specific Content</strong> section will display an archive of staff members.',
        'ps_message' => 'This section displays an archive of staff members.',
    );

    // Events archive message
    $page_specific_messages[] = array(
        'page' => $archive_pages['tribe_events'],
        'pb_message' => 'The <strong>Page Specific Content</strong> section will display a calendar of upcoming events.',
        'ps_message' => 'This section displays a calendar of upcoming events.',
    );

    // Exhibitions archive message
    $page_specific_messages[] = array(
        'page' => $archive_pages['exhibition'],
        'pb_message' => 'The <strong>Page Specific Content</strong> section will display an archive of permanent and rotating exhibitions.',
        'ps_message' => 'This section displays an archive of permanent and rotating exhibitions.',
    );

    // Artwork archive message
    $page_specific_messages[] = array(
        'page' => $archive_pages['artwork'],
        'pb_message' => 'The <strong>Page Specific Content</strong> section will display an archive of artwork.',
        'ps_message' => 'This section displays an an archive of artwork.',
    );

    // Artists archive message
    $page_specific_messages[] = array(
        'page' => $archive_pages['artists'],
        'pb_message' => 'The <strong>Page Specific Content</strong> section will display an archive of artists.',
        'ps_message' => 'This section displays an an archive of artists.',
    );

    if( is_admin() && $page_specific_messages ) {
        if( $post->post_type=='page' ) {
            foreach( $page_specific_messages as $message) {
                if( $post->post_name == $message['page'] ) {
                    if( $field['type']== 'flexible_content' ) {
                        $field['instructions'] = $message['pb_message'];
                    } else if( $field['type']== 'message' ) {
                        $field['message'] = $message['ps_message'].' No additional settings are required for this section.';
                    }
                }
            }
        }
    }

    return $field;

}
add_filter('acf/load_field/name=page_builder', 'ls_add_page_specific_messages');
add_filter('acf/load_field/key=field_65df6b4429772', 'ls_add_page_specific_messages');

/*------------------------------------*\
    
    Custom Post Types

    register_post_type - https://developer.wordpress.org/reference/functions/register_post_type/
    register_taxonomy - https://developer.wordpress.org/reference/functions/register_taxonomy/

\*------------------------------------*/

// Staff
function ls_create_staff_post_type()
{

    $name = 'staff';

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
    
}

add_action('init', 'ls_create_staff_post_type');

// Exhibitions
function ls_create_exhibition_post_type()
{
    
    $name = 'exhibition';

    // Exhibition Post Type
    $singular = 'Exhibition';
    $plural = 'Exhibitions';
    $menu = 'Exhibitions';

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

    register_post_type($name,
        array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-layout',
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
            'excerpt'
        ),
        'taxonomies' => array(
            'exhibition-type',
        ),
        'can_export' => true,
        'rewrite' => array( 'slug' => ls_get_archive_page_slug( $name ), 'with_front' => false)
    ));

    // Exhibition Type Taxonomy
    $singular = 'Type';
    $plural = 'Types';
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
        'rewrite'           => array( 'slug' => ls_get_archive_page_slug( $name ), 'with_front' => false ),
    );

    register_taxonomy( 'exhibition-type', array( $name ), $args );

    // Gallery Post Type
    $name = 'gallery';

    // Gallery Post Type
    $singular = 'Gallery';
    $plural = 'Galleries';
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
        'not_found_in_trash' => 'No '.$plural.' found in Trash',
    );

    register_post_type($name,
        array(
        'labels' => $labels,
        'public' => true,
        'show_in_menu' => 'edit.php?post_type=exhibition',
        'hierarchical' => false,
        'has_archive' => false,
        'menu_icon' => 'dashicons-layout',
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
            'excerpt'
        ),
        'taxonomies' => array(
            'collection',
        ),
        'can_export' => true,
        'rewrite' => array( 'slug' => ls_get_archive_page_slug( $name ), 'with_front' => false)
    ));

}

add_action('init', 'ls_create_exhibition_post_type');

// Custom Exhibition Type rewrite rules
function ls_exhibition_type_rewrite_rules() {

    $terms = get_terms( array(
        'taxonomy' => 'exhibition-type',
        'hide_empty' => false,
    ) );

    if( !empty( $terms ) ) {
        foreach( $terms as $term ) {
            add_rewrite_rule('^exhibitions/'.$term->slug.'/?$','index.php?exhibition-type='.$term->slug, 'top');
            add_rewrite_rule('^exhibitions/'.$term->slug.'/page/([0-9]{1,})/?$','index.php?exhibition-type='.$term->slug.'&paged=$matches[1]', 'top');
        }
    }

}
add_action('init', 'ls_exhibition_type_rewrite_rules');

// Custom rewrite rules for exhibition pagination
function ls_rewrite_exhibition_pagination() {
    add_rewrite_rule('^exhibitions/([^/]+)/page/([0-9]{1,})/?$','index.php?exhibition=$matches[1]&paged=$matches[2]', 'top');
}
add_action('init', 'ls_rewrite_exhibition_pagination');

function ls_disable_exhibition_canonical_redirect($redirect_url) { 
    if( is_singular('exhibition') ) {
        $redirect_url = false; 
    }

    return $redirect_url; 
}
add_filter('redirect_canonical','ls_disable_exhibition_canonical_redirect');  

// Collections
function ls_create_artwork_post_type()
{
    
    $name = 'artwork';

    // Artwork Post Type
    $singular = 'Artwork';
    $plural = 'Artworks';
    $menu = 'Artwork';

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

    register_post_type($name,
        array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-art',
        'supports' => array(
            'title',
            'editor',
            'thumbnail'
        ),
        'taxonomies' => array(
            'collection',
        ),
        'can_export' => true,
        'rewrite' => array( 'slug' => ls_get_archive_page_slug( $name ), 'with_front' => false)
    ));

    // Collection Taxonomy
    $singular = 'Collection';
    $plural = 'Collections';
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
        'rewrite'           => array( 'slug' => '/collections', 'with_front' => false ),
    );

    register_taxonomy( 'collection', array( $name ), $args );

    // Artist Post Type
    $name = 'artist';

    // Artist Post Type
    $singular = 'Artist';
    $plural = 'Artists';
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
        'not_found_in_trash' => 'No '.$plural.' found in Trash',
    );

    register_post_type($name,
        array(
        'labels' => $labels,
        'public' => true,
        'show_in_menu' => 'edit.php?post_type=artwork',
        'hierarchical' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-layout',
        'supports' => array(
            'title',
            'editor',
            'thumbnail'
        ),
        'taxonomies' => array(),
        'can_export' => true,
        'rewrite' => array( 'slug' => ls_get_archive_page_slug( $name ), 'with_front' => false)
    ));

}
add_action('init', 'ls_create_artwork_post_type');

// Custom rewrite rules for artist pagination
function ls_rewrite_artist_pagination() {
    add_rewrite_rule('^artists/([^/]+)/page/([0-9]{1,})/?$','index.php?artist=$matches[1]&paged=$matches[2]', 'top');
}
add_action('init', 'ls_rewrite_artist_pagination');

function ls_disable_artist_canonical_redirect($redirect_url) { 
    if( is_singular('artist') ) {
        $redirect_url = false; 
    }

    return $redirect_url; 
}
add_filter('redirect_canonical','ls_disable_artist_canonical_redirect'); 

function ls_change_artwork_menu_label() {
    global $menu;

    foreach($menu as $key => $item) {
        if($item[0]=='Artwork') {
            $menu[$key][0] = 'Collection';
        }
    }
}
add_action( 'admin_menu', 'ls_change_artwork_menu_label' );

// Authors
function ls_create_author_taxonomy()
{
    // Type Taxonomy
    $singular = 'Author';
    $plural = 'Authors';
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
        'query_var'         => true,
        'show_in_quick_edit'=> false,
        'meta_box_cb'       => false,
        'rewrite'           => array( 'slug' => 'author', 'with_front' => false ),
    );

    register_taxonomy( 'post-author', array( 'post' ), $args );

}
add_action('init', 'ls_create_author_taxonomy');

// Change author name in Yoast SEO metadata
function ls_display_author( $author_name) {
    if( is_singular('post') ) {
        $author = get_field('author');
        if( $author ) {
            $author_name = $author->name;
        } else {
            $author_name = false;
        }
    }

    return $author_name;
}
add_filter( 'wpseo_meta_author', 'ls_display_author' );

function ls_change_slack_data( $data, $presentation ) {
    if( is_singular('post') ) {
        $author = get_field('author');
        if( $author ) {
            $data['Written by'] = $author->name;
        } else {
            unset( $data['Written by'] );
        }
    }

    return $data;
}
add_filter( 'wpseo_enhanced_slack_data', 'ls_change_slack_data', 10, 2 );

// Remove author from schema
add_filter( 'wpseo_schema_needs_author', '__return_false' );

function ls_remove_author_wpseo_article_schema( $graph_piece ) {

    unset( $graph_piece['author'] );

    return $graph_piece;

}
add_filter( 'wpseo_schema_article', 'ls_remove_author_wpseo_article_schema' );
add_filter( 'wpseo_schema_webpage', 'ls_remove_author_wpseo_article_schema' );