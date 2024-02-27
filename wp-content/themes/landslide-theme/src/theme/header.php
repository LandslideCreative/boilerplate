<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<title><?php wp_title(''); ?></title>

		<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?>" href="<?php bloginfo('rss2_url'); ?>" />

		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<?php wp_head(); ?>

		<?php the_field('gtm_head', 'options'); ?>
	</head>
	<body <?php body_class(); ?>>
		<?php the_field('gtm_body', 'options'); ?>

		<div class="off-canvas position-right" id="offCanvas" data-off-canvas data-trap-focus="true">
			
			<ul class="off-canvas-navigation dropdown-nav">
	        	<?php display_simple_navigation('main-nav'); ?>
	        </ul>

	        <?php if( has_nav_menu( 'top-nav' ) ) { ?>
	        	<ul class="off-canvas-navigation secondary dropdown-nav">
	        		<?php display_simple_navigation('top-nav'); ?>
	        	</ul>
	        <?php } ?>

	        <button class="close-off-canvas" aria-label="Close menu" type="button" data-close>
				Close Menu
		    </button>

		</div>
		<div class="off-canvas-content" data-off-canvas-content>
			<header class="header" role="banner" <?php ls_sticky_nav( 'container' ); ?>>

				<div class="accessibility-nav">
					<a href="#main-content" class="skip-to-main">Skip to Main Content</a>
				</div>

				<?php if( get_field('alert_toggle', 'options') ) { ?>
					<div class="alert-bar">
						<?php the_field('alert_copy', 'options'); ?>
					</div>
				<?php } ?>

				<?php get_template_part('partials/header/navigation'); ?>

			</header>
