<?php if( get_field('type') ) {
	$exhibition_type = get_field('type');
	$exhibition_type_name = $exhibition_type->name;
} ?>

<a href="<?php the_permalink(); ?>" class="exhibition-item">

	<?php /* Image */ ?>
	<div class="exhibition-item-image">	
		<img src="<?php echo thumbnail_image_single( 'small' ); ?>" />
	</div>
	
	<?php /* Title */ ?>
	<h3 class="exhibition-item-title"><?php the_title(); ?></h3>

	<?php if( get_field('subtitle') ) { ?>
		<h4 class="exhibition-item-subtitle"><?php the_field('subtitle'); ?></h4>
	<?php } ?>

	<?php if( $exhibition_type_name=='Rotating' ) {
		if( get_field('start_date') ) {
			$start_date = get_field('start_date').' 00:00:01';
			$end_date = false;
			if( get_field('end_date') ) {
				$end_date = get_field('end_date').' 00:00:01';
			}
		} ?>
		<div class="exhibition-item-dates">
			<?php echo ls_get_list_dates($start_date, $end_date); ?>
		</div>
	<?php } ?>

</a>