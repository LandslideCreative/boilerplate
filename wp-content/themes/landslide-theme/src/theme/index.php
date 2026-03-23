<?php // Post Archive

// Redirect categories
$filters = array();
$filters['category'] = get_query_var('category_name', '');
if( $filters['category']!='' ) {
	wp_redirect( get_term_link($filters['category'], 'category').'#post-list', 301 );
}

$page_slug = get_post(get_option( 'page_for_posts' ));

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