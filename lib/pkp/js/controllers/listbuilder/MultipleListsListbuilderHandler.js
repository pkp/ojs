/**
 * @file js/controllers/listbuilder/MultipleListsListbuilderHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MultipleListsListbuilderHandler
 * @ingroup js_controllers_listbuilder
 *
 * @brief Multiple lists listbuilder handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.listbuilder.ListbuilderHandler
	 *
	 * @param {jQueryObject} $listbuilder The listbuilder this handler is
	 *  attached to.
	 * @param {Object} options Listbuilder handler configuration.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler =
			function($listbuilder, options) {
		this.parent($listbuilder, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler,
			$.pkp.controllers.listbuilder.ListbuilderHandler);


	//
	// Private properties
	//
	/**
	 * The list elements of this listbuilder.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			$lists_ = null;


	//
	// Getters and setters.
	//
	/**
	 * Get passed list rows.
	 * @param {jQueryObject} $list JQuery List containing rows.
	 * @return {jQueryObject} JQuery rows objects.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			getRowsByList = function($list) {
		return $list.find('.gridRow');
	};


	/**
	 * Get list elements.
	 * @return {jQueryObject} The JQuery lists.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			getLists = function() {
		return this.$lists_;
	};


	/**
	 * Set list elements based on lists id options.
	 * @param {Array} listsId Array of IDs.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			setLists = function(listsId) {
		var $lists = jQuery(),
				index, $list;

		if (!$.isArray(listsId)) {
			throw new Error('Lists id must be passed using an array object!');
		}

		for (index in listsId) {
			$list = this.getListById(listsId[index]);
			if (this.$lists_) {
				this.$lists_ = this.$lists_.add($list);
			} else {
				this.$lists_ = $list;
			}
		}
	};


	/**
	 * Get the list element by list id.
	 * @param {string} listId List ID.
	 * @return {jQueryObject} List element.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			getListById = function(listId) {
		var listElementId = this.getGridIdPrefix() + '-table-' + listId;
		return $('#' + listElementId, this.getHtmlElement());
	};


	/**
	 * Get the list element of the passed row.
	 * @param {jQueryObject} $row JQuery row object.
	 * @return {jQueryObject} List JQuery element.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			getListByRow = function($row) {
		return $row.parents('table:first');
	};


	/**
	 * Get the passed row list id.
	 * @param {jQueryObject} $row JQuery row object.
	 * @return {string} List ID.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			getListIdByRow = function($row) {
		var $list = this.getListByRow($row);
		return this.getListId($list);
	};


	/**
	 * Get the passed list id.
	 * @param {jQueryObject} $list JQuery list object.
	 * @return {string} List ID.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			getListId = function($list) {
		var idPrefix = this.getGridIdPrefix() + '-table-',
				listElementId = $list.attr('id');

		return /** @type {string} */ (listElementId.slice(idPrefix.length));
	};


	/**
	 * Get no items row inside the passed list.
	 * @param {jQueryObject} $list JQuery list object.
	 * @return {jQueryObject} JQuery "no items" row.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			getListNoItemsRow = function($list) {
		return $list.find('tr.empty');
	};


	//
	// Protected methods.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			initialize = function(options) {
		this.parent('initialize', options);
		this.setLists(/** @type {{listsId: string}} */ options.listsId);
	};


	//
	// Public methods.
	//
	/**
	 * Show/hide the no items row, based on the number of grid rows
	 * inside the passed list.
	 * @param {jQueryObject} $list JQuery elements to scan.
	 * @param {number} limit The minimum number of elements inside the list to
	 * show the no items row.
	 * @param {string} $filterSelector optional Selector to filter the rows that
	 * this method will consider as list rows. If not passed, all grid rows inside
	 * the passed list will be considered.
	 */
	$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler.prototype.
			toggleListNoItemsRow = function($list, limit, $filterSelector) {
		var $noItemsRow = this.getListNoItemsRow($list),
				$listRows = this.getRowsByList($list);

		if ($filterSelector) {
			$listRows = $listRows.not($filterSelector);
		}
		if ($listRows.length == limit) {
			$noItemsRow.detach();
			$list.append($noItemsRow);
			$noItemsRow.show();
		} else {
			$noItemsRow.detach();
			$list.append($noItemsRow);
			$noItemsRow.hide();
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
