<?php // Event List Item 
$start_date = tribe_get_start_date(NULL, false, 'Ymd G:i:s');
$end_date = tribe_get_end_date(NULL, false, 'Ymd G:i:s'); ?>

<a href="<?php the_permalink(); ?>" class="event-list-item">

	<?php if( has_post_thumbnail() ) {  ?>
		<div class="event-list-image">
			<?php the_post_thumbnail('small'); ?>
		</div>
	<?php } ?>

	<h3 class="event-list-title"><?php the_title(); ?></h3>

	<div class="event-list-date">
		<?php echo ls_get_list_dates($start_date, $end_date); ?>
	</div>

	<?php if( !tribe_event_is_all_day() ) { ?>
		<div class="event-list-date">
			<?php echo ls_get_times($start_date, $end_date); ?>
		</div>
	<?php } ?>

</a>