<?php

/**
 * @file controllers/grid/issueGalleys/IssueGalleyGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyGridCellProvider
 * @ingroup issue_galley
 *
 * @brief Grid cell provider for the issue galleys grid
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class IssueGalleyGridCellProvider extends GridCellProvider {
	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$issueGalley = $row->getData();
		$columnId = $column->getId();
		assert (is_a($issueGalley, 'IssueGalley'));
		assert(!empty($columnId));

		switch ($columnId) {
			case 'label': return array('label' => $issueGalley->getLabel());
			case 'locale':
				$allLocales = AppLocale::getAllLocales();
				return array('label' => $allLocales[$issueGalley->getLocale()]);
			case 'publicGalleyId': return array('label' => $issueGalley->getStoredPubId('publisher-id'));
			default: assert(false); break;
		}
	}
}

?>
