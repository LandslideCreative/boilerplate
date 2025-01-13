<?php // Page Builder - CTA

// Background color
$background_color = get_sub_field('cta_background_color'); ?>

<section class="page-section cta <?php echo $background_color; ?>-bg" <?php LSPB()->display_section_id(); ?>>
	<div class="grid-container">
		<div class="grid-x grid-padding-x align-center">
			<div class="cell">
				<?php the_sub_field('copy'); ?>
			</div>
		</div>
	</div>
</section>

<?php // Clear section header
LSPB()->clear_section_header();