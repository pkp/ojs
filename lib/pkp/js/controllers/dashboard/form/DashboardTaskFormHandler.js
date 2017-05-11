/**
 * @defgroup js_controllers_dashboard_form
 */
/**
 * @file js/controllers/dashboard/form/DashboardTaskFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DashboardTaskFormHandler
 * @ingroup js_controllers_dashboard_form
 *
 * @brief Handle the styling and actions on the 'start new submission' form
 *  on the Task tab in the dashboard.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.dashboard =
			$.pkp.controllers.dashboard || {form: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.FormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.dashboard.form.DashboardTaskFormHandler =
			function($form, options) {

		this.parent($form, options);
		this.singleContextSubmissionUrl_ = options.singleContextSubmissionUrl;

		$('#singleContext', $form).click(
				this.callbackWrapper(this.startSingleContextSubmission_));

		$('#multipleContext', $form).change(
				this.callbackWrapper(this.startMultipleContextSubmission_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.dashboard.form.DashboardTaskFormHandler,
			$.pkp.controllers.form.FormHandler);


	//
	// Private properties
	//
	/**
	 * The URL to be called to fetch a spotlight item via autocomplete.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.dashboard.form.DashboardTaskFormHandler.
			prototype.singleContextSubmissionUrl_ = null;


	//
	// Private Methods
	//
	/**
	 * Redirect to the wizard for single context submissions
	 * @private
	 */
	$.pkp.controllers.dashboard.form.DashboardTaskFormHandler.
			prototype.startSingleContextSubmission_ = function() {

		window.location.href = /** @type {string} */ this.singleContextSubmissionUrl_;
	};


	/**
	 * Redirect to the wizard for multiple context submissions
	 * @private
	 */
	$.pkp.controllers.dashboard.form.DashboardTaskFormHandler.
			prototype.startMultipleContextSubmission_ = function() {

		var $form = this.getHtmlElement(),
				url = $form.find('#multipleContext').val();

		if (url != 0) { // not the default
			window.location.href = /** @type {string} */ url;
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
