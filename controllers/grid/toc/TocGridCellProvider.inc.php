<?php

/**
 * @file controllers/grid/toc/TocGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TocGridCellProvider
 * @ingroup controllers_grid_toc
 *
 * @brief Grid cell provider for the TOC (Table of Contents) category grid
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class TocGridCellProvider extends GridCellProvider {
	/**
	 * Constructor
	 */
	function __construct($translate = false) {
		parent::__construct();
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		assert(!empty($columnId));
		switch ($columnId) {
			case 'title':
				return array('label' => $element->getLocalizedTitle());
			case 'access':
				return array('selected' => $element->getAccessStatus()==ARTICLE_ACCESS_OPEN);
			default: assert(false);
		}
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		switch ($column->getId()) {
			case 'access':
				$article = $row->getData(); /* @var $article Submission */
				return array(new LinkAction(
					'disable',
					new AjaxAction(
						$request->url(
							null, null, 'setAccessStatus', null,
							array_merge(
								array(
									'articleId' => $article->getId(),
									'status' => ($article->getAccessStatus() == ARTICLE_ACCESS_OPEN) ? ARTICLE_ACCESS_ISSUE_DEFAULT : ARTICLE_ACCESS_OPEN,
									'csrfToken' => $request->getSession()->getCSRFToken(),
								),
								$row->getRequestArgs()
							)
						)
					),
					__('manager.plugins.disable'),
					null
				));
		}
		return parent::getCellActions($request, $row, $column, $position);
	}
}


