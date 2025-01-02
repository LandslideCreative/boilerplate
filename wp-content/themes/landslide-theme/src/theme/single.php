<?php // Single Post

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-post">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

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
		
	</main>

<?php endwhile; endif;

get_footer();