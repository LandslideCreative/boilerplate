<?php // Page Builder - One Column

// Background color
$background_color = get_sub_field('background_color');

// Column alignment
$alignment = '';
if( get_sub_field('alignment')=='center' ) {
	$alignment = 'align-center';
}

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
}

// Intro section
if( get_sub_field('intro') ) {

	$section_header = '<div class="grid-container intro-section" '.LSPB()->get_section_id().'>
		<div class="grid-x grid-margin-x '.$alignment.'">
			<div class="small-'.$small_width.' medium-'.$medium_width.' large-'.$large_width.' cell">'.
				get_sub_field('copy').
			'</div>
		</div>
	</div>';
	LSPB()->set_section_header($section_header);

} else { ?>

	<section class="page-section <?php echo $background_color; ?>-bg one-column" <?php LSPB()->display_section_id(); ?>>

		<?php LSPB()->display_section_header(); ?>

		<div class="grid-container">
			<div class="grid-x grid-padding-x <?php echo $alignment; ?>">
				<div class="small-<?php echo $small_width; ?> medium-<?php echo $medium_width; ?> large-<?php echo $large_width; ?> cell">
					<?php the_sub_field('copy'); ?>
				</div>
			</div>
		</div>
		
	</section>

<?php } ?>