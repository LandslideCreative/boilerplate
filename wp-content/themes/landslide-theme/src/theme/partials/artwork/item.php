<a href="<?php the_permalink(); ?>" class="artwork-item">

	<?php /* Image */ ?>
	<div class="artwork-item-image">	
		<img src="<?php echo thumbnail_image_single( 'small' ); ?>" />
	</div>
	
	<?php /* Title */ ?>
	<h3 class="artwork-item-title"><?php the_title(); ?></h3>

	<?php if( get_field('artist') ) { 
		$artist = get_field('artist'); ?>
		<div class="artwork-item-artist">
			<?php echo get_the_title($artist); ?>
		</div>
	<?php } ?>

</a>