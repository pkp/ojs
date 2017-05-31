<?php

/**
 * @file controllers/grid/subscriptions/IndividualSubscriptionsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionsGridCellProvider
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Class for a cell provider to display information about individual subscriptions
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class IndividualSubscriptionsGridCellProvider extends GridCellProvider {

	//
	// Template methods from GridCellProvider
	//

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$subscription = $row->getData();

		switch ($column->getId()) {
			case 'name':
				return array('label' => $subscription->getUserFullName());
			case 'email':
				return array('label' => $subscription->getUserEmail());
			case 'subscriptionType':
				return array('label' => $subscription->getSubscriptionTypeName());
			case 'status':
				return array('label' => $subscription->getStatusString());
			case 'dateStart':
				return array('label' => $subscription->getDateStart());
			case 'dateEnd':
				return array('label' => $subscription->getDateEnd());
		}
		assert(false);
	}
}

?>
