<?php /* Template Name: Events Calendar */ 
get_header(); 

if( is_archive() ) {
	$current_page = get_page_by_path('events'); 
} ?>

<main role="main" id="main-content">
	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

		<?php if( is_archive() ) {
			$post = $current_page;
			setup_postdata($post); 
			if( $post ) {
				get_template_part('partials/header/events');
			}
			wp_reset_postdata(); ?>

			<div class="page-section white-bg event-list" id="event-list">
				<div class="grid-container intro-section">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<p class="event-filter-label">Filter Events</p>
							<form id="event-filter">
								<div class="select-container">
									<select name="event-category">
										<option value="<?php echo get_post_type_archive_link('tribe_events'); ?>#event-list">
											All Categories
										</option>
										<?php if( is_tax() ) {
											$current_term = get_queried_object(); 
											$current_slug = $current_term->slug;
										} else {
											$current_slug = '';
										}
										$terms = get_terms( array(
											'taxonomy'	=> 'tribe_events_cat',
											'parent' => 0
										));
										foreach( $terms as $term ) { ?>
											<option value="<?php echo get_term_link($term); ?>#event-list" <?php if($term->slug==$current_slug) { echo 'selected'; } ?>>
												<?php echo $term->name; ?>
											</option>
										<?php }	?>
									</select>
								</div>

								<input type="submit" class="button hollow" value="Go">
							</form>
						</div>
					</div>
				</div>
				<div class="grid-container">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<?php the_content(); ?>
						</div>
					</div>
				</div>
			</div>

			<?php $post = $current_page;
			setup_postdata($post); 
			if( $post ) {
				get_template_part('partials/page', 'builder');
			}
			wp_reset_postdata();
		} else {
			the_content();

			get_template_part('partials/page', 'builder');
		} ?>

	<?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>
