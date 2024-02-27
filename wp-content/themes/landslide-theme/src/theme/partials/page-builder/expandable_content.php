<?php // Page Builder - Expandable Content

// Background color
$background_color = get_sub_field('background_color'); ?>

<section class="page-section <?php echo $background_color; ?>-bg expandable-content" <?php LSPB()->display_section_id(); ?>>

	<?php LSPB()->display_section_header(); ?>
	
	<?php if( have_rows('content') ): ?>
		<div class="grid-container">
			<div class="grid-x grid-padding-x align-center">
				<div class="cell medium-10 large-8">
					<ul class="accordion" data-accordion data-allow-all-closed="true" data-multi-expand="true">

						<?php $item_counter = 1;
						while ( have_rows('content') ) : the_row(); ?>
							<li class="accordion-item" data-accordion-item>
								<a href="#" class="accordion-title">
									<h4><?php the_sub_field('label'); ?></h4>
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