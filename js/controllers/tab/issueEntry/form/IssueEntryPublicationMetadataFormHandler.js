/**
 * @file js/controllers/tab/issueEntry/form/IssueEntryPublicationMetadataFormHandler.js
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryPublicationMetadataFormHandler.js
 * @ingroup js_controllers_tab_issueEntry_form
 *
 * @brief Handle article publication format forms on the issue entry modal.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.issueEntry.form =
			$.pkp.controllers.tab.issueEntry.form || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.tab.issueEntry.form.
			IssueEntryPublicationMetadataFormHandler = function($form, options) {

		this.parent($form, options);

		// initial setup.
		$('input[id^="datePublished"]', $form).datepicker(
				{ dateFormat: 'yy-mm-dd', minDate: '0', autoSize: true});

		// bind click handlers to the two payment waiver buttons.
		$('#paymentReceivedButton', $form).click(
				this.callbackWrapper(this.paymentReceivedHandler));

		$('#waivePaymentButton', $form).click(
				this.callbackWrapper(this.waivePaymentHandler));
	};

	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.issueEntry.form.
					IssueEntryPublicationMetadataFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	/**
	 * Callback that will mark an article as 'payment received'.
	 *
	 * @param {HTMLElement} submitButton The submit button.
	 * @param {Event} event The event that triggered the
	 *  submit button.
	 */
	$.pkp.controllers.tab.issueEntry.form.IssueEntryPublicationMetadataFormHandler.
			prototype.paymentReceivedHandler = function(submitButton, event) {

		var $element;
		$element = this.getHtmlElement();
		$element.find('input[name="waivePublicationFee"]').val('1');
		$element.find('input[name="markAsPaid"]').val('1');

		$element.submit();
	};


	/**
	 * Callback that will waive pament for an article.
	 *
	 * @param {HTMLElement} submitButton The submit button.
	 * @param {Event} event The event that triggered the
	 *  submit button.
	 */
	$.pkp.controllers.tab.issueEntry.form.IssueEntryPublicationMetadataFormHandler.
			prototype.waivePaymentHandler = function(submitButton, event) {

		var $element;
		$element = this.getHtmlElement();
		$element.find('input[name="waivePublicationFee"]').val('1');

		$element.submit();
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
