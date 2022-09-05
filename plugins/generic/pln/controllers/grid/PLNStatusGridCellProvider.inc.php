<?php

/**
 * @file controllers/grid/PLNStatusGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PLNStatusGridCellProvider
 * @brief Class for a cell provider to display information about PLN Deposits
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');
import('lib.pkp.classes.linkAction.request.RedirectAction');

class PLNStatusGridCellProvider extends GridCellProvider {
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	public function getTemplateVarsFromRowColumn($row, $column) {
		$deposit = $row->getData(); /** @var $deposit Deposit */

		switch ($column->getId()) {
			case 'id':
				// The action has the label
				return array('label' => $deposit->getId());
			case 'objectId':
				return array('label' => $deposit->getObjectId());
			case 'status':
				return array('label' => $deposit->getDisplayedStatus());
			case 'latestUpdate':
				return array('label' => $deposit->getLastStatusDate());
			case 'error':
				return array('label' => $deposit->getExportDepositError());
		}
	}
}
