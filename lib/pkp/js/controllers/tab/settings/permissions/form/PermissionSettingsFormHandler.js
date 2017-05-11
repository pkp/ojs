/**
 * @defgroup js_controllers_tab_settings_permissions_form
 */
/**
 * @file js/controllers/tab/settings/permissions/form/PermissionSettingsFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PermissionSettingsFormHandler
 * @ingroup js_controllers_tab_settings_permissions_form
 *
 * @brief Handle the press permission settings form.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.settings.permissions =
			$.pkp.controllers.tab.settings.permissions || {form: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.tab.settings.permissions.form.
			PermissionSettingsFormHandler = function($form, options) {

		this.parent($form, options);

		// Handle events on license URL controls
		$('#licenseURLSelect', $form).change(
				this.callbackWrapper(this.licenseURLSelectChange));
		$('input[id^="licenseURL-"]', $form).keyup(
				this.callbackWrapper(this.licenseURLOtherChange));

		// Handle events on copyright holder type controls
		$('input[id^="copyrightHolderType-"]', $form).change(
				this.callbackWrapper(this.copyrightHolderRadioSelect));

		// Handle events on copyright holder type controls
		$('#resetPermissionsButton', $form).button().click(
				this.callbackWrapper(this.resetPermissionsHandler));


		this.resetPermissionsUrl = options.resetPermissionsUrl;
		this.resetPermissionsConfirmText = options.resetPermissionsConfirmText;
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.settings.permissions.form.
					PermissionSettingsFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	/**
	 * Reset permissions post URL
	 * @protected
	 * @type {string?}
	 */
	$.pkp.controllers.tab.settings.permissions.form.PermissionSettingsFormHandler.
			prototype.resetPermissionsUrl = null;


	/**
	 * Reset permissions confirmation message
	 * @protected
	 * @type {string?}
	 */
	$.pkp.controllers.tab.settings.permissions.form.PermissionSettingsFormHandler.
			prototype.resetPermissionsConfirmText = null;


	//
	// Public methods.
	//
	/**
	 * Event handler that is called when the license URL select is changed.
	 * @param {HTMLElement} element The input element.
	 */
	$.pkp.controllers.tab.settings.permissions.form.
			PermissionSettingsFormHandler.prototype.
					licenseURLSelectChange = function(element) {
		var $htmlElement = this.getHtmlElement(),
				$licenseURLSelect = $htmlElement.find('#licenseURLSelect'),
				$otherField = $htmlElement.find('input[id^="licenseURL-"]');
		$otherField.val(/** @type {string} */ ($licenseURLSelect.val()));
	};


	/**
	 * Event handler that is called when the license URL "other" field is changed.
	 * @param {HTMLElement} element The input element.
	 */
	$.pkp.controllers.tab.settings.permissions.form.
			PermissionSettingsFormHandler.prototype.
					licenseURLOtherChange = function(element) {
		var $licenseURLSelect = this.getHtmlElement().find('#licenseURLSelect');

		// Select the "other" option in the dropdown.
		$licenseURLSelect.val('');
	};


	/**
	 * Event handler that is called when a copyright holder radio is clicked.
	 * @param {HTMLElement} element The input element.
	 */
	$.pkp.controllers.tab.settings.permissions.form.
			PermissionSettingsFormHandler.prototype.
					copyrightHolderRadioSelect = function(element) {
		var $htmlElement = this.getHtmlElement(), $element = $(element),
				$copyrightHolderOther = $htmlElement.find(
				'input[id^="copyrightHolderOther-"]');

		if ($element.val() === 'other') {
			$copyrightHolderOther.removeAttr('disabled');
		} else {
			$copyrightHolderOther.attr('disabled', 'disabled');
		}
	};


	/**
	 * Event handler that is called when the "reset permissions" button is clicked.
	 * @param {HTMLElement} element The input element.
	 */
	$.pkp.controllers.tab.settings.permissions.form.
			PermissionSettingsFormHandler.prototype.
					resetPermissionsHandler = function(element) {
		if (confirm(this.resetPermissionsConfirmText)) {
			$.post(this.resetPermissionsUrl, {}, function() {
				// A notification was posted; display it.
				$('body').trigger('notifyUser');
			});
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
