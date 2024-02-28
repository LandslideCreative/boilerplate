<a href="<?php the_permalink(); ?>" class="staff-list-item">
	
	<?php the_post_thumbnail('small-square'); ?>
	
	<h3><?php the_title(); ?></h3>

	<?php if( get_field('position') ) { ?>
		<p><?php the_field('position'); ?></p>
	<?php } ?>

</a>
