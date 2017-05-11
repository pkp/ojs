/**
 * @file js/classes/features/OrderMultipleListsItemsFeature.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrderMultipleListsItemsFeature
 * @ingroup js_classes_features
 *
 * @brief Feature for ordering grid items.
 */
(function($) {


	/**
	 * @constructor
	 * @inheritDoc
	 * @extends $.pkp.classes.features.OrderListbuilderItemsFeature
	 */
	$.pkp.classes.features.OrderMultipleListsItemsFeature =
			function(gridHandler, options) {
		this.parent(gridHandler, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.OrderMultipleListsItemsFeature,
			$.pkp.classes.features.OrderListbuilderItemsFeature);


	//
	// Extended methods from Feature.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderMultipleListsItemsFeature.prototype.addFeatureHtml =
			function($gridElement, options) {

		var $listInput, $gridRows, index, limit, $row, listId, $listInputClone;
		this.parent('addFeatureHtml', $gridElement, options);

		$listInput = $('<input type="hidden" name="newRowId[listId]" ' +
				'class="itemList" />');
		$gridRows = this.gridHandler.getRows();
		for (index = 0, limit = $gridRows.length; index < limit; index++) {
			$row = $($gridRows[index]);
			listId = this.gridHandler.getListIdByRow($row);
			$listInputClone = $listInput.clone();
			$listInputClone.attr('value', listId);
			$('td.first_column', $row).append($listInputClone);
		}
	};


	//
	// Extended methods from OrderListbuilderItemsFeature.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderMultipleListsItemsFeature.prototype.storeRowOrder =
			function(index, $row) {

		var $listInput, listId;

		this.parent('storeRowOrder', index, $row);

		$listInput = $row.find('.itemList');
		listId = this.gridHandler.getListIdByRow($row);
		$listInput.attr('value', listId);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderMultipleListsItemsFeature.prototype.
			setupSortablePlugin = function() {

		var $lists = this.gridHandler.getLists().find('tbody'),
				extraParams = {connectWith: $lists};

		this.applySortPlgOnElements($lists, 'tr.orderable', extraParams);
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderMultipleListsItemsFeature.prototype.
			dragStartCallback = function(contextElement, event, ui) {
		var $list = this.gridHandler.getListByRow(ui.item);
		this.gridHandler.toggleListNoItemsRow(
				$list, 1, '.ui-sortable-placeholder, .ui-sortable-helper');
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderMultipleListsItemsFeature.prototype.
			dragStopCallback = function(contextElement, event, ui) {
		var $list = this.gridHandler.getListByRow(ui.item);
		this.gridHandler.toggleListNoItemsRow($list, 0, null);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
