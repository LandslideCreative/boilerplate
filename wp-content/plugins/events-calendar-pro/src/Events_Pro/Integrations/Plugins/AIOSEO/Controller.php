<?php
/**
 * Controller for Events Calendar Pro All In One SEO integration.
 *
 * Replaces the AIOSEO metabox with a redirect notice when editing a
 * recurring event occurrence that belongs to a Series, guiding the user
 * to edit SEO settings on the Series post instead.
 *
 * @since 7.8.1
 *
 * @package TEC\Events_Pro\Integrations\Plugins\AIOSEO
 */

namespace TEC\Events_Pro\Integrations\Plugins\AIOSEO;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Models\Event;
use WP_Post;

/**
 * Class Controller
 *
 * @since 7.8.1
 *
 * @package TEC\Events_Pro\Integrations\Plugins\AIOSEO
 */
class Controller extends Controller_Contract {

	/**
	 * The AIOSEO metabox ID as registered by the plugin.
	 *
	 * @since 7.8.1
	 *
	 * @var string
	 */
	const AIOSEO_METABOX_ID = 'aioseo-settings';

	/**
	 * Determines if this controller should register.
	 *
	 * Only active when All In One SEO is installed and active.
	 *
	 * @since 7.8.1
	 *
	 * @return bool Whether the integration is active.
	 */
	public function is_active(): bool {
		return defined( 'AIOSEO_FILE' );
	}

