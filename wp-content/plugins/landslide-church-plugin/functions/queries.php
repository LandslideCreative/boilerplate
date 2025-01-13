<?php // Query functions

// Staff - Show all
function ls_show_all_staff( $query ) {
	if ( $query->is_main_query() && is_post_type_archive( 'staff' ) ) { 
		$query->set( 'posts_per_page', -1 );
	}
}
add_action( 'pre_get_posts', 'ls_show_all_staff' );