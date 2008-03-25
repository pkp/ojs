<?php

/**
 * @file OJSCompletedPaymentDAO.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package payment
 * @class OJSCompletedPaymentDAO
 *
 * Class for completed payment DAO.
 * Operations for retrieving and querying past payments
 *
 */

class OJSCompletedPaymentDAO extends DAO {
	/**
	 * Retrieve a ComplatedPayment by its ID.
	 * @param $subscriptionId int
	 * @return Subscription
	 */
	function &getCompletedPayment($completedPaymentId) {
		$result = &$this->retrieve(
			'SELECT * FROM completed_payments WHERE completed_payment_id = ?', $completedPaymentId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnPaymentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);
		return $returner;
	}
	
	/**
	 * Insert a new completed payment.
	 * @param $completedPayment OJSCompletedPayment
	 */
	function insertCompletedPayment(&$completedPayment) {
		$this->update(
			sprintf('INSERT INTO completed_payments
				(timestamp, payment_type, journal_id, user_id, assoc_id, amount, currency_code_alpha, payment_method_plugin_name)
				VALUES
				(%s, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				$completedPayment->getType(), 
				$completedPayment->getJournalId(), 
				$completedPayment->getUserId(), 
				$completedPayment->getAssocId(), 
				$completedPayment->getAmount(), 
				$completedPayment->getCurrencyCode(), 
				$completedPayment->getPayMethodPluginName()
				)
		);

		return $this->getInsertCompletedPaymentId();
	}

	/**
	 * Get the ID of the last inserted completed payment.
	 * @return int
	 */
	function getInsertCompletedPaymentId() {
		return $this->getInsertId('completed_payments', 'completed_payment_id');
	}
	
	/**
	 * Look for a completed PURCHASE_ARTICLE payment matching the journal and article IDs
	 * @param int $journalId
	 * @param int $articleId
	 */	
	function hasPaidPerViewArticle ( $userId, $articleId ) {
		$result =& $this->retrieve(
			'SELECT count(*) FROM completed_payments WHERE payment_type = ? AND user_id = ? AND assoc_id = ?', 
				array(PAYMENT_TYPE_PURCHASE_ARTICLE,
					$userId,
					$articleId )
		);
		
		$returner = false;
		if (isset($result->fields[0]) && $result->fields[0] != 0) {
			$returner = true;
		}
		
		$result->Close();
		return $returner;
	}

	/**
	 * Look for a completed SUBMISSION payment matching the journal and article IDs
	 * @param int $journalId
	 * @param int $articleId
	 * @return bool
	 */
	function hasPaidSubmission ( $journalId, $articleId ) {
		$result =& $this->retrieve(
			'SELECT count(*) FROM completed_payments WHERE payment_type = ? AND journal_id = ? AND assoc_id = ?', 
			array(
				PAYMENT_TYPE_SUBMISSION,
				$journalId, 
				$articleId
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
	 * get a CompletedPayment for a SUBMISSION type payment matching the journal and article IDs
	 * @param int $journalId
	 * @param int $articleId
	 * @return CompletedPayment
	 */
	function &getSubmissionCompletedPayment ( $journalId, $articleId ) {	
		$result =& $this->retrieve(
			'SELECT * FROM completed_payments WHERE payment_type = ? AND journal_id = ? AND assoc_id = ?', 
			array(
				PAYMENT_TYPE_SUBMISSION,
				$journalId, 
				$articleId
				)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnPaymentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);
		return $returner;
	}		

	/**
	 * Look for a completed FASTTRACK payment matching the journal and article IDs
	 * @param int $journalId
	 * @param int $articleId
	 * @return bool
	 */
	function hasPaidFastTrack ( $journalId, $articleId ) {
		$result =& $this->retrieve(
			'SELECT count(*) FROM completed_payments WHERE payment_type = ? AND journal_id = ? AND assoc_id = ?', 
			array(
				PAYMENT_TYPE_FASTTRACK,
				$journalId, 
				$articleId
				)
		);
		
		$returner = false;
		if (isset($result->fields[0]) && $result->fields[0] != 0) 
			$returner =  true;
		
		$result->Close();
		return $returner;
	}
	
	/**
	 * get a CompletedPayment for a FASTTRACK type payment matching the journal and article IDs
	 * @param int $journalId
	 * @param int $articleId
	 * @return CompletedPayment
	 */	
	function getFastTrackCompletedPayment ( $journalId, $articleId ) {
		$result =& $this->retrieve(
			'SELECT * FROM completed_payments WHERE payment_type = ? AND journal_id = ? AND assoc_id = ?', 
			array(
				PAYMENT_TYPE_FASTTRACK,
				$journalId, 
				$articleId
				)
		);
			
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnPaymentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);
		return $returner;
	}		
	
	/**
	 * Look for a completed payment matching the publication type and article ID
	 * @param int $journalId 
	 * @param int $articleId
	 */
	function hasPaidPublication ( $journalId, $articleId ) {
		$result =& $this->retrieve(
			'SELECT count(*) FROM completed_payments WHERE payment_type = ? AND journal_id = ? AND assoc_id = ?', 
			array(
				PAYMENT_TYPE_PUBLICATION,
				$journalId, 
				$articleId
				)
		);
	
		$returner = (isset($result->fields[0]) && $result->fields[0] != 0) ;
		$result->Close();
		return $returner; 
	}		

	/**
	 * get a CompletedPayment for a PUBLICATION type payment matching the journal and article IDs
	 * @param int $journalId
	 * @param int $articleId
	 * @return CompletedPayment
	 */	
	function getPublicationCompletedPayment ( $journalId, $articleId ) {
		$result =& $this->retrieve(
			'SELECT * FROM completed_payments WHERE payment_type = ? AND journal_id = ? AND assoc_id = ?', 
			array(
				PAYMENT_TYPE_PUBLICATION,
				$journalId, 
				$articleId
				)
		);	

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnPaymentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);
		return $returner;
	}	
		
	/**
	 * Retrieve an array of payments for a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Theses 
	 */
	function &getPaymentsByJournalId($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM completed_payments WHERE journal_id = ? ORDER BY timestamp DESC', $journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnPaymentFromRow');
		return $returner;
	}		
	
	/**
	 * Internal function to return a OJSCompletedPayment object from a row.
	 * @param $row array
	 * @return CompletedPayment
	 */
	function &_returnPaymentFromRow(&$row) {
		import('payment.ojs.OJSCompletedPayment');

		$payment = &new OJSCompletedPayment();
		$payment->setTimestamp($this->datetimeFromDB($row['timestamp']));
		$payment->setPaymentId($row['completed_payment_id']);
		$payment->setType($row['payment_type']); 
		$payment->setJournalId($row['journal_id']); 
		$payment->setAmount($row['amount']); 
		$payment->setCurrencyCode($row['currency_code_alpha']);
		$payment->setUserId($row['user_id']);
		$payment->setAssocId($row['assoc_id']);
		$payment->setPayMethodPluginName($row['payment_method_plugin_name']);
		
		return $payment;
	}
	
}

?>
