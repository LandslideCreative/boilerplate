<?php
declare(strict_types=1);

namespace WP_Rocket\Engine\Optimization\GoogleFonts;

use WP_Rocket\Engine\Common\Head\ElementTrait;
use WP_Rocket\Engine\Media\Fonts\FontsTrait;

/**
 * Abstract Optimization Parent Class for Google Fonts Optimizers.
 *
 * @since 3.8
 */
abstract class AbstractGFOptimization {
	use FontsTrait;
	use ElementTrait;

	/**
	 * Allowed display values.
	 *
	 * @since 3.8
	 *
	 * @var array
	 */
	protected $display_values = [
		'auto'     => 1,
		'block'    => 1,
		'swap'     => 1,
		'fallback' => 1,
		'optional' => 1,
	];

	/**
	 * Flag for whether google fonts have been detected (Default: true)
	 *
	 * @since 3.8.8
	 *
	 * @var bool
	 */
	protected $has_google_fonts = true;

	/**
	 * Optimize Google Fonts
	 *
	 * @param string $html HTML content.
	 *
	 * @return string
	 */
	abstract public function optimize( $html ): string;

	/**
	 * Check whether the optimizer has found google fonts on the page.
	 *
	 * @since 3.8.8
	 *
	 * @return bool Will default to true when extending classes have not set via the optimize() method.
	 */
	public function has_google_fonts() {
		return $this->has_google_fonts;
	}

	/**
	 * Returns font with display value.
	 *
	 * @since  3.8 Moved here from GoogleFonts\Combine::class
	 * @since  3.5.1
	 *
	 * @param array $font Array containing font tag and matches.
	 *
	 * @return string Google Font tag with display param.
	 */
	protected function get_font_with_display( array $font ) {
		$font_url = html_entity_decode( $font['url'] );
		$query    = wp_parse_url( $font_url, PHP_URL_QUERY );

		if ( empty( $query ) ) {
			return $font[0];
		}

		$parsed_font = wp_parse_args( $query );
		$font_url    = ! empty( $parsed_font['display'] )
			? str_replace( "&display={$parsed_font['display']}", '&display=swap', $font_url )
			: "{$font_url}&display=swap";

		return str_replace( $font['url'], esc_url( $font_url ), $font[0] );
	}

	/**
	 * Get the font display value.
	 *
	 * @since  3.8 Moved here from GoogleFonts\Combine::class
	 * @since  3.5.1
	 *
	 * @return string font display value.
	 */
	protected function get_font_display_value(): string {
		/**
		 * Filters the combined Google Fonts display parameter value
		 *
		 * @since  3.8 Moved here from GoogleFonts\Combine::class
		 * @since  3.3.5
		 *
		 * @param string $display Display value. Can be either auto, block, swap, fallback or optional.
		 */
		$display = wpm_apply_filters_typed( 'string', 'rocket_combined_google_fonts_display', 'swap' );

		return isset( $this->display_values[ $display ] ) ? $display : 'swap';
	}

	/**
	 * Check if preload google fonts is enabled or not using filter.
	 *
	 * @return bool
	 */
	protected function is_preload_enabled() {
		return ! wpm_apply_filters_typed( 'boolean', 'rocket_disable_google_fonts_preload', false );
	}

	/**
	 * Prepare preload fonts to the head items.
	 *
	 * @param array $fonts Fonts list.
	 * @param array $items Head items.
	 * @return array
	 */
	protected function prepare_preload_fonts_to_head( array $fonts, array $items ): array {
		foreach ( $fonts as $font_url ) {
			$items[] = $this->preload_link(
				[
					'href' => $font_url,
					'as'   => 'style',
				]
			);
		}

		return $items;
	}

	/**
	 * Prepare stylesheets to the head.
	 *
	 * @param array $fonts Fonts list.
	 * @param array $items Head items.
	 * @return array
	 */
	protected function prepare_stylesheet_fonts_to_head( array $fonts, array $items ): array {
		$preload_enabled = $this->is_preload_enabled();

		foreach ( $fonts as $font_url ) {
			$item = $this->stylesheet_link(
				[
					'href' => $font_url,
				]
			);

			if ( ! $preload_enabled ) {
				$items[] = $item;
				continue;
			}

			$item['media']  = 'print';
			$item['onload'] = "this.media='all'";
			$items[]        = $item;

			$items[] = $this->noscript_tag(
				sprintf( '<link rel="stylesheet" href="%1$s">', $font_url ) // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
			);
		}

		return $items;
	}

	/**
	 * Insert font stylesheets into head.
	 *
	 * @param array $items Head elements.
	 * @return mixed
	 */
	public function insert_font_stylesheet_into_head( $items ) {
		return $items;
	}

	/**
	 * Insert font preloads into head.
	 *
	 * @param array $items Head elements.
	 * @return mixed
	 */
	public function insert_font_preload_into_head( $items ) {
		return $items;
	}
}
