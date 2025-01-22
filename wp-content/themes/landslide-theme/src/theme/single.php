<?php // Single Post

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-post">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

							<?php /* Title */ ?>
							<h5 class="label"><a href="<?php echo get_post_type_archive_link('post'); ?>">Blog Post</a></h5>
							<h1 class="single-post-title"><?php the_title(); ?></h1>

							<?php /* Image */ ?>
							<?php if ( has_post_thumbnail()) : ?>
								<div class="single-post-image">
									<?php the_post_thumbnail('large'); ?>
								</div>
							<?php endif; ?>

							<?php /* Date */ ?>
							<div class="single-post-date">
								<time datetime="<?php the_time('Y-m-d'); ?> <?php the_time('H:i'); ?>"><?php echo get_the_date(); ?></time>
							</div>

							<?php /* Author */ ?>
							<?php $author = get_field('author');
							if( $author ) {
								$author_acf = 'post-author_'.$author->term_id;
								$staff_member = get_field('staff_member', $author_acf); ?>
								<div class="single-post-author">

									<?php /* Author Headshot */ ?>
									<?php // Default to author headshot 
									if( get_field('headshot', $author_acf) ) { ?>
										<div class="single-post-author-image">
											<img src="<?php echo acf_image_single( 'headshot', 'thumbnail', FALSE, $author_acf ); ?>" alt="" />
										</div>
									<?php // Fallback to staff member headshot
									} else if( $staff_member && has_post_thumbnail($staff_member->ID) ) { ?>
										<div class="post-author-image">
											<?php echo get_the_post_thumbnail( $staff_member->ID, 'thumbnail' ); ?>
										</div>
									<?php } ?>

									<?php /* Author Name */ ?>
									<div class="single-post-author-name">
										<?php if( $staff_member ) { ?>
											<a href="<?php echo get_the_permalink($staff_member->ID); ?>">
												<?php echo $author->name; ?>
											</a>
										<?php } else {
											echo $author->name;
										} ?>
									</div>

								</div>
							<?php } ?>

							<?php /* Content */ ?>
							<?php the_content(); ?>

							<?php /* Categories */ ?>
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