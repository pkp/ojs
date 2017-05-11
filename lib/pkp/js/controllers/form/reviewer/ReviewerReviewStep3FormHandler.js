/**
 * @defgroup js_controllers_form_reviewer
 */
/**
 * @file js/controllers/form/reviewer/ReviewerReviewStep3FormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewStep3FormHandler
 * @ingroup js_controllers_form_reviewer
 *
 * @brief Reviewer step 3 form handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.form.reviewer =
			$.pkp.controllers.form.reviewer || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $formElement A wrapped HTML element that
	 *  represents the approved proof form interface element.
	 * @param {Object} options Tabbed modal options.
	 */
	$.pkp.controllers.form.reviewer.ReviewerReviewStep3FormHandler =
			function($formElement, options) {
		this.parent($formElement, options);

		// bind a handler to make sure we update the required state
		// of the comments field.
		$formElement.find('[id^=\'submitFormButton-\']').click(this.callbackWrapper(
				this.updateCommentsRequired_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.reviewer.ReviewerReviewStep3FormHandler,
			$.pkp.controllers.form.AjaxFormHandler
	);


	//
	// Private methods.
	//
	/**
	 * Internal callback called before form validation to ensure the
	 * proper "required" state of the comments field, depending on grid
	 * contents.
	 *
	 * @param {HTMLElement} submitButton The submit button.
	 * @param {Event} event The event that triggered the
	 *  submit button.
	 * @return {boolean} true.
	 * @private
	 */
	$.pkp.controllers.form.reviewer.ReviewerReviewStep3FormHandler.
			prototype.updateCommentsRequired_ = function(submitButton, event) {

		var $formElement = this.getHtmlElement(),
				$commentsElement = $formElement.find('[id^="comments"]');

		if ($('#reviewAttachmentsGridContainer').
				find('tbody.empty:visible').length == 1) {
			// There's nothing in the files grid; make sure the
			// comments field is required.
			$commentsElement.attr('required', '1');
		} else {
			// There's something in the files grid; the comments
			// field is optional.
			$commentsElement.removeAttr('required');
		}
		return true;
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
