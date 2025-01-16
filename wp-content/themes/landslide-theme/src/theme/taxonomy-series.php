<?php // Single Series
$series = get_queried_object();

get_header(); ?>

<main role="main" id="main-content">
		
	<div class="page-section white-bg single-series">
		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">

					<?php /* Title */ ?>
					<p class="label"><a href="<?php echo home_url(ls_get_archive_page_slug( 'sermon' )); ?>">Sermons</a></p>
					<h1 class="single-series-title"><?php echo $series->name; ?></h1>

					<?php /* Image / Video */ ?>
					<?php // Default to series trailer
					if( get_field('series_trailer', 'series_'.$series->term_id) ) { ?>
						<div class="single-series-trailer">
							<?php the_field('series_trailer', 'series_'.$series->term_id); ?>
						</div>
					<?php // Fallback to series featured image
					} else if( get_field('featured_image', 'series_'.$series->term_id) ) { ?>
						<div class="single-series-image">
							<?php acf_image_tag( 'featured_image', '100vw', 'small', FALSE, 'series_'.$series->term_id ); ?>
						</div>
					<?php } ?>

					<?php /* Description */ ?>
					<?php if( $series->description!='' ) { ?>
						<div class="single-series-description">
							<?php echo wpautop($series->description); ?>
						</div>
					<?php } ?>

				</div>
			</div>
		</div>
	</div>

	<?php if ( $wp_query->have_posts() ) { ?>

		<div class="page-section white-bg sermon-list" id="sermon-list">
		
			<?php /* Sermon List */ ?>
			<div class="grid-container sermon-list-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php while ( $wp_query->have_posts() ) { 
							$wp_query->the_post();
							get_template_part('partials/sermon/item');
						} ?>		
					</div>
				</div>
			</div>

			<?php // Pagination */ ?>
			<?php $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
			$args = array(
				'current_page' => $paged,
				'max_pages' => $wp_query->max_num_pages
			);

			if( $args['max_pages'] > 1 ) { ?>
				<div class="grid-container post-list-pagination">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<?php get_template_part('partials/pagination/list', '', $args); ?>
						</div>
					</div>
				</div>
			<?php } ?>

		</div>

	<?php } ?>

</main>

<?php get_footer();