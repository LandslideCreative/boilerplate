<?php // Announcement List Item ?>

<a href="<?php the_permalink(); ?>" <?php post_class('announcement-item'); ?>>

	<?php /* Title */ ?>
	<h3 class="announcement-item-title"><?php the_title(); ?></h3>

	<?php /* Date */ ?>
	<?php if( !get_field('hide_date') ) { ?>
		<div class="announcement-item-date">
			<time datetime="<?php the_time('Y-m-d'); ?> <?php the_time('H:i'); ?>"><?php echo get_the_date(); ?></time>
		</div>
	<?php } ?>

	<div class="text-button">Learn More</div>

</a>