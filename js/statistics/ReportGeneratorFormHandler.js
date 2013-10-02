/**
 * @defgroup js_statistics
 */
/**
 * @file js/statistics/ReportGeneratorFormHandler.js
 *
 * Copyright (c) 2000-2013 John Willinsky
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
	 *
	 * @param {jQueryObject} $form The wrapped HTML form element.
	 * @param {Object} options Configuration of the form handler.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler = function($form, options) {
		var $issueSelectElement, $objectTypeSelectElement, $countrySelectElement;

		// Configure the form handler.
		options.trackFormChanges = false;
		options.transformButtons = false;
		this.parent($form, options);

		this.getHtmlElement().find(':submit').button();
		$('#reportUrlFormArea', $form).hide();

		// Update the article element when issue is changed.
		this.fetchArticlesUrl_ = options.fetchArticlesUrl;
		this.$articleSelectElement_ = $(options.articleSelectSelector,
				this.getHtmlElement());
		$issueSelectElement = $(options.issueSelectSelector, this.getHtmlElement());
		$issueSelectElement.change(this.callbackWrapper(this.fetchArticleInfoHandler_));

		// Update the file type element when object type is changed.
		this.fileAssocTypes_ = options.fileAssocTypes;
		this.$fileTypeSelectElement_ = $(options.fileTypeSelectSelector,
				this.getHtmlElement());
		this.$fileTypeSelectElement_.attr("disabled", true);
		$objectTypeSelectElement = $(options.objectTypeSelectSelector,
				this.getHtmlElement());
		$objectTypeSelectElement.change(this.callbackWrapper(
				this.updateFileTypeSelectHandler_));

		// Update the region element when country is changed.
		this.fetchRegionsUrl_ = options.fetchRegionsUrl;
		this.$regionSelectElement_ = $(options.regionSelectSelector,
				this.getHtmlElement());
		$countrySelectElement = $(options.countrySelectSelector,
				this.getHtmlElement());
		$countrySelectElement.change(this.callbackWrapper(
				this.fetchRegionHandler_));


	};
	$.pkp.classes.Helper.inherits(
			$.pkp.statistics.ReportGeneratorFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);

	//
	// Private properties
	//
	/**
	 * The fetch articles title and id url.
	 * @private
	 * @type {?string}
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			fetchArticlesUrl_ = null;

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


	//
	// Protected extended methods.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.handleResponse =
			function(formElement, jsonData) {
		var data = this.handleJson(jsonData);
		if (data !== false && data.reportUrl !== undefined) {
			$('#reportUrlFormArea', this.getHtmlElement()).show().
				find(':input').val(data.reportUrl);

			window.location = data.reportUrl;
		}

		this.parent('handleResponse', formElement, jsonData);
	};


	//
	// Private methods
	//
	/**
	 * Callback called after issue select is changed to fetch
	 * related article title and id.
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.fetchArticleInfoHandler_ =
			function(callingContext, opt_event) {
		var $issueSelectElement, $issueSelectedOptions, issueId;

		this.$articleSelectElement_.empty();

		$issueSelectElement = $(callingContext);
		$issueSelectedOptions = $("option:selected", $issueSelectElement);
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
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			updateArticleSelectCallback_ = function(ajaxContext, jsonData) {
		var $articleSelectElement, limit, content;
		$articleSelectElement = this.$articleSelectElement_;

		jsonData = this.handleJson(jsonData);
		if (jsonData !== false) {
			content = jsonData.content;
			for (i = 0, limit = content.length; i < limit; i++) {
				$articleSelectElement.append(
						$("<option />").val(content[i].id).text(content[i].title));
			}
		}

		return false;
	};


	/**
	 * Callback called after object type select is changed.
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			updateFileTypeSelectHandler_ = function(callingContext, opt_event) {
		var $objectTypeElement, $objectTypeSelectedOptions, assocType, i;

		$objectTypeElement = $(callingContext);
		$objectTypeSelectedOptions = $("option:selected", $objectTypeElement);
		if ($objectTypeSelectedOptions.length == 1) {
			assocType = $objectTypeSelectedOptions[0].value;
			for (i in this.fileAssocTypes_) {
				if (this.fileAssocTypes_[i] == assocType) {
					this.$fileTypeSelectElement_.attr("disabled", false);
					return false;
				}
			}
		}

		this.$fileTypeSelectElement_.attr("disabled", true);
		return false;
	};


	/**
	 * Callback called after country select is changed to fetch
	 * related region info.
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.fetchRegionHandler_ =
			function(callingContext, opt_event) {
		var $countrySelectElement, $countrySelectedOptions, countryId;

		this.$regionSelectElement_.empty();

		$countrySelectElement = $(callingContext);
		$countrySelectedOptions = $("option:selected", $countrySelectElement);
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
	$.pkp.statistics.ReportGeneratorFormHandler.prototype.
			updateRegionSelectCallback_ = function(ajaxContext, jsonData) {
		var $regionSelectElement, limit, content;
		$regionSelectElement = this.$regionSelectElement_;

		$regionSelectElement.empty();

		jsonData = this.handleJson(jsonData);
		if (jsonData !== false) {
			content = jsonData.content;
			for (i = 0, limit = content.length; i < limit; i++) {
				$regionSelectElement.append(
						$("<option />").val(content[i].id).text(content[i].name));
			}
		}

		return false;
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
