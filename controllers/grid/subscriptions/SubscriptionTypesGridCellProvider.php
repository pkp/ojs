<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionTypesGridCellProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypesGridCellProvider
 *
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Class for a cell provider to display information about individual subscriptions
 */

namespace APP\controllers\grid\subscriptions;

use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;

class SubscriptionTypesGridCellProvider extends GridCellProvider
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
        $subscriptionType = $row->getData();

        switch ($column->getId()) {
            case 'name':
                return ['label' => $subscriptionType->getLocalizedName()];
            case 'type':
                return ['label' => __($subscriptionType->getInstitutional() ? 'manager.subscriptionTypes.institutional' : 'manager.subscriptionTypes.individual')];
            case 'duration':
                return ['label' => $subscriptionType->getDurationYearsMonths()];
            case 'cost':
                return ['label' => sprintf('%.2f', $subscriptionType->getCost()) . ' (' . $subscriptionType->getCurrencyStringShort() . ')'];
        }
        assert(false);
    }
}
