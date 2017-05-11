/**
 * @defgroup js_controllers_grid_users_reviewer_form
 */
/**
 * @file js/controllers/grid/users/reviewer/form/EditReviewFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditReviewFormHandler
 * @ingroup js_controllers_grid_users_reviewer_form
 *
 * @brief Handle the limit reviewer files form. Also used as a base class
 *  for the add reviewer form handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.grid.users.reviewer =
			$.pkp.controllers.grid.users.reviewer ||
			{ form: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.UserFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.grid.users.reviewer.form.
			EditReviewFormHandler = function($form, options) {

		this.parent($form, options);

		// When the form changes, check to see if a warning is necessary
		// (if all reviewer files are unchecked)
		$form.change(this.callbackWrapper(this.handleFormChange));

		// When the reviewer files list loads, trigger the above check
		this.bind('urlInDivLoaded', this.handleFileListLoad_);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.grid.users.reviewer.form.
					EditReviewFormHandler,
			$.pkp.controllers.form.UserFormHandler);


	//
	// Protected methods.
	//
	/**
	 * Handle a form change event.
	 * @protected
	 */
	$.pkp.controllers.grid.users.reviewer.form.EditReviewFormHandler.
			prototype.handleFormChange = function() {
		if (this.getHtmlElement()
				.find('input[name="selectedFiles[]"]:checked').length) {
			this.hideWarning();
		} else {
			this.showWarning();
		}
	};


	/**
	 * Hide the "no files" warning.
	 * @protected
	 */
	$.pkp.controllers.grid.users.reviewer.form.EditReviewFormHandler.
			prototype.hideWarning = function() {
		this.getHtmlElement().find('#noFilesWarning').hide(250);
	};


	/**
	 * Show the "no files" warning.
	 * @protected
	 */
	$.pkp.controllers.grid.users.reviewer.form.EditReviewFormHandler.
			prototype.showWarning = function() {
		this.getHtmlElement().find('#noFilesWarning').show(250);
	};


	//
	// Private methods.
	//
	/**
	 * Handle the loading of the reviewer files list.
	 * @private
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @param {?string} data additional event data.
	 */
	$.pkp.controllers.grid.users.reviewer.form.EditReviewFormHandler.
			prototype.handleFileListLoad_ =
			function(sourceElement, event, data) {

		// Trigger a form change event to display the "no files
		// selected" warning, if necessary.
		this.getHtmlElement().change();
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
