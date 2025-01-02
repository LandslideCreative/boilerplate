<?php /* Template Name: Events Calendar */ 
get_header();

if (have_posts()): while (have_posts()) : the_post();

	if( is_archive() ) {
		$current_page = get_page_by_path('events'); 
	} ?>

	<main role="main" id="main-content">

		<?php if( is_archive() ) {

			// Events calendar header
			$post = $current_page;
			setup_postdata($post); 
				if( $post ) {
					get_template_part('partials/header/events');
				}
			wp_reset_postdata(); ?>

			<div class="page-section white-bg event-list" id="event-list">
				
				<?php get_template_part('partials/event/filter'); ?>

				<div class="grid-container">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<?php the_content(); ?>
						</div>
					</div>
				</div>

			</div>

			<?php // Events calendar page builder
			$post = $current_page;
			setup_postdata($post); 
				if( $post ) {
					get_template_part('partials/page', 'builder');
				}
			wp_reset_postdata();

		} else {

			the_content();

			get_template_part('partials/page', 'builder');
			
		} ?>
		
	</main>

<?php endwhile; endif;

get_footer();