/**
 * @file js/classes/features/OrderCategoryGridItemsFeature.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrderCategoryGridItemsFeature
 * @ingroup js_classes_features
 *
 * @brief Feature for ordering category grid items.
 */
(function($) {


	/**
	 * @constructor
	 * @inheritDoc
	 * @extends $.pkp.classes.features.OrderGridItemsFeature
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature =
			function(gridHandler, options) {
		this.parent(gridHandler, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.OrderCategoryGridItemsFeature,
			$.pkp.classes.features.OrderGridItemsFeature);


	//
	// Extended methods from OrderItemsFeature.
	//
	/**
	 * Setup the sortable plugin.
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.
			setupSortablePlugin = function() {

		var $categories, index, limit, $category, userAgent;

		this.applySortPlgOnElements(
				this.getGridHtmlElement(), 'tbody.orderable', null);

		// FIXME *7610*: IE8 can't handle well ordering in both categories and
		// category rows.
		userAgent = navigator.userAgent.toLowerCase();
		if (/msie/.test(userAgent) &&
				parseInt(userAgent.substr(userAgent.indexOf('msie') + 5, 1), 10) <= 8) {
			return;
		}

		$categories = this.gridHandler.getCategories();
		for (index = 0, limit = $categories.length; index < limit; index++) {
			$category = $($categories[index]);
			this.applySortPlgOnElements($category, 'tr.orderable', null);
		}
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.
			saveOrderHandler = function() {
		this.gridHandler.updateEmptyPlaceholderPosition();
		this.parent('saveOrderHandler');

		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.
			cancelOrderHandler = function() {

		var categorySequence = this.getCategorySequence_(this.itemsOrder);
		this.parent('cancelOrderHandler');
		this.gridHandler.resequenceCategories(categorySequence);

		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.
			toggleItemsDragMode = function() {
		this.parent('toggleItemsDragMode');

		var isOrdering = this.isOrdering,
				$categories = this.gridHandler.getCategories(),
				index, limit, $category;

		for (index = 0, limit = $categories.length; index < limit; index++) {
			$category = $($categories[index]);
			this.toggleCategoryDragMode_($category);
		}
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.
			addOrderingClassToRows = function() {

		var options = this.getOptions(),
				type = options.type, $categories;

		if (type == $.pkp.cons.ORDER_CATEGORY_GRID_CATEGORIES_ONLY ||
				type == $.pkp.cons.ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS) {
			$categories = this.gridHandler.getCategories();
			$categories.addClass('orderable');
		}

		if (type == $.pkp.cons.ORDER_CATEGORY_GRID_CATEGORIES_ROWS_ONLY ||
				type == $.pkp.cons.ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS) {
			this.parent('addOrderingClassToRows');
		}

		// We don't want to order category rows tr elements, so
		// remove any style that might be added by calling parent.
		this.gridHandler.getCategoryRow().removeClass('orderable');
	};


	//
	// Overriden method from OrderGridItemsFeature
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.getItemsDataId =
			function() {
		var categoriesSeq = this.getCategorySequence_(this.itemsOrder),
				itemsDataId = [],
				index, limit,
				$category, categoryRowsDataId, categoryDataId;

		for (index = 0, limit = categoriesSeq.length; index < limit; index++) {
			$category = $('#' + categoriesSeq[index]);
			categoryRowsDataId = this.getRowsDataId($category);
			categoryDataId = this.gridHandler.getCategoryDataId($category);
			itemsDataId.push(
					{'categoryId': categoryDataId, 'rowsId': categoryRowsDataId });
		}

		return itemsDataId;
	};


	//
	// Private helper methods.
	//
	/**
	 * Enable/disable category drag mode.
	 * @param {jQueryObject} $category Category to set mode on.
	 * @private
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.
			toggleCategoryDragMode_ = function($category) {
		var isOrdering = this.isOrdering,
				$categoryRow = this.gridHandler.getCategoryRow($category),
				$categoryRowColumn = $('td:first', $categoryRow),
				moveClasses = this.getMoveItemClasses();

		if (isOrdering) {
			$categoryRowColumn.addClass(moveClasses);
		} else {
			$categoryRowColumn.removeClass(moveClasses);
		}
	};


	/**
	 * Get the categories sequence, based on the passed items order.
	 * @param {Array} itemsOrder Items order.
	 * @return {Array} A sequence array with the category data id as values.
	 * @private
	 */
	$.pkp.classes.features.OrderCategoryGridItemsFeature.prototype.
			getCategorySequence_ = function(itemsOrder) {
		var index, limit, categorySequence = [], categoryDataId, categoryId;
		for (index = 0, limit = itemsOrder.length; index < limit; index++) {
			categoryDataId = this.gridHandler
					.getCategoryDataIdByRowId(itemsOrder[index]);
			categoryId = this.gridHandler.getCategoryIdPrefix() + categoryDataId;
			if ($.inArray(categoryId, categorySequence) > -1) {
				continue;
			}
			categorySequence.push(categoryId);
		}

		return categorySequence;
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
