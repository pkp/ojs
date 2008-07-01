<?php

/**
 * @defgroup payment
 */
 
/**
 * @file classes/payment/Payment.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Payment
 * @ingroup payment
 *
 * @brief Abstract class for payments
 *
 */

class Payment {
	var $paymentId;

	var $amount;

	var $currencyCode;

	var $userId;

	var $assocId;

	function Payment($amount, $currencyCode, $userId = null, $assocId = null) {
		$this->amount = $amount;
		$this->currencyCode = $currencyCode;
		$this->userId = $userId;
		$this->assocId = $assocId;
	}
	
	/**
	 * Get the row id of the payment.
	 * @return int
	 */	
	function getPaymentId() {
		return $this->paymentId;
	}
	
	/**
	 * Set the id of payment 
	 * @param $paymentId int
	 */	
	function setPaymentId($paymentId) {
		$this->paymentId = $paymentId;
	}

	function setAmount($amount) {
		$this->amount = $amount;
	}
	
	function getAmount() {
		return $this->amount;
	}

	function setCurrencyCode($currencyCode) {
		$this->currencyCode = $currencyCode;
	}

	function getCurrencyCode() {
		return $this->currencyCode;
	}

	function getName() {
		fatalError('ABSTRACT METHOD');
	}

	function getDescription() {
		fatalError('ABSTRACT METHOD');
	}

	function setUserId($userId) {
		$this->userId = $userId;
	}

	function getUserId() {
		return $this->userId;
	}

	function setAssocId($assocId) {
		$this->assocId = $assocId;
	}

	function getAssocId() {
		return $this->assocId;
	}
}

?>
