/**
 * @file js/NavigationMenusFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.navigationMenus
 * @class NavigationMenusFormHandler
 *
 * @brief NavigationMenus form handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.form.navigationMenus =
			$.pkp.controllers.form.navigationMenus || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $formElement A wrapped HTML element that
	 *  represents the approved proof form interface element.
	 * @param {Object} options Tabbed modal options.
	 */
	$.pkp.controllers.form.navigationMenus.NavigationMenusFormHandler =
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
			$.pkp.controllers.form.navigationMenus.NavigationMenusFormHandler,
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
	$.pkp.controllers.form.navigationMenus.NavigationMenusFormHandler.prototype.
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
	$.pkp.controllers.form.navigationMenus.NavigationMenusFormHandler.
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
