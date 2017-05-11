/**
 * @file js/controllers/form/ToggleFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ToggleFormHandler
 * @ingroup js_controllers_form
 *
 * @brief Extension to ClientFormHandler that accepts a checkbox click as a
 *  submit action.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.ClientFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 */
	$.pkp.controllers.form.ToggleFormHandler =
			function($form) {
		this.parent($form, {trackFormChanges: false});
		$form.change(
				this.callbackWrapper(this.toggleHandler_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.ToggleFormHandler,
			$.pkp.controllers.form.ClientFormHandler);


	//
	// Private methods
	//
	/**
	 * Click handler for the checkbox.
	 * @private
	 * @return {boolean} Always returns true.
	 */
	$.pkp.controllers.form.ToggleFormHandler.
			prototype.toggleHandler_ = function() {
		this.getHtmlElement().submit();
		return true;
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
