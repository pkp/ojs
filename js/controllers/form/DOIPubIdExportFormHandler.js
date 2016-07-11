/**
 * @file js/controllers/form/DOIPubIdExportFormHandler.js
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdExportFormHandler.js
 * @ingroup js_controllers_form
 *
 * @brief Handle the DOI export form actions.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.FormHandler
	 *
	 * @param {jQueryObject} $formElement the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.form.DOIPubIdExportFormHandler =
			function($formElement, options) {
		this.parent($formElement, options);

		$('#exportSubmissionXmlForm a[id*="-button-"]').click(this.callbackWrapper(
				this.submitAction_));
		$('#exportIssueXmlForm a[id*="-button-"]').click(this.callbackWrapper(
				this.submitAction_));

	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.DOIPubIdExportFormHandler,
			$.pkp.controllers.form.FormHandler);


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
	$.pkp.controllers.form.DOIPubIdExportFormHandler.
			prototype.submitAction_ = function(submitButton, event) {

		var $formElement = this.getHtmlElement(),
				idPattern = new RegExp('(.*)-button-'),
				button = event.target.id,
				idPatternResult = idPattern.exec(button),
				action = idPatternResult[1],
				actionHiddenInput = $('<input>')
					.attr('type', 'hidden')
					.attr('name', action).val('1');
		$formElement.append('<input type="hidden" name="' + action + '" value="1">');
		$formElement.submit();
		return true;
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
