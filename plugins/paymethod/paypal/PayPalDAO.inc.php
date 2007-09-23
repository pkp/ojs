<?php

/**
 * @file PayPalDAO.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.paymethod.paypal
 * @class PayPalDAO
 *
 * Class for PayPal Logging DAO.
 * Operations for retrieving and modifying Transactions objects.
 *
 */

import('db.DAO');

class PayPalDAO extends DAO {

	/**
	 * Constructor.
	 */
	function PayPalDAO() {
		parent::DAO();
	}

	/*
	 * Insert a payment into the payments table
	 */
	 function insertTransaction($txn_id, $txn_type, $payer_email, $receiver_email, $item_number, $payment_date, $payer_id, $receiver_id) {
		$ret = $this->update(
			sprintf('INSERT INTO paypal_transactions (
				txn_id, 
				txn_type, 
				payer_email, 
				receiver_email, 
				item_number, 
				payment_date,
				payer_id, 
				receiver_id) 
				VALUES 
				(?, ?, ?, ?, ?, %s, ?, ?)',
				$this->datetimeToDB($payment_date)
			), 
			array(
				$txn_id, 
				$txn_type, 
				$payer_email, 
				$receiver_email, 
				$item_number, 
				$payer_id, 
				$receiver_id
			) 
		);

		return true;
	 }
	 
	function transactionExists($txn_id) {
		$result =& $this->retrieve(
			'SELECT count(*) 
				FROM paypal_transactions
				WHERE txn_id = ?', 
				array($txn_id)
		);
		
		$returner = false;
		if (isset($result->fields[0]) && $result->fields[0] >= 1) 
			$returner = true;
		
		$result->Close();
		return $returner;		
	}
}

?>
