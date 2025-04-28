jQuery(document).ready(function() {

	// Image overlay ordering
	jQuery('.acf-field-flexible-content[data-name="page_builder"] .acf-flexible-content .layout[data-layout="image_overlay"] .acf-field[data-name="copy_side"]').each( order_image_overlay );

	jQuery('.acf-field-flexible-content[data-name="page_builder"] .acf-flexible-content').on('change', '.layout[data-layout="image_overlay"] .acf-field[data-name="copy_side"]', order_image_overlay);

	function order_image_overlay( event ) {
		var selectValue = jQuery(this).find('select').val();
		var parentDiv = jQuery(this).parent();

		if( selectValue=='right' ) {
			parentDiv.find('.acf-field[data-name="background_image"]').css('order', 0);
			parentDiv.find('.acf-field[data-name="copy"]').css('order', 1);
		} else {
			parentDiv.find('.acf-field[data-name="copy"]').css('order', 0);
			parentDiv.find('.acf-field[data-name="background_image"]').css('order', 1);
		}
	}

});