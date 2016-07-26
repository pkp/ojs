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

		this.submissionsActions_ = [];
		this.issuesActions_ = [];
		this.representationsActions_ = [];
		this.actionRegExp_ = [];

		var i;
		if (options.submissionsActions) {
			for (i = 0; i < options.submissionsActions.length; i++) {
				/** @type {{_id:string}}*/
				this.optionsSubmissionsAction = options.submissionsActions[i];
				this.submissionsActions_.push(this.optionsSubmissionsAction._id);
				$('#exportSubmissionXmlForm a[id^="' + this.optionsSubmissionsAction._id +
					'-button-"]').click(this.callbackWrapper(this.submitAction_));
			}
			this.actionRegExp_.exportSubmissionXmlForm =
					'(' + this.submissionsActions_.join('|') + ')';
		}

		if (options.issuesActions) {
			for (i = 0; i < options.issuesActions.length; i++) {
				/** @type {{_id:string}}*/
				this.optionsIssuesAction = options.issuesActions[i];
				this.issuesActions_.push(this.optionsIssuesAction._id);
				$('#exportIssueXmlForm a[id^="' + this.optionsIssuesAction._id +
					'-button-"]').click(this.callbackWrapper(this.submitAction_));
			}
			this.actionRegExp_.exportIssueXmlForm =
					'(' + this.issuesActions_.join('|') + ')';
		}

		if (options.representationsActions) {
			for (i = 0; i < options.representationsActions.length; i++) {
				/** @type {{_id:string}}*/
				this.optionsRepresentationsActionsAction =
						options.representationsActions[i];
				this.representationsActions_.
						push(this.optionsRepresentationsActionsAction._id);
				$('#exportRepresentationXmlForm a[id^="' +
					this.optionsRepresentationsActionsAction._id +
					'-button-"]').click(this.callbackWrapper(this.submitAction_));
			}
			this.actionRegExp_.exportRepresentationXmlForm =
					'(' + this.representationsActions_.join('|') + ')';
		}

	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.DOIPubIdExportFormHandler,
			$.pkp.controllers.form.FormHandler);


	//
	// Private methods.
	//
	/**
	 * Callback triggered on clicking the link action buttons.
	 *
	 * @param {HTMLElement} submitButton The submit button.
	 * @param {Event} event The event that triggered the
	 *  submit button.
	 * @return {boolean} true.
	 * @private
	 */
	$.pkp.controllers.form.DOIPubIdExportFormHandler.
			prototype.submitAction_ = function(submitButton, event) {

		var button = event.target,
				$formElement = $(button).closest('form'),
				idPattern = new RegExp(this.actionRegExp_[$formElement.attr('id')] +
						'-button-'),
				idPatternResult = idPattern.exec(button.id),
				action = idPatternResult[1];

		$formElement.append('<input type="hidden" name="' + action + '" value="1">');
		$formElement.submit();
		return true;
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
