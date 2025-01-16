<a href="<?php the_permalink(); ?>" class="staff-item">

	<?php /* Image */ ?>
	<div class="staff-item-image">	
		<img src="<?php echo thumbnail_image_single( 'small-square' ); ?>" />
	</div>
	
	<?php /* Title */ ?>
	<h3 class="staff-item-title"><?php the_title(); ?></h3>

	<?php /* Position */ ?>
	<?php if( get_field('position') ) { ?>
		<p class="staff-item-position"><?php the_field('position'); ?></p>
	<?php } ?>

</a>