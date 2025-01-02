<?php // Page Builder - Content Blocks

// Background color
$background_color = get_sub_field('background_color');

// Layout
$layout = get_sub_field('layout'); 

// Column setup
$columns = get_sub_field('blocks_per_row');
if( $columns == 2 ) {
	$block_grid = 'medium-up-2';
	$image_sizes = '(max-width: 640px) 100vw, (max-width: 1200px) 50vw, 600px';
} else if( $columns == 3 ) {
	$block_grid = 'medium-up-3';
	$image_sizes = '(max-width: 640px) 100vw, (max-width: 1200px) 33vw, 400px';
} else if( $columns == 4 ) {
	$block_grid = 'medium-up-2 large-up-4';
	$image_sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, (max-width: 1200px) 25vw, 300px';
}

if( have_rows('blocks') ): ?>

	<section class="page-section <?php echo $background_color; ?>-bg content-blocks <?php echo $columns; ?>-column" <?php LSPB()->display_section_id(); ?>>
		
		<?php LSPB()->display_section_header(); ?>
		
		<div class="grid-container content-blocks-container">
			<div class="grid-x grid-padding-x <?php echo $block_grid; ?>">
				
				<?php // Ouput blocks ?>
				<?php while ( have_rows('blocks') ) : the_row(); ?>
					<div class="cell">
						
						<?php $image = get_sub_field('image');
						if( $layout == 'images' && $image ) { ?>
							<div class="content-blocks-image">
								<?php acf_image_tag( 'image', $image_sizes, 'medium', TRUE ); ?>
							</div>
						<?php } else if( $layout == 'icons' && $image ) { 
							$image = acf_image_single( 'image', 'thumbnail', TRUE ); ?>
							<div class="content-blocks-image icon">
								<img src="<?php echo $image; ?>" alt="" />
							</div>
						<?php } ?>

						<?php the_sub_field('copy'); ?>
					
					</div>
				<?php endwhile; ?>

			</div>
		</div>

	</section>

<?php endif;

// Clear section header
LSPB()->clear_section_header();