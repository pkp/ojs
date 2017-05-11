/**
 * @file js/controllers/ExtrasOnDemandHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExtrasOnDemandHandler
 * @ingroup js_controllers
 *
 * @brief A basic handler for extras on demand UI pattern.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $widgetWrapper An HTML element that contains the
	 *   widget.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.ExtrasOnDemandHandler = function($widgetWrapper, options) {
		this.parent($widgetWrapper, options);

		$('.toggleExtras', $widgetWrapper).click(
				this.callbackWrapper(this.toggleExtras));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.ExtrasOnDemandHandler, $.pkp.classes.Handler);


	//
	// Public methods
	//
	/**
	 * Event handler that is called when toggle extras div is clicked.
	 *
	 * @param {HTMLElement} toggleExtras The div that is clicked to toggle extras.
	 * @param {Event} event The triggering event.
	 */
	$.pkp.controllers.ExtrasOnDemandHandler.prototype.toggleExtras =
			function(toggleExtras, event) {
		var $widgetWrapper = this.getHtmlElement(),
				$scrollable;

		event.preventDefault();

		$widgetWrapper.toggleClass('active');

		if ($widgetWrapper.hasClass('active')) {
			// Identify if there is a scrollable parent.
			$scrollable = $widgetWrapper.closest('.scrollable');
			if ($scrollable.size() > 0) {
				// Scroll the parent so that all extra content in
				// extras container is visible.
				this.scrollToMakeVisible_($widgetWrapper, $scrollable);
			}
		}
	};


	//
	// Private methods
	//
	/**
	 * Scroll a scrollable element to make the
	 * given content element visible. The content element
	 * must be a descendant of a scrollable
	 * element (needs to have class "scrollable").
	 *
	 * NB: This method depends on the position() method
	 * to refer to the same parent element for both the
	 * content element and the scrollable element.
	 *
	 * @private
	 *
	 * @param {jQueryObject} $widgetWrapper The element to be made visible.
	 * @param {Array|jQueryObject} $scrollable The parent scrollable element.
	 */
	$.pkp.controllers.ExtrasOnDemandHandler.prototype.scrollToMakeVisible_ =
			function($widgetWrapper, $scrollable) {
		var extrasWidgetTop, scrollingWidgetTop, currentScrollingTop,
				hiddenPixels, newScrollingTop;

		extrasWidgetTop = $widgetWrapper.position().top;
		scrollingWidgetTop = $scrollable.position().top;
		currentScrollingTop = parseInt($scrollable.scrollTop(), 10);

		// Do we have to scroll down or scroll up?
		if (extrasWidgetTop > scrollingWidgetTop) {
			// Consider scrolling down...

			// Calculate the number of hidden pixels of the child
			// element within the scrollable element.
			hiddenPixels = Math.ceil(extrasWidgetTop +
					$widgetWrapper.height() - $scrollable.height());

			// Scroll down if parts or all of this widget are hidden.
			if (hiddenPixels > 0) {
				$scrollable.scrollTop(currentScrollingTop + hiddenPixels);
			}
		} else {
			// Scroll up...

			// Calculate the new scrolling top.
			newScrollingTop = Math.max(Math.floor(
					currentScrollingTop + extrasWidgetTop - scrollingWidgetTop), 0);

			// Set the new scrolling top.
			$scrollable.scrollTop(newScrollingTop);
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