	/**
	 * Register actions and hooks for the integration.
	 *
	 * @since 7.8.1
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->singleton( static::class, $this );
		add_action( 'add_meta_boxes', [ $this, 'replace_metabox_for_occurrences' ], 100 );
	}

	/**
	 * Unregister actions and hooks for the integration.
	 *
	 * @since 7.8.1
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'add_meta_boxes', [ $this, 'replace_metabox_for_occurrences' ], 100 );
	}

	/**
	 * Replaces the AIOSEO metabox with a Series redirect notice when editing
	 * a recurring event occurrence.
	 *
	 * AIOSEO stores its metadata (focus keyphrases, SEO scores, etc.) in a custom
	 * table keyed by post ID. When editing a recurring event occurrence, the post ID
	 * in context is a provisional ID that differs from the real post ID used during
	 * save_post. This mismatch causes AIOSEO data to appear empty after saving.
	 *
	 * This function replaces the AIOSEO metabox with a helpful notice directing the user
	 * to edit SEO settings on the Series post, where AIOSEO functions correctly.
	 *
	 * @since 7.8.1
	 *
	 * @param string $post_type The current post type.
	 *
	 * @return void
	 */
	public function replace_metabox_for_occurrences( $post_type ) {
		if ( \Tribe__Events__Main::POSTTYPE !== $post_type ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$series_id = $this->get_series_id_for_occurrence( $post->ID );

		if ( empty( $series_id ) ) {
			return;
		}

		if ( ! $this->metabox_is_registered( $post_type ) ) {
			return;
		}

		// Remove the original AIOSEO metabox.
		remove_meta_box( self::AIOSEO_METABOX_ID, $post_type, 'normal' );

		// Add our replacement metabox with the same title.
		$title = defined( 'AIOSEO_PLUGIN_SHORT_NAME' )
			? sprintf(
				/* translators: 1: The AIOSEO plugin short name. */
				esc_html__( '%1$s Settings', 'tribe-events-calendar-pro' ),
				\AIOSEO_PLUGIN_SHORT_NAME
			)
			: esc_html__( 'AIOSEO Settings', 'tribe-events-calendar-pro' );

		add_meta_box(
			self::AIOSEO_METABOX_ID,
			$title,
			[ $this, 'render_series_redirect_metabox' ],
			$post_type,
			'normal',
			'high',
			[ 'series_id' => $series_id ]
		);
	}

	/**
	 * Determines if AIOSEO registered its metabox in the 'normal' context for the
	 * given post type.
	 *
	 * AIOSEO may not register its metabox at all - e.g. it is disabled for this
	 * post type, or the current user lacks the capability - in which case there is
	 * nothing to remove and no reason to show our replacement notice.
	 *
	 * @since 7.8.1
	 *
	 * @param string $post_type The current post type.
	 *
	 * @return bool Whether the AIOSEO metabox is registered.
	 */
	protected function metabox_is_registered( string $post_type ): bool {
		global $wp_meta_boxes;

		$priorities = $wp_meta_boxes[ $post_type ]['normal'] ?? [];

		foreach ( $priorities as $metaboxes ) {
			if ( ! empty( $metaboxes[ self::AIOSEO_METABOX_ID ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Renders the replacement metabox content with Series redirect messages.
	 *
	 * @since 7.8.1
	 *
	 * @param WP_Post              $_post   The current post object. Required by the
	 *                                      add_meta_box callback signature; not used.
	 * @param array<string, mixed> $metabox The metabox arguments including 'args'.
	 *
	 * @return void
	 */
	public function render_series_redirect_metabox( WP_Post $_post, array $metabox ): void {
		$series_id  = $metabox['args']['series_id'] ?? 0;
		$series_url = get_edit_post_link( $series_id, 'raw' );

		if ( empty( $series_url ) ) {
			return;
		}

		$series_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
			esc_url( $series_url ),
			esc_html__( 'edit the Series', 'tribe-events-calendar-pro' )
		);

		$allowed_link_html = [
			'a' => [
				'href'   => [],
				'target' => [],
				'rel'    => [],
			],
		];

		?>
		<div class="tec-aioseo-series-notice" style="padding: 16px 20px;">
			<p class="tec-aioseo-series-notice__title">
				<?php
				printf(
					/* translators: %s: link to edit the Series post. */
					esc_html__( 'You are editing a single occurrence of a recurring event. To manage SEO settings, please %s.', 'tribe-events-calendar-pro' ),
					wp_kses( $series_link, $allowed_link_html )
				);
				?>
			</p>
			<p class="tec-aioseo-series-notice__item">
				<?php
				printf(
					/* translators: %s: link to edit the Series post. */
					esc_html__( 'SEO Title — %s to set the SEO title for this event.', 'tribe-events-calendar-pro' ),
					wp_kses( $series_link, $allowed_link_html )
				);
				?>
			</p>
			<p class="tec-aioseo-series-notice__item">
				<?php
				printf(
					/* translators: %s: link to edit the Series post. */
					esc_html__( 'Meta Description — %s to set the meta description.', 'tribe-events-calendar-pro' ),
					wp_kses( $series_link, $allowed_link_html )
				);
				?>
			</p>
			<p class="tec-aioseo-series-notice__item">
				<?php
				printf(
					/* translators: %s: link to edit the Series post. */
					esc_html__( 'Focus Keyphrase — %s to configure focus keyphrases and SEO analysis.', 'tribe-events-calendar-pro' ),
					wp_kses( $series_link, $allowed_link_html )
				);
				?>
			</p>
			<p class="tec-aioseo-series-notice__action">
				<a href="<?php echo esc_url( $series_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Edit Series SEO Settings', 'tribe-events-calendar-pro' ); ?> &rarr;
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Determines if the given post ID (real or a provisional occurrence ID)
	 * is a recurring event that belongs to a Series, and returns the Series post ID.
	 *
	 * Standalone (non-recurring) events can also be manually attached to a Series via
	 * the "Assign event to series" metabox; those remain independent posts with their
	 * own working AIOSEO metadata, so they are deliberately excluded here.
	 *
	 * @since 7.8.1
	 *
	 * @param int $post_id The post ID to check (may be a provisional ID).
	 *
	 * @return int The Series post ID, or 0 if the post is not a recurring event in a Series.
	 */
	protected function get_series_id_for_occurrence( int $post_id ): int {
		$real_post_id = Occurrence::normalize_id( $post_id );

		if ( empty( $real_post_id ) ) {
			return 0;
		}

		$occurrence = Occurrence::find_by_post_id( $real_post_id );

		if ( ! $occurrence instanceof Occurrence || ! $occurrence->has_recurrence ) {
			return 0;
		}

		return Event::get_series_id( $real_post_id );
	}
}
