/**
 * @file js/controllers/form/ClientFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ClientFormHandler
 * @ingroup js_controllers_form
 *
 * @brief Form handler that serializes the form on submission and
 *  triggers a "formSubmitted" event with the form data. This enables
 *  other widgets to use forms to request data from users although the
 *  data is not meant to be sent to the server through the form.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.FormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options options to be passed
	 *  into the validator plug-in.
	 */
	$.pkp.controllers.form.ClientFormHandler = function($form, options) {
		options.submitHandler = this.submitForm;
		this.parent($form, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.ClientFormHandler,
			$.pkp.controllers.form.FormHandler);


	//
	// Public methods
	//
	/**
	 * Internal callback called after form validation to handle form
	 * submission.
	 *
	 * @param {Object} validator The validator plug-in.
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 */
	$.pkp.controllers.form.ClientFormHandler.prototype.submitForm =
			function(validator, formElement) {
		var $form, formData;

		// This form implementation will trigger an event
		// with the form data.
		$form = this.getHtmlElement();

		// Retrieve form data.
		formData = $form.serializeArray();

		// Inform the server that the form has been submitted.
		formData.push({name: 'clientSubmit', value: true});

		// Trigger a "form submitted" event with the form
		// data as argument.
		this.trigger('formSubmitted', [$.param(formData)]);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
