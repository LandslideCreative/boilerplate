<?php 
// Store current post
$current_post = get_the_ID();

// Reset WP_Query
wp_reset_postdata(); ?>

<div class="page-section white-bg event-list" id="event-list">

	<?php LSPB()->display_section_header(); ?>

	<?php get_template_part('partials/event/filter'); ?>

	<div class="grid-container">
		<div class="grid-x grid-padding-x">
			<div class="cell">
				<?php the_content(); ?>
			</div>
		</div>
	</div>

</div>

<?php // Increment section counter
LSPB()->increment_section_counter();

// Reset postdata
$post = get_post( $current_post );
setup_postdata( $post );

// Clear section header
LSPB()->clear_section_header();