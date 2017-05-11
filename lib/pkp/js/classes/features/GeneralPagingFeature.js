/**
 * @file js/classes/features/GeneralPagingFeature.js
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GeneralPagingFeature
 * @ingroup js_classes_features
 *
 * @brief Base class that implements general functionalities for features
 * that handles paging on grids.
 */
(function($) {


	/**
	 * @constructor
	 * @inheritDoc
	 * @extends $.pkp.classes.features.Feature
	 */
	$.pkp.classes.features.GeneralPagingFeature =
			function(gridHandler, options) {
		options.defaultItemsPerPage = parseInt(options.defaultItemsPerPage, 10);
		options.currentItemsPerPage = parseInt(options.currentItemsPerPage, 10);
		if (!options.itemsTotal) {
			options.itemsTotal = 0;
		} else {
			options.itemsTotal = parseInt(options.itemsTotal, 10);
		}
		options.currentPage = parseInt(options.currentPage, 10);
		this.parent(gridHandler, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.GeneralPagingFeature,
			$.pkp.classes.features.Feature);

	
	//
	// Getters and setters.
	//
	/**
	 * @return {{itemsPerPageParamName: string,
	 *			defaultItemsPerPage: number,
	 *			currentItemsPerPage: number,
	 *			itemsTotal: number,
	 *			pageParamName: string,
	 *			currentPage: number,
				filter: string,
	 *			pagingMarkup: string }}
	 * @override
	 */
	$.pkp.classes.features.GeneralPagingFeature.prototype.getOptions =
			function() {
		var castOptions = /** @type {{itemsPerPageParamName: string,
								defaultItemsPerPage: number,
								currentItemsPerPage: number,
								itemsTotal: number,
								pageParamName: string,
								currentPage: number,
								filter: string,
								pagingMarkup: string }} */
				this.parent('getOptions');

		return castOptions;
	};


	//
	// Protected methods.
	//
	/**
	 * Set grid requests extra parameters.
	 * @param {Object} params
	 */
	$.pkp.classes.features.GeneralPagingFeature.prototype.setGridParams =
			function(params) {
		var options = this.getOptions(), filter;

		// Add the filter data, if any.
		if (options.hasOwnProperty('filter')) {
			filter = $.parseJSON(options.filter);
			$.extend(true, params, filter);
		}

		this.gridHandler.setFetchExtraParams(params);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
