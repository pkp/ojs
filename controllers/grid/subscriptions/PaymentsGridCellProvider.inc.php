<?php

/**
 * @file controllers/grid/subscriptions/PaymentsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaymentsGridCellProvider
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Class for a cell provider to display information about payments
 */

use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;

class PaymentsGridCellProvider extends GridCellProvider
{
    /** @var Request */
    public $_request;

    /**
     * Constructor.
     *
     * @param $request Request
     */
    public function __construct($request)
    {
        $this->_request = $request;
        parent::__construct();
    }

    //
    // Template methods from GridCellProvider
    //

    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @param $row \PKP\controllers\grid\GridRow
     * @param $column GridColumn
     *
     * @return array
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $payment = $row->getData();

        switch ($column->getId()) {
            case 'name':
                $userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
                $user = $userDao->getById($payment->getUserId());
                return ['label' => $user ? $user->getFullName() : __('common.user.nonexistent')]; // If no $user, returns "[Nonexistent user]" to avoid null user
            case 'type':
                $paymentManager = Application::getPaymentManager($this->_request->getJournal());
                return ['label' => $paymentManager->getPaymentName($payment)];
            case 'amount':
                return ['label' => $payment->getAmount() . ' ' . $payment->getCurrencyCode()];
            case 'timestamp':
                return ['label' => $payment->getTimestamp()];
        }
        assert(false);
    }
}
