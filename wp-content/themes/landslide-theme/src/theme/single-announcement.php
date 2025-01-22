<?php // Single Announcement

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<section class="page-section white-bg single-announcement">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<?php /* Title */ ?>
						<h5 class="label">Announcement</h5>
						<h1 class="single-announcement-title"><?php the_title(); ?></h1>

						<?php /* Date */ ?>
						<?php if( !get_field('hide_date') ) { ?>
							<div class="single-post-date">
								<time datetime="<?php the_time('Y-m-d'); ?> <?php the_time('H:i'); ?>"><?php echo get_the_date(); ?></time>
							</div>
						<?php } ?>

						<?php /* Content */ ?>
						<?php the_content(); ?>
												
					</div>
				</div>
			</div>
		</section>
		
	</main>

<?php endwhile; endif;

get_footer();