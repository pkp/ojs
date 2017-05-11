/**
 * @file plugins/generic/usageStats/js/UsageStatsFrontendHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.usageStats
 *
 * @brief A small handler to initialize Chart.js graphs on the frontend
 */
(function() {

	if (typeof pkpUsageStats === 'undefined' || typeof pkpUsageStats.data === 'undefined') {
		return;
	}

	var graphs, noStatsNotice;

	// Check for .querySelectorAll in browser support
	try {
		graphs = document.querySelectorAll('.usageStatsGraph');
		noStatsNotice = document.querySelectorAll('.usageStatsUnavailable');
	} catch (e) {
		console.log('Usage stats graph could not be loaded because your browser is too old. Please update your browser.');
		return;
	}

	// Hide the unavailable stats notice when a chart is loaded
	document.addEventListener('usageStatsChartLoaded.pkp', function(e) {
		for (var i = 0; i < noStatsNotice.length; i++) {
			if (typeof noStatsNotice[i].dataset.objectType !== 'undefined' && typeof noStatsNotice[i].dataset.objectId !== 'undefined' && noStatsNotice[i].dataset.objectType === e.target.dataset.objectType && noStatsNotice[i].dataset.objectId === e.target.dataset.objectId ) {
				noStatsNotice[i].parentNode.removeChild(noStatsNotice[i]);
			}
		}
	});

	// Define default chart options
	var chartOptions = {
		legend: {
			display: false,
		},
		tooltips: {
			titleColor: '#333',
			bodyColor: '#333',
			footerColor: '#333',
			backgroundColor: '#ddd',
			cornerRadius: 2,
		},
		elements: {
			line: {
				borderColor: 'rgba(0,0,0,0.3)',
				borderWidth: 1,
				borderJoinStyle: 'round',
				backgroundColor: 'rgba(0,0,0,0.3)',
			},
			rectangle: {
				backgroundColor: 'rgba(0,0,0,0.3)',
			},
			point: {
				radius: 2,
				hoverRadius: 6,
				borderWidth: 0,
				hitRadius: 5,
			},
		},
		scales: {
			xAxes: [{
				gridLines: {
					color: 'rgba(0,0,0,0.05)',
					drawTicks: false,
				},
			}],
			yAxes: [{
				gridLines: {
					color: 'rgba(0,0,0,0.05)',
					drawTicks: false,
				},
			}],
		},
	};

	if (pkpUsageStats.config.chartType === 'bar') {
		chartOptions.scales.xAxes = [{
			gridLines: {
				color: 'transparent',
			}
		}];
	}

	// Fire an event to allow third-party customization of the options
	var optionsEvent = document.createEvent('Event');
	optionsEvent.initEvent('usageStatsChartOptions.pkp', true, true);
	optionsEvent.chartOptions = chartOptions;
	document.dispatchEvent(optionsEvent);

	var graph, objectType, objectId, graphData, initializedEvent;
	pkpUsageStats.charts = {};
	for (var g = 0; g < graphs.length; g++) {
		graph = graphs[g];

		// Check for markup we can use
		if (typeof graph.dataset.objectType === 'undefined' || typeof graph.dataset.objectId === 'undefined' ) {
			console.log('Usage stats graph is missing data-object-type and data-object-id attributes', graph);
			continue;
		}

		objectType = graph.dataset.objectType;
		objectId = graph.dataset.objectId;

		// Check that data exists for this graph
		if (typeof pkpUsageStats.data[objectType] === 'undefined' || pkpUsageStats.data[objectType][objectId] === 'undefined' ) {
			console.log('Could not find data for this usage stats graph.', objectType, objectId);
			continue;
		}

		// Do nothing if there's no data for this chart
		if (typeof pkpUsageStats.data[objectType][objectId].data === 'undefined') {
			graph.parentNode.removeChild(graph);
			continue;
		}

		graphData = pkpUsageStats.data[objectType][objectId];

		// Turn the data set into an array
		var dataArray = [], currentYear = new Date().getFullYear();
		for(month in graphData.data[currentYear]) {
			dataArray.push(graphData.data[currentYear][month]);
		}

		pkpUsageStats.charts[objectType + '_' + objectId] = new Chart(graph, {
			type: pkpUsageStats.config.chartType,
			data: {
				labels: pkpUsageStats.locale.months,
				datasets: [{
					label: graphData.label,
					data: dataArray,
				}]
			},
			options: chartOptions,
		});

		// Fire an event when the chart is initialized
		var initializedEvent = document.createEvent('Event');
		initializedEvent.initEvent('usageStatsChartLoaded.pkp', true, true);
		graph.dispatchEvent(initializedEvent);
	}
})();
