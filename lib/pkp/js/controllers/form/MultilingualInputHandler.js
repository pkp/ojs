/**
 * @file js/controllers/form/MultilingualInputHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MultilingualInputHandler
 * @ingroup js_controllers_form
 *
 * @brief Handler for toggling the pop-over on multi lingual inputs (text
 * inputs and text areas, mostly).
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $popover the wrapped HTML element.
	 * @param {Object} options options to be passed
	 *  into the validator plug-in.
	 */
	$.pkp.controllers.form.MultilingualInputHandler = function($popover, options) {
		this.parent($popover, options);

		// Bind to the focus of the primary language (the first input)
		// open the pop-over
		var $popoverNode = null;

		if ($popover.hasClass('pkpTagit')) {
			$popoverNode = $popover.find(':input').filter(':visible');
		} else {
			$popoverNode = $popover.find(':input').first();
		}

		$popoverNode
				.focus(this.callbackWrapper(this.focusHandler_));
		// Bind to the blur of any of the inputs to check if we should close.
		$popover.find(':input').
				blur(this.callbackWrapper(this.blurHandler_));

		this.publishEvent('tinyMCEInitialized');

		this.tinyMCEInitHandler_();

		setTimeout(this.callbackWrapper(this.isIncomplete_), 500);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.MultilingualInputHandler,
			$.pkp.classes.Handler);


	//
	// Private helper methods.
	//
	/**
	 * Focus event handler. This is attached to all primary inputs.
	 *
	 * @param {HTMLElement} multilingualInput The primary multilingual
	 * element.
	 * @param {Event} event The focus event.
	 * @private
	 */
	$.pkp.controllers.form.MultilingualInputHandler.prototype.focusHandler_ =
			function(multilingualInput, event) {

		this.showPopover_();
	};


	/**
	 * Blur event handler. This is attached to all inputs inside this
	 * popover element.
	 *
	 * @param {HTMLElement} multilingualInput The element in the
	 * multilingual set to hide.
	 * @param {Event} event The event that triggered the action.
	 * @return {boolean} Return true to continue the event handling.
	 * @private
	 */
	$.pkp.controllers.form.MultilingualInputHandler.prototype.blurHandler_ =
			function(multilingualInput, event) {

		// Use a timeout to give the other element a chance to acquire the focus.
		setTimeout(this.callbackWrapper(function() {
			if (!this.hasElementInFocus_()) {
				this.hidePopover_();
			}
		}), 0);

		return true;
	};


	/**
	 * Hide this popover.
	 * @private
	 */
	$.pkp.controllers.form.MultilingualInputHandler.prototype.hidePopover_ =
			function() {
		var $popover = this.getHtmlElement();
		$popover.removeClass('localization_popover_container_focus');
		$popover.find('.localization_popover').hide();
		this.isIncomplete_();
	};


	/**
	 * Show this popover.
	 * @private
	 */
	$.pkp.controllers.form.MultilingualInputHandler.prototype.showPopover_ =
			function() {
		var $popover = this.getHtmlElement();
		$popover.addClass('localization_popover_container_focus');

		// Hack alert: setting width in JS because they do not line up otherwise.
		$popover.find('.localization_popover').width(
				/** @type {number} */ ($popover.width()));

		// Show the pop over.
		$popover.find('.localization_popover').show();
	};


	/**
	 * Test if any of the elements inside this popover has focus.
	 * @return {boolean} True iff an element is in focus.
	 * @private
	 */
	$.pkp.controllers.form.MultilingualInputHandler.prototype.hasElementInFocus_ =
			function() {

		var $popover = this.getHtmlElement();

		// Do the test.
		if ($popover.has(document.activeElement).length) {
			return true;
		} else {
			return false;
		}
	};


	/**
	 * Check if the field is missing a localization
	 *
	 * Fields are not considered to be missing a localization unless at least
	 * one of the localizations has an entry.
	 *
	 * @private
	 */
	$.pkp.controllers.form.MultilingualInputHandler.prototype.isIncomplete_ =
			function() {

		var $popover = this.getHtmlElement(),
				$inputs = [],
				valuesCount = 0;

		// Track current values in the tinyMCE control
		if (this.getHtmlElement().find('.richContent').length) {
			$popover.find('textarea').each(function() {
				var id = $(this).attr('id'),
						tinymce;

				$inputs.push($(this));
				tinymce = tinyMCE.EditorManager.get(/** @type {string} */(
						$(this).attr('id')));
				if (tinymce.getContent()) {
					valuesCount++;
				}
			});

		} else {
			$inputs = $popover.find(':input');
			$inputs.each(function() {
				if ($(this).val()) {
					valuesCount++;
				}
			});
		}

		if (valuesCount > 0 && valuesCount < $inputs.length) {
			$popover.removeClass('localizationComplete')
					.addClass('localizationIncomplete');
		} else if (valuesCount === $inputs.length) {
			$popover.removeClass('localizationIncomplete')
					.addClass('localizationComplete');
		} else {
			$popover.removeClass('localizationIncomplete localizationComplete');
		}
	};


	/**
	 * Attach focus and blur event handlers to the tinyMCE window element, which
	 * show and hide the localization popover
	 * @private
	 */
	$.pkp.controllers.form.MultilingualInputHandler.prototype.tinyMCEInitHandler_ =
			function() {

		if (!this.getHtmlElement().find('.richContent').length) {
			return;
		}

		var htmlElement = this.getHtmlElement(),
				tinyMCEObject = tinyMCE.EditorManager.get(/** @type {string} */(
				htmlElement.find('textarea').first().attr('id')));

		tinyMCEObject.on('focus', this.callbackWrapper(function() {
			// We need also to close the multilingual popover when user clicks
			// outside the popover element. The blur event is not enough because
			// sometimes (with text selected in editor) Chrome will consider the
			// tinyMCE editor as still active and that will avoid the popover to
			// close (see the first check of the blur handler, just above).
			//
			// Firefox will also not completely focus on tinyMCE editors after
			// coming back from fullscreen mode (the callback to focus the
			// editor when set content will only trigger the focus handler that
			// we attach here, but will not move the cursor inside the tinyMCE
			// editor). Then, if user clicks outside the popover, it will not
			// close because no blur event will be triggered.
			this.trigger('callWhenClickOutside', {
				container: this.getHtmlElement(),
				callback: this.callbackWrapper(this.hidePopover_),
				skipWhenVisibleModals: false
			});

			this.showPopover_();
		}));

		tinyMCEObject.on('blur', this.callbackWrapper(function() {
			// Check if the active document element is still the tinyMCE
			// editor. If true, return false. This will avoid closing the
			// popover if user is just inserting an image or editing the
			// html source, for example (both actions open a new window).
			if ($(tinyMCEObject.getContainer()).find('iframe').attr('id') ==
					$(document.activeElement).attr('id')) {
				return false;
			}

			// Use a timeout to give the other element a chance to acquire the
			// focus.
			setTimeout(this.callbackWrapper(function() {
				if (!this.hasElementInFocus_()) {
					this.hidePopover_();
				}
			}), 0);
		}));
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
