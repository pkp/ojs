/**
 * @file js/controllers/grid/CategoryGridHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryGridHandler
 * @ingroup js_controllers_grid
 *
 * @brief Category grid handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.GridHandler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {Object} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.CategoryGridHandler = function($grid, options) {
		this.parent($grid, options);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.grid.CategoryGridHandler,
			$.pkp.controllers.grid.GridHandler);


	//
	// Public methods.
	//
	/**
	 * Get category id prefix.
	 * @return {string} Category id prefix.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getCategoryIdPrefix =
			function() {
		return this.getGridIdPrefix() + '-category-';
	};


	/**
	 * Get categories tbody element.
	 * @return {jQueryObject} Categories tbody elements.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getCategories =
			function() {
		return $('.category_grid_body:not(.empty)',
				this.getHtmlElement());
	};


	/**
	 * Get a category tbody element by category data id.
	 * @param {string} categoryDataId The category data id.
	 * @return {jQueryObject} Category tbody element.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getCategoryByDataId =
			function(categoryDataId) {
		return $('#' + this.getCategoryIdPrefix() + categoryDataId);
	};


	/**
	 * Get the category row inside a tbody category element. If none element
	 * is passed, get all grid category rows.
	 * @param {jQueryObject} $opt_category Category tbody element.
	 * @return {jQueryObject} Category rows.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getCategoryRow =
			function($opt_category) {
		var $context = this.getHtmlElement();
		if ($opt_category !== undefined) {
			$context = $opt_category;
		}

		return $('tr.category', $context);
	};


	/**
	 * Get rows inside a tbody category element, excluding the category row.
	 * @param {jQueryObject} $category Category tbody element.
	 * @return {jQueryObject} Category rows.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getRowsInCategory =
			function($category) {
		return $('tr.gridRow', $category).not('.category');
	};


	/**
	 * Get the category empty placeholder.
	 * @param {jQueryObject} $category A grid category element.
	 * @return {jQueryObject} The category empty placeholder.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.
			getCategoryEmptyPlaceholder = function($category) {
		var selector = '#' + $category.attr('id') + '-emptyPlaceholder';
		return $(selector, this.getHtmlElement());
	};


	/**
	 * Get the category data id by the passed category element.
	 * @param {jQueryObject} $category Category element.
	 * @return {string} Category data id.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getCategoryDataId =
			function($category) {
		var categoryId = $category.attr('id'),
				startExtractPosition = this.getCategoryIdPrefix().length;

		return /** @type {string} */ (categoryId.slice(startExtractPosition));
	};


	/**
	 * Get the category data id by the passed row element id.
	 * @param {string} gridRowId Category row element id.
	 * @return {string} Category data id.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getCategoryDataIdByRowId =
			function(gridRowId) {
		// Remove the category id prefix to avoid getting wrong data.
		gridRowId = gridRowId.replace(this.getCategoryIdPrefix(), ' ');

		// Get the category data id.
		var categoryDataId = gridRowId.match('(.*)-row');
		return $.trim(categoryDataId[1]);
	};


	/**
         * Get the id prefix of the grid row inside a category.
         * @return {string}
         */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getRowIdPrefix =
			function() {
		return this.getGridIdPrefix() + '-category-';
	};


	/**
	 * Get the grid row by the passed data element id.
	 * @param {number} rowDataId
	 * @param {number=} opt_parentElementId
	 * @return {jQueryObject}
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getRowByDataId =
			function(rowDataId, opt_parentElementId) {
		this.parent('getRowByDataId', rowDataId, opt_parentElementId);
		return $('#' + this.getRowIdPrefix() + opt_parentElementId +
				'-row-' + rowDataId, this.getHtmlElement());
	};


	/**
	 * Get the data element id of the passed grid row.
	 * @param {jQueryObject} $gridRow The grid row JQuery object.
	 * @return {string} The data element id of the passed grid row.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getRowDataId =
			function($gridRow) {
		var rowDataId;
		rowDataId = $gridRow.attr('id').
				slice(this.getRowIdPrefix().length);
		rowDataId = rowDataId.match('-row-(.*)');
		return /** @type {string} */ $.trim(rowDataId[1]);
	};


	/**
	 * Append a category to the end of the list.
	 * @param {jQueryObject} $category Category to append.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.appendCategory =
			function($category) {
		var $gridBody = this.getHtmlElement().find(this.bodySelector);
		$gridBody.append($category);
	};


	/**
	 * Re-sequence all category elements based on the passed sequence map.
	 * @param {Array} sequenceMap A sequence array with the category
	 * element id as value.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.resequenceCategories =
			function(sequenceMap) {
		var categoryId, index, $category;
		for (index in sequenceMap) {
			categoryId = sequenceMap[index];
			$category = $('#' + categoryId);
			this.appendCategory($category);
		}

		this.updateEmptyPlaceholderPosition();
	};


	/**
	 * Move all empty category placeholders to their correct position,
	 * below of each correspondent category element.
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.
			updateEmptyPlaceholderPosition = function() {
		var $categories = this.getCategories(),
				index, limit,
				$category, $emptyPlaceholder;
		for (index = 0, limit = $categories.length; index < limit; index++) {
			$category = $($categories[index]);
			$emptyPlaceholder = this.getCategoryEmptyPlaceholder($category);
			if ($emptyPlaceholder.length > 0) {
				$emptyPlaceholder.insertAfter($category);
			}
		}
	};


	//
	// Extended methods from GridHandler
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.initialize =
			function(options) {
		// Save the URL to fetch a whole category.
		this.fetchCategoryUrl_ = options.fetchCategoryUrl;

		this.parent('initialize', options);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getElementsByType =
			function($element) {
		if ($element.hasClass('category_grid_body')) {
			return this.getCategories();
		} else {
			return /** @type {jQueryObject} */ (
					this.parent('getElementsByType', $element));
		}
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.getEmptyElement =
			function($element) {
		if ($element.hasClass('category_grid_body')) {
			// Return the grid empty element placeholder.
			return this.getHtmlElement().find('.empty').not('.category_placeholder');
		} else {
			return /** @type {jQueryObject} */ (
					this.parent('getEmptyElement', $element));
		}
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.refreshGridHandler =
			function(sourceElement, event, opt_elementId) {

		var fetchedAlready = false, elementIds,
				// Hack to avoid closure compiler warnings on type difference
				castElementId = /** @type {{parentElementId: number}} */ opt_elementId;

		if (opt_elementId !== undefined) {
			// Check if we want to refresh a row inside a category.
			if (castElementId.parentElementId !== undefined) {
				elementIds = {rowId: opt_elementId[0],
					rowCategoryId: castElementId.parentElementId};

				// Store the category id.
				this.currentCategoryId_ = castElementId.parentElementId;

				// Retrieve a single row from the server.
				$.get(this.fetchRowUrl, elementIds,
						this.callbackWrapper(
								this.replaceElementResponseHandler), 'json');
			} else {
				// Retrieve the entire category from the server.
				$.get(this.fetchCategoryUrl_, {rowId: opt_elementId},
						this.callbackWrapper(
								this.replaceElementResponseHandler), 'json');
			}
			fetchedAlready = true;
		}

		this.parent('refreshGridHandler', sourceElement,
				event, opt_elementId, fetchedAlready);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.deleteElement =
			function($element) {

		var $gridBody, index, limit, $parent, $emptyPlaceholder;

		if ($element.length > 1) {
			// Category and category row have the same element data id,
			// handle this case.
			if ($element.length == 2 &&
					$element.hasClass('category_grid_body') &&
					$element.hasClass('category')) {
				// Always delete the entire category.
				$element = $element.filter('.category_grid_body');
			}

			// Sometimes grid rows inside different categories may have
			// the same id. Try to find the correct one to delete.
			if (this.currentCategoryId_) {
				$gridBody = this.getCategoryByDataId(this.currentCategoryId_);
				for (index = 0, limit = $element.length; index < limit; index++) {
					$parent = $($element[index]).
							parents('#' + $gridBody.attr('id'));
					if ($parent.length === 1) {
						$element = $($element[index]);
						break;
					}
				}
			}
		}

		if ($element.hasClass('category_grid_body')) {
			// Need to delete the category empty placeholder.
			$emptyPlaceholder = this.getCategoryEmptyPlaceholder($element);
			$emptyPlaceholder.remove();
		}

		this.parent('deleteElement', $element);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.addElement =
			function($element) {
		var $gridBody = null, categoryDataId, $emptyPlaceholder;

		if ($element.hasClass('gridRow')) {
			// New row must be inside a category.
			categoryDataId = /** @type {string} */ (
					this.getCategoryDataIdByRowId(
					/** @type {string} */ ($element.attr('id'))));
			$gridBody = /** @type {jQueryObject} */ (
					this.getCategoryByDataId(categoryDataId));
		}

		// Add the element.
		this.parent('addElement', $element, $gridBody);

		// Make sure the placeholder is the last grid element.
		if ($element.hasClass('category_grid_body')) {
			$emptyPlaceholder = this.getEmptyElement($element);
			this.getHtmlElement().find(this.bodySelector).append($emptyPlaceholder);
		}
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.replaceElement =
			function($existingElement, $newElement) {

		if ($newElement.hasClass('category_grid_body')) {
			// Need to delete the category empty placeholder.
			var $emptyPlaceholder = this.getCategoryEmptyPlaceholder($existingElement);
			$emptyPlaceholder.remove();
		}

		this.parent('replaceElement', $existingElement, $newElement);

	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.CategoryGridHandler.prototype.hasSameNumOfColumns =
			function($row) {
		var $element = $row,
				checkColSpan = false;

		if ($row.hasClass('category_grid_body')) {
			$element = $row.find('tr');
			checkColSpan = true;
		}

		return /** @type {boolean} */ (
				this.parent('hasSameNumOfColumns', $element, checkColSpan));
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
