<?php // Post List Item ?>

<a href="<?php the_permalink(); ?>" <?php post_class('post-item'); ?>>

	<?php if( has_post_thumbnail() ) {  ?>
		<div class="post-item-image">
			<?php the_post_thumbnail('thumbnail'); ?>
		</div>
	<?php } ?>

	<h3 class="post-item-title"><?php the_title(); ?></h3>

	<div class="post-item-date">
		<time datetime="<?php the_time('Y-m-d'); ?> <?php the_time('H:i'); ?>"><?php echo get_the_date(); ?></time>
	</div>

	<?php ls_excerpt('ls_excerpt_default'); ?>

</a>