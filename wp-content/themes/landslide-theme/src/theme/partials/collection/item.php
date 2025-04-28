<?php $collection = $args['collection'];
$collection_acf = 'collection_'.$collection->term_id; ?>

<a href="<?php echo get_term_link($collection); ?>" class="collection-item">
	<?php if( get_field('featured_image', $collection_acf) ) { ?>
		<div class="collection-item-image">
			<?php acf_image_tag( 'featured_image', '100vw', 'small', FALSE, $collection_acf ); ?>
		</div>
	<?php } ?>

	<h3 class="collection-item-name"><?php echo $collection->name; ?></h3>

	<?php if( get_field('excerpt', $collection_acf) ) { ?>
		<div class="collection-item-excerpt">
			<?php the_field('excerpt', $collection_acf); ?>
		</div>
	<?php } ?>
</a>