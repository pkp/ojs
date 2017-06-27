<?php

/**
 * @file controllers/grid/subscriptions/IndividualSubscriptionsGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionsGridHandler
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Handle subscription grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');

import('controllers.grid.subscriptions.IndividualSubscriptionsGridCellProvider');
import('controllers.grid.subscriptions.IndividualSubscriptionsGridRow');
import('controllers.grid.subscriptions.IndividualSubscriptionForm');

class IndividualSubscriptionsGridHandler extends GridHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER),
			array('fetchGrid', 'fetchRow', 'editSubscription', 'updateSubscription',
				'deleteSubscription', 'addSubscription')
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

		// Basic grid configuration.
		$this->setTitle('subscriptionManager.individualSubscriptions');

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

		//
		// Grid columns.
		//
		$cellProvider = new IndividualSubscriptionsGridCellProvider();

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
				'email',
				'user.email',
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
				'manager.subscriptions.dateStart',
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
	 * @copydoc GridHandler::getRowInstance()
	 * @return IndividualSubscriptionsGridRow
	 */
	protected function getRowInstance() {
		return new IndividualSubscriptionsGridRow();
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
	}

	/**
	 * @copydoc GridHandler::loadData()
	 * @param $request PKPRequest
	 * @return array Grid data.
	 */
	protected function loadData($request, $filter) {
		// Get the context.
		$journal = $request->getContext();

		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		return $subscriptionDao->getByJournalId($journal->getId());
		// FIXME: , $filterStatus, $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $rangeInfo);
		/* return $userGroupDao->getUsersById(
			$filter['userGroup'],
			$filter['includeNoRole']?null:$context->getId(),
			$filter['searchField'],
			$filter['search']?$filter['search']:null,
			$filter['searchMatch'],
			$rangeInfo
		); */
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request) {
		$context = $request->getContext();

		// Import PKPUserDAO to define the USER_FIELD_* constants.
		import('lib.pkp.classes.user.PKPUserDAO');
		$fieldOptions = array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		);

		$matchOptions = array(
			'contains' => 'form.contains',
			'is' => 'form.is'
		);

		$filterData = array(
			'fieldOptions' => $fieldOptions,
			'matchOptions' => $matchOptions
		);

		return parent::renderFilter($request, $filterData);
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
		return 'controllers/grid/subscriptions/individualSubscriptionsGridFilter.tpl';
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
	 * Edit an existing subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editSubscription($args, $request) {
		// Form handling.
		$subscriptionForm = new IndividualSubscriptionForm($request, $request->getUserVar('rowId'));
		$subscriptionForm->initData($args, $request);

		return new JSONMessage(true, $subscriptionForm->fetch($request));
	}

	/**
	 * Update an existing subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateSubscription($args, $request) {
		$subscriptionId = $request->getUserVar('subscriptionId');
		// Form handling.
		$subscriptionForm = new IndividualSubscriptionForm($request, $subscriptionId);
		$subscriptionForm->readInputData();

		if ($subscriptionForm->validate()) {
			$subscriptionForm->execute($args, $request);
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
		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$subscriptionDao->deleteById($subscriptionId, $context->getId());
		return DAO::getDataChangedEvent($subscriptionId);
	}
}

?>
