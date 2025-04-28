<?php // Query functions

// Staff - Show all
function ls_show_all_staff( $query ) {
	if ( $query->is_main_query() && !is_admin() && is_post_type_archive( 'staff' ) ) { 
		$query->set( 'posts_per_page', -1 );
	}
}
add_action( 'pre_get_posts', 'ls_show_all_staff' );

// Exhibitions - Limit to past exhibitions
function ls_limit_rotating_exhibitions( $query ) {
	$now = new DateTime('NOW', new DateTimeZone('America/New_York'));

	if ( $query->is_main_query() && !is_admin() && (is_post_type_archive( 'exhibition' ) || is_tax('exhibition-type', 'rotating')) ) { 
		$query->set( 'tax_query', array(
			array(
				'taxonomy' => 'exhibition-type',
				'field' => 'slug',
				'terms' => 'rotating'
			)
		));
		$query->set( 'meta_query', array(
        	'relation' => 'AND',
        	'event_date' => array(
            	'key'     => 'end_date',
            	'compare' => '<',
            	'value'   => $now->format('Ymd'),
            	'type'    => 'numeric',
        	),
        	array(
            	'key'     => 'end_date',
            	'compare' => '!=',
            	'value'   => ''
        	)
    	));
    	$query->set( 'meta_key', 'end_date' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'DESC' );
	}
}
add_action( 'pre_get_posts', 'ls_limit_rotating_exhibitions' );