/**
 * @file plugins/generic/usageStats/js/UsageStatsGraphHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.usageStats
 * @class UsageStatsGraphHandler
 *
 * @brief Usage statistics graph handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.plugins.generic.usageStats =
			$.pkp.plugins.generic.usageStats || {};

	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $graph the wrapped HTML graph element.
	 * @param {Object} options Graph options.
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler =
			function($graph, options) {

		this.parent($graph, options);
		this.data_ = options.data;
		this.labels_ = options.labels;
		this.datasetMaxCount_ = options.datasetMaxCount;
		this.chartType_ = options.chartType;

		$("#statsYear", $graph).change(this.callbackWrapper(this.loadGraph_));
		$("#statsSum", $graph).change(this.callbackWrapper(this.loadGraph_));
		$('#usageStatsWarning', $graph).hide();

		// Make sure we don't allow users to display more datasets than it's allowed.
		if (typeof this.data_['byRepresentation'] !== undefined) {
			if (Object.keys(this.data_['byRepresentation']).length > this.datasetMaxCount_) {
				$('#statsSum', $graph).parent().hide();
			}
		}

		this.loadGraph_();
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler,
			$.pkp.classes.Handler);


	//
	// Private properties.
	//
	/**
	 * The data to be presented by the chart.
	 * @private
	 * @type {Object}
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.data_ = {};


	/**
	 * The x-axis labels.
	 * @private
	 * @type {Object}
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.labels_ = {};


	/**
	 * Maximum number of datasets.
	 * @private
	 * @type {integer}
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.datasetMaxCount_ = 0;


	/**
	 * The chart type.
	 * @private
	 * @type {string}
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.chartType_ = '';


	/**
	 * The chart object.
	 * @private
	 * @type {?Object}
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.chart_ = null;


	//
	// Private helper methods.
	//
	/**
	 * Load the chart.
	 *
	 * @private
	 *
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.loadGraph_ = function() {

		var datasets = [], data, chartData, $canvas,
			aggregationLevel = this.getAggregationLevel_();

		// We expect an object, if array (empty or not), do nothing.
		if ($.isArray(this.data_[aggregationLevel])) {
			// Show no data message.
			$('#usageStatsWarning', this.getHtmlElement()).show();
			$('#usageStatsChart', this.getHtmlElement()).hide();
			$('#statsSum', this.getHtmlElement()).parent().hide();
			return;
		}

		data = this.data_[aggregationLevel];
		datasets = this.setupDatasets_(data);

		chartData = {
			labels: this.labels_,
			datasets: datasets
		}

		if (this.chart_) {
			// Make sure we destroy any possible loaded chart.
			this.chart_.destroy();
		}

		$canvas = $("#usageStatsChart", this.getHtmlElement());
		this.chart_ = new Chart($canvas, {
			type: this.chartType_,
			data: chartData
		});

	}


	/**
	 * Based on the current UI controls state,
	 * decide which data aggregation level to use.
	 *
	 * @private
	 *
	 * @return {string}
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.getAggregationLevel_ = function() {

		if ($('#statsSum', this.getHtmlElement()).is(':checked')) {
			return 'byMonth';
		} else {
			return 'byRepresentation';
		}
	}


	/**
	 * Prepare the dataset object with the passed data,
	 * applying any needed filter that comes from user input.
	 *
	 * @private
	 *
	 * @param {Object} data Datasets data definition.
	 * @return {Object} Datasets properly setup with filtered data.
	 */
	$.pkp.plugins.generic.usageStats.UsageStatsGraphHandler.
			prototype.setupDatasets_ = function (data) {

		var year = $("#statsYear :selected", this.getHtmlElement()).text(),
			datasetId, filteredData, datasetData, month, datasets = [];

		for (datasetId in data) {
			filteredData = [];
			datasetData = data[datasetId]['data'];
			for (month in datasetData[year]) {
				// Make sure we get only the current year data.
				filteredData.push(datasetData[year][month]);
			};

			datasets.push(
				{
					label: data[datasetId]['label'],
					data: filteredData,
					backgroundColor: 'rgba(' + data[datasetId]['color'] + ', 0.6)'
				});
		};

		return datasets;
	}


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
