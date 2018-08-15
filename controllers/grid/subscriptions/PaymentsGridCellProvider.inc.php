<?php

/**
 * @file controllers/grid/subscriptions/PaymentsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentsGridCellProvider
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Class for a cell provider to display information about payments
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class PaymentsGridCellProvider extends GridCellProvider {
	/** @var Request */
	var $_request;

	/**
	 * Constructor.
	 * @param $request Request
	 */
	function __construct($request) {
		$this->_request = $request;
		parent::__construct();
	}

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
		$payment = $row->getData();

		switch ($column->getId()) {
			case 'name':
				$userDao = DAORegistry::getDAO('UserDAO');
				$user = $userDao->getById($payment->getUserId());
				return array('label' => $user->getFullName());
			case 'type':
				$paymentManager = Application::getPaymentManager($this->_request->getJournal());
				return array('label' => $paymentManager->getPaymentName($payment));
			case 'amount':
				return array('label' => $payment->getAmount() . ' ' . $payment->getCurrencyCode());
			case 'timestamp':
				return array('label' => $payment->getTimestamp());
		}
		assert(false);
	}
}


