<?php
/**
 * Handles the registration of all the hooks required for the Series to correctly integrate
 * with this and other plugins.
 *
 * @since   6.0.0
 *
 * @package TEC\Events_Pro\Custom_Tables\V1\Series
 */

namespace TEC\Events_Pro\Custom_Tables\V1\Series;

use TEC\Common\Contracts\Service_Provider;
use TEC\Events\Custom_Tables\V1\Updates\Requests;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use TEC\Events_Pro\Custom_Tables\V1\Series\Providers\Base;
use TEC\Events_Pro\Custom_Tables\V1\Series\Providers\Modifications;
use TEC\Events_Pro\Custom_Tables\V1\Series\Providers\Theme_Compatibility;
use TEC\Events_Pro\Custom_Tables\V1\Updates\Relationships;
use TEC\Events_Pro\Custom_Tables\V1\Models\Event;
use Tribe__Events__Main as TEC;
use WP_Post;

/**
 * Class Provider
 *
 * @since   6.0.0
 *
 * @package TEC\Events_Pro\Custom_Tables\V1\Series
 */
class Provider extends Service_Provider {

	/**
	 * Registers the implementations and hooks to the filters and actions
	 * required for the Series to work correctly.
	 *
	 * @since 6.0.0
	 *
	 */
	public function register() {
		$series_post_type_name = Series::POSTTYPE;
		$event_post_type       = TEC::POSTTYPE;

		if ( ! has_action( 'delete_post', [ $this, 'remove_series_relationships' ] ) ) {
			add_action( 'delete_post', [ $this, 'remove_series_relationships' ], 10 );
		}

		if ( ! has_action( 'wp_trash_post', [ $this, 'trash_autogenerated_series' ] ) ) {
			add_action( 'wp_trash_post', [ $this, 'trash_autogenerated_series' ] );
		}

		if ( ! has_action( 'delete_post', [ $this, 'delete_autogenerated_series' ], 5, 2 ) ) {
			add_action( 'delete_post', [ $this, 'delete_autogenerated_series' ], 5, 2 );
		}

		if ( ! has_action( "save_post_{$series_post_type_name}", [ $this, 'remove_series_autogenerated_flag' ] ) ) {
			add_action( "save_post_{$series_post_type_name}", [ $this, 'remove_series_autogenerated_flag' ], 10, 2 );
		}

		if ( ! has_action( "save_post_{$series_post_type_name}", [ $this, 'snapshot_new_series_state' ] ) ) {
			add_action( "save_post_{$series_post_type_name}", [ $this, 'snapshot_new_series_state' ], 20, 3 );
		}

		if ( ! has_action( "save_post_{$series_post_type_name}", [ $this, 'save_series_relationship' ] ) ) {
			add_action( "save_post_{$series_post_type_name}", [ $this, 'save_series_relationship' ], 10, 2 );
		}

		if ( ! has_action( 'untrashed_post', [ $this, 'untrash_series_following_event' ] ) ) {
			add_action( 'untrashed_post', [ $this, 'untrash_series_following_event' ] );
		}

		if ( ! has_action( "save_post_{$series_post_type_name}", [ $this, 'save_show_series_title' ] ) ) {
			add_action( "save_post_{$series_post_type_name}", [ $this, 'save_show_series_title' ] );
		}

		if ( ! has_filter( 'tribe_events_register_default_linked_post_types', [
			$this,
			'register_series_linked_post_type'
		] ) ) {
			add_filter( 'tribe_events_register_default_linked_post_types', [
				$this,
				'register_series_linked_post_type'
			] );
		}

		add_filter( 'tec_events_community_allowed_fields', [ $this, 'register_events_to_series_request_key' ] );
		add_filter( 'tribe_tickets_settings_post_types', [ $this, 'filter_remove_series_post_type' ] );
		add_action( 'init', [ $this, 'remove_series_from_ticketable_post_types' ] );

		if ( ! has_action( "transition_post_status", [ $this, 'update_series_post_status' ] ) ) {
			add_action( "transition_post_status", [ $this, 'update_series_post_status' ], 10,3 );
		}

		$this->container->register( Base::class );
		$this->container->register( Modifications::class );
		$this->container->register( Theme_Compatibility::class );

		if ( did_action( 'init' ) || doing_action( 'init' ) ) {
			$this->container->get( Series::class )->register_post_type_or_fail();
		} else {
			add_action( 'init', $this->container->callback( Series::class, 'register_post_type_or_fail' ) );
		}

		add_filter( 'tec_events_qr_valid_screens', [ $this, 'add_series_screens' ] );
		add_filter( 'tec_events_qr_code_supported_post_types', [ $this, 'add_series_to_qr_types' ] );
		add_filter( 'tec_events_qr_code_redirection_type', [ $this, 'add_series_to_qr_redirection' ], 10, 2 );
		add_filter( 'tec_events_qr_code_post_types', [ $this, 'add_series_post_type' ], 10, 1 );
		add_filter( 'tec_events_qr_widget_options', [ $this, 'add_series_to_qr_widget_options' ], 10, 1 );
		add_filter( 'tec_events_qr_widget_fields', [ $this, 'add_series_to_qr_widget_fields' ], 10, 1 );
		add_filter( 'tec_events_qr_redirection_url', [ $this, 'send_to_next_event_in_series' ], 10, 3 );
		add_action( 'add_meta_boxes', [ $this, 'add_qr_code_meta_box' ] );
	}

