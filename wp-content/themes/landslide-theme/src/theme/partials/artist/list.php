<?php 
// Store current post
$current_post = get_the_ID();

// Reset WP_Query
wp_reset_postdata();

if ( $wp_query->have_posts() ) { ?>

	<div class="page-section white-bg artist-list" id="artist-list">

		<?php LSPB()->display_section_header(); ?>

		<?php /* List */ ?>
		<div class="grid-container artist-list-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<?php while ( $wp_query->have_posts() ) { 
						$wp_query->the_post();
						get_template_part('partials/artist/item');
					} ?>					
				</div>
			</div>
		</div>

		<?php /* Pagination */ ?>
		<?php $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
		$args = array(
			'current_page' => $paged,
			'max_pages' => $wp_query->max_num_pages,
			'anchor' => '#artist-list'
		);

		if( $args['max_pages'] > 1 ) { ?>
			<div class="grid-container artist-list-pagination">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php get_template_part('partials/pagination/list', '', $args); ?>
					</div>
				</div>
			</div>
		<?php } ?>

	</div>

	<?php // Increment section counter
	LSPB()->increment_section_counter();

}

// Reset postdata
$post = get_post( $current_post );
setup_postdata( $post );

// Clear section header
LSPB()->clear_section_header();