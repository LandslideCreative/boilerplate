<?php // Single Sermon

$series = get_field('series');
$speaker = get_field('speaker');

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-sermon">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<?php /* Title */ ?>
						<h5 class="label"><a href="<?php echo get_post_type_archive_link('sermon'); ?>">Sermon</a></h5>
						<h1 class="single-post-title"><?php the_title(); ?></h1>

						<?php /* Image/Video */ ?>
						<?php // Default to sermon video
						if( get_field( 'sermon_video') ) { ?>
							<div class="single-sermon-video">
								<?php the_field('sermon_video'); ?>
							</div>
						<?php // Fallback to featured image
						} else if( has_post_thumbnail() ) {  ?>
							<div class="single-sermon-image">
								<?php thumbnail_image_tag( '100vw', 'small' ); ?>
							</div>
						<?php // Fallback to the series featured image
						} else if( get_field('featured_image', 'series_'.$series->term_id) ) { ?>
							<div class="single-sermon-image">
								<?php acf_image_tag( 'featured_image', '100vw', 'small', FALSE, 'series_'.$series->term_id ); ?>
							</div>
						<?php } ?>

						<?php /* Audio */ ?>
						<?php if( get_field('sermon_audio') ) { ?>
							<div class="single-sermon-audio">
								<?php echo do_shortcode('[audio src="'.get_field('sermon_audio').'"]'); ?>
							</div>
						<?php } ?>

						<?php /* Date */ ?>
						<div class="single-sermon-date">
							<?php the_field('sermon_date'); ?>
						</div>

						<?php /* Scripture Reference */ ?>
						<?php if( get_field('scripture_reference') ) { ?>
							<div class="single-sermon-scripture">
								<?php the_field('scripture_reference'); ?>
							</div>
						<?php } ?>

						<?php /* Speaker */ ?>
						<?php if( $speaker ) { ?>
							<div class="single-sermon-speaker">
								<a href="<?php echo home_url(ls_get_archive_page_slug( 'sermon' ).'/?speaker='.$speaker->slug); ?>"><?php echo $speaker->name; ?></a>
							</div>
						<?php } ?>

						<?php /* Series */ ?>
						<?php if( $series ) { ?>
							<div class="single-sermon-series">
								<a href="<?php echo get_term_link($series); ?>"><?php echo $series->name; ?></a>
							</div>
						<?php } ?>

						<?php /* Description */ ?>
						<?php the_content(); ?>

						<?php /* Topics */ ?>
						<?php $topics = wp_get_object_terms($post->ID,  'topic');
						if( $topics ) { ?>
							<div class="single-sermon-topics">
								<?php foreach($topics as $topic) { ?>
									<a href="<?php echo home_url(ls_get_archive_page_slug( 'sermon' ).'/?topic='.$topic->slug); ?>"><?php echo $topic->name; ?></a>
								<?php } ?>
							</div>
						<?php } ?>

						<?php /* Books of the Bible */ ?>
						<?php $books = wp_get_object_terms($post->ID,  'bible');
						if( $books ) { ?>
							<div class="single-sermon-bible">
								<?php foreach($books as $book) { ?>
									<a href="<?php echo home_url(ls_get_archive_page_slug( 'sermon' ).'/?bible='.$book->slug); ?>"><?php echo $book->name; ?></a>
								<?php } ?>
							</div>
						<?php } ?>
												
					</div>
				</div>
			</div>
		</section>
		
	</main>

<?php endwhile; endif;

get_footer();