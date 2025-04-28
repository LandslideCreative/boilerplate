<?php // Artist Post Type Archive
$page_slug = get_page_by_path(ls_get_archive_page_slug( 'artist' ));

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