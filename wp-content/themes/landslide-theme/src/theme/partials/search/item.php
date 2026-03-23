<?php // Search List Item ?>

<a href="<?php the_permalink(); ?>" class="search-item">
	
	<h3 class="search-item-title"><?php the_title(); ?></h3>
	
	<div class="search-item-url"><?php echo wp_make_link_relative( get_permalink() ); ?></div>
	
	<?php if( $post->post_content!='' || $post->post_excerpt!='' ) { ?>
		<div class="search-list-excerpt">
			<?php ls_excerpt('ls_search_item_excerpt_length'); ?>
		</div>
	<?php } ?>

</a>