jQuery(document).ready(function() {

	var browserWindow = jQuery(window);
	var wpAdminBarHeight = jQuery("#wpadminbar").height();
	var wpAdminMenu = jQuery("#adminmenuwrap");

	var containerElement = jQuery("#minor-publishing");
	var stickyElement = jQuery("#major-publishing-actions");
	var stickyElementHeight = stickyElement.outerHeight();
	var stickyPosition = stickyElement.offset().top - wpAdminBarHeight;
	var stickyClass = "sticky-publishing-actions";	

	var backToTopButton = jQuery('<a href="" title="Back to Top" id="admin-back-to-top">↑</div>');
    
	jQuery('#wpcontent').append(backToTopButton);
	stickyElement.css('top', wpAdminBarHeight);

	// Handle page refreshes
	makeElementSticky();

	// Scroll function
	browserWindow.on( 'scroll', function (event) {
		makeElementSticky();		
	});

	// Handle when admin menu is collapsed
	jQuery('#collapse-menu').on('click', function (event) {
		makeElementSticky();
    });

	// Perform scroll functions
	function makeElementSticky() {
	    var windowTop = browserWindow.scrollTop();

	    // Determine back to top visibility
	    if (windowTop >= wpAdminBarHeight) {
	    	backToTopButton.addClass('admin-back-to-top-visible');
	    } else {
			backToTopButton.removeClass('admin-back-to-top-visible');
	    }

	    // Position major publish actions
	    if (windowTop >= stickyPosition) {
	      stickyElement.addClass(stickyClass);
	      stickyElement.css('left', wpAdminMenu.width());
	      containerElement.css('padding-bottom', stickyElementHeight);
	    } else {
	      stickyElement.removeClass(stickyClass);
	      containerElement.css('padding-bottom', 0);
	    }
	}

	// Smooth scroll Back to Top button
	backToTopButton.on('click', function (event) {
		event.stopPropagation();
		event.preventDefault();
        jQuery('html').animate({ scrollTop: 0 }, 400);
    });

});