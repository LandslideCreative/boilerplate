import $ from 'jquery';
import 'what-input';

window.$ = window.jQuery = $;

import './lib/foundation-explicit-pieces';

require('./lib/jquery.fitVids.js');
require('./lib/jquery.matchHeight.js');
// require('./lib/jquery.slick.js');

$(document).ready(function() {

	$(document).foundation();

	// Initialize FitVids
	$('body').fitVids({
		customSelector: 'iframe[src*="facebook"], iframe[src^="https://livestream.com"]'
	});

	// Dropdown Navigation
	$('.dropdown-nav .menu-item-has-children>a').attr('aria-expanded', "false");
	$('.dropdown-nav .menu-item-has-children>a').click(function(event) {
		event.stopPropagation();
		event.preventDefault();
		if ($(this).parent().hasClass('nav-open')) {
			$('.nav-open .sub-menu').slideUp('fast', function() {
				$('.nav-open>a').attr('aria-expanded', "false");
			    $('.nav-open').removeClass('nav-open');
			});
		} else {
			$('.nav-open .sub-menu').hide();
			$('.nav-open>a').attr('aria-expanded', 'false');
			$('.nav-open').removeClass('nav-open');

			$(this).parent().addClass('nav-open');
			$(this).parent().find('.sub-menu').slideDown('fast');
			$(this).attr('aria-expanded', 'true');
		}
	});

	$('*:not(.dropdown-nav .menu-item-has-children>a)').on('click', function() {
		$('.nav-open .sub-menu').hide();
		$('.nav-open>a').attr('aria-expanded', 'false');
		$('.nav-open').removeClass('nav-open');
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