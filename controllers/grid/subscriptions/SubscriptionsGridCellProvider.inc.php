<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionsGridCellProvider
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Class for a cell provider to display information about subscriptions
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class SubscriptionsGridCellProvider extends GridCellProvider {

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
				switch (1) {
					case is_a($subscription, 'IndividualSubscription'):
						return array('label' => $subscription->getUserFullName());
					case is_a($subscription, 'InstitutionalSubscription'):
						return array('label' => $subscription->getInstitutionName());
				}
				assert(false);
				break;
			case 'email':
				assert(is_a($subscription, 'IndividualSubscription'));
				return array('label' => $subscription->getUserEmail());
			case 'subscriptionType':
				return array('label' => $subscription->getSubscriptionTypeName());
			case 'status':
				return array('label' => $subscription->getStatusString());
			case 'dateStart':
				return array('label' => $subscription->getDateStart());
			case 'dateEnd':
				return array('label' => $subscription->getDateEnd());
			case 'referenceNumber':
				return array('label' => $subscription->getReferenceNumber());
		}
		assert(false);
	}
}


