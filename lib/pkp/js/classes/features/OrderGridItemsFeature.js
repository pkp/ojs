/**
 * @file js/classes/features/OrderGridItemsFeature.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrderGridItemsFeature
 * @ingroup js_classes_features
 *
 * @brief Feature for ordering grid items.
 */
(function($) {


	/**
	 * @constructor
	 * @inheritDoc
	 * @extends $.pkp.classes.features.OrderItemsFeature
	 */
	$.pkp.classes.features.OrderGridItemsFeature =
			function(gridHandler, options) {
		this.parent(gridHandler, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.OrderGridItemsFeature,
			$.pkp.classes.features.OrderItemsFeature);


	//
	// Extended methods from OrderItemsFeature.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderGridItemsFeature.prototype.setupSortablePlugin =
			function() {
		this.applySortPlgOnElements(
				this.getGridHtmlElement(), 'tr.orderable', null);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderGridItemsFeature.prototype.saveOrderHandler =
			function() {

		var stringifiedData, saveOrderCallback,
				options = /** @type {{saveItemsSequenceUrl: string}} */
				(this.getOptions()),
				returner;

		this.parent('saveOrderHandler');

		stringifiedData = JSON.stringify(this.getItemsDataId());
		saveOrderCallback = this.callbackWrapper(
				this.saveOrderResponseHandler_, this);
		$.post(options.saveItemsSequenceUrl, {data: stringifiedData},
				saveOrderCallback, 'json');

		return false;
	};


	//
	// Protected methods to be overriden by subclasses
	//
	/**
	 * Get all items data id in a sequence array.
	 * @return {Array} List of all items data.
	 */
	$.pkp.classes.features.OrderGridItemsFeature.prototype.getItemsDataId =
			function() {
		return this.getRowsDataId(this.getGridHtmlElement());
	};


	//
	// Private helper methods.
	//
	/**
	 * Save order response handler.
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.classes.features.OrderGridItemsFeature.prototype.
			saveOrderResponseHandler_ = function(ajaxContext, jsonData) {
		var processedJsonData = this.gridHandler.handleJson(jsonData);
		this.toggleState(false);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
