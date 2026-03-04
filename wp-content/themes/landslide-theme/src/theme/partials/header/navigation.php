<?php // Optional top navigation
if( has_nav_menu( 'top-nav' ) ) { ?>
	<div class="top-bar">
		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<div class="top-bar-search">
						<a href="" class="top-bar-search-toggle" aria-label="Toggle search form">
							<svg viewBox="0 0 16 16" width="32" height="32" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
							    <path d="M16 13.5 11.3049 8.8049C11.7484 7.9678 12 7.0133 12 6c0-3.3137-2.6863-6-6-6S0 2.6863 0 6s2.6863 6 6 6c1.0133 0 1.9678-0.2516 2.8049-0.6951L13.5 16zM2 6c0-2.2091 1.7909-4 4-4s4 1.7909 4 4-1.7909 4-4 4-4-1.7909-4-4z" />
							</svg>
						</a>
						<?php get_template_part('searchform'); ?>
					</div>
					<nav class="top-nav dropdown-nav">
						<?php display_navigation('top-nav'); ?>
					</nav>
				</div>
			</div>
		</div>
	</div>
<?php } ?>

<?php if( ls_sticky_nav() ) { ?>
	<div class="sticky-navigation-container" id="sticky-navigation" <?php ls_sticky_nav('container'); ?>>
<?php } ?>
	<div class="navigation-container" id="main-navigation" <?php ls_sticky_nav('element'); ?>>
		<div class="grid-container">
			<div class="grid-x grid-padding-x vertical-center">
				<div class="cell auto">
					<div class="logo">
						<a href="<?php echo home_url(); ?>">
							<?php $site_logo = get_field( 'site_logo', 'options');
							if( $site_logo ) { ?>
								<img src="<?php echo acf_image_single( 'site_logo', 'small', FALSE, 'options' ); ?>" alt="" />
							<?php } else { ?>
								<img src="<?php echo get_image_directory(); ?>/logo.png" alt="" />
							<?php } ?>
						</a>
					</div>
				</div>
				<div class="cell shrink">
					<nav class="main-nav dropdown-nav">
						<?php display_navigation('main-nav'); ?>
					</nav>
					<div class="mobile-nav-toggle">
						<a href="#" class="off-canvas-toggle" data-toggle="offCanvas" aria-label="Mobile Navigation">
							<div></div>
							<div></div>
							<div></div>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php if( ls_sticky_nav() ) { ?>
	</div>
<?php } ?>