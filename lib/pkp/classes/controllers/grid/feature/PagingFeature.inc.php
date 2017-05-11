<?php

/**
 * @file classes/controllers/grid/feature/PagingFeature.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PagingFeature
 * @ingroup controllers_grid_feature
 *
 * @brief Add paging functionality to grids.
 *
 */

import('lib.pkp.classes.controllers.grid.feature.GeneralPagingFeature');

class PagingFeature extends GeneralPagingFeature {

	/**
	 * @see GridFeature::GridFeature()
	 * Constructor.
	 * @param $id string Feature identifier.
	 */
	function __construct($id = 'paging') {
		parent::__construct($id);
	}


	//
	// Extended methods from GridFeature.
	//
	/**
	 * @copydoc GridFeature::getJSClass()
	 */
	function getJSClass() {
		return '$.pkp.classes.features.PagingFeature';
	}


	/**
	 * @copydoc GridFeature::fetchUIElements()
	 */
	function fetchUIElements($request, $grid) {
		$options = $this->getOptions();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'iterator' => $this->getItemIterator(),
			'currentItemsPerPage' => $options['currentItemsPerPage'],
			'grid' => $grid,
		));
		return array('pagingMarkup' => $templateMgr->fetch('controllers/grid/feature/gridPaging.tpl'));
	}


	//
	// Hooks implementation.
	//
	/**
	 * @copydoc GridFeature::fetchRow()
	 * Check if user really deleted a row. Handle following cases:
	 * 1 - recently added requested row is on previous pages and its
	 * addition changes the current requested page items;
	 * 2 - deleted a row from a page that's not the last one;
	 * 3 - deleted the last row from a page that's not the last one;
	 *
	 * The solution is:
	 * 1 - fetch the first grid data row;
	 * 2 - fetch the last grid data row;
	 * 3 - send a request to refresh the entire grid usign the previous
	 * page.
	 */
	function fetchRow($args) {
		$request = $args['request'];
		$grid = $args['grid'];
		$row = $args['row'];
		$jsonMessage = $args['jsonMessage'];
		$pagingAttributes = array();

		if (is_null($row)) {
			$gridData = $grid->getGridDataElements($request);
			$iterator = $this->getItemIterator();
			$rangeInfo = $grid->getGridRangeInfo($request, $grid->getId());

			// Check if row was really deleted or if the requested row is
			// just not inside the requested range.
			$deleted = true;
			$topLimitRowId = (int) $request->getUserVar('topLimitRowId');
			$bottomLimitRowId = (int) $request->getUserVar('bottomLimitRowId');

			reset($gridData);
			$firstDataId = key($gridData);
			next($gridData);
			$secondDataId = key($gridData);
			end($gridData);
			$lastDataId = key($gridData);

			if ($secondDataId == $topLimitRowId) {
				$deleted = false;
				// Case 1.
				// Row was added but it's on previous pages, so the first
				// item of the grid was moved to the second place by the added
				// row. Render the first one that's currently not visible yet in
				// grid.
				$args = array('rowId' => $firstDataId);
				$row = $grid->getRequestedRow($request, $args);
				$pagingAttributes['newTopRow'] = $grid->renderRow($request, $row);
			}

			if ($firstDataId == $topLimitRowId && $lastDataId == $bottomLimitRowId) {
				$deleted = false;
			}

			if ($deleted) {
				if ((empty($gridData) ||
					// When DAOResultFactory, it seems that if no items were found for the current
					// range information, the last page is fetched, which give us grid data even if
					// the current page is empty. So we check for iterator and rangeInfo current pages.
					$iterator->getPage() != $rangeInfo->getPage())
					&& $iterator->getPageCount() >= 1) {
					// Case 3.
					$pagingAttributes['loadLastPage'] = true;
				} else {
					if (count($gridData) >= $rangeInfo->getCount()) {
						// Case 2.
						// Get the last data element id of the current page.
						end($gridData);
						$firstRowId = key($gridData);

						// Get the row and render it.
						$args = array('rowId' => $firstRowId);
						$row = $grid->getRequestedRow($request, $args);
						$pagingAttributes['deletedRowReplacement'] = $grid->renderRow($request, $row);
					}
				}
			}
		}

		// Render the paging options, including updated markup.
		$this->setOptions($request, $grid);
		$pagingAttributes['pagingInfo'] = $this->getOptions();

		// Add paging attributes to json so grid can update UI.
		$additionalAttributes = $jsonMessage->getAdditionalAttributes();
		$jsonMessage->setAdditionalAttributes(array_merge(
			$pagingAttributes,
			$additionalAttributes)
		);
	}
}

?>
