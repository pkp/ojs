<?php

/**
 * @file controllers/grid/subscriptions/PaymentsGridHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentsGridHandler
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Handle payment grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');

import('controllers.grid.subscriptions.PaymentsGridCellProvider');

class PaymentsGridHandler extends GridHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER),
			array('fetchGrid', 'fetchRow', 'viewPayment')
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
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_USER
		);

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
		$paymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		return $paymentDao->getByContextId($request->getContext()->getId(), $rangeInfo);
	}

	//
	// Public grid actions.
	//
	/**
	 * View a payment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewPayment($args, $request) {
		// FIXME
	}
}

?>

