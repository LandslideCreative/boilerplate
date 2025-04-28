<?php // Permanent Exhibition Archive
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

	<?php if ( $wp_query->have_posts() ) { ?>
		<div class="page-section white-bg exhibition-list permanent">		
		
			<?php /* Post List */ ?>
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
				'max_pages' => $wp_query->max_num_pages
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
	<?php } ?>
</main>

<?php get_footer();