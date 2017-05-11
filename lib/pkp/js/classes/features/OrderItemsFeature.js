/**
 * @file js/classes/features/OrderItemsFeature.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrderItemsFeature
 * @ingroup js_classes_features
 *
 * @brief Base feature class for ordering grid items.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @param {jQueryObject} gridHandler The handler of
	 *  the grid element that this feature is attached to.
	 * @param {Object} options Configuration of this feature.
	 * @extends $.pkp.classes.features.Feature
	 */
	$.pkp.classes.features.OrderItemsFeature =
			function(gridHandler, options) {
		this.parent(gridHandler, options);

		this.$orderButton_ = $('.pkp_linkaction_orderItems',
				this.getGridHtmlElement());
		this.$finishControl_ = $('.order_finish_controls', this.getGridHtmlElement());

		if (this.$orderButton_.length === 0) {
			// No order button, it will always stay in ordering mode.
			this.isOrdering = true;
		}

		this.itemsOrder = [];
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.OrderItemsFeature,
			$.pkp.classes.features.Feature);


	//
	// Protected properties
	//
	/**
	 * Item sequence.
	 * @protected
	 * @type {Array}
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.itemsOrder = null;


	/**
	 * Flag to control if user is ordering items.
	 * @protected
	 * @type {boolean}
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.isOrdering = false;


	//
	// Private properties.
	//
	/**
	 * Initiate ordering state button.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.$orderButton_ = null;


	/**
	 * Cancel ordering state button.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.$cancelButton_ = null;


	/**
	 * Save ordering state button.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.$saveButton_ = null;


	/**
	 * Ordering finish control.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.$finishControl_ = null;


	//
	// Getters and setters.
	//
	/**
	 * Get the order button.
	 * @return {jQueryObject} The order button JQuery object.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.getOrderButton =
			function() {
		return this.$orderButton_;
	};


	/**
	 * Get the finish control.
	 * @return {jQueryObject} The JQuery "finish" control.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.getFinishControl =
			function() {
		return this.$finishControl_;
	};


	/**
	 * Get save order button.
	 *
	 * @return {jQueryObject} The "save order" JQuery object.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.getSaveOrderButton =
			function() {
		return this.getFinishControl().find('.saveButton');
	};


	/**
	 * Get cancel order link.
	 *
	 * @return {jQueryObject} The "cancel order" JQuery control.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.getCancelOrderButton =
			function() {
		return this.getFinishControl().find('.cancelFormButton');
	};


	/**
	 * Get the move item row action element selector.
	 * @return {string} Return the element selector.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.
			getMoveItemRowActionSelector = function() {
		return '.orderable .pkp_linkaction_moveItem';
	};


	/**
	 * Get the css classes used to stylize the ordering items.
	 * @return {string} CSS classes.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.getMoveItemClasses =
			function() {
		return 'pkp_helpers_moveicon ordering';
	};


	//
	// Public template methods.
	//
	/**
	 * Called every time user start dragging an item.
	 * @param {jQueryObject} contextElement The element this event occurred for.
	 * @param {Event} event The drag/drop event.
	 * @param {Object} ui Object with data related to the event elements.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.dragStartCallback =
			function(contextElement, event, ui) {
		// The default implementation does nothing.
	};


	/**
	 * Called every time user stop dragging an item.
	 * @param {jQueryObject} contextElement The element this event occurred for.
	 * @param {Event} event The drag/drop event.
	 * @param {Object} ui Object with data related to the event elements.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.dragStopCallback =
			function(contextElement, event, ui) {
		// The default implementation does nothing.
	};


	/**
	 * Called every time sequence is changed.
	 * @param {jQueryObject} contextElement The element this event occurred for.
	 * @param {Event} event The drag/drop event.
	 * @param {Object} ui Object with data related to the event elements.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.updateOrderCallback =
			function(contextElement, event, ui) {
		// The default implementation does nothing.
	};


	//
	// Extended methods from Feature
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.init =
			function() {

		this.addOrderingClassToRows();
		this.toggleMoveItemRowAction(this.isOrdering);
		this.getGridHtmlElement().find('div.order_message').hide();

		this.toggleOrderLink_();
		if (this.isOrdering) {
			this.setupSortablePlugin();
		}
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.addFeatureHtml =
			function($gridElement, options) {
		var castOptions = /** @type {{orderFinishControls: string?,
				orderMessage: string?}} */ (options),
				$orderFinishControls, orderMessageHtml, $gridRows;
		if (castOptions.orderFinishControls !== undefined) {
			$orderFinishControls = $(castOptions.orderFinishControls);
			$gridElement.find('table').last().after($orderFinishControls);
			$orderFinishControls.hide();
		}

		if (castOptions.orderMessage !== undefined) {
			orderMessageHtml = castOptions.orderMessage;
			$gridRows = $gridElement.find('.gridRow').filter(function(index, element) {
				return !Boolean($(this).find('a.pkp_linkaction_moveItem').length);
			});
			$gridRows.find('td:first-child').prepend(orderMessageHtml);
		}

		this.updateOrderLinkVisibility_();
	};


	//
	// Protected template methods.
	//
	/**
	 * Add orderable class to grid rows.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.addOrderingClassToRows =
			function() {
		// Add ordering class to grid rows.
		var $gridRows = this.gridHandler.getRows().filter(function(index, element) {
			return $(this).find('a.pkp_linkaction_moveItem').length;
		});
		$gridRows.addClass('orderable');
	};


	/**
	 * Setup the sortable plugin. Must be implemented in subclasses.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.setupSortablePlugin =
			function() {
		// Default implementation does nothing.
	};


	/**
	 * Called every time storeOrder is called. This is a chance to subclasses
	 * execute operations with each row that has their sequence being saved.
	 * @param {number} index The current row index position inside the rows
	 * jQuery object.
	 * @param {jQueryObject} $row Row for which to store the sequence.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.storeRowOrder =
			function(index, $row) {
		// The default implementation does nothing.
	};


	//
	// Protected methods.
	//
	/**
	 * Initiate ordering button click event handler.
	 * @return {boolean} Always returns false.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.clickOrderHandler =
			function() {
		this.gridHandler.hideAllVisibleRowActions();
		this.storeOrder(this.gridHandler.getRows());
		this.toggleState(true);
		return false;
	};


	/**
	 * Save order handler.
	 * @return {boolean} Return false to stop click event processing.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.saveOrderHandler =
			function() {
		var $rows;

		this.gridHandler.updateControlRowsPosition();
		this.unbindOrderFinishControlsHandlers_();
		$rows = this.gridHandler.getRows();
		this.storeOrder($rows);

		return false;
	};


	/**
	 * Cancel ordering action click event handler.
	 * @return {boolean} Always returns false.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.cancelOrderHandler =
			function() {
		this.gridHandler.resequenceRows(this.itemsOrder);
		this.toggleState(false);
		return false;
	};


	/**
	 * Execute all operations necessary to change the state of the
	 * ordering process (enabled or disabled).
	 * @param {boolean} isOrdering Is ordering process active?
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.toggleState =
			function(isOrdering) {
		this.isOrdering = isOrdering;
		this.toggleGridLinkActions_();
		this.toggleOrderLink_();
		this.toggleFinishControl_();
		this.toggleItemsDragMode();
		this.setupSortablePlugin();
		this.setupNonOrderableMessage_();
	};


	/**
	 * Set rows sequence store, using
	 * the sequence of the passed items.
	 *
	 * @param {jQueryObject} $rows The rows to be used to get the sequence
	 *   information.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.storeOrder =
			function($rows) {
		var index, limit, $row, elementId;
		this.itemsOrder = [];
		for (index = 0, limit = $rows.length; index < limit; index++) {
			$row = $($rows[index]);
			elementId = $row.attr('id');

			this.itemsOrder.push(elementId);

			// Give a chance to subclasses do extra operations to store
			// the current row order.
			this.storeRowOrder(index, $row);
		}
	};


	/**
	 * Enable/disable the items drag mode.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.toggleItemsDragMode =
			function() {
		var isOrdering = this.isOrdering,
				$rows = this.gridHandler.getRows(),
				$orderableRows = $rows.filter('.orderable'),
				moveClasses = this.getMoveItemClasses();

		if (isOrdering) {
			$orderableRows.addClass(moveClasses);
		} else {
			$orderableRows.removeClass(moveClasses);
		}

		this.toggleMoveItemRowAction(isOrdering);
	};


	/**
	 * Apply (disabled or enabled) the sortable plugin on passed elements.
	 * @param {jQueryObject} $container The element that contain all the orderable
	 *   items.
	 * @param {string} itemsSelector The jQuery selector for orderable items.
	 * @param {Object?} extraParams Optional set of extra parameters for sortable.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.applySortPlgOnElements =
			function($container, itemsSelector, extraParams) {
		var isOrdering = this.isOrdering,
				dragStartCallback = this.gridHandler.callbackWrapper(
						this.dragStartCallback, this),
				dragStopCallback = this.gridHandler.callbackWrapper(
						this.dragStopCallback, this),
				orderItemCallback = this.gridHandler.callbackWrapper(
						this.updateOrderCallback, this),
				config = {
					disabled: !isOrdering,
					items: itemsSelector,
					activate: dragStartCallback,
					deactivate: dragStopCallback,
					update: orderItemCallback,
					tolerance: 'pointer'};

		if (typeof extraParams === 'object') {
			config = $.extend(true, config, extraParams);
		}

		$container.sortable(config);
	};


	/**
	 * Get the data element id of all rows inside the passed
	 * container, in the current order.
	 * @param {jQueryObject} $rowsContainer The element that contains the rows
	 * that will be used to retrieve the id.
	 * @return {Array} A sequence array with data element ids as values.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.getRowsDataId =
			function($rowsContainer) {
		var index, rowDataIds = [], $row, rowDataId;
		for (index in this.itemsOrder) {
			$row = $('#' + this.itemsOrder[index], $rowsContainer);
			if ($row.length < 1) {
				continue;
			}
			rowDataId = this.gridHandler.getRowDataId($row);
			rowDataIds.push(rowDataId);
		}

		return rowDataIds;
	};


	/**
	 * Show/hide the move item row action (position left).
	 * @param {boolean} enable New enable state.
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.toggleMoveItemRowAction =
			function(enable) {
		var $grid = this.getGridHtmlElement(),
				$actionsContainer = $('div.row_actions', $grid),
				allLinksButMoveItemSelector = 'a:not(' +
						this.getMoveItemRowActionSelector() + ')',
				$actions = $actionsContainer.find(allLinksButMoveItemSelector),
				$moveItemRowAction = $(this.getMoveItemRowActionSelector(), $grid),
				$rowActionsContainer, $rowActions;

		if (enable) {
			$actions.addClass('pkp_helpers_display_none');
			$moveItemRowAction.show();
			// Make sure row actions div is visible.
			this.gridHandler.showRowActionsDiv();
		} else {
			$actions.removeClass('pkp_helpers_display_none');

			$rowActionsContainer = $('.gridRow div.row_actions', $grid);
			$rowActions = $rowActionsContainer.
					find(allLinksButMoveItemSelector);
			if ($rowActions.length === 0) {
				// No link action to show, hide row actions div.
				this.gridHandler.hideRowActionsDiv();
			}
			$moveItemRowAction.hide();
		}
	};


	//
	// Hooks implementation.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.addElement =
			function($element) {
		this.addOrderingClassToRows();
		this.toggleItemsDragMode();
		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.replaceElement =
			function($content) {
		this.addOrderingClassToRows();
		this.toggleItemsDragMode();
		return false;
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.
			replaceElementResponseHandler = function(handledJsonData) {
		this.updateOrderLinkVisibility_();
		this.setupNonOrderableMessage_();
		return false;
	};


	//
	// Private helper methods.
	//
	/**
	 * Make sure that the order action visibility state is correct,
	 * based on the grid rows number.
	 * @private
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.
			updateOrderLinkVisibility_ = function() {
		var $orderLink = $('.pkp_linkaction_orderItems', this.getGridHtmlElement());
		if (this.gridHandler.getRows().length <= 1) {
			$orderLink.hide();
		} else {
			$orderLink.show();
		}
	};


	/**
	 * Set the state of the grid link actions, based on current ordering state.
	 * @private
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.toggleGridLinkActions_ =
			function() {
		var isOrdering = this.isOrdering,
				// We want to enable/disable all link actions, except this
				// features controls.
				$gridLinkActions = $('.pkp_controllers_linkAction',
						this.getGridHtmlElement()).not(
								this.getMoveItemRowActionSelector()).not(
								this.getOrderButton()).not(
								this.getFinishControl().find('*'));

		this.gridHandler.changeLinkActionsState(!isOrdering, $gridLinkActions);
	};


	/**
	 * Enable/disable the order link action.
	 * @private
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.toggleOrderLink_ =
			function() {
		if (this.isOrdering) {
			this.$orderButton_.unbind('click');
			this.$orderButton_.attr('disabled', 'disabled');
		} else {
			var clickHandler = this.gridHandler.callbackWrapper(
					this.clickOrderHandler, this);
			this.$orderButton_.click(clickHandler);
			this.$orderButton_.removeAttr('disabled');
		}
	};


	/**
	 * Show/hide the ordering process finish control, based
	 * on the current ordering state.
	 * @private
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.toggleFinishControl_ =
			function() {
		if (this.isOrdering) {
			this.bindOrderFinishControlsHandlers_();
			this.getFinishControl().slideDown(300);
		} else {
			this.unbindOrderFinishControlsHandlers_();
			this.getFinishControl().slideUp(300);
		}
	};


	/**
	 * Bind event handlers to the controls that finish the
	 * ordering action (save and cancel).
	 * @private
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.
			bindOrderFinishControlsHandlers_ = function() {
		var $saveButton = this.getSaveOrderButton(),
				$cancelLink = this.getCancelOrderButton(),
				cancelLinkHandler = this.gridHandler.callbackWrapper(
						this.cancelOrderHandler, this),
				saveButtonHandler = this.gridHandler.callbackWrapper(
						this.saveOrderHandler, this);

		$saveButton.click(saveButtonHandler);
		$cancelLink.click(cancelLinkHandler);
	};


	/**
	 * Unbind event handlers from the controls that finish the
	 * ordering action (save and cancel).
	 * @private
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.
			unbindOrderFinishControlsHandlers_ = function() {

		this.getSaveOrderButton().unbind('click');
		this.getCancelOrderButton().unbind('click');
	};


	/**
	 * Toggle hover action to show message for non orderable
	 * grid rows.
	 * @private
	 */
	$.pkp.classes.features.OrderItemsFeature.prototype.
			setupNonOrderableMessage_ = function() {
		if (this.isOrdering) {
			this.gridHandler.getRows().hover(function() {
				$(this).find('div.order_message').toggle();
			});
		} else {
			this.gridHandler.getRows().unbind('mouseenter mouseleave');
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
