<?php // Page Builder - Image Overlay

// Content position
$copy_position = get_sub_field('copy_side');

// Copy size 
if( $copy_position =='center' ) {
	$copy_size = 'medium-8 large-7';
} else {
	$copy_size = 'medium-7 large-6';
}?>

<section class="page-section image-overlay <?php echo 'copy-'.$copy_position; ?>" <?php LSPB()->display_section_id(); ?>>
	
	<div class="image-bg">
		<?php acf_image_tag( 'background_image', '100vw', 'medium', TRUE ); ?>
	</div>
	
	<div class="grid-container">
		<div class="grid-x grid-margin-x">
			<div class="cell <?php echo $copy_size; ?>">
				<div class="image-overlay-copy">
					<?php the_sub_field('copy'); ?>
				</div>
			</div>
		</div>
	</div>

</section>

<?php // Clear section header
LSPB()->clear_section_header(); ?>