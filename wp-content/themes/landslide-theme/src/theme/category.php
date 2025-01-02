<?php // Category Archive

$queried_object = get_queried_object();

get_header(); ?>

<main role="main" id="main-content">
	<div class="page-section white-bg post-list" id="post-list">

		<?php // Header ?>
		<div class="grid-container intro-section">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<h1>Posts about <?php echo $queried_object->name; ?></h1>
				</div>
			</div>
		</div>

		<?php // Post List ?>
		<div class="grid-container post-list-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<?php get_template_part('loop'); ?>
				</div>
			</div>
		</div>

		<?php // Pagination
		$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
		$args = array(
			'current_page' => $paged,
			'max_pages' => $wp_query->max_num_pages
		);

		if( $args['max_pages'] > 1 ) { ?>
			<div class="grid-container post-list-pagination">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php get_template_part('partials/pagination/list', '', $args); ?>
					</div>
				</div>
			</div>
		<?php } ?>
		
	</div>
</main>

<?php get_footer(); ?>
