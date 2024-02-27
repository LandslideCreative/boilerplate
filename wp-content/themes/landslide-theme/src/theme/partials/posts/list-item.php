<?php // Post List Item ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post-list-item'); ?>>

	<?php if( has_post_thumbnail() ) {  ?>
		<a class="post-list-image" href="<?php the_permalink(); ?>">
			<?php the_post_thumbnail('thumbnail'); ?>
		</a>
	<?php } ?>

	<h3 class="post-list-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

	<div class="post-list-date">
		<time datetime="<?php the_time('Y-m-d'); ?> <?php the_time('H:i'); ?>"><?php the_date(); ?></time>
	</div>
	
	<div class="post-list-author">
		Written by <?php the_author_posts_link(); ?>
	</div>

	<?php ls_excerpt('ls_excerpt_default'); ?>

</article>
