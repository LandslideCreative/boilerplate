<?php
/**
 * Block: Additional Fields - Textarea
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/blocks/additional-fields/textarea.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1ajx
 *
 * @version5.1.2
 *
 */
$label = $this->attr( 'label' );
$value = $this->attr( 'value' );

if ( empty( $value ) ) {
	return;
}

$default_classes = [ 'tribe-block', 'tribe-block__additional-field', 'tribe-block__additional-field__textarea' ];

// Add the custom classes from the block attributes.
$classes = isset( $attributes['className'] ) ? array_merge( $default_classes, [ $attributes['className'] ] ) : $default_classes;
?>
<div <?php tec_classes( $classes ); ?>>
	<h3><?php echo esc_html( $label ); ?></h3>
	<?php echo wpautop( esc_html( $value ) ); // phpcs:ignore ?>
</div>
