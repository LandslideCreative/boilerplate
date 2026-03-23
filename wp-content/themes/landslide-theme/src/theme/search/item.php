<?php // Search List Item 

// Search item layout
$search_item_layout = array(
	'title' => true,
	'url' => true,
	'excerpt' => true,
	'button' => true
);
$search_item_layout = apply_filters( 'ls_search_item_layout', $search_item_layout ); ?>

<a href="<?php the_permalink(); ?>" class="search-item">

	<?php foreach( $search_item_layout as $layout => $content ) {
		if( $content ) {
			switch($layout) {

				case 'title': 
					// Search item title header
					$search_item_title_header = 'h3';
					$search_item_title_header = apply_filters( 'ls_search_item_title_header', $search_item_title_header ); ?>
					<<?php echo $search_item_title_header; ?> class="search-item-title"><?php the_title(); ?></<?php echo $search_item_title_header; ?>>
					<?php break;

				case 'url': ?>
						<div class="search-item-url"><?php echo wp_make_link_relative( get_permalink() ); ?></div>
					<?php break;

				case 'excerpt': 
					if( $post->post_content != '' ) { ?>
						<div class="search-list-excerpt">
							<?php ls_excerpt('ls_search_item_excerpt_length'); ?>
						</div>
					<?php }
					break;

				case 'button': 
					// Search item button text
					$search_item_button_text = 'Learn More';
					$search_item_button_text = apply_filters( 'ls_search_item_button_text', $search_item_button_text ); ?>
					<div class="search-item-link text-button"><?php echo $search_item_button_text; ?></div>
					<?php break;

				default:
					echo $content;

			}
		}
	} ?>

</a>