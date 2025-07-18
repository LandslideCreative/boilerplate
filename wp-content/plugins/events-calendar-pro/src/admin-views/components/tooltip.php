<?php
/**
 * View: Virtual Events Metabox Tooltip.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/admin-views/components/tooltip.php
 *
 * See more documentation about our views templating system.
 *
 * @since   1.8.0
 *
 * @version 1.8.0
 *
 * @link    http://evnt.is/1aiy
 *
 * @var array<string,string> $classes_wrap An array of classes for the tooltip wrap.
 * @var string               $message  The message to add to the tooltip.
 *
 */
$wrap_classes = [ 'tribe-tooltip', 'event-helper-text' ];
if ( ! empty( $classes_wrap ) ) {
	$wrap_classes = array_merge( $wrap_classes, $classes_wrap );
}

?>
<div
	<?php tec_classes( $wrap_classes ); ?>
	aria-expanded="false"
>
	<span class="dashicons dashicons-info"></span>
	<div class="down">
		<p>
			<?php
			echo wp_kses(
				$message,
				[ 'a' => [ 'href' => [] ] ] );
			?>
		</p>
	</div>
</div>
