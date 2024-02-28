<div class="page-header">
	<div class="grid-container">
		<div class="grid-x grid-padding-x align-center vertical-center">
			<div class="cell auto">
				<?php if( get_field('events_header') ) {
					the_field('events_header');
				} else { ?>
					<h1><?php the_title(); ?></h1>
				<?php } ?>
			</div>
			<?php if( has_post_thumbnail() ) { ?>
				<div class="cell medium-6 large-5">
					<?php the_post_thumbnail(); ?>
				</div>
			<?php } ?>
		</div>
	</div>
</div>