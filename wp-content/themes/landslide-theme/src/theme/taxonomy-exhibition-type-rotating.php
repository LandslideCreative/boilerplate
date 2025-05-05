<?php // Rotating Exhibition Archive
$exhibition_type = get_queried_object();

get_header(); ?>

<main role="main" id="main-content">
	<div class="page-header">
		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<h1><?php echo $exhibition_type->name; ?></h1>

					<?php if( $exhibition_type->description!='' ) {
						echo wpautop($exhibition_type->description);
					} ?>
				</div>
			</div>
		</div>
	</div>

	<?php $now = new DateTime('NOW', new DateTimeZone('America/New_York'));

	// Current Rotating Exhibitions
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
	        )
	    ),
	    'meta_key'   => 'start_date',
		'orderby'    => 'meta_value_num',
		'order'      => 'DESC'
	);

	$current_query = new WP_Query( $args );

	if ( $current_query->have_posts() ) { ?>

		<div class="page-section white-bg exhibition-list current">

			<div class="grid-container intro-section">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<h2>Current Rotating Exhibits</h2>
					</div>
				</div>
			</div>

			<?php /* List */ ?>
			<div class="grid-container exhibition-list-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php while ( $current_query->have_posts() ) { 
							$current_query->the_post();
							get_template_part('partials/exhibition/item');
						} ?>					
					</div>
				</div>
			</div>

		</div>

	<?php }

	// Upcoming Rotating Exhibitions
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
	            'compare' => '>',
	            'value'   => $now->format('Ymd'),
	            'type'    => 'numeric',
	        )
	    ),
	    'meta_key'   => 'start_date',
		'orderby'    => 'meta_value_num',
		'order'      => 'ASC'
	);

	$upcoming_query = new WP_Query( $args );

	if ( $upcoming_query->have_posts() ) { ?>

		<div class="page-section white-bg exhibition-list upcoming">

			<div class="grid-container intro-section">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<h2>Upcoming Rotating Exhibits</h2>
					</div>
				</div>
			</div>

			<?php /* List */ ?>
			<div class="grid-container exhibition-list-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php while ( $upcoming_query->have_posts() ) { 
							$upcoming_query->the_post();
							get_template_part('partials/exhibition/item');
						} ?>					
					</div>
				</div>
			</div>

		</div>

	<?php }
	wp_reset_postdata();

	if ( $wp_query->have_posts() ) { ?>

		<div class="page-section white-bg exhibition-list past" id="exhibition-list-past">

			<div class="grid-container intro-section">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<h2>Past Rotating Exhibits</h2>
					</div>
				</div>
			</div>

			<?php /* List */ ?>
			<div class="grid-container exhibition-list-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php while ( $wp_query->have_posts() ) { 
							$wp_query->the_post();
							get_template_part('partials/exhibition/item');
						} ?>					
					</div>
				</div>
			</div>

			<?php /* Pagination */ ?>
			<?php $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
			$args = array(
				'current_page' => $paged,
				'max_pages' => $wp_query->max_num_pages,
				'anchor' => '#exhibition-list-past'
			);

			if( $args['max_pages'] > 1 ) { ?>
				<div class="grid-container exhibition-list-pagination">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<?php get_template_part('partials/pagination/list', '', $args); ?>
						</div>
					</div>
				</div>
			<?php } ?>

		</div>

	<?php }	 ?>
</main>

<?php get_footer();