<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionsGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionsGridHandler
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Handle subscription grid requests.
 */


import('controllers.grid.subscriptions.SubscriptionsGridRow');
import('controllers.grid.subscriptions.SubscriptionsGridCellProvider');

use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\GridHandler;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

abstract class SubscriptionsGridHandler extends GridHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_SUBSCRIPTION_MANAGER],
            ['fetchGrid', 'fetchRow', 'editSubscription', 'updateSubscription',
                'deleteSubscription', 'addSubscription', 'renewSubscription']
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

        $this->addAction(
            new LinkAction(
                'addSubscription',
                new AjaxModal(
                    $router->url($request, null, null, 'addSubscription', null, null),
                    __('manager.subscriptions.create'),
                    'modal_add_subscription',
                    true
                ),
                __('manager.subscriptions.create'),
                'add_subscription'
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
     * @copydoc GridHandler::getRowInstance()
     *
     * @return SubscriptionsGridRow
     */
    protected function getRowInstance()
    {
        return new SubscriptionsGridRow();
    }

    /**
     * @copydoc GridHandler::getFilterSelectionData()
     *
     * @return array Filter selection data.
     */
    public function getFilterSelectionData($request)
    {
        // Get the search terms.
        $searchField = $request->getUserVar('searchField');
        $searchMatch = $request->getUserVar('searchMatch');
        $search = $request->getUserVar('search');

        return $filterSelectionData = [
            'searchField' => $searchField,
            'searchMatch' => $searchMatch,
            'search' => $search ? $search : ''
        ];
    }

    /**
     * @copydoc GridHandler::getFilterForm()
     *
     * @return string Filter template.
     */
    protected function getFilterForm()
    {
        return 'controllers/grid/subscriptions/subscriptionsGridFilter.tpl';
    }


    //
    // Public grid actions.
    //
    /**
     * Add a new subscription.
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function addSubscription($args, $request)
    {
        // Calling editSubscription with an empty row id will add a new subscription.
        return $this->editSubscription($args, $request);
    }

    /**
     * Renew a subscription.
     *
     * @param array $args first parameter is the ID of the subscription to renew
     * @param PKPRequest $request
     */
    public function renewSubscription($args, $request)
    {
        $subscriptionDao = DAORegistry::getDAO($request->getUserVar('institutional') ? 'InstitutionalSubscriptionDAO' : 'IndividualSubscriptionDAO');
        $subscriptionId = $request->getUserVar('rowId');
        if ($subscription = $subscriptionDao->getById($subscriptionId, $request->getJournal()->getId())) {
            $subscriptionDao->renewSubscription($subscription);
        }
        return DAO::getDataChangedEvent($subscriptionId);
    }
}

?>

