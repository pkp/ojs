<?php

/**
 * @file classes/controllers/grid/feature/InfiniteScrollingFeature.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InfiniteScrollingFeature
 * @ingroup controllers_grid_feature
 *
 * @brief Add infinite scrolling functionality to grids. It doesn't support
 * category grids.
 *
 */

import('lib.pkp.classes.controllers.grid.feature.GeneralPagingFeature');

class InfiniteScrollingFeature extends GeneralPagingFeature {

	/**
	 * @copydoc GeneralPagingFeature::GeneralPagingFeature()
	 * Constructor.
	 */
	function __construct($id = 'infiniteScrolling', $itemsPerPage = null) {
		parent::__construct($id, $itemsPerPage);
	}


	//
	// Extended methods from GridFeature.
	//
	/**
	 * @copydoc GridFeature::getJSClass()
	 */
	function getJSClass() {
		return '$.pkp.classes.features.InfiniteScrollingFeature';
	}

	/**
	 * @copydoc GridFeature::fetchUIElements()
	 */
	function fetchUIElements($request, $grid) {
		$options = $this->getOptions();

		$shown = $options['currentItemsPerPage'] * $options['currentPage'];
		if ($shown > $options['itemsTotal']) $shown = $options['itemsTotal'];

		$moreItemsLinkAction = false;
		if ($shown < $options['itemsTotal']) {
			import('lib.pkp.classes.linkAction.request.NullAction');
			$moreItemsLinkAction = new LinkAction(
				'moreItems',
				new NullAction(),
				__('grid.action.moreItems'),
				'more_items'
			);
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'iterator' => $this->getItemIterator(),
			'shown' => $shown,
			'grid' => $grid,
			'moreItemsLinkAction' => $moreItemsLinkAction,
		));

		return array(
			'pagingMarkup' => $templateMgr->fetch('controllers/grid/feature/infiniteScrolling.tpl'),
		);
	}


	//
	// Hooks implementation.
	//
	/**
	 * @copydoc GridFeature::fetchRows()
	 */
	function fetchRows($args) {
		$request = $args['request'];
		$grid = $args['grid'];
		$jsonMessage = $args['jsonMessage'];

		// Render the paging options, including updated markup.
		$this->setOptions($request, $grid);
		$pagingAttributes = array('pagingInfo' => $this->getOptions());

		// Add paging attributes to json so grid can update UI.
		$additionalAttributes = (array) $jsonMessage->getAdditionalAttributes();
		$jsonMessage->setAdditionalAttributes(array_merge(
			$pagingAttributes,
			$additionalAttributes)
		);
	}

	/**
	 * @copydoc GridFeature::fetchRow()
	 * Check if user really deleted a row and fetch the last one from
	 * the current page.
	 */
	function fetchRow($args) {
		$request = $args['request'];
		$grid = $args['grid'];
		$row = $args['row'];
		$jsonMessage = $args['jsonMessage'];
		$pagingAttributes = array();

		// Render the paging options, including updated markup.
		$this->setOptions($request, $grid);
		$pagingAttributes['pagingInfo'] = $this->getOptions();

		if (is_null($row)) {
			$gridData = $grid->getGridDataElements($request);

			// Get the last data element id of the current page.
			end($gridData);
			$lastRowId = key($gridData);

			// Get the row and render it.
			$args = array('rowId' => $lastRowId);
			$row = $grid->getRequestedRow($request, $args);
			$pagingAttributes['deletedRowReplacement'] = $grid->renderRow($request, $row);
		} else {
			// No need for paging markup.
			unset($pagingAttributes['pagingInfo']['pagingMarkup']);
		}

		// Add paging attributes to json so grid can update UI.
		$additionalAttributes = $jsonMessage->getAdditionalAttributes();

		// Unset sequence map until we support that.
		unset($additionalAttributes['sequenceMap']);
		$jsonMessage->setAdditionalAttributes(array_merge(
			$pagingAttributes,
			$additionalAttributes)
		);
	}
}

?>
