<?php // Page Builder - Event List

// Background color
$background_color = get_sub_field('background_color');

// Type
$type = get_sub_field('type');

// Posts Query
$args = array(
	'start_date' => 'now'
);

// Specific Posts
if( $type=='specific' ) {
	$args['posts_per_page'] = -1;

	$specific_posts = get_sub_field('events');
	$args['post__in'] = $specific_posts;
	$args['orderby'] = 'post__in';

// Filtered
} else if( $type=='filtered' ) {
	$args['posts_per_page'] = 3;

	$categories = get_sub_field('category');
	$args['tax_query'] = array(
        array(
            'taxonomy' => 'tribe_events_cat',
            'field' => 'term_id',
            'terms' => $categories
        )
    );

// Upcoming Events
} else {
	$args['posts_per_page'] = 3;
}

$event_list = tribe_get_events($args, true);

if ( $event_list->have_posts() ) { 

	// Store current post
	$current_post = get_the_ID(); ?>

	<div class="page-section <?php echo $background_color; ?>-bg event-list" <?php LSPB()->display_section_id(); ?>>

		<?php LSPB()->display_section_header(); ?>

		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<?php while ( $event_list->have_posts() ) {
	        		$event_list->the_post(); ?>
	        		<div class="cell">
	        			<?php get_template_part('partials/event/item'); ?>
	        		</div>
	        	<?php } ?>
			</div>
		</div>

	</div>

	<?php // Reset postdata
	$post = get_post( $current_post );
	setup_postdata( $post );
}

// Clear section header
LSPB()->clear_section_header();