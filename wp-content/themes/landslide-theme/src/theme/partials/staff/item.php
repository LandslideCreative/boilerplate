<a href="<?php the_permalink(); ?>" class="staff-item">

	<div class="staff-item-image">	
		<img src="<?php echo thumbnail_image_single( 'small-square' ); ?>" />
	</div>
	
	<h3 class="staff-item-title"><?php the_title(); ?></h3>

	<?php if( get_field('position') ) { ?>
		<p class="staff-item-position"><?php the_field('position'); ?></p>
	<?php } ?>

</a>