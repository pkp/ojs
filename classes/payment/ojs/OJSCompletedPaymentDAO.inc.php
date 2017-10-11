<?php

/**
 * @file classes/payment/ojs/OJSCompletedPaymentDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSCompletedPaymentDAO
 * @ingroup payment
 * @see OJSCompletedPayment, Payment
 *
 * @brief Operations for retrieving and querying past payments
 *
 */

import('lib.pkp.classes.payment.CompletedPayment');

class OJSCompletedPaymentDAO extends DAO {
	/**
	 * Retrieve a ComplatedPayment by its ID.
	 * @param $completedPaymentId int
	 * @param $contextId int optional
	 * @return CompletedPayment
	 */
	function getCompletedPayment($completedPaymentId, $contextId = null) {
		$params = array((int) $completedPaymentId);
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM completed_payments WHERE completed_payment_id = ?' . ($contextId?' AND context_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_returnPaymentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Insert a new completed payment.
	 * @param $completedPayment CompletedPayment
	 */
	function insertObject($completedPayment) {
		$this->update(
			sprintf('INSERT INTO completed_payments
				(timestamp, payment_type, context_id, user_id, assoc_id, amount, currency_code_alpha, payment_method_plugin_name)
				VALUES
				(%s, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				(int) $completedPayment->getType(),
				(int) $completedPayment->getContextId(),
				(int) $completedPayment->getUserId(),
				(int) $completedPayment->getAssocId(),
				$completedPayment->getAmount(),
				$completedPayment->getCurrencyCode(),
				$completedPayment->getPayMethodPluginName()
			)
		);

		return $this->getInsertId();
	}

	/**
	 * Update an existing completed payment.
	 * @param $completedPayment CompletedPayment
	 * @return boolean
	 */
	function updateObject($completedPayment) {
		$returner = false;
		
		$returner = $this->update(
			sprintf('UPDATE completed_payments
			SET
				timestamp = %s,
				payment_type = ?,
				context_id = ?,
				user_id = ?,
				assoc_id = ?,
				amount = ?,
				currency_code_alpha = ?,
				payment_method_plugin_name = ? 
			WHERE completed_payment_id = ?',
			$this->datetimeToDB($completedPayment->getTimestamp())),
			array(
				(int) $completedPayment->getType(),
				(int) $completedPayment->getContextId(),
				(int) $completedPayment->getUserId(),
				(int) $completedPayment->getAssocId(),
				$completedPayment->getAmount(),
				$completedPayment->getCurrencyCode(),
				$completedPayment->getPayMethodPluginName(),
				(int) $completedPayment->getCompletedPaymentId()
			)
		);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted completed payment.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('completed_payments', 'completed_payment_id');
	}

	/**
	 * Look for a completed PURCHASE_ARTICLE payment matching the article ID
	 * @param $userId int
	 * @param $articleId int
	 */
	function hasPaidPurchaseArticle($userId, $articleId) {
		$result = $this->retrieve(
			'SELECT count(*) FROM completed_payments WHERE payment_type = ? AND user_id = ? AND assoc_id = ?',
			array(
				PAYMENT_TYPE_PURCHASE_ARTICLE,
				(int) $userId,
				(int) $articleId
			)
		);

		$returner = false;
		if (isset($result->fields[0]) && $result->fields[0] != 0) {
			$returner = true;
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Look for a completed PURCHASE_ISSUE payment matching the user and issue IDs
	 * @param int $userId
	 * @param int $issueId
	 */
	function hasPaidPurchaseIssue($userId, $issueId) {
		$result = $this->retrieve(
			'SELECT count(*) FROM completed_payments WHERE payment_type = ? AND user_id = ? AND assoc_id = ?',
			array(
				PAYMENT_TYPE_PURCHASE_ISSUE,
				(int) $userId,
				(int) $issueId
			)
		);

		$returner = false;
		if (isset($result->fields[0]) && $result->fields[0] != 0) {
			$returner = true;
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve an array of payments for a particular context ID.
	 * @param $contextId int
	 * @return object DAOResultFactory containing matching payments
	 */
	function getByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM completed_payments WHERE context_id = ? ORDER BY timestamp DESC',
			(int) $contextId,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_returnPaymentFromRow');
	}

	/**
	 * Retrieve an array of payments for a particular user ID.
	 * @param $userId int
	 * @return object DAOResultFactory containing matching payments
	 */
	function getByUserId($userId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM completed_payments WHERE user_id = ? ORDER BY timestamp DESC',
			(int) $userId,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnPaymentFromRow');
		return $returner;
	}

	/**
	 * Return a new data object.
	 * @return CompletedPayment
	 */
	function newDataObject() {
		return new CompletedPayment();
	}

	/**
	 * Internal function to return a CompletedPayment object from a row.
	 * @param $row array
	 * @return CompletedPayment
	 */
	function _returnPaymentFromRow($row) {
		$payment = $this->newDataObject();
		$payment->setTimestamp($this->datetimeFromDB($row['timestamp']));
		$payment->setId($row['completed_payment_id']);
		$payment->setType($row['payment_type']);
		$payment->setContextId($row['context_id']);
		$payment->setAmount($row['amount']);
		$payment->setCurrencyCode($row['currency_code_alpha']);
		$payment->setUserId($row['user_id']);
		$payment->setAssocId($row['assoc_id']);
		$payment->setPayMethodPluginName($row['payment_method_plugin_name']);

		return $payment;
	}
}

?>
