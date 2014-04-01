/**
 * ALMViz
 * See https://github.com/jalperin/almviz for more details
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Article level metrics visualization controller.
 */
function AlmViz(options) {
	// allow jQuery object to be passed in
	// in case a different version of jQuery is needed from the one globally defined
	var $ = options.jQuery || $;

	// Init data
	var categories_ = options.categories;
	var data = options.almStatsJson;
	var additionalStats = options.additionalStatsJson;
	if (additionalStats) {
		data[0].sources.push(additionalStats);
	}

	// Init basic options
	var baseUrl_ = options.baseUrl;
	var hasIcon = options.hasIcon;
	var minItems_ = options.minItemsToShowGraph;
	var showTitle = options.showTitle;
	var formatNumber_ = d3.format(",d");

	// extract publication date
	var pub_date = d3.time.format.iso.parse(data[0]["publication_date"]);

	var vizDiv;
	// Get the Div where the viz should go (default to one with ID "alm')
	if (options.vizDiv) {
		vizDiv = d3.select(options.vizDiv);
	} else {
		vizDiv = d3.select("#alm");
	}

	// look to make sure browser support SVG
	var hasSVG_ = document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1");

	// to track if any metrics have been found
	var metricsFound_;

	/**
	 * Initialize the visualization.
	 * NB: needs to be accessible from the outside for initialization
	 */
	this.initViz = function() {
		vizDiv.select("#loading").remove();

		if (showTitle) {
			vizDiv.append("a")
				.attr('href', 'http://dx.doi.org/' + data[0].doi)
				.attr("class", "title")
				.text(data[0].title);
		}

		// loop through categories
		categories_.forEach(function(category) {
			addCategory_(vizDiv, category, data);
		});


		if (!metricsFound_) {
			vizDiv.append("p")
				.attr("class", "muted")
				.text("No metrics found.");
		}
	};


	/**
	 * Build each article level statistics category.
	 * @param {Object} canvas d3 element
	 * @param {Array} category Information about the category.
	 * @param {Object} data Statistics.
	 * @return {JQueryObject|boolean}
	 */
	var addCategory_ = function(canvas, category, data) {
		var $categoryRow = false;

		// Loop through sources to add statistics data to the category.
		data[0]["sources"].forEach(function(source) {
			var total = source.metrics[category.name];
			if (total > 0) {
				// Only add the category row the first time
				if (!$categoryRow) {
					$categoryRow = getCategoryRow_(canvas, category);
				}

				// Flag that there is at least one metric
				metricsFound_ = true;
				addSource_(source, total, category, $categoryRow);
			}
		});
	};


	/**
	 * Get category row d3 HTML element. It will automatically
	 * add the element to the passed canvas.
	 * @param {d3Object} canvas d3 HTML element
	 * @param {Array} category Category information.
	 * @param {d3Object}
	 */
	var getCategoryRow_ = function(canvas, category) {
		var categoryRow, categoryTitle, tooltip;

		// Build category html objects.
		categoryRow = canvas.append("div")
			.attr("class", "alm-category-row")
			.attr("style", "width: 100%; overflow: hidden;")
			.attr("id", "category-" + category.name);

		categoryTitle = categoryRow.append("div")
			.attr("class", "alm-category-row-heading")
			.attr("id", "month-" + category.name)
			.text(category.display_name);

		tooltip = categoryTitle.append("div")
			.attr("class", "alm-category-row-info").append("span")
			.attr("class", "ui-icon ui-icon-info");

		$(tooltip).tooltip({title: category.tooltip_text, container: 'body'});

		return categoryRow;
	};


	/**
	 * Add source information to the passed category row element.
	 * @param {Object} source
	 * @param {integer} sourceTotalValue
	 * @param {Object} category
	 * @param {JQueryObject} $categoryRow
	 * @return {JQueryObject}
	 */
	var addSource_ = function(source, sourceTotalValue, category, $categoryRow) {
		var $row, $countLabel, $count,
			total = sourceTotalValue;

		$row = $categoryRow
			.append("div")
			.attr("class", "alm-row")
			.attr("style", "float: left")
			.attr("id", "alm-row-" + source.name + "-" + category.name);

		$countLabel = $row.append("div")
			.attr("class", "alm-count-label");

		if (hasIcon.indexOf(source.name) >= 0) {
			$countLabel.append("img")
				.attr("src", baseUrl_ + '/assets/' + source.name + '.png')
				.attr("alt", 'a description of the source')
				.attr("class", "label-img");
		}

		if (source.events_url) {
			// if there is an events_url, we can link to it from the count
			$count = $countLabel.append("a")
				.attr("href", function(d) { return source.events_url; });
		} else {
			// if no events_url, we just put in the count
			$count = $countLabel.append("span");
		}

		$count
			.attr("class", "alm-count")
			.attr("id", "alm-count-" + source.name + "-" + category.name)
			.text(formatNumber_(total));

		$countLabel.append("br");

		if (source.name == 'ojsViews') {
			$countLabel.append("span")
				.text(source.display_name);
		} else {
			// link the source name
			$countLabel.append("a")
				.attr("href", baseUrl_ + "/sources/" + source.name)
				.text(source.display_name);
		}

		// Only add a chart if the browser supports SVG
		if (hasSVG_) {
			var level = false;

			// check what levels we can show
			var showDaily = false;
			var showMonthly = false;
			var showYearly = false;

			if (source.by_year) {
				level_data = getData_('year', source);
				var yearTotal = level_data.reduce(function(i, d) { return i + d[category.name]; }, 0);
				var numYears = d3.time.year.utc.range(pub_date, new Date()).length;

				if (yearTotal >= minItems_.minEventsForYearly &&
					numYears >= minItems_.minYearsForYearly) {
					showYearly = true;
					level = 'year';
				};
			}

			if (source.by_month) {
				level_data = getData_('month', source);
				var monthTotal = level_data.reduce(function(i, d) { return i + d[category.name]; }, 0);
				var numMonths = d3.time.month.utc.range(pub_date, new Date()).length;

				if (monthTotal >= minItems_.minEventsForMonthly &&
					numMonths >= minItems_.minMonthsForMonthly) {
					showMonthly = true;
					level = 'month';
				};
			}

			if (source.by_day){
				level_data = getData_('day', source);
				var dayTotal = level_data.reduce(function(i, d) { return i + d[category.name]; }, 0);
				var numDays = d3.time.day.utc.range(pub_date, new Date()).length;

				if (dayTotal >= minItems_.minEventsForDaily && numDays >= minItems_.minDaysForDaily) {
					showDaily = true;
					level = 'day';
				};
			}

			// The level and level_data should be set to the finest level
			// of granularity that we can show
			timeInterval = getTimeInterval_(level);

			// check there is data for
			if (showDaily || showMonthly || showYearly) {
				var $chartDiv = $row.append("div")
					.attr("style", "width: 70%; float:left;")
					.attr("class", "alm-chart-area");

				var viz = getViz_($chartDiv, source, category);
				loadData_(viz, level);

				var update_controls = function(control) {
					control.siblings('.alm-control').removeClass('active');
					control.addClass('active');
				};

				var $levelControlsDiv = $chartDiv.append("div")
					.attr("style", "width: " + (viz.margin.left + viz.width) + "px;")
					.append("div")
					.attr("style", "float:right;");

				if (showDaily) {
					$levelControlsDiv.append("a")
						.attr("href", "javascript:void(0)")
						.classed("alm-control", true)
						.classed("disabled", !showDaily)
						.classed("active", (level == 'day'))
						.text("daily (first 30)")
						.on("click", function() {
							if (showDaily && !$(this).hasClass('active')) {
								loadData_(viz, 'day');
								update_controls($(this));
							}
						}
					);

					$levelControlsDiv.append("text").text(" | ");
				}

				if (showMonthly) {
					$levelControlsDiv.append("a")
						.attr("href", "javascript:void(0)")
						.classed("alm-control", true)
						.classed("disabled", !showMonthly || !showYearly)
						.classed("active", (level == 'month'))
						.text("monthly")
						.on("click", function() { if (showMonthly && !$(this).hasClass('active')) {
							loadData_(viz, 'month');
							update_controls($(this));
						} });

					if (showYearly) {
						$levelControlsDiv.append("text")
							.text(" | ");
					}

				}

				if (showYearly) {
					$levelControlsDiv.append("a")
						.attr("href", "javascript:void(0)")
						.classed("alm-control", true)
						.classed("disabled", !showYearly || !showMonthly)
						.classed("active", (level == 'year'))
						.text("yearly")
						.on("click", function() {
							if (showYearly && !$(this).hasClass('active')) {
								loadData_(viz, 'year');
								update_controls($(this));
							}
						}
					);
				}

				// add a clearer and styles to ensure graphs on their own line
				$row.insert("div", ":first-child")
					.attr('style', 'clear:both');
				$row.attr('style', "width: 100%");
			};
		};

		return $row;
	};


	/**
	 * Extract the date from the source
	 * @param level (day|month|year)
	 * @param d the datum
	 * @return {Date}
	 */
	var getDate_ = function(level, d) {
		switch (level) {
			case 'year':
				return new Date(d.year, 0, 1);
			case 'month':
				// js Date indexes months at 0
				return new Date(d.year, d.month - 1, 1);
			case 'day':
				// js Date indexes months at 0
				return new Date(d.year, d.month - 1, d.day);
		}
	};


	/**
	 * Format the date for display
	 * @param level (day|month|year)
	 * @param d the datum
	 * @return {String}
	 */
	var getFormattedDate_ = function(level, d) {
		switch (level) {
			case 'year':
				return d3.time.format("%Y")(getDate_(level, d));
			case 'month':
				return d3.time.format("%b %y")(getDate_(level, d));
			case 'day':
				return d3.time.format("%d %b %y")(getDate_(level, d));
		}
	};


	/**
	 * Extract the data from the source.
	 * @param {string} level (day|month|year)
	 * @param {Object} source
	 * @return {Array} Metrics
	 */
	var getData_ = function(level, source) {
		switch (level) {
			case 'year':
				return source.by_year;
			case 'month':
				return source.by_month;
			case 'day':
				return source.by_day;
		}
	};

	/**
	 * Returns a d3 timeInterval for date operations.
	 * @param {string} level (day|month|year
	 * @return {Object} d3 time Interval
	 */
	var getTimeInterval_ = function(level) {
		switch (level) {
			case 'year':
				return d3.time.year.utc;
			case 'month':
				return d3.time.month.utc;
			case 'day':
				return d3.time.day.utc;
		}
	};

	/**
	 * The basic general set up of the graph itself
	 * @param {JQueryElement} chartDiv The div where the chart should go
	 * @param {Object} source
	 * @param {Array} category The category for 86 chart
	 * @return {Object}
	 */
	var getViz_ = function(chartDiv, source, category) {
		var viz = {};

		// size parameters
		viz.margin = {top: 10, right: 40, bottom: 0, left: 40};
		viz.width = 400 - viz.margin.left - viz.margin.right;
		viz.height = 100 - viz.margin.top - viz.margin.bottom;

		// div where everything goes
		viz.chartDiv = chartDiv;

		// source data and which category
		viz.category = category;
		viz.source = source;

		// just for record keeping
		viz.name = source.name + '-' + category.name;

		viz.x = d3.time.scale();
		viz.x.range([0, viz.width]);

		viz.y = d3.scale.linear();
		viz.y.range([viz.height, 0]);

		viz.z = d3.scale.ordinal();
		viz.z.range(['main', 'alt']);

		// the chart
		viz.svg = viz.chartDiv.append("svg")
			.attr("width", viz.width + viz.margin.left + viz.margin.right)
			.attr("height", viz.height + viz.margin.top + viz.margin.bottom)
			.append("g")
			.attr("transform", "translate(" + viz.margin.left + "," + viz.margin.top + ")");


		// draw the bars g first so it ends up underneath the axes
		viz.bars = viz.svg.append("g");

		// and the shadow bars on top for the tooltips
		viz.barsForTooltips = viz.svg.append("g");

		viz.svg.append("g")
			.attr("class", "x axis")
			.attr("transform", "translate(0," + (viz.height - 1) + ")");

		viz.svg.append("g")
			.attr("class", "y axis");

		return viz;
	};


	/**
	 * Takes in the basic set up of a graph and loads the data itself
	 * @param {Object} viz AlmViz object
	 * @param {string} level (day|month|year)
	 */
	var loadData_ = function(viz, level) {
		var category = viz.category;
		var level_data = getData_(level, viz.source);
		var timeInterval = getTimeInterval_(level);

		var end_date = new Date();
		// use only first 29 days if using day view
		// close out the year otherwise
		if (level == 'day') {
			end_date = timeInterval.offset(pub_date, 29);
		} else {
			end_date = d3.time.year.utc.ceil(end_date);
		}

		//
		// Domains for x and y
		//
		// a time x axis, between pub_date and end_date
		viz.x.domain([timeInterval.floor(pub_date), end_date]);

		// a linear axis from 0 to max value found
		viz.y.domain([0, d3.max(level_data, function(d) { return d[category.name]; })]);

		//
		// Axis
		//
		// a linear axis between publication date and current date
		viz.xAxis = d3.svg.axis()
			.scale(viz.x)
			.tickSize(0)
			.ticks(0);

		// a linear y axis between 0 and max value found in data
		viz.yAxis = d3.svg.axis()
			.scale(viz.y)
			.orient("left")
			.tickSize(0)
			.tickValues([d3.max(viz.y.domain())])   // only one tick at max
			.tickFormat(d3.format(",d"));

		//
		// The chart itself
		//

		// TODO: these transitions could use a little work
		var barWidth = Math.max((viz.width/(timeInterval.range(pub_date, end_date).length + 1)) - 2, 1);

		var barsForTooltips = viz.barsForTooltips.selectAll(".barsForTooltip")
			.data(level_data, function(d) { return getDate_(level, d); });

		barsForTooltips
			.exit()
			.remove();

		var bars = viz.bars.selectAll(".bar")
			.data(level_data, function(d) { return getDate_(level, d); });

		bars
			.enter().append("rect")
			.attr("class", function(d) { return "bar " + viz.z((level == 'day' ? d3.time.weekOfYear(getDate_(level, d)) : d.year)); })
			.attr("y", viz.height)
			.attr("height", 0);

		bars
			.attr("x", function(d) { return viz.x(getDate_(level, d)) + 2; }) // padding of 2, 1 each side
			.attr("width", barWidth);

		bars.transition()
			.duration(1000)
			.attr("width", barWidth)
			.attr("y", function(d) { return viz.y(d[category.name]); })
			.attr("height", function(d) { return viz.height - viz.y(d[category.name]); });

		bars
			.exit().transition()
			.attr("y", viz.height)
			.attr("height", 0);

		bars
			.exit()
			.remove();

		viz.svg
			.select(".x.axis")
			.call(viz.xAxis);

		viz.svg
			.transition().duration(1000)
			.select(".y.axis")
			.call(viz.yAxis);

		barsForTooltips
			.enter().append("rect")
			.attr("class", function(d) { return "barsForTooltip " + viz.z((level == 'day' ? d3.time.weekOfYear(getDate_(level, d)) : d.year)); });

		barsForTooltips
			.attr("width", barWidth + 2)
			.attr("x", function(d) { return viz.x(getDate_(level, d)) + 1; })
			.attr("y", function(d) { return viz.y(d[category.name]) - 1; })
			.attr("height", function(d) { return viz.height - viz.y(d[category.name]) + 1; });


		// add in some tool tips
		viz.barsForTooltips.selectAll("rect").each(
			function(d,i){
				$(this).tooltip('destroy'); // need to destroy so all bars get updated
				$(this).tooltip({title: formatNumber_(d[category.name]) + " in " + getFormattedDate_(level, d), container: "body"});
			}
		);
	}
}