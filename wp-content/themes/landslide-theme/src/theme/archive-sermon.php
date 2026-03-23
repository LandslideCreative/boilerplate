<?php // Sermon Post Type Archive

// Redirect categories
$filters = array();
$filters['topic'] = get_query_var('topic', '');
$filters['speaker'] = get_query_var('speaker', '');
$filters['bible'] = get_query_var('bible', '');
if( $filters['topic']!='' && $filters['speaker']=='' && $filters['bible']=='' ) {
	wp_redirect( get_term_link($filters['topic'], 'topic').'#sermon-list', 301 );
} else if( $filters['speaker']!='' && $filters['topic']=='' && $filters['bible']=='' ) {
	wp_redirect( get_term_link($filters['speaker'], 'speaker').'#sermon-list', 301 );
} else if( $filters['bible']!='' && $filters['topic']=='' && $filters['speaker']=='' ) {
	wp_redirect( get_term_link($filters['bible'], 'bible').'#sermon-list', 301 );
}

$page_slug = get_page_by_path(ls_get_archive_page_slug( 'sermon' ));

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