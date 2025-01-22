<?php // Query functions

// Staff - Show all
function ls_show_all_staff( $query ) {
	if ( $query->is_main_query() && is_post_type_archive( 'staff' ) ) { 
		$query->set( 'posts_per_page', -1 );
	}
}
add_action( 'pre_get_posts', 'ls_show_all_staff' );

// Reorder sermons by sermon date
function ls_filter_sermon_query($query) {
    if ( is_post_type_archive('sermon') && $query->is_main_query() ) {
        $query->set( 'meta_key', 'sermon_date' );
        $query->set( 'orderby', 'meta_value_num' );
        $query->set( 'order', 'DESC');
    } else if ( is_tax('series') && $query->is_main_query() ) {
    	$query->set( 'meta_key', 'sermon_date' );
        $query->set( 'orderby', 'meta_value_num' );
        $query->set( 'order', 'ASC');
    }
}
add_action( 'pre_get_posts', 'ls_filter_sermon_query' );

// Dashboard - Create sermon date column
function ls_add_sermon_column_header($columns)
{
    $columns = array(
        'cb'        => '<input type="checkbox" />',
        'sermon-date' =>  'Date',
        'title'     => 'Title',
        'taxonomy-series'     => 'Series'
    );
    return $columns;
}
add_action('manage_sermon_posts_columns', 'ls_add_sermon_column_header');

// Dashboard - Set sermon date column content
function ls_add_sermon_column_content($column)
{
    global $post;
    if($column == 'sermon-date') {
        $sermon_date = get_field('sermon_date');
        echo date('F j, Y', strtotime($sermon_date));
    }
}
add_filter('manage_sermon_posts_custom_column', 'ls_add_sermon_column_content');

// Dashboard - Sort sermons by date
function ls_sermon_column_register_sortable( $columns )
{
    $columns['sermon-date'] = 'sermon-date';
    return $columns;
}
add_filter('manage_edit-sermon_sortable_columns', 'ls_sermon_column_register_sortable' );

function ls_sermon_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'sermon-date' == $vars['orderby'] ) {
	    $vars = array_merge( $vars, array(
	        'meta_key' => 'sermon_date',
	        'orderby' => 'meta_value'
	    ) );
	}

	return $vars;
}
add_filter( 'request', 'ls_sermon_column_orderby' );

// Dashboard - Resize sermon date column
function ls_resize_sermon_date_column() { ?>
    <style type="text/css">
        .edit-php .fixed .column-sermon-date {
            width: 150px;
        }
    </style>
<?php }
add_action( 'admin_enqueue_scripts', 'ls_resize_sermon_date_column' );

// Dashboard - Create announcement expiration date column
function ls_add_announcement_column_header($columns)
{
    $columns = array(
        'cb'        => '<input type="checkbox" />',
        'title'     => 'Title',
        'expiration-date' =>  'Expiration Date',
        'taxonomy-announcement-category'     => 'Categories'
    );
    return $columns;
}
add_action('manage_announcement_posts_columns', 'ls_add_announcement_column_header');

// Dashboard - Set sermon date column content
function ls_add_announcement_column_content($column)
{
    global $post;
    if($column == 'expiration-date') {
        $expiration_date = get_field('expiration_date');
        if( $expiration_date ) {
            echo date('m/d/Y', strtotime($expiration_date));
        }
    }
}
add_filter('manage_announcement_posts_custom_column', 'ls_add_announcement_column_content');

// Dashboard - Resize sermon date column
function ls_resize_announcement_date_column() { ?>
    <style type="text/css">
        .edit-php .fixed .column-expiration-date {
            width: 150px;
        }
    </style>
<?php }
add_action( 'admin_enqueue_scripts', 'ls_resize_announcement_date_column' );