	/**
	 * Saves the relationship between Events and Series when built from the Series side.
	 *
	 * @since 6.0.0
	 *
	 * @param int     $post_id The post ID of the Series currently being saved.
	 * @param WP_Post $post    A reference to the Series Post object.
	 */
	public function save_series_relationship( $post_id, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( ! $this->container->make( Series::class )->is_same_type( $post ) ) {
			return;
		}

		/** @var Relationships $relationship_handler */
		$relationship_handler = $this->container->make( Relationships::class );
		$relationship_handler->save_relationships_from_series( tribe( Requests::class )->from_http_request() );
	}

	/**
	 * Handles the ancillary operations that should be performed when a relevant post series
	 * is trashed or deleted.
	 *
	 * Currently, the method will severe a Series relationships when the Series is trashed or deleted.
	 *
	 * @since 6.0.0
	 *
	 * @param int $post_id The trashed post ID.
	 */
	public function remove_series_relationships( $post_id ) {
		$series = tribe( Series::class );

		if ( ! $series->is_same_type( get_post( $post_id ) ) ) {
			return;
		}

		( new Relationship() )->delete( $post_id );
	}

	/**
	 * Handles the trashing of the auto-generated Series following the last related
	 * Event trashing.
	 *
	 * @since 6.0.0
	 *
	 * @param int $post_id The trashed post ID.
	 */
	public function trash_autogenerated_series( $post_id ) {
		tribe( Autogenerated_Series::class )->trash_following( $post_id );
	}

	/**
	 * Handles the deletion of the auto-generated Series following the last related
	 * Event deletion.
	 *
	 * @since 6.0.0
	 *
	 * @param int     $post_id The deleted post ID.
	 * @param WP_Post $post    A reference to the current object model.
	 */
	public function delete_autogenerated_series( $post_id, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		tribe( Autogenerated_Series::class )->delete_following( $post );
	}

	/**
	 * Removes a Series auto-generated flag when the user manually updates the series.
	 *
	 * @since 6.0.0
	 *
	 * @param int     $post_id The Series post ID.
	 * @param WP_Post $post    A reference to the Series post object.
	 */
	public function remove_series_autogenerated_flag( $post_id, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		tribe( Autogenerated_Series::class )->remove_autogenerated_flag( $post );
	}

	/**
	 * Snapshots the Series post state on creation.
	 *
	 * @since 6.0.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function snapshot_new_series_state( $post_id, $post, $update ) {
		if ( $update ) {
			return;
		}

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		tribe( Autogenerated_Series::class )->checksum_matches( $post );
	}

	/**
	 * Untrashes a Series post following the untrash of an Event post.
	 *
	 * @since 6.0.0
	 *
	 * @param int $post_id The untrashed post ID.
	 */
	public function untrash_series_following_event( $post_id ) {
		tribe( Autogenerated_Series::class )->untrash_following( $post_id );
	}

	/**
	 * Saves the value for the checkbox to hide/show the title on views.
	 *
	 * @since 6.0.0
	 *
	 * @param int $post_id The post ID of the Event currently being saved.
	 *
	 * @return void
	 */
	public function save_show_series_title( $post_id ) {
		update_post_meta(
			$post_id,
			'_tec-series-show-title',
			tribe_get_request_var( '_tec-series-show-title', false )
		);
	}

