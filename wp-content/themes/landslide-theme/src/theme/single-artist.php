<?php // Single Artist

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-artist">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<?php /* Title */ ?>
						<h5 class="label"><a href="<?php echo get_post_type_archive_link('artist'); ?>">Artist</a></h5>
						<h1 class="single-artist-title"><?php the_title(); ?></h1>

						<?php if( get_field('dates') ) {?>
							<div class="single-artist-dates">
								<?php the_field('dates'); ?>
							</div>
						<?php } ?>

						<?php if( get_field('location') ) {?>
							<div class="single-artist-location">
								<?php the_field('location'); ?>
							</div>
						<?php } ?>

						<?php /* Image */ ?>
						<?php if ( has_post_thumbnail()) : ?>
							<div class="single-artist-image">
								<?php the_post_thumbnail('square'); ?>
							</div>
						<?php endif; ?>

						<?php /* Content */ ?>
						<?php the_content(); ?>
												
					</div>
				</div>
			</div>
		</section>

		<?php 

		$args = array(
			'post_type' => 'artwork',
			'posts_per_page' => -1,
			'meta_query' => array(
                array(
                    'key' => 'artist', // name of custom field
                    'value' => get_the_ID(), // matches exactly "123", not just 123. This prevents a match for "1234"
                    'compare' => '='
                )
            )
		);

		$artwork_query = new WP_Query( $args );

		if ( $artwork_query->have_posts() ) { ?>

			<div class="page-section white-bg artwork-list single-artist-artwork" id="artwork-list">

				<div class="grid-container intro-section">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<h2>Artwork by <?php the_title(); ?></h2>
						</div>
					</div>
				</div>

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

			</div>

		<?php }
		wp_reset_postdata(); ?>

		<?php get_template_part('partials/page', 'builder'); ?>
		
	</main>

<?php endwhile; endif;

get_footer();