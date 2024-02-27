<?php // Page Builder - Two Column

// Background color
$background_color = get_sub_field('background_color');

// Vertical alignment
if( get_sub_field('vertical_alignment') ) {
	$vertical_alignment = 'vertical-center';
} else {
	$vertical_alignment = '';
}

// Switch mobile order
if( get_sub_field('mobile_order') ) {
	$mobile_order = 'reverse-mobile';
} else {
	$mobile_order = '';
}

// Get column content
$columns = get_sub_field('columns');
$col_1 = $columns[0];
$col_2 = $columns[1];

// Calculate column widths
$col_1_width = $col_1['width'];
$col_1_small = 12;
$col_1_medium = $col_1_width;
$col_1_large = $col_1_width;
if( $col_1_width<4 ) {
	$col_1_medium = 4;
	$col_1_small = 6;
} else if( $col_1_width>8 ) {
	$col_1_medium=8;
}

$col_2_width = $col_2['width'];
$col_2_small = 12;
$col_2_medium = 12-$col_1_medium;
$col_2_large = $col_2_width;
if( ($col_1_width==6 & $col_2_width==4) || ($col_1_width==7 & $col_2_width==4) ) {
	$col_1_medium = 7;
	$col_2_medium = 5;
} else if( ($col_1_width==4 & $col_2_width==6) || ($col_1_width==4 & $col_2_width==7) ) {
	$col_1_medium = 5;
	$col_2_medium = 7;
} else if( ($col_1_width==5 & $col_2_width==5) || ($col_1_width==6 & $col_2_width==5) || ($col_1_width==5 & $col_2_width==6) ) {
	$col_1_medium = 6;
	$col_2_medium = 6;
} ?>

<section class="page-section <?php echo $background_color; ?>-bg two-column" <?php LSPB()->display_section_id(); ?>>

	<?php LSPB()->display_section_header(); ?>

	<div class="grid-container">
		<div class="grid-x grid-padding-x two-column-content align-center <?php echo $vertical_alignment; ?> <?php echo $mobile_order; ?>">
			
			<?php // Column 1 ?>
			<div class="small-<?php echo $col_1_small; ?> medium-<?php echo $col_1_medium; ?> large-<?php echo $col_1_large; ?> cell">
				<?php if($col_1['image']) { ?>
					<img src="<?php echo $col_1['image']['sizes']['medium']; ?>" alt="<?php echo $col_1['image']['alt']; ?>" />
				<?php }
				echo $col_1['copy']; ?>
			</div>
			
			<?php // Column 2 ?>
			<div class="small-<?php echo $col_2_small; ?> medium-<?php echo $col_2_medium; ?> large-<?php echo $col_2_large; ?> cell">
				<?php if($col_2['image']) { ?>
					<img src="<?php echo $col_2['image']['sizes']['medium']; ?>" alt="<?php echo $col_2['image']['alt']; ?>" />
				<?php }
				echo $col_2['copy']; ?>
			</div>

		</div>
	</div>
	
</section>