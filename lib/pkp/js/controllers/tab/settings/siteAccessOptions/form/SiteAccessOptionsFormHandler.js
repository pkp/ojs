/**
 * @defgroup js_controllers_tab_settings_siteAccessOptions_form
 */
/**
 * @file js/controllers/tab/settings/siteAccessOptions/form/SiteAccessOptionsFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteAccessOptionsFormHandler
 * @ingroup js_controllers_tab_settings_siteAccessOptions_form
 *
 * @brief Handle the site access options form.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.settings.siteAccessOptions =
			$.pkp.controllers.tab.settings.siteAccessOptions || {form: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.tab.settings.siteAccessOptions.form.
			SiteAccessOptionsFormHandler = function($form, options) {

		this.parent($form, options);

		// Attach form elements events.
		$('#disableUserReg-0', $form).click(
				this.callbackWrapper(this.changeRegOptsState));
		$('#disableUserReg-1', $form).click(
				this.callbackWrapper(this.changeRegOptsState));

	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.settings.siteAccessOptions.form.
			SiteAccessOptionsFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Public methods.
	//
	/**
	 * Event handler that is called when the suggest username button is clicked.
	 * @param {HTMLElement} element The checkbox input element.
	 */
	$.pkp.controllers.tab.settings.siteAccessOptions.form.
			SiteAccessOptionsFormHandler.prototype.
			changeRegOptsState = function(element) {
		if (element.id === 'disableUserReg-0') {
			this.setRegOptsDisabled_(false);
		} else {
			this.setRegOptsDisabled_(true);
			this.setRegOptsChecked_(false);
		}
	};


	//
	// Private helper methods
	//
	/**
	 * Change the disabled state of the user registration options.
	 * @private
	 * @param {boolean} state The state of the disabled attribute.
	 */
	$.pkp.controllers.tab.settings.siteAccessOptions.form.
			SiteAccessOptionsFormHandler.prototype.
			setRegOptsDisabled_ = function(state) {
		if (state) {
			$('[id^="allow"]').attr('disabled', 'disabled');
		} else {
			$('[id^="allow"]').removeAttr('disabled');
		}
	};


	/**
	 * Change the checked state of the user registration options.
	 * @private
	 * @param {boolean} state The state of the checked attribute.
	 */
	$.pkp.controllers.tab.settings.siteAccessOptions.form.
			SiteAccessOptionsFormHandler.prototype.
			setRegOptsChecked_ = function(state) {
		if (state) {
			$('[id^="allow"]').attr('checked', 'checked');
		} else {
			$('[id^="allow"]').removeAttr('checked');
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
