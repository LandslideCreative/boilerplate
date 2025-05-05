<?php // Collection Archive
$collection = get_queried_object();

get_header(); ?>

<main role="main" id="main-content">
	<div class="page-header">
		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<h1><?php echo $collection->name; ?></h1>

					<?php if( $collection->description!='' ) {
						echo wpautop($collection->description);
					} ?>
				</div>
			</div>
		</div>
	</div>

	<?php 

	$args = array(
		'taxonomy' => 'collection',
		'parent' => $collection->term_id
	);

	$children = get_terms($args);

	$args = array(
		'collections' => $children
	);
	get_template_part('partials/collection/list', '', $args); 

	?>

	<?php if ( $wp_query->have_posts() ) { ?>
		<div class="page-section white-bg artwork-list" id="artwork-list">		
		
			<?php /* Post List */ ?>
			<div class="grid-container artwork-list-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php while ( $wp_query->have_posts() ) { 
							$wp_query->the_post();
							get_template_part('partials/artwork/item');
						} ?>
					</div>
				</div>
			</div>

			<?php /* Pagination */ ?>
			<?php $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
			$args = array(
				'current_page' => $paged,
				'max_pages' => $wp_query->max_num_pages,
				'anchor' => '#artwork-list'
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
	<?php } ?>
</main>

<?php get_footer();