	/**
	 * Adds the Series post type to the list of linked post types for Events.
	 *
	 * @since 6.0.0
	 *
	 * @param array<string> $linked_post_types A list of post types that should be considered
	 *                                         linked to the Event post type.
	 *
	 * @return array<string> The filtered list of linked post types.
	 */
	public function register_series_linked_post_type( array $linked_post_types = [] ) {
		$linked_post_types[] = Series::POSTTYPE;

		return $linked_post_types;
	}

	/**
	 * Adds the Event to Series key to the list of allowed keys for Community Events.
	 *
	 * @since 6.0.0
	 *
	 * @param array<string> $allowed_keys A list of post types that should be considered
	 *                                    linked to the Event post type.
	 *
	 * @return array<string> The filtered list of linked post types.
	 */
	public function register_events_to_series_request_key( array $allowed_keys = [] ) {

		if ( ! apply_filters( 'tec_community_events_use_series', false ) ) {
			return $allowed_keys;
		}

		$allowed_keys[] = Relationship::EVENTS_TO_SERIES_REQUEST_KEY;
		return $allowed_keys;
	}

	/**
	 * Updates the post status of auto-generated Series posts when one of their related Events post status changes.
	 *
	 * @since 6.0.11
	 *
	 * @param string  $new_status The new post status.
	 * @param string  $old_status The old post status.
	 * @param WP_Post $post       The post object, at this stage it might not be an Event post.
	 *
	 * @return void The method will return early if the post is not an Event post, else the post status will be updated.
	 */
	public function update_series_post_status( $new_status, $old_status, $post ): void {
		if ( ! (
			is_string( $old_status )
			&& is_string( $new_status )
			&& $post instanceof WP_Post
			&& $post->post_type === TEC::POSTTYPE )
		) {
			return;
		}

		$this->container->make( Autogenerated_Series::class )
		                ->update_series_post_status( $post, $old_status, $new_status );
	}

	/**
	 * Filters the list of post types available under "Tickets > Settings" in ET to remove the Series post type.
	 *
	 * @since 6.2.1
	 *
	 * @param array $post_types The original list of post types available under "Tickets > Settings".
	 *
	 * @return array The updated list of post types with Series removed.
	 */
	public function filter_remove_series_post_type( $post_types ) {
		// Bail if the Series post type is not in the list of post types.
		if ( ! isset( $post_types[ Series::POSTTYPE ] ) ) {
			return $post_types;
		}

		// Remove the Series post type from the list of post types.
		unset( $post_types[ Series::POSTTYPE ] );

		return $post_types;
	}

	/**
	 * Removes the Series post type from the list of post types that can have tickets.
	 *
	 * @since 6.3.0
	 *
	 * @return void
	 */
	public function remove_series_from_ticketable_post_types() {
		// Get the current ticket-enabled post types.
		$options = get_option( TEC::OPTIONNAME, [] );

		// Check if 'ticket-enabled-post-types' key exists in options.
		if ( isset( $options['ticket-enabled-post-types'] ) ) {
			// Search for the Series post type in the list of enabled post types.
			$key = array_search( Series::POSTTYPE, $options['ticket-enabled-post-types'] );

			// If the Series post type is found, remove it from the list.
			if ( $key !== false ) {
				unset( $options['ticket-enabled-post-types'][ $key ] );
			}

			// Update the option with the new list of post types.
			update_option( TEC::OPTIONNAME, $options );
		}
	}

	/**
	 * Adds Series post type screens to the list of valid screens for QR code assets.
	 *
	 * @since 7.5.0
	 *
	 * @param array $valid_screens Array of screen IDs where QR code assets should be loaded.
	 *
	 * @return array The filtered list of valid screens.
	 */
	public function add_series_screens( array $valid_screens ): array {
		$series_screens = [ 'edit-' . Series::POSTTYPE, Series::POSTTYPE ];

		return array_merge( $valid_screens, $series_screens );
	}

	/**
	 * Adds the Series post type to the list of post types that support QR codes.
	 *
	 * @since 7.5.0
	 *
	 * @param array $supported Array of supported post types.
	 *
	 * @return array The filtered list of supported post types.
	 */
	public function add_series_to_qr_types( array $supported ): array {
		$supported[] = Series::POSTTYPE;
		return $supported;
	}

