<?php // Sermon List Item 
$series = get_field('series');
$speaker = get_field('speaker');  ?>

<a href="<?php the_permalink(); ?>" <?php post_class('sermon-item'); ?>>

	<?php // Default to the featured image
	if( has_post_thumbnail() ) {  ?>
		<div class="sermon-item-image">
			<?php thumbnail_image_tag( '100vw', 'small' ); ?>
		</div>
	<?php // Fallback to the series image
	} else if( get_field('featured_image', 'series_'.$series->term_id) ) { ?>
		<div class="sermon-item-image">
			<?php acf_image_tag( 'featured_image', '100vw', 'small', FALSE, 'series_'.$series->term_id ); ?>
		</div>
	<?php } ?>

	<h3 class="sermon-item-title"><?php the_title(); ?></h3>

	<div class="sermon-item-date">
		<?php echo the_field('sermon_date'); ?>
	</div>

	<?php if( $speaker ) { ?>
		<div class="sermon-item-speaker">
			<?php echo $speaker->name; ?>
		</div>
	<?php } ?>

	<?php if( $series ) { ?>
		<div class="sermon-item-series">
			from <?php echo $series->name; ?>
		</div>
	<?php } ?>

</a>