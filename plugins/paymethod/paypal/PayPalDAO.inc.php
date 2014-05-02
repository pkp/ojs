<?php

/**
 * @file plugins/paymethod/paypal/PayPalDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PayPalDAO
 * @ingroup plugins_paymethod_paypal
 *
 * @brief Operations for retrieving and modifying Transactions objects.
 */

import('lib.pkp.classes.db.DAO');

class PayPalDAO extends DAO {
	/**
	 * Constructor.
	 */
	function PayPalDAO() {
		parent::DAO();
	}

	/**
	 * Insert a payment into the payments table
	 * @param $txnId string
	 * @param $txnType string
	 * @param $payerEmail string
	 * @param $receiverEmail string
	 * @param $itemNumber string
	 * @param $paymentDate datetime
	 * @param $payerId string
	 * @param $receiverId string
	 */
	 function insertTransaction($txnId, $txnType, $payerEmail, $receiverEmail, $itemNumber, $paymentDate, $payerId, $receiverId) {
		$ret = $this->update(
			sprintf(
				'INSERT INTO paypal_transactions (
					txn_id,
					txn_type,
					payer_email,
					receiver_email,
					item_number,
					payment_date,
					payer_id,
					receiver_id
				) VALUES (
					?, ?, ?, ?, ?, %s, ?, ?
				)',
				$this->datetimeToDB($paymentDate)
			),
			array(
				$txnId,
				$txnType,
				$payerEmail,
				$receiverEmail,
				$itemNumber,
				$payerId,
				$receiverId
			)
		);

		return true;
	 }

	/**
	 * Check whether a given transaction exists.
	 * @param $txnId string
	 * @return boolean
	 */
	function transactionExists($txnId) {
		$result =& $this->retrieve(
			'SELECT	count(*) FROM paypal_transactions WHERE txn_id = ?',
			array($txnId)
		);

		$returner = false;
		if (isset($result->fields[0]) && $result->fields[0] >= 1) $returner = true;

		$result->Close();
		return $returner;
	}
}

?>
