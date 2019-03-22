/**
 * Symphony backend initialisation
 *
 * @package assets
 */

(function($, Symphony) {
	'use strict';

	// Set environment
	var environment = (function () {
		var env = document.getElementById('environment');
		return env ? JSON.parse(env.textContent) : {};
	})();
	Symphony.Context.add(null, environment);

	// Get translations
	Symphony.Language.add({
		'Are you sure you want to proceed?': false,
		'Reordering was unsuccessful.': false,
		'Change Password': false,
		'Remove File': false,
		'Untitled Field': false,
		'The field “{$title}” ({$type}) has been removed.': false,
		'Undo?': false,
		'untitled': false,
		'Expand all': false,
		'Collapse all': false,
		'drag to reorder': false,
		'Please reset your password': false,
		'required': false,
		'Click to select': false,
		'Type to search': false,
		'Clear': false,
		'Search for {$item}': false,
		'Add filter': false,
		'filtered': false,
		'None': false,
		'Clear filters': false,
		'Apply filters': false,
		'The Symphony calendar widget has been disabled because your system date format is currently not supported. Try one of the following instead or disable the calendar in the field settings:': false,
		'no leading zero': false
	});

	// Initialise backend
	$(function() {

		// Cache main elements
		Symphony.Elements.window = $(window);
		Symphony.Elements.html = $('html').addClass('js-active');
		Symphony.Elements.body = $('body');
		Symphony.Elements.wrapper = $('#wrapper');
		Symphony.Elements.header = $('#header');
		Symphony.Elements.headerMobileToggler = $('#btn-toggle-header-mobile');
		Symphony.Elements.nav = $('#nav');
		Symphony.Elements.session = $('#session');
		Symphony.Elements.context = $('#context');
		Symphony.Elements.breadcrumbs = $('#breadcrumbs');
		Symphony.Elements.contents = $('#contents');

		// Create context id
		var path = Symphony.Context.get('path');
		var route = Symphony.Context.get('route');
		if (path && route) {
			var contextId = (path + route).split('/').filter(function(part) {
				return (part != 'edit' && part != 'new' && part != 'created' && part != 'saved' && part !== '');
			}).join('.');
			Symphony.Context.add('context-id', contextId);
		}

		// Render view
		Symphony.View.render();

		// Update state to canonical url
		if (window.history.replaceState) {
			var replaceState = function () {
				$('head > link[rel="canonical"][href]').eq(0).each(function () {
					var href = $(this).attr('href');
					if (href) {
						window.history.replaceState(document.title, null, href);
					}
				});
			};
			// Let extensions read the window.location when load is completed
			if (document.readyState === 'complete') {
				replaceState();
			} else {
				// Document not loaded, delay change on load
				$(window).on('load', replaceState);
			}
		}

		// Header Nav - Toggle Subnav on parent click
		$('li', Symphony.Elements.nav).has('ul').on('click', '> span', function(){
			var t = $(this);

			// Open
			if(!t.parent().hasClass('opened')) {
				$('.opened ul', Symphony.Elements.nav).slideUp(250);
				$('.opened', Symphony.Elements.nav).removeClass('opened');

				t.parent().addClass('opened');
				t.siblings('ul').slideDown(250);
			}
			// Close
			else {
				t.parent().removeClass('opened');
				t.siblings('ul').slideUp(250);
			}
		});

		// Header Nav - Mobile Toggler
		Symphony.Elements.headerMobileToggler.on('click', function(){
			Symphony.Elements.header.toggleClass('opened');

			return false;
		});
	});

	$(window).load(function(){

		// Cache main elements
		Symphony.Elements.contextActions = $('#context .actions > li');

		// Context Actions - LI Max-Width
		Symphony.Elements.contextActions.css({'max-width': (100 / Symphony.Elements.contextActions.length) + '%'});

	});

})(window.jQuery, window.Symphony);
