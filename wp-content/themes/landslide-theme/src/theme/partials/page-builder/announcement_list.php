<?php // Page Builder - Announcement List

// Background color
$background_color = get_sub_field('background_color');

// Feed
$feed = get_sub_field('feed');

// Announcements Query
$today = new DateTime("now", new DateTimeZone('America/Chicago') );
$args = array(
	'post_type' => 'announcement',
	'posts_per_page' => -1,
	'meta_query' => array(
    	'relation' => 'OR',
        array(
            'key' => 'expiration_date',
            'value' => $today->format('Y-m-d'),
            'compare' => '>=', 
            'type' => 'DATE'
        ),
        array(
            'key' => 'expiration_date',
            'value' => '',
            'compare' => '=',
        )
	)
);

// By Category
if( $feed=='category' ) {
	$categories = get_sub_field('category');
	$args['tax_query'] = array(
        array(
            'taxonomy' => 'announcement-category',
            'field' => 'term_id',
            'terms' => $categories
        )
    );
}

$announcement_list = new WP_Query( $args );

if ( $announcement_list->have_posts() ) { 

	// Store current post
	$current_post = get_the_ID(); ?>

	<div class="page-section <?php echo $background_color; ?>-bg announcement-list" <?php LSPB()->display_section_id(); ?>>

		<?php LSPB()->display_section_header(); ?>

		<div class="grid-container">
			<div class="grid-x grid-padding-x vertical-center">
				<div class="cell">
					<?php while ( $announcement_list->have_posts() ) {
		        		$announcement_list->the_post(); ?>
		        		<div class="cell">
		        			<?php get_template_part('partials/announcement/item'); ?>
		        		</div>
		        	<?php } ?>
		        </div>
			</div>
		</div>

	</div>

	<?php // Reset postdata
	$post = get_post( $current_post );
	setup_postdata( $post );
}

// Clear section header
LSPB()->clear_section_header();
