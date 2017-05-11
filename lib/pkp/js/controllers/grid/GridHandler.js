/**
 * @defgroup js_controllers_grid
 */
/**
 * @file js/controllers/grid/GridHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridHandler
 * @ingroup js_controllers_grid
 *
 * @brief Grid row handler.
 */
(function($) {

	// Define the namespace.
	$.pkp.controllers.grid = $.pkp.controllers.grid || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {{features}} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.GridHandler = function($grid, options) {
		this.parent($grid, options);

		// We give a chance for this handler to initialize
		// before we initialize its features.
		this.initialize(options);

		this.initFeatures_(options.features);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.grid.GridHandler,
			$.pkp.classes.Handler);


	//
	// Constants.
	//
	/**
	 * Flag to be used to fetch all curent page grid rows.
	 * @public
	 * @type {Object}
	 */
	$.pkp.controllers.grid.GridHandler.FETCH_ALL_ROWS_ID = {};


	//
	// Protected properties
	//
	/**
	 * The selector for the grid body.
	 * @protected
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.bodySelector = null;


	/**
	 * The URL to fetch a grid row.
	 * @protected
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.fetchRowUrl = null;


	/**
	 * The URL to fetch all loaded grid rows.
	 * @protected
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.fetchRowsUrl = null;


	//
	// Private properties
	//
	/**
	 * The id of the grid.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.gridId_ = null;


	/**
	 * The URL to fetch the entire grid.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.fetchGridUrl_ = null;


	/**
	 * This grid features.
	 * @private
	 * @type {Object}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.features_ = null;


	/**
	 * Fetch elements extra request parameters.
	 * @private
	 * @type {Object}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.fetchExtraParams_ = null;


	//
	// Public methods
	//
	/**
	 * Get fetch element extra request parameters.
	 * @return {Object} Extra request parameters.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getFetchExtraParams =
			function() {
		return this.fetchExtraParams_;
	};


	/**
	 * Set fetch element extra request parameters.
	 * @param {Object} extraParams Extra request parameters.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.setFetchExtraParams =
			function(extraParams) {
		this.fetchExtraParams_ = extraParams;
	};


	/**
	 * Get the fetch row URL.
	 * @return {?string} URL to the "fetch row" operation handler.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getFetchRowUrl =
			function() {

		return this.fetchRowUrl;
	};


	/**
	 * Get the fetch rows URL.
	 * @return {?string} URL to the "fetch rows" operation handler.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getFetchRowsUrl =
			function() {

		return this.fetchRowsUrl;
	};


	/**
	 * Get all grid rows.
	 *
	 * @return {jQueryObject} The rows as a JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getRows =
			function() {
		return $('.gridRow', this.getHtmlElement()).not('.gridRowDeleted');
	};


	/**
	 * Get the id prefix of this grid.
	 * @return {string} The ID prefix of this grid.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getGridIdPrefix =
			function() {
		return 'component-' + this.gridId_;
	};


	/**
	 * Get the id prefix of this grid row.
	 * @return {string} The id prefix of this grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getRowIdPrefix =
			function() {
		return this.getGridIdPrefix() + '-row-';
	};


	/**
	 * Get the grid row by the passed data element id.
	 * @param {number} rowDataId
	 * @param {number=} opt_parentElementId
	 * @return {jQueryObject}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getRowByDataId =
			function(rowDataId, opt_parentElementId) {
		return $('#' + this.getRowIdPrefix() + rowDataId, this.getHtmlElement());
	};


	/**
	 * Get the data element id of the passed grid row.
	 * @param {jQueryObject} $gridRow The grid row JQuery object.
	 * @return {string} The data element id of the passed grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getRowDataId =
			function($gridRow) {
		var rowDataId;
		rowDataId = /** @type {string} */ $gridRow.attr('id').
				slice(this.getRowIdPrefix().length);
		return rowDataId;
	};


	/**
	 * Get the parent grid row of the passed element, if any.
	 * @param {jQueryObject} $element The element that is inside the row.
	 * @return {jQueryObject} The element parent grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getParentRow =
			function($element) {
		return $element.parents('.gridRow:first');
	};


	/**
	 * Get the same type elements of the passed element.
	 * @param {jQueryObject} $element The element to get the type from.
	 * @return {jQueryObject} The grid elements with the same type
	 * of the passed element.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getElementsByType =
			function($element) {
		if ($element.hasClass('gridRow')) {
			var $container = $element.parents('tbody:first');
			return $('.gridRow', $container);
		} else {
			return null;
		}
	};


	/**
	 * Get the empty element based on the type of the passed element.
	 * @param {jQueryObject} $element The element to get the type from.
	 * @return {jQueryObject} The empty element.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getEmptyElement =
			function($element) {
		if ($element.hasClass('gridRow')) {
			// Return the rows empty element placeholder.
			var $container = $element.parents('tbody:first');
			return $container.next('.empty');
		} else {
			return null;
		}
	};


	/**
	 * Show/hide row actions.
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.toggleRowActions =
			function(sourceElement, event) {

		// Don't follow the link
		event.preventDefault();

		// Toggle the extras link class.
		$(sourceElement).toggleClass('show_extras');
		$(sourceElement).toggleClass('hide_extras');

		// Toggle the row actions.
		var $controlRow = $(sourceElement).parents('tr').next('.row_controls');
		this.applyToggleRowActionEffect_($controlRow);
	};


	/**
	 * Hide all visible row controls.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.hideAllVisibleRowActions =
			function() {
		this.getHtmlElement().find('a.hide_extras').click();
	};


	/**
	 * Hide row actions div container.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.hideRowActionsDiv =
			function() {
		var $rowActionDivs, index, limit, $div;

		$rowActionDivs = $('.gridRow div.row_actions', this.getHtmlElement());
		$rowActionDivs.hide();

		// FIXME: Hack to correctly align the first column cell content after
		// hiding the row actions div.
		for (index = 0, limit = $rowActionDivs.length; index < limit; index++) {
			$div = $($rowActionDivs[index]);
		}
	};


	/**
	 * Show row actions div container.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.showRowActionsDiv =
			function() {
		var $rowActionDivs, index, limit, $div;

		$rowActionDivs = $('.gridRow div.row_actions', this.getHtmlElement());
		$rowActionDivs.show();
	};


	/**
	 * Enable/disable all link actions inside this grid.
	 * @param {boolean} enable Enable/disable flag.
	 * @param {jQueryObject} $linkElements Link elements JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.changeLinkActionsState =
			function(enable, $linkElements) {
		if ($linkElements === undefined) {
			$linkElements = $('.pkp_controllers_linkAction', this.getHtmlElement());
		}
		$linkElements.each(function() {
			/** {$.pkp.controllers.LinkActionHandler} */
			var linkHandler;
			linkHandler = $.pkp.classes.Handler.getHandler($(this));
			if (enable) {
				linkHandler.enableLink();
			} else {
				linkHandler.disableLink();
			}
		});
	};


	/**
	 * Re-sequence all grid rows based on the passed sequence map.
	 * @param {Array} sequenceMap A sequence array with the row id or
	 * row data id as value.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.resequenceRows =
			function(sequenceMap) {
		var id, index, $row;
		if (!sequenceMap) {
			return;
		}
		for (index in sequenceMap) {
			id = sequenceMap[index];
			$row = $('#' + id);
			if ($row.length == 0) {
				$row = this.getRowByDataId(id);
			}
			if ($row.length == 0) {
				throw new Error('Row with id ' + id + ' not found!');
			}
			this.addElement($row);
		}
		this.updateControlRowsPosition();

		this.callFeaturesHook('resequenceRows', sequenceMap);
	};


	/**
	 * Move all grid control rows to their correct position,
	 * below of each correspondent data grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.updateControlRowsPosition =
			function() {
		var $rows, index, limit, $row, $controlRow;

		$rows = this.getRows();
		for (index = 0, limit = $rows.length; index < limit; index++) {
			$row = $($rows[index]);
			$controlRow = this.getControlRowByGridRow($row);
			if ($controlRow.length > 0) {
				$controlRow.insertAfter($row);
			}
		}
	};


	/**
	 * Inserts or replaces a grid element.
	 * @param {string|jQueryObject} elementContent The new mark-up of the element.
	 * @param {boolean=} opt_prepend Prepend the new row instead of append it?
	 */
	$.pkp.controllers.grid.GridHandler.prototype.insertOrReplaceElement =
			function(elementContent, opt_prepend) {
		var $newElement, newElementId, $grid, $existingElement;

		// Parse the HTML returned from the server.
		$newElement = $(elementContent);
		newElementId = $newElement.attr('id');

		// Does the element exist already?
		$grid = this.getHtmlElement();
		$existingElement = newElementId ? $grid.find('#' + newElementId) : null;

		if ($existingElement !== null && $existingElement.length > 1) {
			throw new Error('There were ' + $existingElement.length +
					' rather than 0 or 1 elements to be replaced!');
		}

		if (!this.hasSameNumOfColumns($newElement)) {
			// Redraw the whole grid so new columns
			// get added/removed to match element.
			$.get(this.fetchGridUrl_, null,
					this.callbackWrapper(this.replaceGridResponseHandler_), 'json');
		} else {
			if ($existingElement !== null && $existingElement.length === 1) {
				// Update element.
				this.replaceElement($existingElement, $newElement);
			} else {
				// Insert row.
				this.addElement($newElement, null, opt_prepend);
			}

			// Refresh row action event binding.
			this.activateRowActions_();
		}
	};


	/**
	 * Delete a grid element.
	 * @param {jQueryObject} $element The element to be deleted.
	 * @param {boolean=} opt_noFadeOut Whether the item deletion
	 * will use the fade out effect or not.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.deleteElement =
			function($element, opt_noFadeOut) {
		var lastElement, $emptyElement, deleteFunction;

		// Check whether we really only match one element.
		if ($element.length !== 1) {
			throw new Error('There were ' + $element.length +
					' rather than 1 element to delete!');
		}

		// Flag this element as deleted, so getRows()
		// will return only existing rows from now on.
		$element.addClass('gridRowDeleted');

		// Check whether this is the last row.
		lastElement = false;
		if (this.getElementsByType($element).length == 1) {
			lastElement = true;
		}

		// Remove the controls row, if any.
		if ($element.hasClass('gridRow')) {
			this.deleteControlsRow_($element);
		}

		$emptyElement = this.getEmptyElement($element);
		deleteFunction = function() {
			$element.remove();
			if (lastElement) {
				$emptyElement.fadeIn(100);
			}
		};

		if (opt_noFadeOut != undefined && opt_noFadeOut) {
			deleteFunction();
		} else {
			$element.fadeOut(500, deleteFunction);
		}
	};


	//
	// Protected methods
	//
	/**
	 * Set data and execute operations to initialize.
	 *
	 * @protected
	 *
	 * @param {Object} options Grid options.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.initialize =
			function(options) {
		var $searchLink;

		// Bind the handler for the "elements changed" event.
		this.bind('dataChanged', this.refreshGridHandler);

		// Bind the handler for the "add new row" event.
		this.bind('addRow', this.addRowHandler_);

		// Handle grid filter events.
		this.bind('formSubmitted', this.refreshGridWithFilterHandler_);

		// Save the ID of this grid.
		this.gridId_ = options.gridId;

		// Save the URL to fetch a row.
		this.fetchRowUrl = options.fetchRowUrl;

		// Save the URL to fetch all rows.
		this.fetchRowsUrl = options.fetchRowsUrl;

		// Save the URL to fetch the entire grid
		this.fetchGridUrl_ = options.fetchGridUrl;

		// Save the selector for the grid body.
		if ($('div.scrollable', this.getHtmlElement()).length > 0) {
			this.bodySelector = 'div.scrollable table';
		} else {
			this.bodySelector = options.bodySelector;
		}

		// Show/hide row action feature.
		this.activateRowActions_();

		this.setFetchExtraParams({});

		// Search control.
		this.getHtmlElement().find('.pkp_form').hide();
		$searchLink = this.getHtmlElement().
				find('.pkp_linkaction_search');
		if ($searchLink.length !== 0) {
			$searchLink.click(
					this.callbackWrapper(function() {
						this.getHtmlElement().find('.pkp_form').toggle();
						$searchLink.toggleClass('is_open');
					}));
		} else {
			// This grid doesn't have an expand/collapse control. If there is
			// a form, expand it.
			this.getHtmlElement().find('.pkp_form').toggle();
		}

		this.trigger('gridInitialized');
	};


	/**
	 * Call features hooks.
	 *
	 * @protected
	 *
	 * @param {string} hookName The name of the hook.
	 * @param {Array|jQueryObject|Object|number|boolean=} opt_args
	 * The arguments array.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.callFeaturesHook =
			function(hookName, opt_args) {
		var featureName;
		if (!$.isArray(opt_args)) {
			opt_args = [opt_args];
		}
		for (featureName in this.features_) {
			this.features_[featureName][hookName].
					apply(this.features_[featureName], opt_args);
		}
	};


	/**
	 * Refresh either a single row of the grid or the whole grid.
	 *
	 * @protected
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @param {number|Object=} opt_elementId The id of a data element that was
	 *  updated, added or deleted. If not given then the whole grid
	 *  will be refreshed.
	 *  @param {Boolean=} opt_fetchedAlready Flag that subclasses can send
	 *  telling that a fetch operation was already handled there.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.refreshGridHandler =
			function(sourceElement, event, opt_elementId, opt_fetchedAlready) {
		var params;

		this.callFeaturesHook('refreshGrid', opt_elementId);

		params = this.getFetchExtraParams();


		// Check if subclasses already handled the fetch of new elements.
		if (!opt_fetchedAlready) {
			if (opt_elementId) {
				if (opt_elementId ==
						$.pkp.controllers.grid.GridHandler.FETCH_ALL_ROWS_ID) {
					$.get(this.fetchRowsUrl, params,
							this.callbackWrapper(this.replaceElementResponseHandler), 'json');
				} else {
					params.rowId = opt_elementId;
					// Retrieve a single row from the server.
					$.get(this.fetchRowUrl, params,
							this.callbackWrapper(this.replaceElementResponseHandler), 'json');
				}
			} else {
				// Retrieve the whole grid from the server.
				$.get(this.fetchGridUrl_, params,
						this.callbackWrapper(this.replaceGridResponseHandler_), 'json');
			}
		}

		// Let the calling context (page?) know that the grids are being redrawn.
		this.trigger('gridRefreshRequested');
		this.publishChangeEvents();
	};


	/**
	 * Add a new row to the grid.
	 *
	 * @protected
	 *
	 * @param {jQueryObject} $newRow The new row to append.
	 * @param {jQueryObject=} opt_$gridBody The tbody container element.
	 * @param {boolean=} opt_prepend Prepend the element instead of append it?
	 */
	$.pkp.controllers.grid.GridHandler.prototype.addElement =
			function($newRow, opt_$gridBody, opt_prepend) {

		if (opt_$gridBody === undefined || opt_$gridBody === null) {
			opt_$gridBody = this.getHtmlElement().find(this.bodySelector);
		}

		// Add the new element.
		if (opt_prepend != undefined && opt_prepend) {
			opt_$gridBody.prepend($newRow);
		} else {
			opt_$gridBody.append($newRow);
		}

		// Hide the empty placeholder.
		var $emptyElement = this.getEmptyElement($newRow);
		$emptyElement.hide();

		this.callFeaturesHook('addElement', $newRow);
	};


	/**
	 * Update an existing element using the passed new element content.
	 *
	 * @protected
	 *
	 * @param {jQueryObject} $existingElement The element that is already
	 *  in grid.
	 * @param {jQueryObject} $newElement The element with new content.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.replaceElement =
			function($existingElement, $newElement) {

		if ($newElement.hasClass('gridRow')) {
			this.deleteControlsRow_($existingElement);
		}

		$existingElement.replaceWith($newElement);
		this.callFeaturesHook('replaceElement', $newElement);
	};


	/**
	 * Does the passed row have a different number of columns than the
	 * existing grid?
	 *
	 * @protected
	 *
	 * @param {jQueryObject} $row The row to be checked against grid columns.
	 * @param {Boolean=} opt_checkColSpan Will get the number of row columns
	 * by column span.
	 * @return {boolean} Whether it has the same number of grid columns
	 * or not.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.hasSameNumOfColumns =
			function($row, opt_checkColSpan) {
		var $grid, numColumns, $tdElements, numCellsInNewRow;

		$grid = this.getHtmlElement();
		numColumns = $grid.find('th').length;
		$tdElements = $row.first().find('td');
		if (opt_checkColSpan) {
			numCellsInNewRow = $tdElements.attr('colspan');
		} else {
			numCellsInNewRow = $tdElements.length;
		}

		return (numColumns == numCellsInNewRow);
	};


	/**
	 * Callback to insert, remove or replace a row after an
	 * element has been inserted, update or deleted.
	 *
	 * @protected
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @return {boolean|undefined} Return false when no replace action is taken.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.replaceElementResponseHandler =
			function(ajaxContext, jsonData) {
		var elementId, $element, handledJsonData, castJsonData, $responseElement,
				$responseRow, $responseControlRow, $responseRows, $responseRowsControls,
				index, limit;

		handledJsonData = this.handleJson(jsonData);
		if (handledJsonData !== false) {
			if (handledJsonData.elementNotFound) {
				// The server reported that this element no
				// longer exists in the database so let's
				// delete it.
				elementId = handledJsonData.elementNotFound;
				$element = this.getRowByDataId(elementId);

				// Sometimes we get a delete event before the
				// element has actually been inserted (e.g. when deleting
				// elements due to a cancel action or similar).
				if ($element.length > 0) {
					this.deleteElement($element);
				}
			} else {
				// The server returned mark-up to replace
				// or insert the row.
				$responseElement = $(handledJsonData.content);
				if ($responseElement.filter("tr:not('.row_controls')").length > 1) {
					$responseRows = $responseElement.filter('tr.gridRow');
					$responseRowsControls = $responseElement.filter('tr.row_controls');
					for (index = 0, limit = $responseRows.length; index < limit; index++) {
						$responseRow = $($responseRows[index]);
						$responseControlRow = this.getControlRowByGridRow($responseRow,
								$responseRowsControls);
						this.insertOrReplaceElement($responseRow.add($responseControlRow));
					}
				} else {
					this.insertOrReplaceElement(handledJsonData.content);
				}

				castJsonData = /** @type {{sequenceMap: Array}} */ handledJsonData;
				this.resequenceRows(castJsonData.sequenceMap);
			}
		}

		this.callFeaturesHook('replaceElementResponseHandler', handledJsonData);
	};


	//
	// Private methods
	//
	/**
	 * Refresh the grid after its filter has changed.
	 *
	 * @private
	 *
	 * @param {$.pkp.controllers.form.ClientFormHandler} filterForm
	 *  The filter form.
	 * @param {Event} event A "formSubmitted" event.
	 * @param {string} filterData Serialized filter data.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.refreshGridWithFilterHandler_ =
			function(filterForm, event, filterData) {

		// Retrieve the grid from the server and add the
		// filter data as form data.
		$.post(this.fetchGridUrl_, filterData,
				this.callbackWrapper(this.replaceGridResponseHandler_), 'json');
	};


	/**
	 * Add a new row to the grid.
	 *
	 * @private
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @param {Object} params The request parameters to use to generate
	 *  the new row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.addRowHandler_ =
			function(sourceElement, event, params) {

		// Retrieve a single new row from the server.
		$.get(this.fetchRowUrl, params,
				this.callbackWrapper(this.replaceElementResponseHandler), 'json');
	};


	/**
	 * Callback to replace a grid's content.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.replaceGridResponseHandler_ =
			function(ajaxContext, jsonData) {
		var handledJsonData, $grid, $gridParent, $newGrid,
				isFilterVisible;

		handledJsonData = this.handleJson(jsonData);
		if (handledJsonData !== false) {
			// Get the grid that we're updating
			$grid = this.getHtmlElement();
			$gridParent = $grid.parent();

			isFilterVisible = $grid.find('.filter').is(':visible');

			// Replace the grid content
			$grid.replaceWith(handledJsonData.content);

			// Update the html element of this handler.
			$newGrid = $('div[id^="' + this.getGridIdPrefix() + '"]', $gridParent);
			this.setHtmlElement($newGrid);

			// Refresh row action event binding.
			this.activateRowActions_();

			if (isFilterVisible) {
				// Open search control again.
				$newGrid.find('.pkp_linkaction_search').click();
			}
		}
	};


	/**
	 * Helper that deletes the row of controls (if present).
	 *
	 * @private
	 *
	 * @param {jQueryObject} $row The row whose matching control row should be
	 *  deleted.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.deleteControlsRow_ =
			function($row) {
		var $controlRow = $('#' + $row.attr('id') + '-control-row',
				this.getHtmlElement());

		if ($controlRow.is('tr') && $controlRow.hasClass('row_controls')) {
			$controlRow.remove();
		}
	};


	/**
	 * Get the control row for the passed the grid row.
	 *
	 * @param {jQueryObject} $gridRow The grid row JQuery object.
	 * @param {jQueryObject=} opt_$context Optional context to get
	 * the control row from.
	 * @return {jQueryObject} The control row JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getControlRowByGridRow =
			function($gridRow, opt_$context) {
		var rowId, controlRowId, $context;

		if (opt_$context === undefined || opt_$context === null) {
			$context = this.getHtmlElement().find('tr');
		} else {
			$context = opt_$context;
		}

		rowId = $gridRow.attr('id');
		controlRowId = rowId + '-control-row';
		return $context.filter('#' + controlRowId);
	};


	/**
	 * Helper that attaches any action handlers related to rows.
	 *
	 * @private
	 */
	$.pkp.controllers.grid.GridHandler.prototype.activateRowActions_ =
			function() {

		var $grid = this.getHtmlElement(),
						$gridRows = this.getHtmlElement().find('tr.gridRow').not('.category');

		$grid.find('a.show_extras').unbind('click').bind('click',
				this.callbackWrapper(this.toggleRowActions));
	};


	/**
	 * Apply the effect for hide/show row actions.
	 *
	 * @private
	 *
	 * @param {jQueryObject} $controlRow The control row JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.applyToggleRowActionEffect_ =
			function($controlRow) {
		var $row;

		$row = $controlRow.prev().find('td:not(.indent_row)');
		$row = $row.add($controlRow.prev());
		$controlRow.toggle();
	};


	/**
	 * Add a grid feature.
	 *
	 * @private
	 *
	 * @param {string} id Feature id.
	 * @param {$.pkp.classes.features.Feature} $feature The grid
	 * feature to be added.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.addFeature_ =
			function(id, $feature) {
		if (!this.features_) {
			this.features_ = [];
		}
		this.features_[id] = $feature;
	};


	/**
	 * Add grid features.
	 *
	 * @private
	 *
	 * @param {Array.<{JSClass, options}>} features The features options array.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.initFeatures_ =
			function(features) {

		var id, $feature, jsClass;

		for (id in features) {
			// Only initiate features that have a js handler.
			jsClass = features[id].JSClass;
			if (jsClass === null) {
				continue;
			}

			$feature =
					/** @type {$.pkp.classes.features.Feature} */
					($.pkp.classes.Helper.objectFactory(
							jsClass, [this, features[id].options]));

			this.addFeature_(id, $feature);
			this.features_[id].init();
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
