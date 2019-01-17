<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionsGridHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionsGridHandler
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Handle subscription grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');

import('controllers.grid.subscriptions.SubscriptionsGridRow');
import('controllers.grid.subscriptions.SubscriptionsGridCellProvider');

abstract class SubscriptionsGridHandler extends GridHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER),
			array('fetchGrid', 'fetchRow', 'editSubscription', 'updateSubscription',
				'deleteSubscription', 'addSubscription', 'renewSubscription')
		);
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_PKP_USER
		);

		// Grid actions.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');
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
				'add_subscription')
		);
	}


	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return SubscriptionsGridRow
	 */
	protected function getRowInstance() {
		return new SubscriptionsGridRow();
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 * @return array Filter selection data.
	 */
	function getFilterSelectionData($request) {
		// Get the search terms.
		$searchField = $request->getUserVar('searchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		return $filterSelectionData = array(
			'searchField' => $searchField,
			'searchMatch' => $searchMatch,
			'search' => $search ? $search : ''
		);
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	protected function getFilterForm() {
		return 'controllers/grid/subscriptions/subscriptionsGridFilter.tpl';
	}


	//
	// Public grid actions.
	//
	/**
	 * Add a new subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addSubscription($args, $request) {
		// Calling editSubscription with an empty row id will add a new subscription.
		return $this->editSubscription($args, $request);
	}

	/**
	 * Renew a subscription.
	 * @param $args array first parameter is the ID of the subscription to renew
	 * @param $request PKPRequest
	 */
	function renewSubscription($args, $request) {
		$subscriptionDao = DAORegistry::getDAO($request->getUserVar('institutional')?'InstitutionalSubscriptionDAO':'IndividualSubscriptionDAO');
		$subscriptionId = $request->getUserVar('rowId');
		if ($subscription = $subscriptionDao->getById($subscriptionId, $request->getJournal()->getId())) {
			$subscriptionDao->renewSubscription($subscription);
		}
		return DAO::getDataChangedEvent($subscriptionId);
	}
}

?>

