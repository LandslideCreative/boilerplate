<?php get_header(); ?>

<main role="main" id="main-content">
	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

		<section class="page-section white-bg">
			<div class="grid-container">
				<div class="grid-x grid-padding-x align-center">
					<div class="large-10 cell">

						<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

							<h1 class="single-post-title"><?php the_title(); ?></h1>

							<?php if ( has_post_thumbnail()) : ?>
								<div class="single-post-image">
									<?php the_post_thumbnail('large'); ?>
								</div>
							<?php endif; ?>

							<div class="single-post-date">
								<time datetime="<?php the_time('Y-m-d'); ?> <?php the_time('H:i'); ?>"><?php echo get_the_date(); ?></time>
							</div>

							<div class="single-post-author">
								<?php the_author_posts_link(); ?>
							</div>

							<?php the_content(); ?>

							<?php $categories = wp_get_object_terms($post->ID,  'category');
							if( $categories ) { ?>
								<div class="single-post-categories">
									<?php foreach($categories as $category) { ?>
										<a href="<?php echo get_term_link($category); ?>"><?php echo $category->name; ?></a>
									<?php } ?>
								</div>
							<?php } ?>

							<?php // comments_template(); ?>

						</article>
						
					</div>
				</div>
			</div>
		</section>

		<?php // Show YARPP related posts
		if( function_exists('yarpp_related') ) {
			yarpp_related( array(
				'template' => 'yarpp-template-default',
				'limit' => 3
			));
		} ?>

	<?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>
