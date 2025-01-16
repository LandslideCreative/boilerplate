<?php // Post List Item ?>

<a href="<?php the_permalink(); ?>" <?php post_class('post-item'); ?>>

	<?php /* Image */ ?>
	<?php if( has_post_thumbnail() ) {  ?>
		<div class="post-item-image">
			<?php the_post_thumbnail('thumbnail'); ?>
		</div>
	<?php } ?>

	<?php /* Title */ ?>
	<h3 class="post-item-title"><?php the_title(); ?></h3>

	<?php /* Author */ ?>
	<?php $author = get_field('author');
	if( $author ) { ?>
		<div class="post-item-author">
			<?php echo $author->name; ?>
		</div>
	<?php } ?>

	<?php /* Date */ ?>
	<div class="post-item-date">
		<time datetime="<?php the_time('Y-m-d'); ?> <?php the_time('H:i'); ?>"><?php echo get_the_date(); ?></time>
	</div>

	<?php /* Excerpt */ ?>
	<?php ls_excerpt('ls_excerpt_default'); ?>

</a>