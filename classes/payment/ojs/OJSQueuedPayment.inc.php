<?php

/**
 * @file OJSQueuedPayment.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package payment
 * @class OJSQueuedPayment
 *
 * Queued payment data structure for OJS
 *
 */

import('payment.QueuedPayment');

class OJSQueuedPayment extends QueuedPayment {
	var $journalId;
	
	var $paperId;

	var $type;
	
	var $requestUrl;

	/**
	 * Get the journal ID of the payment.
	 * @return int
	 */
	function getJournalId() {
		return $this->journalId;
	}
	
	/**
	 * Set the journal ID of the payment.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		$this->journalId = $journalId;
	}

	function setType($type) {
		$this->type = $type;
	}

	function getType() {
		return $this->type;
	}
	
	function getDescription() {
		switch ($this->type) {
			case PAYMENT_TYPE_MEMBERSHIP:
				return Locale::translate('payment.type.membership');
			case PAYMENT_TYPE_SUBSCRIPTION:
				return Locale::translate('payment.type.subscription');
			case PAYMENT_TYPE_PAYPERVIEW:
				return Locale::translate('payment.type.payPerView');
			case PAYMENT_TYPE_DONATION:
				return Locale::translate('payment.type.donation');
			case PAYMENT_TYPE_SUBMISSION:
				return Locale::translate('payment.type.submission');
			case PAYMENT_TYPE_FASTTRACK:
				return Locale::translate('payment.type.fastTrack');				
			case PAYMENT_TYPE_PUBLICATION:
				return Locale::translate('payment.type.publication');
		}
	}
	
	function setRequestUrl($url) {
		$this->requestUrl = $url;
	}
	function getRequestUrl() {
		return $this->requestUrl;
	}
	
	

}

?>
