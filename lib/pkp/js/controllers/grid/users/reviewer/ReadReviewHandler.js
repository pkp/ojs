/**
 * @defgroup js_controllers_grid_users_reviewer
 */
/**
 * @file js/controllers/grid/users/reviewer/ReadReviewHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReadReviewHandler
 * @ingroup js_controllers_grid_users_reviewer
 *
 * @brief Handle the advanced reviewer search tab in the add reviewer modal.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.grid.users.reviewer =
			$.pkp.controllers.grid.users.reviewer ||
			{ };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped page element.
	 * @param {Object} options handler options.
	 */
	$.pkp.controllers.grid.users.reviewer.ReadReviewHandler =
			function($form, options) {
		this.parent($form, options);

		this.reviewCompleted_ = options.reviewCompleted;
		// bind a handler to make sure that a review file has been uploaded.
		$form.find('[id^=\'submitFormButton-\']').click(this.callbackWrapper(
				this.reviewFilesRequired_));

	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.grid.users.reviewer.ReadReviewHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Private methods.
	//
	/**
	 * Is the review completed.
	 * @private
	 * @type {boolean}
	 */
	$.pkp.controllers.grid.users.reviewer.ReadReviewHandler.
			prototype.reviewCompleted_ = false;


	/**
	 * Internal callback called on form submit to ensure there are
	 * some review files uploaded.
	 * @private
	 * @param {HTMLElement} submitButton The submit button.
	 * @param {Event} event The event that triggered the
	 *  submit button.
	 * @return {boolean} true.
	 */
	$.pkp.controllers.grid.users.reviewer.ReadReviewHandler.
			prototype.reviewFilesRequired_ = function(submitButton, event) {

		if (!this.reviewCompleted_ && $('#readReviewAttachmentsGridContainer').
				find('tbody.empty:visible').length == 1) {
			// There's nothing in the files grid; don't submit the form
			this.showWarning_();
			return false;
		} else {
			// There's something in the files grid;
			this.hideWarning_();
		}
		return true;
	};


	/**
	 * Hide the "no files" warning.
	 * @private
	 */
	$.pkp.controllers.grid.users.reviewer.ReadReviewHandler.
			prototype.hideWarning_ = function() {
		this.getHtmlElement().find('#noFilesWarning').hide(250);
	};


	/**
	 * Show the "no files" warning.
	 * @private
	 */
	$.pkp.controllers.grid.users.reviewer.ReadReviewHandler.
			prototype.showWarning_ = function() {
		this.getHtmlElement().find('#noFilesWarning').show(250);
	};
	

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
