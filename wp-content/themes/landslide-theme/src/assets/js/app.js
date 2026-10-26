import $ from 'jquery';
import 'what-input';

window.$ = window.jQuery = $;

import './lib/foundation-explicit-pieces';
// import './lib/swiper-explicit-pieces';

require('./lib/jquery.fitVids.js');
require('./lib/jquery.matchHeight.js');

// Close navigation submenus.
function close_navigation_submenus(exceptItem = null, animate = true) {
	$('.dropdown-nav .menu-item-has-children.nav-open').each(function() {
		if (this === exceptItem) {
			return;
		}

		const item = $(this);
		const toggle = item.children('.submenu-toggle');
		const submenu = item.children('.sub-menu');

		if (submenu.find(document.activeElement).length) {
			toggle.trigger('focus');
		}

		toggle.attr('aria-expanded', 'false');
		item.removeClass('nav-open');
		submenu.stop(true, false);

		if (animate || item.closest('.off-canvas-navigation-container').length) {
			submenu.slideUp('fast');
		} else {
			submenu.hide();
		}
	});
}

// Close header search.
function close_header_search(returnFocus = false, animate = true) {
	const container = $('.top-bar-search');
	const toggle = container.find('.top-bar-search-toggle');
	const search = container.find('#header-search');

	if (toggle.attr('aria-expanded') !== 'true') {
		return;
	}

	if (returnFocus || search.find(document.activeElement).length) {
		toggle.trigger('focus');
	}

	toggle.attr('aria-expanded', 'false');
	container.removeClass('nav-open');
	search.stop(true, false);

	if (animate) {
		search.slideUp('fast');
	} else {
		search.hide();
	}
}

$(document).ready(function() {

	$(document).foundation();

	// Initialize FitVids
	$('body').fitVids({
		customSelector: 'iframe[src*="facebook"], iframe[src^="https://livestream.com"]'
	});

	// Connect each submenu toggle to its submenu.
	$('.dropdown-nav .submenu-toggle').each(function(index) {
		const submenu = $(this).siblings('.sub-menu');

		if (!submenu.attr('id')) {
			submenu.attr('id', 'navigation-submenu-' + index);
		}

		$(this).attr('aria-controls', submenu.attr('id'));
	});

	// Toggle navigation submenus.
	$('.dropdown-nav .submenu-toggle').on('click', function(event) {
		event.stopPropagation();

		const item = $(this).parent();
		const submenu = item.children('.sub-menu');

		if ($(this).attr('aria-expanded') === 'true') {
			close_navigation_submenus(null, true);
			return;
		}

		close_navigation_submenus(item[0], false);
		close_header_search(false, false);

		item.addClass('nav-open');
		$(this).attr('aria-expanded', 'true');
		submenu.stop(true, false).slideDown('fast');
	});

	// Escape closes the current submenu and returns focus to its toggle.
	$('.dropdown-nav').on('keydown', function(event) {
		if (event.key !== 'Escape') {
			return;
		}

		const item = $(event.target).closest('.menu-item-has-children.nav-open');

		if (!item.length) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		item.children('.submenu-toggle').trigger('focus');
		close_navigation_submenus(null, true);
	});

	// Toggle header search.
	$('.top-bar-search-toggle').on('click', function(event) {
		event.stopPropagation();

		const container = $(this).closest('.top-bar-search');
		const search = container.find('#header-search');

		if ($(this).attr('aria-expanded') === 'true') {
			close_header_search(true);
			return;
		}

		close_navigation_submenus(null, false);

		container.addClass('nav-open');
		$(this).attr('aria-expanded', 'true');

		search.stop(true, false).slideDown('fast', function() {
			if (container.find('.top-bar-search-toggle').attr('aria-expanded') === 'true') {
				$(this).find('.search-input').trigger('focus');
			}
		});
	});

	// Keep clicks inside search from closing it.
	$('.top-bar-search').on('click', function(event) {
		event.stopPropagation();
	});

	// Escape closes search and returns focus to the toggle.
	$('.top-bar-search').on('keydown', function(event) {
		if (event.key === 'Escape' && $(this).find('.top-bar-search-toggle').attr('aria-expanded') === 'true') {
			event.preventDefault();
			event.stopPropagation();
			close_header_search(true);
		}
	});

	// Close menus and search when clicking elsewhere.
	$(document).on('click', function() {
		close_navigation_submenus();
		close_header_search();
	});


	// Pagination select
	$('.pagination.page-select .pagination-select-current').on('click', function() {
		event.stopPropagation();
		event.preventDefault();

		var container = $(this).parent();

		if( container.hasClass('pagination-select-open') ) {
			container.find('ul').slideUp('fast', function() {
				container.find('.pagination-select-current').attr('aria-expanded', 'false');
			    container.removeClass('pagination-select-open');
			});			
		} else {
			container.find('ul').slideDown('fast', function() {
				container.find('.pagination-select-current').attr('aria-expanded', 'true');
			    container.addClass('pagination-select-open');
			});
		}
	});

});