<?php

/**
 * @file classes/payment/ojs/OJSQueuedPayment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSQueuedPayment
 * @ingroup payment
 *
 * @brief Queued payment data structure for OJS
 *
 */

import('lib.pkp.classes.payment.QueuedPayment');

class OJSQueuedPayment extends QueuedPayment {
	/** @var int journal ID this payment applies to */
	var $journalId;

	/** @var int PAYMENT_TYPE_... */
	var $type;

	/** @var string URL associated with this payment */
	var $requestUrl;

	/**
	 * @copydoc QueuedPayment::QueuedPayment
	 */
	function __construct($amount, $currencyCode, $userId = null, $assocId = null) {
		parent::__construct($amount, $currencyCode, $userId, $assocId);
	}

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
	 * @return $journalId int New journal ID
	 */
	function setJournalId($journalId) {
		return $this->journalId = $journalId;
	}

	/**
	 * Set the type for this payment (PAYMENT_TYPE_...)
	 * @param $type int PAYMENT_TYPE_...
	 * @return int New payment type
	 */
	function setType($type) {
		return $this->type = $type;
	}

	/**
	 * Get the type of this payment (PAYMENT_TYPE_...)
	 * @return int PAYMENT_TYPE_...
	 */
	function getType() {
		return $this->type;
	}

	/**
	 * Returns the name of the QueuedPayment.
	 * Pulled from Journal Settings if present, or from locale file
	 * otherwise. For subscriptions, pulls subscription type name.
	 * @return string
	 */
	function getName() {
		switch ($this->type) {
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');

				if ($institutionalSubscriptionDao->subscriptionExists($this->assocId)) {
					$subscription = $institutionalSubscriptionDao->getById($this->assocId);
				} else {
					$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
					$subscription = $individualSubscriptionDao->getById($this->assocId);
				}
				if (!$subscription) return __('payment.type.subscription');

				$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
				$subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());

				return __('payment.type.subscription') . ' (' . $subscriptionType->getLocalizedName() . ')';
			case PAYMENT_TYPE_DONATION:
				// DEPRECATED: This is only for display of OJS 2.x data.
				return __('payment.type.donation');
			case PAYMENT_TYPE_MEMBERSHIP:
				return __('payment.type.membership');
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				return __('payment.type.purchaseArticle');
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				return __('payment.type.purchaseIssue');
			case PAYMENT_TYPE_SUBMISSION:
				// DEPRECATED: This is only for display of OJS 2.x data.
				return __('payment.type.submission');
			case PAYMENT_TYPE_FASTTRACK:
				// DEPRECATED: This is only for display of OJS 2.x data.
				return __('payment.type.fastTrack');
			case PAYMENT_TYPE_PUBLICATION:
				return __('payment.type.publication');
			default:
				// Invalid payment type
				assert(false);
		}
	}

	/**
	 * Set the request URL.
	 * @param $url string
	 * @return string New URL
	 */
	function setRequestUrl($url) {
		return $this->requestUrl = $url;
	}

	/**
	 * Get the request URL.
	 * @return string
	 */
	function getRequestUrl() {
		return $this->requestUrl;
	}
}

?>
