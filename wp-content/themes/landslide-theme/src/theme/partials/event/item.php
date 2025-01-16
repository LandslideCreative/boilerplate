<?php // Event List Item

$start_date = tribe_get_start_date(NULL, false, 'Ymd G:i:s');
$end_date = tribe_get_end_date(NULL, false, 'Ymd G:i:s'); ?>

<a href="<?php the_permalink(); ?>" class="event-item">

	<?php /* Image */ ?>
	<?php if( has_post_thumbnail() ) {  ?>
		<div class="event-item-image">
			<?php the_post_thumbnail('small'); ?>
		</div>
	<?php } ?>

	<?php /* Title */ ?>
	<h3 class="event-item-title"><?php the_title(); ?></h3>

	<?php /* Date */ ?>
	<div class="event-item-date">
		<?php echo ls_get_list_dates($start_date, $end_date); ?>
	</div>

	<?php /* Time */ ?>
	<?php if( !tribe_event_is_all_day() ) { ?>
		<div class="event-item-date">
			<?php echo ls_get_times($start_date, $end_date); ?>
		</div>
	<?php } ?>

</a>