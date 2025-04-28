<?php // Single Exhibition

if( get_field('type') ) {
	$exhibition_type = get_field('type');
	$exhibition_type_name = $exhibition_type->name;
}

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-exhibition">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<?php /* Title */ ?>
						<h5 class="label"><a href="<?php echo get_term_link($exhibition_type); ?>"><?php echo $exhibition_type_name; ?> Exhibition</a></h5>
						<h1 class="single-exhibition-title"><?php the_title(); ?></h1>

						<?php if( get_field('subtitle') ) { ?>
							<h2 class="single-exhibition-subtitle"><?php the_field('subtitle'); ?></h2>
						<?php } ?>

						<?php if( $exhibition_type_name=='Rotating' ) {
							if( get_field('start_date') ) {
								$start_date = get_field('start_date').' 00:00:01';
								$end_date = false;
								if( get_field('end_date') ) {
									$end_date = get_field('end_date').' 00:00:01';
								}
							} ?>
							<div class="single-exhibition-dates">
								<?php echo ls_get_list_dates($start_date, $end_date); ?>
							</div>
						<?php } ?>

						<?php if( get_field('artist') ) { 
							$artist = get_field('artist'); ?>
							<div class="single-exhibition-artist"><a href="<?php echo get_the_permalink($artist); ?>"><?php echo $artist->post_title; ?></a></div>
						<?php } ?>

						<?php if( get_field('gallery') ) { 
							$gallery = get_field('gallery'); ?>
							<div class="single-exhibition-gallery"><a href="<?php echo get_the_permalink($gallery); ?>"><?php echo $gallery->post_title; ?></a></div>
						<?php } ?>

						<?php /* Image */ ?>
						<?php if ( has_post_thumbnail()) : ?>
							<div class="single-exhibition-image">
								<?php the_post_thumbnail('square'); ?>
							</div>
						<?php endif; ?>

						<?php /* Content */ ?>
						<?php the_content(); ?>
												
					</div>
				</div>
			</div>
		</section>

		<?php // Exhibition Artwork
		if( get_field('artwork') ) {

			$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

			$args = array(
				'post_type' => 'artwork',
				'posts_per_page' => get_option( 'posts_per_page' ),
				'paged' => $paged,
				'post__in' => get_field('artwork')
			);

			$artwork_query = new WP_Query( $args );

			if ( $artwork_query->have_posts() ) { ?>

				<div class="page-section white-bg artwork-list single-exhibition-artwork" id="artwork-list">

					<?php /* List */ ?>
					<div class="grid-container artwork-list-container">
						<div class="grid-x grid-padding-x">
							<div class="cell">
								<?php while ( $artwork_query->have_posts() ) { 
									$artwork_query->the_post();
									get_template_part('partials/artwork/item');
								} ?>					
							</div>
						</div>
					</div>

					<?php /* Pagination */
					$args = array(
						'current_page' => $paged,
						'max_pages' => $artwork_query->max_num_pages
					);

					if( $args['max_pages'] > 1 ) { ?>
						<div class="grid-container artwork-list-pagination">
							<div class="grid-x grid-padding-x">
								<div class="cell">
									<?php get_template_part('partials/pagination/list', '', $args); ?>
								</div>
							</div>
						</div>
					<?php } ?>

				</div>

			<?php } 
		}
		wp_reset_postdata(); ?>

		<?php get_template_part('partials/page', 'builder'); ?>
		
	</main>

<?php endwhile; endif;

get_footer();