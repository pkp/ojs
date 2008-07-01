<?php

/**
 * @file classes/payment/QueuedPaymentDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueuedPaymentDAO
 * @ingroup payment
 * @see QueuedPayment
 *
 * @brief Operations for retrieving and modifying queued payment objects.
 *
 */

class QueuedPaymentDAO extends DAO {

	/**
	 * Constructor.
	 */
	function QueuedPaymentDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a queued payment by ID.
	 * @param $queuedPaymentId int
	 * @return QueuedPayment
	 */
	function &getQueuedPayment($queuedPaymentId) {
		$result = &$this->retrieve(
			'SELECT * FROM queued_payments WHERE queued_payment_id = ?',
			$queuedPaymentId
		);

		$queuedPayment = null;
		if ($result->RecordCount() != 0) {
			$queuedPayment = unserialize($result->fields['payment_data']);
			if (!is_object($queuedPayment)) unset($queuedPayment);
		}
		$result->Close();
		unset($result);
		return $queuedPayment;
	}
	
	/**
	 * Insert a new queued payment.
	 * @param $payment Payment
	 */
	function insertQueuedPayment(&$queuedPayment) {
		$this->update(
			sprintf('INSERT INTO queued_payments
				(date_created, date_modified, payment_data)
				VALUES
				(%s, %s, ?)',
				$this->datetimeToDB(Core::getCurrentDate()),
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				serialize($queuedPayment)
			)
		);
		
		return $this->getInsertQueuedPaymentId();
	}
	
	/**
	 * Update an existing queued payment.
	 * @param $paymentId int
	 * @param $payment Payment
	 */
	function updateQueuedPayment($queuedPaymentId, &$queuedPayment) {
		return $this->update(
			sprintf('UPDATE queued_payments
				SET
					date_modified = %s,
					payment_data = ?
				WHERE queued_payment_id = ?',
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				serialize($queuedPayment),
				$queuedPaymentId
			)
		);
	}
	
	/**
	 * Get the ID of the last inserted queued payment.
	 * @return int
	 */
	function getInsertQueuedPaymentId() {
		return $this->getInsertId('queued_payments', 'queued_payment_id');
	}
	
	function deleteQueuedPayment(&$queuedPayment) {
		return $this->update(
			'DELETE FROM queued_payments WHERE queued_payment_id = ?',
			array($queuedPayment->getQueuedPaymentId())
		);
	}
}

?>
