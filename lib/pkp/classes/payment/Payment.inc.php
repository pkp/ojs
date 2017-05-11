<?php

/**
 * @defgroup payment Payment
 * Payment handling and processing code.
 */

/**
 * @file classes/payment/Payment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Payment
 * @ingroup payment
 *
 * @brief Abstract class for payments.
 *
 */

/** DOES NOT inherit from DataObject for the sake of concise serialization */
class Payment {
	/** @var int payment id */
	var $paymentId;

	/** @var numeric amount of payment in $currencyCode units */
	var $amount;

	/** @var string ISO 4217 alpha currency code */
	var $currencyCode;

	/** @var int user ID of customer making payment */
	var $userId;

	/** @var int association ID for payment */
	var $assocId;

	/**
	 * Constructor
	 * @param $amount number
	 * @param $currencyCode string
	 * @param $userId int
	 * @param $assocId int optional
	 */
	function __construct($amount = null, $currencyCode = null, $userId = null, $assocId = null) {
		$this->amount = $amount;
		$this->currencyCode = $currencyCode;
		$this->userId = $userId;
		$this->assocId = $assocId;
	}

	/**
	 * Get the row id of the payment.
	 * @return int
	 */
	function getId() {
		return $this->paymentId;
	}

	/**
	 * Set the id of payment
	 * @param $paymentId int
	 * @return int new payment id
	 */
	function setId($paymentId) {
		return $this->paymentId = $paymentId;
	}

	/**
	 * Set the payment amount
	 * @param $amount numeric
	 * @return numeric new amount
	 */
	function setAmount($amount) {
		return $this->amount = $amount;
	}

	/**
	 * Get the payment amount
	 * @return numeric
	 */
	function getAmount() {
		return $this->amount;
	}

	/**
	 * Set the currency code for the transaction (ISO 4217)
	 * @param $currencyCode string
	 * @return string new currency code
	 */
	function setCurrencyCode($currencyCode) {
		return $this->currencyCode = $currencyCode;
	}

	/**
	 * Get the currency code for the transaction (ISO 4217)
	 * @return string
	 */
	function getCurrencyCode() {
		return $this->currencyCode;
	}

	/**
	 * Get the name of the transaction.
	 * @return string
	 */
	function getName() {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Get a description of the transaction.
	 * @return string
	 */
	function getDescription() {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Set the user ID of the customer.
	 * @param $userId int
	 * @return int New user ID
	 */
	function setUserId($userId) {
		return $this->userId = $userId;
	}

	/**
	 * Get the user ID of the customer.
	 * @return int
	 */
	function getUserId() {
		return $this->userId;
	}

	/**
	 * Set the association ID for the payment.
	 * @param $assocId int
	 * @return int New association ID
	 */
	function setAssocId($assocId) {
		return $this->assocId = $assocId;
	}

	/**
	 * Get the association ID for the payment.
	 * @return int
	 */
	function getAssocId() {
		return $this->assocId;
	}
}

?>
