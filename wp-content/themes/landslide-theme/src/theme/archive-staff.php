<?php // Staff Post Type Archive

// Redirect taxonomies
$filters = array();
$filters['department'] = get_query_var('department', '');
if( $filters['department']!='' ) {
	wp_redirect( get_term_link($filters['department'], 'department').'#staff-list', 301 );
}

$page_slug = get_page_by_path(ls_get_archive_page_slug( 'staff' ));

get_header(); 

$post = $page_slug;
setup_postdata($post); 
	if( $post ) { ?>
		<main role="main" id="main-content">

			<?php get_template_part('partials/header/page'); ?>

			<?php get_template_part('partials/page', 'builder'); ?>

		</main>
	<?php }
wp_reset_postdata();

get_footer();