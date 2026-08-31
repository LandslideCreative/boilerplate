<?php // Page Builder - Expandable Content

// Background color
$background_color = get_sub_field('background_color'); 

// Column width
$large_width = get_sub_field('width');
if( $large_width>10 ) {
	$medium_width = 12;
	$small_width = 12;
} else if( $large_width>8 ) {
	$medium_width = 11;
	$small_width = 12;
} else {
	$medium_width = 10;
	$small_width = 11;
} ?>

<section class="page-section <?php echo $background_color; ?>-bg expandable-content" <?php LSPB()->display_section_id(); ?>>

	<?php LSPB()->display_section_header(); ?>
	
	<?php if( have_rows('content') ): ?>
		<div class="grid-container expandable-content-container">
			<div class="grid-x grid-padding-x align-center">
				<div class="cell small-<?php echo $small_width; ?> medium-<?php echo $medium_width; ?> large-<?php echo $large_width; ?>">
					<ul class="accordion" data-accordion data-allow-all-closed="true" data-multi-expand="true">

						<?php $item_counter = 1;
						while ( have_rows('content') ) : the_row(); ?>
							<li class="accordion-item" data-accordion-item>
								<a href="#" class="accordion-title">
									<h4><?php the_sub_field('label'); ?></h4>
									<div class="accordion-toggle"></div>
								</a>

								<div class="accordion-content" data-tab-content>
									<?php the_sub_field('copy'); ?>
								</div>
							</li>
							<?php $item_counter++; 
						endwhile; ?>
						
					</ul>
				</div>
			</div>
		</div>
	<?php endif; ?>

</section>