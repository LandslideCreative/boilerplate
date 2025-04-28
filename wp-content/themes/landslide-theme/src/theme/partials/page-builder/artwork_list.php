<?php // Page Builder - Artwork List

// Background color
$background_color = get_sub_field('background_color');

// Type
$type = get_sub_field('type');

$now = new DateTime('NOW', new DateTimeZone('America/New_York'));

// Exhibition Query
$args = array(
	'post_type' => 'artwork',
);

// Specific Exhibitions
if( $type=='specific' ) {
	$args['posts_per_page'] = -1;
	$specific_posts = get_sub_field('artwork');
	$args['post__in'] = $specific_posts;
	$args['orderby'] = 'post__in';

// Permanent Exhibitions
} else if( $type=='filtered' ) {
	$args['posts_per_page'] = 3;
	$args['tax_query'] = array(
        array(
            'taxonomy' => 'collection',
            'terms' => get_sub_field('collections')
        )
    );
}

$artwork_list = new WP_Query( $args );

if ( $artwork_list->have_posts() ) { 

	// Store current post
	$current_post = get_the_ID(); ?>

	<div class="page-section <?php echo $background_color; ?>-bg artwork-list" <?php LSPB()->display_section_id(); ?>>

		<?php LSPB()->display_section_header(); ?>

		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<?php while ( $artwork_list->have_posts() ) {
	        		$artwork_list->the_post(); ?>
	        		<div class="cell">
	        			<?php get_template_part('partials/artwork/item'); ?>
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