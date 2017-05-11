/**
 * @defgroup js_controllers_tab_settings_paymentMethod
 */
/**
 * @file js/controllers/tab/settings/paymentMethod/PaymentMethodHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentMethodHandler
 * @ingroup js_controllers_tab_settings_paymentMethod
 *
 * @brief JS controller for the payment method form.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.settings.paymentMethod =
			$.pkp.controllers.tab.settings.paymentMethod ||
			{ };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $containerForm A wrapped HTML element that
	 *  represents the container form element.
	 * @param {Object} options Optional Options.
	 */
	$.pkp.controllers.tab.settings.paymentMethod.PaymentMethodHandler =
			function($containerForm, options) {
		this.parent($containerForm, options);

		// Save the URL template for the metadata form.
		this.paymentMethodFormUrlTemplate_ = options.paymentMethodFormUrlTemplate;

		// Bind for a change in the selected plugin
		this.bind('selectPaymentMethod', this.selectPaymentMethodHandler);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.settings.paymentMethod.PaymentMethodHandler,
			$.pkp.classes.Handler
	);


	//
	// Private properties
	//
	/**
	 * The URL template used to fetch the metadata edit form.
	 * @private
	 * @type {string}
	 */
	$.pkp.controllers.tab.settings.paymentMethod.PaymentMethodHandler.
			prototype.paymentMethodFormUrlTemplate_ = '';


	//
	// Private methods
	//
	/**
	 * Get the metadata edit form URL for the given stage and submission ID.
	 *
	 * @private
	 * @param {string} paymentPluginName The name of the payment plugin.
	 * @return {string} The URL for the fetch payment form contents op.
	 */
	$.pkp.controllers.tab.settings.paymentMethod.PaymentMethodHandler.
			prototype.getPaymentMethodFormUrl_ = function(paymentPluginName) {

		// Set the hidden input to the new plugin name (used when saving the form)
		$('#paymentPluginName').val(paymentPluginName);

		// Look for PAYMENT_PLUGIN_NAME token in the URL and replace
		return this.paymentMethodFormUrlTemplate_.
				replace('PAYMENT_PLUGIN_NAME', paymentPluginName);
	};


	//
	// Public methods
	//
	/**
	 * Handle the "submission selected" event triggered by the
	 * submission select form to load the respective metadata form.
	 *
	 * @param {$.pkp.controllers.form.AjaxFormHandler} callingForm The form
	 *  that triggered the event.
	 * @param {Event} event The upload event.
	 * @param {string|number} paymentPluginName The name of the payment plugin.
	 */
	$.pkp.controllers.tab.settings.paymentMethod.PaymentMethodHandler.
			prototype.selectPaymentMethodHandler =
			function(callingForm, event, paymentPluginName) {

		if (paymentPluginName !== 0) {
			// Fetch the form
			$.get(this.getPaymentMethodFormUrl_(
					/** @type {string} */ (paymentPluginName)),
					this.callbackWrapper(this.showFetchedPaymentMethodForm_), 'json');
		} else {
			// Else it was the placeholder; blank out the form
			var $paymentMethodFormContainer = $('#paymentMethodFormContainer');
			$paymentMethodFormContainer.children().remove();
		}
	};


	/**
	 * Show a fetched metadata edit form.
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @private
	 */
	$.pkp.controllers.tab.settings.paymentMethod.PaymentMethodHandler.
			prototype.showFetchedPaymentMethodForm_ = function(ajaxContext, jsonData) {

		var processedJsonData = this.handleJson(jsonData),
				// Find the container and remove all children.
				$paymentMethodFormContainer = $('#paymentMethodFormContainer');

		$paymentMethodFormContainer.children().remove();

		// Replace it with the form content.
		$paymentMethodFormContainer.append(processedJsonData.content);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
