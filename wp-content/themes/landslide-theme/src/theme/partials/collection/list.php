<?php 

if( array_key_exists('collections', $args ) ) {
	$collections = $args['collections'];
} else {
	$args = array(
		'taxonomy' => 'collection',
		'parent' => 0
	);

	$collections = get_terms($args);
}

if( $collections ) { ?>

	<div class="page-section white-bg collection-list">

		<?php LSPB()->display_section_header(); ?>

		<?php /* List */ ?>
		<div class="grid-container collection-list-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<?php foreach ( $collections as $collection ) {
						$args = array(
							'collection' => $collection
						);
						get_template_part('partials/collection/item', '', $args);
					} ?>					
				</div>
			</div>
		</div>

	</div>

	<?php // Increment section counter
	LSPB()->increment_section_counter();

}

// Clear section header
LSPB()->clear_section_header();