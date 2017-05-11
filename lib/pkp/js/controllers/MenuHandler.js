/**
 * @file js/controllers/MenuHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MenuHandler
 * @ingroup js_controllers
 *
 * @brief A basic handler for a hierarchical list of navigation items.
 *
 * Attach this handler to a <ul> with nested <li> and <ul> elements.
 * <li> elements with submenu should have aria properties:
 *   <li aria-haspopup="true" aria-expanded="false">
 *
 * <li> elements wiith a submenu that opens below the parent item should add a
 * submenuOpensBelow class to support scrolling in long lists when necessary.
 *   <li class="submenuOpensBelow"
 *       aria-haspopup="true" aria-expanded="false"></li>
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $menu The outer <ul> element
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.MenuHandler = function($menu, options) {

		this.parent($menu, options);

		// Fix dropdown menus that may go off-screen and recalculate whenever
		// the browser window is resized
		// 1ms delay allows dom insertion to complete
		var self = this;
		setTimeout(function() {
			self.callbackWrapper( /** @type {Function} */ (
					self.setDropdownAlignment()));
		}, 1);
		$(window).resize(this.callbackWrapper(this.onResize));

		// Show/hide dropdown menus using WCAG-compliant aria attributes
		this.getHtmlElement().on('focus mouseenter', '[aria-haspopup="true"]',
				function(e) {
					$(e.currentTarget).attr('aria-expanded', 'true');
				});
		this.getHtmlElement().on('blur mouseleave', '[aria-haspopup="true"]',
				function(e) {
					$(e.currentTarget).attr('aria-expanded', 'false');
				});

		// Prevent first touch on top-level menu items from following the link
		this.getHtmlElement().find('[aria-haspopup="true"] > a').on(
				'touchstart', function(e) {
					if ($(this).parent().attr('aria-expanded') != false) {
						$(this).focus();
						e.preventDefault();
					}
				});
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.MenuHandler, $.pkp.classes.Handler);


	//
	// Public methods
	//
	/**
	 * Check if submenus are straying off-screen and adjust as needed
	 */
	$.pkp.controllers.MenuHandler.prototype.setDropdownAlignment = function() {
		var $this = $(this),
				width = Math.max(
						document.documentElement.clientWidth, window.innerWidth || 0),
				height = Math.max(
						document.documentElement.clientHeight, window.innerHeight || 0);

		this.getHtmlElement().find('[aria-haspopup="true"]').each(function() {
			var $parent = $(this),
					$submenus = $parent.children('ul'),
					right, pos_top, min_top, pos_btm, offset_top, new_top;

			// Width
			right = $parent.offset().left + $submenus.outerWidth();
			if (right > width) {
				$parent.addClass('align_right');
			} else {
				$parent.removeClass('align_right');
			}

			// Height
			$submenus.attr('style', ''); // reset
			pos_top = $parent.offset().top;
			min_top = 0;
			if ($parent.hasClass('submenuOpensBelow')) {
				min_top = pos_top + $parent.outerHeight();
			}
			pos_btm = pos_top + $submenus.outerHeight();
			if (pos_btm > height) {
				offset_top = pos_btm - height;
				new_top = pos_top - offset_top;
				if (new_top < min_top) {
					if (min_top > 0) {
						offset_top = min_top;
					} else {
						offset_top = -Math.abs(offset_top) - new_top;
					}
					$submenus.css('overflow-y', 'scroll');
					$submenus.css('bottom',
							-Math.abs(height - pos_top - $parent.outerHeight()) + 'px');
				}
				$submenus.css('top', offset_top + 'px');
			}
		});
	};


	/**
	 * Throttle the dropdown alignment check during resize events. During
	 * browser resizing this will fire off every single frame, causing lag
	 * during the resize. So this just throttles the actual alignment check
	 * function by only firing when resizing has stopped.
	 */
	$.pkp.controllers.MenuHandler.prototype.onResize = function() {
		clearTimeout(this.resize_check);
		this.resize_check = setTimeout(
				this.callbackWrapper(this.setDropdownAlignment), 1000);
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
