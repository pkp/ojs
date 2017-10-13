<?php

/**
 * @file controllers/grid/subscriptions/PaymentsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
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
				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = new OJSPaymentManager($this->_request);
				return array('label' => $paymentManager->getPaymentName($payment));
			case 'timestamp':
				return array('label' => $payment->getTimestamp());
		}
		assert(false);
	}
}

?>
