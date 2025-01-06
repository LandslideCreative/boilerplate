<?php 

$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

// Store current post
$current_post = get_the_ID(); ?>

<div class="page-section white-bg sermon-list" id="sermon-list">

	<?php LSPB()->display_section_header(); ?>

	<?php get_template_part('partials/sermon/filter'); ?>

	<?php 
	if ( $wp_query->have_posts() ) { 

		// Sermon List ?>
		<div class="grid-container sermon-list-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<?php get_template_part('loop'); ?>				
				</div>
			</div>
		</div>

		<?php // Pagination
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
		<?php } 

	} ?>

</div>

<?php // Reset postdata
$post = get_post( $current_post );
setup_postdata( $post );

// Increment section counter
LSPB()->increment_section_counter();

// Clear section header
LSPB()->clear_section_header();