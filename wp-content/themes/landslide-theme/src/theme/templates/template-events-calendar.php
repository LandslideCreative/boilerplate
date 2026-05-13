<?php /* Template Name: Events Calendar */ 

$filters = array();
if( isset($_GET['tribe_events_cat']) ) {
	$filters['tribe_events_cat'] = $_GET['tribe_events_cat'];
	if( $filters['tribe_events_cat']!='' ) {
		wp_redirect( get_term_link($filters['tribe_events_cat'], 'tribe_events_cat').'#event-list', 301 );
	}
}

get_header();

if (have_posts()): while (have_posts()) : the_post();

	if( is_archive() ) {

		$page_slug = get_page_by_path(ls_get_archive_page_slug( 'tribe_events' ));

		$post = $page_slug;
		setup_postdata($post); 
			if( $post ) { ?>
				<main role="main" id="main-content">

					<?php get_template_part('partials/header/events'); ?>

					<?php get_template_part('partials/page', 'builder'); ?>

				</main>
			<?php }
		wp_reset_postdata();

	} else { ?>

		<main role="main" id="main-content">

			<?php the_content(); ?>

			<?php get_template_part('partials/page', 'builder'); ?>
			
		</main>

	<?php }

endwhile; endif;

get_footer();