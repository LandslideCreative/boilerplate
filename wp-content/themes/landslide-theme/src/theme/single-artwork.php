<?php // Single Artwork

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-artwork">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<?php /* Title */ ?>
						<h5 class="label"><a href="<?php echo get_post_type_archive_link('artwork'); ?>">Artwork</a></h5>
						<h1 class="single-artwork-title"><?php the_title(); ?></h1>

						<?php if( get_field('artist') ) { 
							$artist = get_field('artist'); ?>
							<div class="single-artwork-artist">
								<a href="<?php echo get_the_permalink($artist); ?>"><?php echo get_the_title($artist); ?></a>
							</div>
						<?php } ?>

						<?php if( get_field('date') ) {?>
							<div class="single-artwork-date">
								<?php the_field('date'); ?>
							</div>
						<?php } ?>

						<?php if( get_field('location') ) {?>
							<div class="single-artwork-location">
								<?php the_field('location'); ?>
							</div>
						<?php } ?>

						<?php /* Image */ ?>
						<?php if ( has_post_thumbnail()) : ?>
							<div class="single-artwork-image">
								<?php the_post_thumbnail('large'); ?>
							</div>
						<?php endif; ?>

						<?php /* Content */ ?>
						<?php the_content(); ?>

						<?php /* Collection */ ?>
						<?php $collections = wp_get_object_terms($post->ID,  'collection');
						if( $collections ) { ?>
							<div class="single-artwork-collections">
								<?php foreach($collections as $collection) { ?>
									<a href="<?php echo get_term_link($collection); ?>"><?php echo $collection->name; ?></a>
								<?php } ?>
							</div>
						<?php } ?>
												
					</div>
				</div>
			</div>
		</section>

		<?php get_template_part('partials/page', 'builder'); ?>
		
	</main>

<?php endwhile; endif;

get_footer();