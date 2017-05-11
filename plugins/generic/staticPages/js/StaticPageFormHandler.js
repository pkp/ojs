/**
 * @file js/StaticPageFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPageFormHandler
 *
 * @brief Static pages form handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.form.staticPages =
			$.pkp.controllers.form.staticPages || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $formElement A wrapped HTML element that
	 *  represents the approved proof form interface element.
	 * @param {Object} options Tabbed modal options.
	 */
	$.pkp.controllers.form.staticPages.StaticPageFormHandler =
			function($formElement, options) {
		this.parent($formElement, options);

		// Save the preview URL for later
		this.previewUrl_ = options.previewUrl;

		// bind a handler to make sure we update the required state
		// of the comments field.
		$('#previewButton', $formElement).click(this.callbackWrapper(
				this.showPreview_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.staticPages.StaticPageFormHandler,
			$.pkp.controllers.form.AjaxFormHandler
	);


	//
	// Private properties
	//
	/**
	 * The preview url.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.form.staticPages.StaticPageFormHandler.prototype.
			previewUrl_ = null;


	//
	// Private methods.
	//
	/**
	 * Callback triggered on clicking the "preview" button to open a preview window.
	 *
	 * @param {HTMLElement} submitButton The submit button.
	 * @param {Event} event The event that triggered the
	 *  submit button.
	 * @return {boolean} true.
	 * @private
	 */
	$.pkp.controllers.form.staticPages.StaticPageFormHandler.
			prototype.showPreview_ = function(submitButton, event) {

		var $formElement = this.getHtmlElement();
		$.post(this.previewUrl_,
				$formElement.serialize(),
				function(data) {
					var win = window.open('about:blank');
					with(win.document) {
						open();
						write(data);
						close();
					}
				});
		return true;
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
