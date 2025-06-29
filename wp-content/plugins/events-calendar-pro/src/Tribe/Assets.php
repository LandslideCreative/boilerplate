<?php

/**
 * Registers and Enqueues the assets
 *
 * @since  4.4.30
 */
class Tribe__Events__Pro__Assets {
	/**
	 * Caches the result of the `should_enqueue_frontend` check.
	 *
	 * @since 5.0.0
	 *
	 * @var bool
	 */
	protected $should_enqueue_frontend;

	/**
	 * Registers and Enqueues the assets
	 *
	 * @since  4.4.30
	 *
	 * @return void
	 */
	public function register() {
		$pro = Tribe__Events__Pro__Main::instance();

		// Vendor
		tec_assets(
			$pro,
			[
				[ 'tribe-events-pro-imagesloaded', 'vendor/imagesloaded/imagesloaded.pkgd.js', [ 'tribe-events-pro' ] ],
				[ 'tribe-events-pro-isotope', 'vendor/isotope/isotope.pkgd.js', [ 'tribe-events-pro-imagesloaded' ] ],
				[ 'tribe-events-pro-slimscroll', 'vendor/nanoscroller/jquery.nanoscroller.js', [ 'tribe-events-pro', 'jquery-ui-draggable' ] ],
			],
			null,
			[
				'in_footer' => false,
			]
		);

		// Vendor: Admin
		tec_assets(
			$pro,
			[
				[ 'tribe-events-pro-handlebars', 'vendor/handlebars/handlebars.min.js' ],
			],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ Tribe__Main::instance(), 'should_load_common_admin_css' ],
			]
		);

		tec_asset(
			$pro,
			'tec-events-pro-single',
			'events-single.css',
			[],
			'wp_enqueue_scripts',
			[
				'groups'       => [ 'events-pro-styles' ],
				'conditionals' => [ $this, 'should_enqueue_frontend' ],
			]
		);

		tec_asset(
			$pro,
			'tribe_events-premium-admin-style',
			'events-admin.css',
			[],
			'admin_enqueue_scripts',
			[
				'priority' => 10,
			]
		);

		tec_asset(
			$pro,
			'tribe_events-premium-admin-style',
			'events-admin.css',
			[],
			[ 'tribe_venues_enqueue', 'tribe_events_enqueue' ]
		);

		tec_asset(
			$pro,
			'tribe_events-premium-admin',
			'events-admin.js',
			[ 'jquery-ui-datepicker', 'wp-util', 'tribe-timepicker' ],
			[ 'tribe_venues_enqueue', 'tribe_events_enqueue' ],
			[
				'localize' => [
					[
						'name' => 'TribeEventsProAdmin',
						'data' => static fn () => apply_filters( 'tribe_events_pro_localize_script', [], 'TribeEventsProAdmin', Tribe__Events__Main::POSTTYPE . '-premium-admin' ),
					],
					[
						'name' => 'tribe_events_pro_recurrence_strings',
						'data' => static fn () => apply_filters(
							'tribe_events_pro_recurrence_strings',
							[
								'date'       => Tribe__Events__Pro__Recurrence__Meta::date_strings(),
								'recurrence' => Tribe__Events__Pro__Recurrence__Strings::recurrence_strings(),
								'exclusion'  => [],
							]
						),
					],
				],
			]
		);

		tec_asset(
			$pro,
			'tribe-events-full-pro-calendar-style',
			'tribe-events-pro-full.css',
			[],
			'wp_enqueue_scripts',
			[
				'priority'     => 5,
				'conditionals' => [
					'operator' => 'AND',
					[ $this, 'is_style_option_tribe' ],
					[ $this, 'should_enqueue_frontend' ],
				],
			]
		);

		tec_asset(
			$pro,
			'tribe-events-calendar-pro-style',
			$this->get_style_file(),
			[],
			'wp_enqueue_scripts',
			[
				'groups'       => [ 'events-pro-styles' ],
				'conditionals' => [
					[ $this, 'should_enqueue_frontend' ],
				],
			]
		);


		// Custom stylesheet.
		$override_sheet = Tribe__Events__Templates::locate_stylesheet( 'tribe-events/pro/tribe-events-pro.css' );

		if ( ! empty( $override_sheet ) && file_exists( $override_sheet ) ) {
			tec_asset(
				$pro,
				'tribe-events-calendar-pro-override-style',
				$override_sheet,
				[],
				'wp_enqueue_scripts',
				[
					'conditionals' => [ $this, 'should_enqueue_frontend' ],
					'groups'       => [ 'events-pro-styles' ],
				]
			);
		}

		$widget_overrides_stylesheet = Tribe__Events__Templates::locate_stylesheet( 'tribe-events/pro/widget-calendar.css' );

		if ( ! empty( $widget_overrides_stylesheet ) ) {
			tec_asset(
				$pro,
				Tribe__Events__Main::POSTTYPE . '-widget-calendar-pro-override-style',
				$widget_overrides_stylesheet,
				[],
				null,
				[]
			);
		}

		tec_asset(
			$pro,
			'tec-pro-widget-blocks',
			'app/widgets.js',
			[
				'tec-widget-blocks',
			],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'conditionals' => [ $this, 'is_edit_screen' ],
				'priority'     => 201,
				'group_path'   => get_class( $pro ) . '-packages',
			]
		);

		tec_asset(
			$pro,
			'tec-pro-widget-blocks-styles',
			'app/style-widgets.css',
			[
				'wp-widgets',
				'tribe-select2-css',
			],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'conditionals' => [ $this, 'is_edit_screen' ],
				'group_path'   => get_class( $pro ) . '-packages',
			]
		);

		tec_asset(
			$pro,
			'tribe-events-pro-mini-calendar-block-styles',
			'tribe-events-pro-mini-calendar-block.css',
			[],
			'wp_enqueue_scripts',
			[
				'in_footer' => true,
			]
		);

		tec_asset(
			$pro,
			'tribe_events-premium-recurrence',
			'events-recurrence.js',
			[
				'tribe-events-admin',
				Tribe__Events__Main::POSTTYPE . '-premium-admin',
				'tribe-events-pro-handlebars',
				'tec-dayjs',
				'tec-dayjs-isoweek',
				'tec-dayjs-customparseformat',
				'tribe-dropdowns',
				'jquery-ui-dialog',
				'tribe-buttonset'
			],
			[ 'tribe_events_enqueue', 'tribe_venue_enqueue' ],
			[
				'in_footer' => true,
			]
		);
	}

	/**
	 * Enqueue any asset loading to specific actions.
	 *
	 * @since 6.0.0
	 */
	public function add_actions() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_dependencies' ] );
	}

	/**
	 * Enqueue the dependency on any block editor page since because of widgets we might have special
	 * needs for these pages.
	 *
	 * @since 6.0.0
	 */
	public function enqueue_dependencies() {
		tribe_asset_enqueue( 'tribe-dependency' );
		tribe_asset_enqueue( 'tribe-dependency-style' );
	}

	/**
	 * Checks if we have a mobile Breakpoint
	 *
	 * @since  4.4.30
	 *
	 * @return bool
	 */
	public function is_mobile_breakpoint() {
		$mobile_break = tribe_get_mobile_breakpoint();

		return $mobile_break > 0;
	}

	/**
	 * Checks if we are using Tribe setting for Style
	 *
	 * @since  4.4.30
	 *
	 * @return bool
	 */
	public function is_style_option_tribe() {
		$style_option = tribe_get_option( 'stylesheetOption', 'tribe' );

		return 'tribe' === $style_option;
	}

	/**
	 * Due to how we define which style we use based on an Option on the Administration
	 * we need to determine this file.
	 *
	 * @since  4.4.30
	 *
	 * @return string
	 */
	public function get_style_file() {
		$name = tribe_get_option( 'stylesheetOption', 'tribe' );

		$stylesheets = [
			'tribe' => 'tribe-events-pro-full.css',
			'full'  => 'tribe-events-pro-full.css',
		];

		// By default we go with `full`.
		$file = $stylesheets['full'];

		// If we have one we use it.
		if ( isset( $stylesheets[ $name ] ) ) {
			$file = $stylesheets[ $name ];
		}

		/**
		 * Allows filtering of the Stylesheet file for Events Calendar Pro.
		 *
		 * @deprecated  4.4.30
		 *
		 * @param string $file Which file we are loading
		 * @param string $name Option from the DB of style we are using
		 */
		return apply_filters( 'tribe_events_pro_stylesheet_url', $file, $name );
	}


	/**
	 * Due to how we define which style we use based on an Option on the Administration
	 * we need to determine this file.
	 *
	 * @since  4.4.33
	 *
	 * @return string
	 */
	public function get_widget_style_file() {
		$name = tribe_get_option( 'stylesheetOption', 'tribe' );

		$stylesheets = [
			'tribe'    => 'widget-theme.css',
			'full'     => 'widget-full.css',
			'skeleton' => 'widget-skeleton.css',
		];

		// By default we go with `tribe`
		$file = $stylesheets['tribe'];

		// if we have one we use it
		if ( isset( $stylesheets[ $name ] ) ) {
			$file = $stylesheets[ $name ];
		}

		/**
		 * Allows filtering of the Stylesheet file for Events Calendar Pro Widgets
		 *
		 * @deprecated  4.4.33
		 *
		 * @param string $file Which file we are loading
		 * @param string $name Option from the DB of style we are using
		 */
		return apply_filters( 'tribe_events_pro_widget_calendar_stylesheet_url', $file, $name );
	}

	/**
	 * When to enqueue the Pro Styles on the front-end
	 *
	 * @since  4.4.30
	 * @since  5.0.0 Cache the check value.
	 *
	 * @return bool
	 */
	public function should_enqueue_frontend() {
		if ( null !== $this->should_enqueue_frontend ) {
			return $this->should_enqueue_frontend;
		}

		global $post;

		$should_enqueue = (
			tribe_is_event_query()
			|| ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'tribe_events' ) )
		);

		$this->should_enqueue_frontend = $should_enqueue;

		return $should_enqueue;
	}

	/**
	 * Check if the override stylesheet exists.
	 *
	 * @since 7.0.0
	 *
	 * @return bool
	 */
	public function override_style_exists(): bool {
		_deprecated_function( __METHOD__, '7.0.1', 'Tribe__Events__Pro__Assets::should_enqueue_frontend' );
		// This is a frontend script, let's bail early if we can.
		if ( ! $this->should_enqueue_frontend() ) {
			return false;
		}

		$file = Tribe__Events__Templates::locate_stylesheet( 'tribe-events/pro/tribe-events-pro.css' );
		return $file && file_exists( $file );
	}

	/**
	 * Gets the localize data for Main Events Calendar Pro
	 *
	 * @since  4.4.30
	 *
	 * @return array
	 */
	public function get_data_tribe_events_pro() {
		$data = [
			'geocenter'           => Tribe__Events__Pro__Geo_Loc::instance()->estimate_center_point(),
			'map_tooltip_event'   => esc_html( sprintf( _x( '%s: ', 'Event title map marker prefix', 'tribe-events-calendar-pro' ), tribe_get_event_label_singular() ) ),
			'map_tooltip_address' => esc_html__( 'Address: ', 'tribe-events-calendar-pro' ),
		];

		/**
		 * Filters the Main Events Calendar Pro script localization
		 *
		 * @since 4.4.30
		 *
		 * @param array  $data        JS variable
		 * @param string $object_name The localization object var name.
		 * @param string $script      Which script this localizes
		 */
		$data = apply_filters( 'tribe_events_pro_localize_script', $data, 'TribeEventsPro', 'tribe-events-pro' );

		return $data;
	}

	/**
	 * Gets the localize data for Geoloc on Events Calendar Pro
	 *
	 * @since  4.4.30
	 *
	 * @return array
	 */
	public function get_data_tribe_geoloc() {

		$data = [
			'ajaxurl'  => admin_url( 'admin-ajax.php', admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) ),
			'nonce'    => wp_create_nonce( 'tribe_geosearch' ),
			'map_view' => 'map' === tribe( 'tec.main' )->displaying,
			'pin_url'  => Tribe__Customizer::instance()->get_option( [ 'global_elements', 'map_pin' ], false ),
		];

		/**
		 * Filters the Events Calendar Pro Maps script localization
		 *
		 * @since  4.4.30  Removed the Third param
		 *
		 * @param array  $data   JS variable
		 * @param string $script Which script this localizes
		 */
		$data = apply_filters( 'tribe_events_pro_geoloc_localize_script', $data, 'tribe-events-pro-geoloc' );

		return $data;
	}

	/**
	 * Check whether the current page is an edit post type page.
	 *
	 * @since 5.11.1
	 *
	 * @return bool
	 */
	public function is_edit_screen() {
		$current_screen = get_current_screen();

		return 'post' === $current_screen->base;
	}
}
