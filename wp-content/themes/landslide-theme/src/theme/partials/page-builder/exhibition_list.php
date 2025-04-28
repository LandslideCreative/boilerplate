<?php // Page Builder - Exhibition List

// Background color
$background_color = get_sub_field('background_color');

// Type
$type = get_sub_field('type');

$now = new DateTime('NOW', new DateTimeZone('America/New_York'));

// Exhibition Query
$args = array(
	'post_type' => 'exhibition',
);

// Specific Exhibitions
if( $type=='specific' ) {
	$args['posts_per_page'] = -1;
	$specific_posts = get_sub_field('exhibitions');
	$args['post__in'] = $specific_posts;
	$args['orderby'] = 'post__in';

// Permanent Exhibitions
} else if( $type=='permanent' ) {
	$args['posts_per_page'] = -1;
	$args['tax_query'] = array(
        array(
            'taxonomy' => 'exhibition-type',
            'field' => 'slug',
            'terms' => 'permanent'
        )
    );

// Current Rotating Exhibitions
} else if( $type=='current' ) {
	$args['posts_per_page'] = -1;
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'exhibition-type',
			'field' => 'slug',
			'terms' => 'rotating'
		)
	);
	$args['meta_query'] = array(
        'relation' => 'AND',
        'event_date' => array(
            'key'     => 'start_date',
            'compare' => '<=',
            'value'   => $now->format('Ymd'),
            'type'    => 'numeric',
        ),
        array(
        	'relation' => 'OR',
        	array(
            	'key'     => 'end_date',
            	'compare' => '>=',
            	'value'   => $now->format('Ymd'),
            	'type'    => 'numeric',
            ),
            array(
            	'key'     => 'end_date',
            	'value'   => '',
            ),
        )
    );
    $args['meta_key'] = 'start_date';
	$args['orderby'] = 'meta_value_num';
	$args['order'] = 'DESC';

// Upcoming Rotating Exhibitions
} else if( $type=='upcoming' ) {
	$args['posts_per_page'] = -1;
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'exhibition-type',
			'field' => 'slug',
			'terms' => 'rotating'
		)
	);
	$args['meta_query'] = array(
        'relation' => 'AND',
        'event_date' => array(
            'key'     => 'start_date',
            'compare' => '>',
            'value'   => $now->format('Ymd'),
            'type'    => 'numeric',
        )
    );
    $args['meta_key'] = 'start_date';
	$args['orderby'] = 'meta_value_num';
	$args['order'] = 'ASC';

// Past Rotating Exhibitions
} else if( $type=='past' ) {
	$args['posts_per_page'] = 6;
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'exhibition-type',
			'field' => 'slug',
			'terms' => 'rotating'
		)
	);
	$args['meta_query'] = array(
    	'relation' => 'AND',
    	'event_date' => array(
        	'key'     => 'end_date',
        	'compare' => '<',
        	'value'   => $now->format('Ymd'),
        	'type'    => 'numeric',
    	),
    	array(
        	'key'     => 'end_date',
        	'compare' => '!=',
        	'value'   => ''
    	)
	);
    $args['meta_key'] = 'end_date';
	$args['orderby'] = 'meta_value_num';
	$args['order'] = 'DESC';
}

$exhibition_list = new WP_Query( $args );

if ( $exhibition_list->have_posts() ) { 

	// Store current post
	$current_post = get_the_ID(); ?>

	<div class="page-section <?php echo $background_color; ?>-bg exhibition-list <?php echo $type; ?>" <?php LSPB()->display_section_id(); ?>>

		<?php LSPB()->display_section_header(); ?>

		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<?php while ( $exhibition_list->have_posts() ) {
	        		$exhibition_list->the_post(); ?>
	        		<div class="cell">
	        			<?php get_template_part('partials/exhibition/item'); ?>
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