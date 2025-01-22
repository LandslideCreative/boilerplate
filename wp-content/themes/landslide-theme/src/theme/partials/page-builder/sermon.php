<?php // Page Builder - Sermon

// Background color
$background_color = get_sub_field('background_color');

// Type
$type = get_sub_field('type');

// Posts Query
$args = array(
	'post_type' => 'sermon',
	'posts_per_page' => 1
);

// Specific Sermon
if( $type=='specific' ) {
	$sermon = get_sub_field('sermon');
	$args['post__in'] = array($sermon);

// Most Recent
} else {
	$args['meta_key'] = 'sermon_date';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'DESC';
}

$sermon_list = new WP_Query( $args );

if ( $sermon_list->have_posts() ) { 

	// Store current post
	$current_post = get_the_ID(); ?>

	<div class="page-section <?php echo $background_color; ?>-bg featured-sermon" <?php LSPB()->display_section_id(); ?>>

		<?php LSPB()->display_section_header(); ?>

		<div class="grid-container">
			<div class="grid-x grid-padding-x vertical-center">
				<?php while ( $sermon_list->have_posts() ) {
	        		$sermon_list->the_post(); ?>
	        		<div class="medium-6 large-7 cell">

						<?php /* Image/Video */ ?>
						<?php // Default to sermon video
						if( get_field( 'sermon_video') ) { ?>
							<div class="featured-sermon-video">
								<?php the_field('sermon_video'); ?>
							</div>
						<?php // Fallback to featured image
						} else if( has_post_thumbnail() ) {  ?>
							<div class="featured-sermon-image">
								<?php thumbnail_image_tag( '100vw', 'small' ); ?>
							</div>
						<?php // Fallback to the series featured image
						} else if( get_field('featured_image', 'series_'.$series->term_id) ) { ?>
							<div class="featured-sermon-image">
								<?php acf_image_tag( 'featured_image', '100vw', 'small', FALSE, 'series_'.$series->term_id ); ?>
							</div>
						<?php } ?>

	        		</div>
	        		<div class="medium-6 large-5 cell">

	        			<?php /* Title */ ?>
	        			<p class="label">Sermon</p>
	        			<h2 class="featured-sermon-title"><?php the_title(); ?></h2>

	        			<?php /* Date */ ?>
						<div class="featured-sermon-date">
							<?php the_field('sermon_date'); ?>
						</div>

						<?php /* Scripture Reference */ ?>
						<?php if( get_field('scripture_reference') ) { ?>
							<div class="featured-sermon-scripture">
								<?php the_field('scripture_reference'); ?>
							</div>
						<?php } ?>

						<?php /* Speaker */ ?>
						<?php $speaker = get_field('speaker');
						if( $speaker ) { ?>
							<div class="featured-sermon-speaker">
								<a href="<?php echo home_url(ls_get_archive_page_slug( 'sermon' ).'/?speaker='.$speaker->slug); ?>"><?php echo $speaker->name; ?></a>
							</div>
						<?php } ?>

						<a href="<?php the_permalink(); ?>" class="button">Watch Sermon</a>

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
