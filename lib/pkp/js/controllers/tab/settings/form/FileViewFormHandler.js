/**
 * @defgroup js_controllers_tab_settings_form
 */
// Create the namespace.
/**
 * @file js/controllers/tab/settings/form/FileViewFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileViewFormHandler
 * @ingroup js_controllers_tab_settings_form
 *
 * @brief This handles a form that needs to present information about
 * uploaded files, and refresh itself when a file is saved, but refreshing
 * only the uploaded file part. This is necessary when we don't want to
 * fetch the entire form and unnecessarily fetch other widgets inside the
 * form too (listbuilders or grids).
 *
 * To start the refresh, this class binds the 'dataChanged' event to know
 * when the file is saved and the setting name of the file. So, this handler
 * assumes that your save file action will trigger a 'dataChanged' event,
 * and that this event will pass a parameter with the setting name of the file
 * that have been uploaded.
 *
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab =
			$.pkp.controllers.tab ||
			{ settings: { form: { } } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.tab.settings.form.FileViewFormHandler =
			function($form, options) {

		this.parent($form, options);

		this.fetchFileUrl_ = options.fetchFileUrl;

		this.bind('dataChanged', this.refreshForm_);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.settings.form.FileViewFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Private properties
	//
	/**
	 * The url to fetch a file.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.tab.settings.form.FileViewFormHandler.prototype
			.fetchFileUrl_ = null;


	//
	// Private helper methods
	//
	/**
	 * Refresh the form, fetching a file.
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @param {string} settingName The setting name of the uploaded file.
	 * @private
	 */
	$.pkp.controllers.tab.settings.form.FileViewFormHandler.prototype.refreshForm_ =
			function(sourceElement, event, settingName) {

		$.get(this.fetchFileUrl_, {settingName: settingName},
				this.callbackWrapper(this.refreshResponseHandler_), 'json');

	};


	/**
	 * Show the file rendered data in the form.
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @private
	 */
	$.pkp.controllers.tab.settings.form.FileViewFormHandler.prototype.
			refreshResponseHandler_ = function(ajaxContext, jsonData) {

		var $fileElement, processedJsonData =
				/** @type {{noData: string, elementId: string, content: string}} */
				this.handleJson(jsonData);

		if (processedJsonData.noData) {

			// The file setting data was deleted, we can remove
			// its markup from the form.
			$fileElement = this.getFileHtmlElement_(processedJsonData.noData);
			$fileElement.empty();
		} else {

			// The server returned mark-up to replace
			// or insert the file data in form.
			$fileElement = this.getFileHtmlElement_(processedJsonData.elementId);
			$fileElement.html(processedJsonData.content);
		}
	};


	/**
	 * Get the file HTML element that contains all the file data markup.
	 * We assume that the id of the file HTML element it is equal
	 * to the file setting name.
	 *
	 * @param {string} settingName The file setting name.
	 * @return {jQueryObject} JQuery element.
	 * @private
	 */
	$.pkp.controllers.tab.settings.form.FileViewFormHandler.prototype.
			getFileHtmlElement_ = function(settingName) {

		var $form = this.getHtmlElement(),
				$fileHtmlElement = $('#' + settingName, $form);

		return $fileHtmlElement;
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
