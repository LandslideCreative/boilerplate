<a href="<?php the_permalink(); ?>" class="artist-item">

	<?php /* Image */ ?>
	<div class="artist-item-image">	
		<img src="<?php echo thumbnail_image_single( 'small-square' ); ?>" />
	</div>
	
	<?php /* Title */ ?>
	<h3 class="artist-item-title"><?php the_title(); ?></h3>

	<?php if( get_field('dates') ) {?>
		<div class="artist-item-dates">
			<?php the_field('dates'); ?>
		</div>
	<?php } ?>

</a>