				<footer class="footer" role="contentinfo">

					<div class="grid-container footer-content">
						<div class="grid-x grid-padding-x">

							<div class="cell medium-4 footer-contact">
								<?php if( get_field('contact_page', 'options') ) { ?>
									<p><?php link_from_link( 'contact_page', FALSE, 'options' ) ?></p>
								<?php } ?>

								<?php if( get_field('address', 'options') ) { ?>
									<p><?php the_field('address', 'options'); ?></p>
								<?php } ?>

								<?php if( get_field('phone_number', 'options') ) { ?>
									<p><a href="tel:<?php the_field('phone_number', 'options'); ?>"><?php the_field('phone_number', 'options'); ?></a></p>
								<?php } ?>

								<?php if( get_field('email_address', 'options') ) { ?>
									<p><a href="mailto:<?php echo antispambot(get_field('email_address', 'options')); ?>"><?php echo antispambot(get_field('email_address', 'options')); ?></a></p>
								<?php } ?>
							</div>

							<div class="cell medium-4 footer-navigation">
								<nav class="footer-nav">
									<?php display_navigation('footer-nav'); ?>
								</nav>
							</div>

							<div class="cell medium-4 footer-social">
								<?php get_template_part('partials/footer/social'); ?>
							</div>
						</div>
					</div>

					<section class="copyright">
						<div class="grid-container">
							<div class="grid-x grid-padding-x">
								<div class="cell medium-auto">
									<p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
									<?php display_navigation('policy-nav'); ?>
								</div>
								<div class="cell medium-shrink">
									<p><a href="http://landslidecreative.com">Website Design</a> by Landslide Creative</a>
								</div>
							</div>
						</div>
					</section>

				</footer>
			</div> <!-- /off-canvas-content -->
		<?php wp_footer(); ?>
	</body>
</html>