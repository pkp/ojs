/**
 * @defgroup js_controllers_tab_settings_announcements_form
 */
/**
 * @file js/controllers/tab/settings/announcements/form/AnnouncementSettingsFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementSettingsFormHandler
 * @ingroup js_controllers_tab_settings_announcements_form
 *
 * @brief Handle the press announcement settings form.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.settings.announcements =
			$.pkp.controllers.tab.settings.announcements || {form: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.tab.settings.announcements.form.
			AnnouncementSettingsFormHandler = function($form, options) {

		this.parent($form, options);

		// Attach form elements events.
		$('#enableAnnouncementsHomepage', $form).click(
				this.callbackWrapper(this.toggleEnableAnnouncementsHomepage));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.settings.announcements.form.
					AnnouncementSettingsFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Public methods.
	//
	/**
	 * Event handler that is called when the announcements are toggled.
	 * @param {HTMLElement} element The checkbox input element.
	 */
	$.pkp.controllers.tab.settings.announcements.form.
			AnnouncementSettingsFormHandler.prototype.
					toggleEnableAnnouncementsHomepage = function(element) {
		var $numAnnouncementsHomepage =
				$('#numAnnouncementsHomepage', this.getHtmlElement());
		if ($numAnnouncementsHomepage.attr('disabled')) {
			$numAnnouncementsHomepage.removeAttr('disabled');
		} else {
			$numAnnouncementsHomepage.attr('disabled', 'disabled');
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
