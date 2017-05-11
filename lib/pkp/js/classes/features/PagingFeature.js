/**
 * @file js/classes/features/PagingFeature.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PagingFeature
 * @ingroup js_classes_features
 *
 * @brief Feature that implements paging on grids.
 */
(function($) {


	/**
	 * @constructor
	 * @inheritDoc
	 * @extends $.pkp.classes.features.GeneralPagingFeature
	 */
	$.pkp.classes.features.PagingFeature =
			function(gridHandler, options) {
		this.parent(gridHandler, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.PagingFeature,
			$.pkp.classes.features.GeneralPagingFeature);


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.PagingFeature.prototype.init =
			function() {
		this.configPagingLinks_();
		this.configItemsPerPageElement_();
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.PagingFeature.prototype.addFeatureHtml =
			function($gridElement, options) {
		$gridElement.append(options.pagingMarkup);
	};


	//
	// Hooks implementation.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.PagingFeature.prototype.resequenceRows =
			function(sequenceMap) {
		var $rows = this.gridHandler.getRows(),
				extraRowsNum, index,
				options = this.getOptions();
		// Clean any extra rows that might still be visible from old range data.
		extraRowsNum = $rows.length - options.currentItemsPerPage;
		if (extraRowsNum > 0) {
			for (index = 0; index < extraRowsNum; index++) {
				this.gridHandler.deleteElement($rows.first(), true);
			}
		}

		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.PagingFeature.prototype.refreshGrid =
			function(opt_elementId) {
		var options = this.getOptions(), params, $firstRow, $lastRow;

		params = this.gridHandler.getFetchExtraParams();

		params[options.pageParamName] = options.currentPage;
		params[options.itemsPerPageParamName] = options.currentItemsPerPage;

		$firstRow = this.gridHandler.getRows().first();
		$lastRow = this.gridHandler.getRows().last();

		if ($firstRow.length == 0) {
			params.topLimitRowId = 0;
		} else {
			params.topLimitRowId = this.gridHandler.getRowDataId($firstRow);
		}

		if ($lastRow.length == 0) {
			params.bottomLimitRowId = 0;
		} else {
			params.bottomLimitRowId = this.gridHandler.getRowDataId($lastRow);
		}
	
		this.setGridParams(params);

		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.PagingFeature.prototype.replaceElementResponseHandler =
			function(handledJsonData) {
		var rowMarkup, rowDataId, pagingInfo, options, $rows, castJsonData;
		options = this.getOptions();
		castJsonData = /** @type {{deletedRowReplacement: string,
									pagingInfo: string,
									loadLastPage: boolean,
									newTopRow: string}} */
				handledJsonData;
	
		if (castJsonData.deletedRowReplacement != undefined) {
			rowMarkup = handledJsonData.deletedRowReplacement;
			this.gridHandler.insertOrReplaceElement(rowMarkup);
		}

		if (castJsonData.pagingInfo != undefined) {
			pagingInfo = handledJsonData.pagingInfo;
			this.setOptions(pagingInfo);

			$('div.gridPaging', this.getGridHtmlElement()).
					replaceWith(pagingInfo.pagingMarkup);
			this.init();
		}

		if (castJsonData.loadLastPage) {
			this.getGridHtmlElement().trigger('dataChanged');
		}

		if (castJsonData.newTopRow != undefined) {
			// Check if we need to remove one row from the bottom
			// to keep the same range info count value.
			$rows = this.gridHandler.getRows();

			if (options.currentItemsPerPage == $rows.length) {
				this.gridHandler.deleteElement($rows.last(), true);
			}

			rowMarkup = handledJsonData.newTopRow;
			this.gridHandler.insertOrReplaceElement(rowMarkup, true);
		}

		return false;
	};


	//
	// Private helper methods.
	//
	/**
	 * Configure paging links.
	 *
	 * @private
	 */
	$.pkp.classes.features.PagingFeature.prototype.configPagingLinks_ =
			function() {

		var options, $pagingDiv, $links, index, limit, $link, regex, match,
				clickPagesCallback;

		options = this.getOptions();
		$pagingDiv = $('div.gridPaging', this.getGridHtmlElement());

		if ($pagingDiv) {
			clickPagesCallback = this.callbackWrapper(
					function(sourceElement, event) {
						regex = new RegExp('[?&]' + options.pageParamName +
								'(?:=([^&]*))?', 'i');
						match = regex.exec($(event.target).attr('href'));
						if (match != null) {
							options.currentPage = parseInt(match[1], 10);
							this.getGridHtmlElement().trigger('dataChanged');
						}

						// Stop event handling.
						return false;
					}, this);

			$links = $pagingDiv.find('a').
					not('.showMoreItems').not('.showLessItems');
			for (index = 0, limit = $links.length; index < limit; index++) {
				$link = $($links[index]);
				$link.click(clickPagesCallback);
			}
		}
	};


	/**
	 * Configure items per page element.
	 *
	 * @private
	 */
	$.pkp.classes.features.PagingFeature.prototype.configItemsPerPageElement_ =
			function() {

		var options, $pagingDiv, index, limit, $select, itemsPerPageValues,
				changeItemsPerPageCallback;

		options = this.getOptions();
		$pagingDiv = $('div.gridPaging', this.getGridHtmlElement());

		if ($pagingDiv) {
			changeItemsPerPageCallback = this.callbackWrapper(
					function(sourceElement, event) {
						options.currentItemsPerPage = parseInt($('option',
								event.target).filter(':selected').attr('value'), 10);
						// Reset to first page.
						options.currentPage = 1;

						this.getGridHtmlElement().trigger('dataChanged');

						// Stop event handling.
						return false;
					}, this);

			$select = $pagingDiv.find('select.itemsPerPage');
			itemsPerPageValues = [10, 25, 50, 75, 100];
			if ($.inArray(options.defaultItemsPerPage,
					itemsPerPageValues) < 0) {
				itemsPerPageValues.push(options.defaultItemsPerPage);
			}

			itemsPerPageValues.sort(function(a, b) { return a - b; });

			if (options.itemsTotal <= itemsPerPageValues[0]) {
				$('div.gridItemsPerPage', $pagingDiv).hide();
			} else {
				limit = itemsPerPageValues.length - 1;
				for (index = 0; index <= limit; index++) {
					$select.append($('<option value="' + itemsPerPageValues[index] +
							'">' + itemsPerPageValues[index] + '</option>'));
				}
				$select.val(options.currentItemsPerPage.toString());
				$select.change(changeItemsPerPageCallback);
			}
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
