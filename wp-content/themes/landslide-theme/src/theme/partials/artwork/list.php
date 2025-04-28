<?php 
// Store current post
$current_post = get_the_ID();

// Reset WP_Query
wp_reset_postdata();

if ( $wp_query->have_posts() ) { ?>

	<div class="page-section white-bg artwork-list" id="artwork-list">

		<?php LSPB()->display_section_header(); ?>

		<?php /* List */ ?>
		<div class="grid-container staff-list-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<?php while ( $wp_query->have_posts() ) { 
						$wp_query->the_post();
						get_template_part('partials/artwork/item');
					} ?>					
				</div>
			</div>
		</div>

	</div>

	<?php // Increment section counter
	LSPB()->increment_section_counter();

}

// Reset postdata
$post = get_post( $current_post );
setup_postdata( $post );

// Clear section header
LSPB()->clear_section_header();