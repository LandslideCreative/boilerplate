<?php // Single Gallery

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-gallery">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<?php /* Title */ ?>
						<h5 class="label">Gallery</h5>
						<h1 class="single-gallery-title"><?php the_title(); ?></h1>

						<?php /* Image */ ?>
						<?php if ( has_post_thumbnail()) : ?>
							<div class="single-gallery-image">
								<?php the_post_thumbnail('square'); ?>
							</div>
						<?php endif; ?>

						<?php /* Content */ ?>
						<?php the_content(); ?>
												
					</div>
				</div>
			</div>
		</section>

		<?php // Permanent & Current Exhibitions in Gallery

		// Permanent Exhibitions
		$args = array(
			'post_type' => 'exhibition',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'exhibition-type',
					'field' => 'slug',
					'terms' => 'permanent'
				)
			),
			'meta_query' => array(
                array(
                    'key' => 'gallery',
                    'value' => get_the_ID(),
                    'compare' => '='
                )
            )
		);

		$permanent_query = new WP_Query( $args );

		// Current Rotating Exhibitions
		$now = new DateTime('NOW', new DateTimeZone('America/New_York'));

		$args = array(
			'post_type' => 'exhibition',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'exhibition-type',
					'field' => 'slug',
					'terms' => 'rotating'
				)
			),
			'meta_query' => array(
		        'relation' => 'AND',
		        'event_date' => array(
		            'key'     => 'start_date',
		            'compare' => '<=',
		            'value'   => $now->format('Ymd'),
		            'type'    => 'numeric',
		        ),
		        array(
		        	'relation' => 'OR',
		        	array(
		            	'key'     => 'end_date',
		            	'compare' => '>=',
		            	'value'   => $now->format('Ymd'),
		            	'type'    => 'numeric',
		            ),
		            array(
		            	'key'     => 'end_date',
		            	'value'   => '',
		            ),
		        ),
		        array(
                    'key' => 'gallery',
                    'value' => get_the_ID(),
                    'compare' => '='
                )
		    ),
		    'meta_key'   => 'start_date',
			'orderby'    => 'meta_value_num',
			'order'      => 'DESC'
		);

		$current_query = new WP_Query( $args );

		if ( $permanent_query->have_posts() || $current_query->have_posts() ) { ?>

			<div class="page-section white-bg exhibition-list single-gallery-exhibitions">

				<div class="grid-container intro-section">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<h2>Exhibitions</h2>
						</div>
					</div>
				</div>

				<?php /* List */ ?>
				<div class="grid-container exhibition-list-container">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<?php while ( $permanent_query->have_posts() ) { 
								$permanent_query->the_post();
								get_template_part('partials/exhibition/item');
							} ?>
							<?php while ( $current_query->have_posts() ) { 
								$current_query->the_post();
								get_template_part('partials/exhibition/item');
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