<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionTypesGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypesGridCellProvider
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Class for a cell provider to display information about individual subscriptions
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class SubscriptionTypesGridCellProvider extends GridCellProvider {

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
		$subscriptionType = $row->getData();

		switch ($column->getId()) {
			case 'name':
				return array('label' => $subscriptionType->getLocalizedName());
			case 'type':
				return array('label' => __($subscriptionType->getInstitutional()?'manager.subscriptionTypes.institutional':'manager.subscriptionTypes.individual'));
			case 'duration':
				return array('label' => $subscriptionType->getDurationYearsMonths());
			case 'cost':
				return array('label' => sprintf('%.2f', $subscriptionType->getCost()) . ' (' . $subscriptionType->getCurrencyStringShort() . ')');
		}
		assert(false);
	}
}

?>
