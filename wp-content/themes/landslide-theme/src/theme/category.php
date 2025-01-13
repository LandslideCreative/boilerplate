<?php // Category Archive
$page_slug = get_page_by_path(ls_get_archive_page_slug( 'post' ));
$queried_object = get_queried_object();

get_header();

$post = $page_slug;
setup_postdata($post); 
	if( $post ) { ?>
		<main role="main" id="main-content">

			<div class="page-header">
				<div class="grid-container">
					<div class="grid-x grid-padding-x align-center vertical-center">
						<div class="cell auto">
							<h1>Posts about <?php echo $queried_object->name; ?></h1>
						</div>
					</div>
				</div>
			</div>

			<?php get_template_part('partials/page', 'builder'); ?>

		</main>
	<?php }
wp_reset_postdata();

get_footer();