<?php

/**
 * @file controllers/grid/toc/TocGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	 * @param $request PKPRequest
	 */
	function __construct($request) {
		parent::__construct($request);
	}

	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		assert(!empty($columnId));
		switch ($columnId) {
			case 'title':
				return array('label' => $element->getLocalizedTitle());
			default: assert(false);
		}
	}
}

?>