	/**
	 * Adds the QR code meta box.
	 *
	 * @since 7.5.0
	 * @return void
	 */
	public function add_qr_code_meta_box(): void {
		$screen = get_current_screen();
		if ( $screen && 'add' === $screen->action ) {
			return;
		}

		add_meta_box(
			'tec-events-qr-code',
			esc_html__( 'QR Code', 'the-events-calendar' ),
			[ $this, 'render_qr_code_meta_box' ],
			Series::POSTTYPE,
			'side',
			'default'
		);
	}

	/**
	 * Renders the QR code meta box.
	 *
	 * @since 7.5.0
	 * @return void
	 */
	public function render_qr_code_meta_box(): void {
		tribe( \TEC\Events\QR\QR_Code::class )->render_qr_code_meta_box();
	}

	/**
	 * Filters the redirection type for QR codes.
	 *
	 * @since 7.5.0
	 *
	 * @param string  $redirection The redirection type ('specific' or 'next').
	 * @param WP_Post $post The post object.
	 *
	 * @return string The filtered redirection type.
	 */
	public function add_series_to_qr_redirection( string $redirection, WP_Post $post ): string {
		if ( tribe_is_recurring_event( $post ) || Series::POSTTYPE === $post->post_type ) {
			return 'next';
		}

		return $redirection;
	}

	/**
	 * Adds Series to the list of post types that support QR codes.
	 *
	 * @since 7.5.0
	 *
	 * @param array $supported_post_types Array of supported post types.
	 *
	 * @return array The filtered list of supported post types.
	 */
	public function add_series_post_type( array $supported_post_types ): array {
		$supported_post_types[] = Series::POSTTYPE;
		return $supported_post_types;
	}

	/**
	 * Adds Series to the list of post types that support QR codes.
	 *
	 * @since 7.5.0
	 *
	 * @param array $options The array of options.
	 *
	 * @return array The filtered array of options.
	 */
	public function add_series_to_qr_widget_options( array $options ): array {

		$options[] = [
			'value' => 'next',
			'text'  => _x( 'Redirect to the next event in a series', 'Next event in series redirection option', 'the-events-calendar' ),
		];

		return $options;
	}

	/**
	 * Adds Series to the list of fields for the QR Code widget.
	 *
	 * @since 7.5.0
	 *
	 * @param array $fields The array of fields.
	 *
	 * @return array The filtered array of fields.
	 */
	public function add_series_to_qr_widget_fields( $fields ): array {
		$args = [
			'posts_per_page' => -1,
			'post_type'      => Series::POSTTYPE,
			'post_status'    => 'publish',
			'orderby'        => 'ID',
			'order'          => 'DESC',
		];

		$series_query = get_posts( $args );
		if ( ! empty( $series_query ) ) {
			foreach ( $series_query as $series ) {
				$series_options[] = [
					'value' => $series->ID,
					'text'  => "{$series->ID} - {$series->post_title}",
				];
			}
		} else {
			$series_options[] = [
				'value' => '',
				'text'  => esc_html__( 'No Series have been created yet.', 'the-events-calendar' ),
			];
		}

		$fields['series_id'] = [
			'id'             => 'series_id',
			'label'          => _x( 'Series ID:', 'The label for the series ID setting.', 'the-events-calendar' ),
			'type'           => 'dropdown',
			'parent_classes' => 'hidden',
			'classes'        => 'tribe-dependent',
			'options'        => $series_options,
			'dependency'     => [
				'ID' => 'redirection',
				'is' => 'next',
			],
		];

		return $fields;
	}

	/**
	 * Get the URL for the next event in a series.
	 *
	 * @since 7.5.0
	 *
	 * @param string $url The URL to redirect to.
	 * @param array  $data The data array.
	 * @param object $context The context object.
	 *
	 * @return string The URL to redirect to.
	 */
	public function send_to_next_event_in_series( $url, $data, $context ): string {

		if ( ! isset( $data['qr_type'] ) || ! isset( $data['post_id'] ) ) {
			return $url;
		}

		if ( 'next' !== $data['qr_type'] ) {
			return $url;
		}

		$next_event_id = Event::next_in_series( (int) $data['post_id'] );
		if ( null === $next_event_id ) {
			return $url;
		}

		$url = get_permalink( $next_event_id );

		/**
		 * Filters the URL for the next event in a series redirection.
		 *
		 * @since 7.5.0
		 *
		 * @param string $url     The URL to redirect to.
		 * @param array  $data    The data array.
		 * @param object $context The context object.
		 */
		$url = apply_filters( 'tec_events_qr_next_series_event_url', $url, $data, $context );

		return esc_url( $url );
	}
}
