<?php

/**
 * @file classes/payment/ojs/OJSCompletedPayment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSCompletedPayment
 * @ingroup payment
 * @see OJSCompletedPaymentDAO
 *
 * @brief Class describing a completed payment.
 */

import('lib.pkp.classes.payment.Payment');

class OJSCompletedPayment extends Payment {
	/** @var int Journal ID */
	var $_journalId;

	/** @var string Payment completion timestamp */
	var $_timestamp;

	/** @var int PAYMENT_TYPE_... */
	var $_type;

	/** @var string Payment plugin name */
	var $_paymentPluginName;

	/**
	 * Get the journal ID for the payment.
	 * @return int
	 */
	function getJournalId() {
		return $this->_journalId;
	}

	/**
	 * Set the journal ID for the payment.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		$this->_journalId = $journalId;
	}

	/**
	 * Get the payment completion timestamp.
	 * @return string
	 */
	function getTimestamp() {
		return $this->_timestamp;
	}

	/**
	 * Set the payment completion timestamp.
	 * @param $timestamp string Timestamp
	 */
	function setTimestamp($timestamp) {
		$this->_timestamp = $timestamp;
	}

	/**
	 * Set the payment type.
	 * @param $type int PAYMENT_TYPE_...
	 */
	function setType($type) {
		$this->_type = $type;
	}

	/**
	 * Set the payment type.
	 * @return $type int PAYMENT_TYPE_...
	 */
	function getType() {
		return $this->_type;
	}

	/**
	 * Get the payment plugin name.
	 * @return string
	 */
	function getPayMethodPluginName() {
		return $this->_paymentPluginName;
	}

	/**
	 * Set the payment plugin name.
	 * @param $paymentPluginName string
	 */
	function setPayMethodPluginName($paymentPluginName) {
		$this->_paymentPluginName = $paymentPluginName;
	}
}

?>
