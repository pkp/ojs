<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionsGridCellProvider
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Class for a cell provider to display information about subscriptions
 */

use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;

class SubscriptionsGridCellProvider extends GridCellProvider
{
    //
    // Template methods from GridCellProvider
    //

    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @param \PKP\controllers\grid\GridRow $row
     * @param GridColumn $column
     *
     * @return array
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $subscription = $row->getData();

        switch ($column->getId()) {
            case 'name':
                switch (1) {
                    case is_a($subscription, 'IndividualSubscription'):
                        return ['label' => $subscription->getUserFullName()];
                    case is_a($subscription, 'InstitutionalSubscription'):
                        return ['label' => $subscription->getInstitutionName()];
                }
                assert(false);
                break;
            case 'email':
                assert(is_a($subscription, 'IndividualSubscription'));
                return ['label' => $subscription->getUserEmail()];
            case 'subscriptionType':
                return ['label' => $subscription->getSubscriptionTypeName()];
            case 'status':
                return ['label' => $subscription->getStatusString()];
            case 'dateStart':
                return ['label' => $subscription->getDateStart()];
            case 'dateEnd':
                return ['label' => $subscription->getDateEnd()];
            case 'referenceNumber':
                return ['label' => $subscription->getReferenceNumber()];
        }
        assert(false);
    }
}
