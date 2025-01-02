<?php $args = array(
	'post_type' => 'staff',
	'posts_per_page' => -1
); 

$staff_query = new WP_Query( $args );

if ( $staff_query->have_posts() ) { 

	// Store current post
	$current_post = get_the_ID(); ?>

	<div class="page-section white-bg staff-list" id="staff-list">

		<?php LSPB()->display_section_header(); ?>

		<div class="grid-container staff-list-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<?php while ( $staff_query->have_posts() ) { 
						$staff_query->the_post();
						get_template_part('partials/staff/item');
					} 
					wp_reset_postdata(); ?>					
				</div>
			</div>
		</div>

	</div>

	<?php // Reset postdata
	$post = get_post( $current_post );
	setup_postdata( $post );

	// Increment section counter
	LSPB()->increment_section_counter();

}

// Clear section header
LSPB()->clear_section_header();