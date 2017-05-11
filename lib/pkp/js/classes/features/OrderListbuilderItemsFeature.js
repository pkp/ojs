/**
 * @file js/classes/features/OrderListbuilderItemsFeature.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrderListbuilderItemsFeature
 * @ingroup js_classes_features
 *
 * @brief Feature for ordering grid items.
 */
(function($) {


	/**
	 * @constructor
	 * @inheritDoc
	 * @extends $.pkp.classes.features.OrderItemsFeature
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature =
			function(gridHandler, options) {
		this.parent(gridHandler, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.OrderListbuilderItemsFeature,
			$.pkp.classes.features.OrderItemsFeature);


	//
	// Extended methods from OrderItemsFeature.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.addFeatureHtml =
			function($gridElement, options) {

		var $itemSequenceInput, $gridRows, index, limit, $gridRow,
				$itemSequenceInputClone;

		this.parent('addFeatureHtml', $gridElement, options);

		$itemSequenceInput = this.getSequenceInput_();
		$gridRows = this.gridHandler.getRows();
		for (index = 0, limit = $gridRows.length; index < limit; index++) {
			$gridRow = $($gridRows[index]);
			$itemSequenceInputClone = $itemSequenceInput.clone();

			$('td.first_column', $gridRow).append($itemSequenceInputClone);
		}
	};


	/**
	 * Set up the sortable plugin.
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.
			setupSortablePlugin = function() {
		this.applySortPlgOnElements(
				this.getGridHtmlElement(), 'tr.orderable', null);
	};


	//
	// Extended methods from ToggleableOrderItemsFeature.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.init =
			function() {
		this.parent('init');
		this.toggleItemsDragMode();
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.toggleState =
			function(isOrdering) {
		this.parent('toggleState', isOrdering);
		this.toggleContentHandlers_();
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.storeRowOrder =
			function(index, $row) {
		var seq = index + 1,
				$orderableInput = $row.find('.itemSequence'),
				$modifiedInput;

		$orderableInput.attr('value', seq);
		$modifiedInput = $row.find('.isModified');
		$modifiedInput.attr('value', 1);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.saveOrderHandler =
			function() {
		this.parent('saveOrderHandler');
		this.toggleState(false);

		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.
			updateOrderCallback = function(contextElement, event, ui) {

		var $rows;
		this.parent('updateOrderCallback');
		$rows = this.gridHandler.getRows();
		this.storeOrder($rows);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.
			clickOrderHandler = function() {
		var $selects = $('select:visible', this.getGridHtmlElement()),
				index, limit;
		if ($selects.length > 0) {
			for (index = 0, limit = $selects.length; index < limit; index++) {
				this.gridHandler.saveRow($($selects[index]).parents('.gridRow'));
			}
		}

		return /** @type {boolean} */ (this.parent('clickOrderHandler'));
	};


	//
	// Implemented Feature template hook methods.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.addElement =
			function($newElement) {
		this.parent('addElement', $newElement);
		this.formatAndStoreNewRow_($newElement);
		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.replaceElement =
			function($newContent) {
		this.parent('replaceElement', $newContent);
		this.formatAndStoreNewRow_($newContent);
		return false;
	};


	//
	// Private helper methods.
	//
	/**
	 * Get the sequence input html element.
	 * @private
	 * @return {jQueryObject} Sequence input.
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.
			getSequenceInput_ = function() {
		return $('<input type="hidden" name="newRowId[sequence]" ' +
				'class="itemSequence" />');
	};


	/**
	 * Enable/disable row content handlers.
	 * @private
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.
			toggleContentHandlers_ = function() {
		var $rows = this.gridHandler.getRows(),
				index, limit, $row;
		for (index = 0, limit = $rows.length; index < limit; index++) {
			$row = $($rows[index]);
			if (this.isOrdering) {
				$row.find('.gridCellDisplay').unbind('click');
			} else {
				this.gridHandler.attachContentHandlers_($row);
			}
		}
	};


	/**
	 * Format and store new row.
	 * @private
	 * @param {jQueryObject} $row The new row element.
	 */
	$.pkp.classes.features.OrderListbuilderItemsFeature.prototype.
			formatAndStoreNewRow_ = function($row) {

		var $rows;

		$row.children().after(this.getSequenceInput_());
		$rows = this.gridHandler.getRows();
		this.storeOrder($rows);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
