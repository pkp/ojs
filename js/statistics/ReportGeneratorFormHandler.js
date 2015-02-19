/**
 * @defgroup js_statistics
 */
/**
 * @file js/statistics/ReportGeneratorFormHandler.js
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportGeneratorFormHandler
 *
 * @brief Form handler that handles the statistics report
 * generator form.
 */
(function($) {

	/** @type {Object} */
	$.pkp.statistics = $.pkp.statistics || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *\
	 * @param {jQueryObject} $form The wrapped HTML form element.
	 * @param {{
	 *   timeFilterWrapperSelector: string,
	 *   rangeByDaySelector: string,
	 *   rangeByMonthSelector: string,
	 *   startDayElementSelector: string,
	 *   endDayElementSelector: string,
	 *   fetchFormUrl: string,
	 *   metricTypeSelectSelector: string,
	 *   reportTemplateSelectSelector: string,
	 *   columnsSelector: string,
	 *   aggregationOptionsSelector: string,
	 *   currentMonthSelector: string,
	 *   currentDaySelector: string,
	 *   dateRangeWrapperSelector: string,
	 *   fetchArticlesUrl: string,
	 *   articleSelectSelector: string,
	 *   issueSelectSelector: string,
	 *   fileAssocTypes: Object,
	 *   fileTypeSelectSelector: string,
	 *   objectTypeSelectSelector: string,
	 *   fetchRegionsUrl: string,
	 *   regionSelectSelector: string,
	 *   countrySelectSelector: string
	 *   }} options Handler options.

	 */
	$.pkp.statistics.ReportGeneratorFormHandler = function($form, options) {

		var $issueSelectElement, $objectTypeSelectElement,
				$countrySelectElement, $metricTypeSelectElement,
				$reportTemplateSelectElement, $currentTimeElements,
				$rangeTimeElements, $aggregationOptions,
				$currentDaySelectElement, $rangeByDaySelectElement,
				$fileTypeSelectElement;

		// Configure the form handler.
		options.trackFormChanges = false;
		options.transformButtons = false;
		this.parent($form, options);

		this.getHtmlElement().find(':submit').button();
		$('#reportUrlFormArea', $form).hide();

		this.timeFilterWrapperSelector_ = options.timeFilterWrapperSelector;
		this.rangeByDaySelector_ = options.rangeByDaySelector;
		this.rangeByMonthSelector_ = options.rangeByMonthSelector;
		this.startDayElementSelector_ = options.startDayElementSelector;
		this.endDayElementSelector_ = options.endDayElementSelector;

		// Update form when metric type is changed.
		this.fetchFormUrl_ = options.fetchFormUrl;
		$metricTypeSelectElement = $(options.metricTypeSelectSelector,
				this.getHtmlElement());
		this.$metricTypeSelectElement_ = $metricTypeSelectElement;
		if ($metricTypeSelectElement.length == 1) {
			$metricTypeSelectElement.change(this.callbackWrapper(
					this.fetchFormHandler_));
		}

		// Update form when report template is changed.
		$reportTemplateSelectElement = $(options.reportTemplateSelectSelector,
				this.getHtmlElement());
		this.$reportTemplateSelectElement_ = $reportTemplateSelectElement;
		if ($reportTemplateSelectElement.length == 1) {
			$reportTemplateSelectElement.change(this.callbackWrapper(
					this.fetchFormHandler_));
		}

		// Update report columns when aggregation options are changed.
		this.columnsSelector_ = options.columnsSelector;
		$aggregationOptions = $(options.aggregationOptionsSelector);
		if ($aggregationOptions.length > 0) {
			$aggregationOptions.change(this.callbackWrapper(
					this.aggregationOptionsChangeHandler_));
		}

		// Add click handler to current time filter selectors.
		$currentDaySelectElement = $(options.currentDaySelector,
				this.getHtmlElement());
		$currentTimeElements = $(options.currentMonthSelector,
				this.getHtmlElement()).add($currentDaySelectElement);
		if ($currentTimeElements.length == 2) {
			$currentTimeElements.click(this.callbackWrapper(
					this.currentTimeElementsClickHandler_));
		}

		// Add click handler to range time filter selectors.
		$rangeByDaySelectElement = $(options.rangeByDaySelector,
				this.getHtmlElement());
		$rangeTimeElements = $(options.rangeByMonthSelector,
				this.getHtmlElement()).add($rangeByDaySelectElement);
		if ($rangeTimeElements.length == 2) {
			$rangeTimeElements.click(this.callbackWrapper(
					this.rangeTimeElementsClickHandler_));
		}

		// Call a click event on the current selected
		// range time filter element, if any, so the event
		// handler can run and perform the necessary actions.
		this.dateRangeElementsWrapper_ = $(options.dateRangeWrapperSelector,
				this.getHtmlElement());
		if ($rangeTimeElements.filter('input:checked').length == 1) {
			$rangeTimeElements.filter('input:checked').click();
		} else {
			this.dateRangeElementsWrapper_.hide();
		}

		// Update the article element when issue is changed.
		this.fetchArticlesUrl_ = options.fetchArticlesUrl;
		this.$articleSelectElement_ = $(options.articleSelectSelector,
				this.getHtmlElement());
		if (this.$articleSelectElement_.length == 1) {
			$issueSelectElement = $(options.issueSelectSelector,
					this.getHtmlElement());
			$issueSelectElement.change(this.callbackWrapper(
					this.fetchArticleInfoHandler_));
		}

		// Update the file type element when object type is changed.
		this.fileAssocTypes_ = options.fileAssocTypes;
		this.fileTypeSelectSelector_ = options.fileTypeSelectSelector;
		$fileTypeSelectElement = $(this.fileTypeSelectSelector_,
				this.getHtmlElement());
		$objectTypeSelectElement = $(options.objectTypeSelectSelector,
				this.getHtmlElement());
		if ($fileTypeSelectElement.length == 1) {
			$fileTypeSelectElement.attr('disabled', 'disabled');
			$objectTypeSelectElement.change(this.callbackWrapper(
					this.updateFileTypeSelectHandler_));
		}

		// Call the change event on the object type select element,
		// so the event handler can perform the expected actions.
		$objectTypeSelectElement.change();

		// Update the region element when country is changed.
		this.fetchRegionsUrl_ = options.fetchRegionsUrl;
		this.$regionSelectElement_ = $(options.regionSelectSelector,
				this.getHtmlElement());
		if (this.$regionSelectElement_.length == 1) {
			$countrySelectElement = $(options.countrySelectSelector,
					this.getHtmlElement());
			$countrySelectElement.change(this.callbackWrapper(
					this.fetchRegionHandler_));
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.statistics.ReportGeneratorFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Private properties
	//
	/**
	 * The fetch form url.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			fetchFormUrl_ = null;


	/**
	 * The fetch articles title and id url.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			fetchArticlesUrl_ = null;


	/**
	 * Metric type select element.
	 * @private
	 * @type {Object}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			$metricTypeSelectElement_ = {};


	/**
	 * Report template select element.
	 * @private
	 * @type {Object}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			$reportTemplateSelectElement_ = {};


	/**
	 * Articles select element.
	 * @private
	 * @type {Object}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			$articleSelectElement_ = {};


	/**
	 * Region select element.
	 * @private
	 * @type {Object}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			$regionSelectElement_ = {};


	/**
	 * The fetch regions url.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			fetchRegionsUrl_ = null;


	/**
	 * File assoc types.
	 * @private
	 * @type {Object}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			$fileAssocTypes_ = {};


	/**
	 * Date filter range by day element selector.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			rangeByDaySelector_ = null;


	/**
	 * Date filter range by month element selector.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			rangeByMonthSelector_ = null;


	/**
	 * Start day filter input element selector.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			startDayElementSelector_ = null;


	/**
	 * End day filter input element selector.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			endDayElementSelector_ = null;


	/**
	 * Time filter form elements wrapper selector.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			timeFilterWrapperSelector_ = null;


	/**
	 * Columns select element selector.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			columnsSelector_ = null;


	/**
	 * File type select selector.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			fileTypeSelectSelector_ = null;


	//
	// Protected extended methods.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.handleResponse =
			function(formElement, jsonData) {
		/** @type {{reportUrl: string}|Object|boolean|null} data */
		var data = this.handleJson(jsonData);
		if (data !== false && data.reportUrl !== undefined) {
			$('#reportUrlFormArea', this.getHtmlElement()).show().
					find(':input').val(data.reportUrl);

			window.location = data.reportUrl;
		}

		return /** @type {boolean} */ this.parent(
				'handleResponse', formElement, jsonData);
	};


	//
	// Private methods
	//
	/**
	 * Callback called by components that needs to
	 * refresh the form when changed (metric type and report
	 * template selectors).
	 *
	 * @private
	 *
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.fetchFormHandler_ =
			function() {
		var $metricTypeSelectedOption, $reportTemplateSelectedOption,
				$timeFilterElements, timeFilterValues, args = {};

		// Serialize time filter values, so the form can be refreshed
		// with the same values.
		$timeFilterElements = $(this.timeFilterWrapperSelector_,
				this.getHtmlElement());
		timeFilterValues = $timeFilterElements.serializeArray();
		/*jslint unparam: true*/
		$.each(timeFilterValues, function(i, element) {
			args[element.name] = element.value;
		});
		/*jslint unparam: false*/

		$metricTypeSelectedOption = $('option:selected',
				this.$metricTypeSelectElement_);
		if ($metricTypeSelectedOption[0] !== undefined &&
				$metricTypeSelectedOption[0].value !== undefined) {
			args.metricType = $metricTypeSelectedOption[0].value;
		}

		$reportTemplateSelectedOption = $('option:selected',
				this.$reportTemplateSelectElement_);
		if ($reportTemplateSelectedOption[0] !== undefined &&
				$reportTemplateSelectedOption[0].value !== undefined) {
			args.reportTemplate = $reportTemplateSelectedOption[0].value;
		}

		args.refreshForm = true;

		$.get(this.fetchFormUrl_, args, this.callbackWrapper(
				this.handleResponse, null), 'json');

		return false;
	};


	/**
	 * Callback called after issue select is changed to fetch
	 * related article title and id.
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.fetchArticleInfoHandler_ =
			function(callingContext) {
		var $issueSelectElement, $issueSelectedOptions, issueId;

		this.$articleSelectElement_.empty();

		$issueSelectElement = $(callingContext);
		$issueSelectedOptions = $('option:selected', $issueSelectElement);
		if ($issueSelectedOptions.length == 1) {
			issueId = $issueSelectedOptions[0].value;
			$.get(this.fetchArticlesUrl_, {issueId: issueId},
					this.callbackWrapper(this.updateArticleSelectCallback_, null), 'json');
		}

		return false;
	};


	/**
	 * Callback that will be activated when a request for article
	 * information returns.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	/*jslint unparam: true*/
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			updateArticleSelectCallback_ = function(ajaxContext, jsonData) {
		var $articleSelectElement, limit, content, i,
				/** @type {{content: string}|Object|boolean|null} */ handledJson;
		$articleSelectElement = this.$articleSelectElement_;

		handledJson = this.handleJson(jsonData);
		if (handledJson !== false) {
			content = handledJson.content;
			for (i = 0, limit = content.length; i < limit; i++) {
				$articleSelectElement.append(
						$('<option />').val(content[i].id).text(content[i].title));
			}
		}

		return false;
	};
	/*jslint unparam: false*/


	/**
	 * Callback called after object type select is changed.
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			updateFileTypeSelectHandler_ = function(callingContext) {
		var $objectTypeElement, $objectTypeSelectedOptions, assocType, i;

		$objectTypeElement = $(callingContext);
		$objectTypeSelectedOptions = $('option:selected', $objectTypeElement);
		if ($objectTypeSelectedOptions.length == 1) {
			assocType = $objectTypeSelectedOptions[0].value;
			for (i in this.fileAssocTypes_) {
				if (this.fileAssocTypes_[i] == assocType) {
					$(this.fileTypeSelectSelector_,
							this.getHtmlElement()).removeAttr('disabled');
					return false;
				}
			}
		}

		$(this.fileTypeSelectSelector_,
				this.getHtmlElement()).attr('disabled', 'disabled');

		return false;
	};


	/**
	 * Callback called after country select is changed to fetch
	 * related region info.
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.fetchRegionHandler_ =
			function(callingContext) {
		var $countrySelectElement, $countrySelectedOptions, countryId;

		this.$regionSelectElement_.empty();

		$countrySelectElement = $(callingContext);
		$countrySelectedOptions = $('option:selected', $countrySelectElement);
		if ($countrySelectedOptions.length == 1) {
			countryId = $countrySelectedOptions[0].label;
			$.get(this.fetchRegionsUrl_, {countryId: countryId},
					this.callbackWrapper(this.updateRegionSelectCallback_, null), 'json');
		}

		return false;
	};


	/**
	 * Callback that will be activated when a request for region
	 * information returns.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	/*jslint unparam: true*/
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			updateRegionSelectCallback_ = function(ajaxContext, jsonData) {
		var $regionSelectElement, limit, content, i,
				/** @type {{content: string}|Object|boolean|null} */ handledJson;
		$regionSelectElement = this.$regionSelectElement_;

		$regionSelectElement.empty();

		handledJson = this.handleJson(jsonData);
		if (handledJson !== false) {
			content = handledJson.content;
			for (i = 0, limit = content.length; i < limit; i++) {
				$regionSelectElement.append(
						$('<option />').val(content[i].id).text(content[i].name));
			}
		}

		return false;
	};
	/*jslint unparam: false*/


	/**
	 * Callback called when current time selectors are clicked.
	 *
	 * @private
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			currentTimeElementsClickHandler_ =
					function() {
		this.dateRangeElementsWrapper_.hide();
	};


	/**
	 * Callback called when range time selectors are clicked.
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			rangeTimeElementsClickHandler_ =
					function(callingContext) {

		var $dayElements = $(this.startDayElementSelector_).
				add(this.endDayElementSelector_);

		this.dateRangeElementsWrapper_.show();
		if ('#' + $(callingContext).attr('id') == this.rangeByDaySelector_) {
			$dayElements.show();
		}

		if ('#' + $(callingContext).attr('id') == this.rangeByMonthSelector_) {
			$dayElements.hide();
		}
	};


	/**
	 * Callback called when aggregation options are changed.
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			aggregationOptionsChangeHandler_ =
					function(callingContext) {
		var $aggregationOption, $columns, $column;
		$columns = $(this.columnsSelector_);
		$aggregationOption = $(callingContext);
		$column = $columns.find('option[value="' + $aggregationOption.
				attr('value') + '"]');

		if ($aggregationOption.attr('checked')) {
			$column.attr('selected', 'selected');
		} else {
			$column.removeAttr('selected');
		}

		return false;
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
