<?php

/**
 * @file controllers/grid/subscriptions/PaymentsGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaymentsGridHandler
 *
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Handle payment grid requests.
 */

namespace APP\controllers\grid\subscriptions;

use APP\core\Request;
use APP\payment\ojs\OJSCompletedPaymentDAO;
use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\db\DAORegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

class PaymentsGridHandler extends GridHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_SUBSCRIPTION_MANAGER],
            ['fetchGrid', 'fetchRow', 'viewPayment']
        );
    }


    //
    // Implement template methods from PKPHandler.
    //
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc GridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        // Grid actions.
        $router = $request->getRouter();

        //
        // Grid columns.
        //
        $cellProvider = new PaymentsGridCellProvider($request);

        $this->addColumn(
            new GridColumn(
                'name',
                'common.user',
                null,
                null,
                $cellProvider
            )
        );
        $this->addColumn(
            new GridColumn(
                'type',
                'manager.payment.paymentType',
                null,
                null,
                $cellProvider
            )
        );
        $this->addColumn(
            new GridColumn(
                'amount',
                'manager.payment.amount',
                null,
                null,
                $cellProvider
            )
        );
        $this->addColumn(
            new GridColumn(
                'timestamp',
                'manager.payment.timestamp',
                null,
                null,
                $cellProvider
            )
        );
    }


    /**
     * @copydoc GridHandler::initFeatures()
     */
    public function initFeatures($request, $args)
    {
        return [new PagingFeature()];
    }


    /**
     * @copydoc GridHandler::loadData()
     */
    protected function loadData($request, $filter)
    {
        $paymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $paymentDao */
        $rangeInfo = $this->getGridRangeInfo($request, $this->getId());
        return $paymentDao->getByContextId($request->getContext()->getId(), $rangeInfo);
    }

    //
    // Public grid actions.
    //
    /**
     * View a payment.
     *
     * @param array $args
     * @param Request $request
     */
    public function viewPayment($args, $request)
    {
        // FIXME
    }
}
