<?php

/**
 * @file controllers/grid/subscriptions/InstitutionalSubscriptionsGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionsGridHandler
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Handle subscription grid requests.
 */

import('controllers.grid.subscriptions.SubscriptionsGridHandler');

import('controllers.grid.subscriptions.InstitutionalSubscriptionForm');

class InstitutionalSubscriptionsGridHandler extends SubscriptionsGridHandler {
	/**
	 * @copydoc SubscriptionsGridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Basic grid configuration.
		$this->setTitle('subscriptionManager.institutionalSubscriptions');

		//
		// Grid columns.
		//
		$cellProvider = new SubscriptionsGridCellProvider();

		$this->addColumn(
			new GridColumn(
				'name',
				'common.name',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'subscriptionType',
				'manager.subscriptions.subscriptionType',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'status',
				'manager.subscriptions.form.status',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'dateStart',
				'manager.subscriptions.dateStart',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'dateEnd',
				'manager.subscriptions.dateEnd',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'referenceNumber',
				'manager.subscriptions.referenceNumber',
				null,
				null,
				$cellProvider
			)
		);
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request, $filterData = []) {
		// Import field constants.
		import('lib.pkp.classes.user.UserDAO');
		import('classes.subscription.InstitutionalSubscriptionDAO');

		$filterData = array_merge($filterData, [
			'fieldOptions' => [
				IDENTITY_SETTING_GIVENNAME => 'user.givenName',
				IDENTITY_SETTING_FAMILYNAME => 'user.familyName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email',
				SUBSCRIPTION_MEMBERSHIP => 'user.subscriptions.form.membership',
				SUBSCRIPTION_REFERENCE_NUMBER => 'manager.subscriptions.form.referenceNumber',
				SUBSCRIPTION_NOTES => 'manager.subscriptions.form.notes',
				SUBSCRIPTION_INSTITUTION_NAME => 'manager.subscriptions.form.institutionName',
				SUBSCRIPTION_DOMAIN => 'manager.subscriptions.form.domain',
				SUBSCRIPTION_IP_RANGE => 'manager.subscriptions.form.ipRange',
			],
			'matchOptions' => [
				'contains' => 'form.contains',
				'is' => 'form.is'
			],
		]);

		return parent::renderFilter($request, $filterData);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		// Get the context.
		$journal = $request->getContext();

		$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		return $subscriptionDao->getByJournalId($journal->getId(), null, $filter['searchField'], $filter['searchMatch'], $filter['search']?$filter['search']:null, null, null, null, $rangeInfo);
	}


	//
	// Public grid actions.
	//
	/**
	 * Edit an existing subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editSubscription($args, $request) {
		// Form handling.
		$subscriptionForm = new InstitutionalSubscriptionForm($request, $request->getUserVar('rowId'));
		$subscriptionForm->initData();

		return new JSONMessage(true, $subscriptionForm->fetch($request));
	}

	/**
	 * Update an existing subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateSubscription($args, $request) {
		$subscriptionId = (int) $request->getUserVar('subscriptionId');
		// Form handling.
		$subscriptionForm = new InstitutionalSubscriptionForm($request, $subscriptionId);
		$subscriptionForm->readInputData();

		if ($subscriptionForm->validate()) {
			$subscriptionForm->execute();
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_SUCCESS);
			// Prepare the grid row data.
			return DAO::getDataChangedEvent($subscriptionId);
		} else {
			return new JSONMessage(true, $subscriptionForm->fetch($request));
		}
	}

	/**
	 * Delete a subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteSubscription($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$context = $request->getContext();
		$user = $request->getUser();

		// Identify the subscription ID.
		$subscriptionId = $request->getUserVar('rowId');
		$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
		$subscriptionDao->deleteById($subscriptionId, $context->getId());
		return DAO::getDataChangedEvent();
	}
}


