<?php

/**
 * @file classes/payment/ojs/OJSQueuedPayment.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	/** @var $journalId int journal ID this payment applies to */
	var $journalId;

	/** @var $type int PAYMENT_TYPE_... */
	var $type;

	/** @var $requestUrl string URL associated with this payment */
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
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getById($this->getJournalId());

		switch ($this->type) {
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

				if ($institutionalSubscriptionDao->subscriptionExists($this->assocId)) {
					$subscription =& $institutionalSubscriptionDao->getSubscription($this->assocId);
				} else {
					$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
					$subscription =& $individualSubscriptionDao->getSubscription($this->assocId);
				}
				if (!$subscription) return __('payment.type.subscription');

				$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
				$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

				return __('payment.type.subscription') . ' (' . $subscriptionType->getSubscriptionTypeName() . ')';
			case PAYMENT_TYPE_DONATION:
				if ($journal->getLocalizedSetting('donationFeeName') != '') {
					return $journal->getLocalizedSetting('donationFeeName');
				} else {
					return __('payment.type.donation');
				}
			case PAYMENT_TYPE_MEMBERSHIP:
				if ($journal->getLocalizedSetting('membershipFeeName') != '') {
					return $journal->getLocalizedSetting('membershipFeeName');
				} else {
					return __('payment.type.membership');
				}
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				if ($journal->getLocalizedSetting('purchaseArticleFeeName') != '') {
					return $journal->getLocalizedSetting('purchaseArticleFeeName');
				} else {
					return __('payment.type.purchaseArticle');
				}
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				if ($journal->getLocalizedSetting('purchaseIssueFeeName') != '') {
					return $journal->getLocalizedSetting('purchaseIssueFeeName');
				} else {
					return __('payment.type.purchaseIssue');
				}
			case PAYMENT_TYPE_SUBMISSION:
				if ($journal->getLocalizedSetting('submissionFeeName') != '') {
					return $journal->getLocalizedSetting('submissionFeeName');
				} else {
					return __('payment.type.submission');
				}
			case PAYMENT_TYPE_FASTTRACK:
				if ($journal->getLocalizedSetting('fastTrackFeeName') != '') {
					return $journal->getLocalizedSetting('fastTrackFeeName');
				} else {
					return __('payment.type.fastTrack');
				}
			case PAYMENT_TYPE_PUBLICATION:
				if ($journal->getLocalizedSetting('publicationFeeName') != '') {
					return $journal->getLocalizedSetting('publicationFeeName');
				} else {
					return __('payment.type.publication');
				}
			case PAYMENT_TYPE_GIFT:
				$giftDao =& DAORegistry::getDAO('GiftDAO');
				$gift =& $giftDao->getGift($this->assocId);

				// Try to return gift details in name
				if ($gift) {
					return $gift->getGiftName();
				}

				// Otherwise, generic gift name
				return __('payment.type.gift');
			default:
				// Invalid payment type
				assert(false);
		}
	}

	/**
	 * Returns the description of the QueuedPayment.
	 * Pulled from Journal Settings if present, or from locale file otherwise.
	 * For subscriptions, pulls subscription type name.
	 * @return string
	 */
	function getDescription() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getById($this->getJournalId());

		switch ($this->type) {
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

				if ($institutionalSubscriptionDao->subscriptionExists($this->assocId)) {
					$subscription =& $institutionalSubscriptionDao->getSubscription($this->assocId);
				} else {
					$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
					$subscription =& $individualSubscriptionDao->getSubscription($this->assocId);
				}
				if (!$subscription) return __('payment.type.subscription');

				$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
				$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());
				return $subscriptionType->getSubscriptionTypeDescription();
			case PAYMENT_TYPE_DONATION:
				if ($journal->getLocalizedSetting('donationFeeDescription') != '') {
					return $journal->getLocalizedSetting('donationFeeDescription');
				} else {
					return __('payment.type.donation');
				}
			case PAYMENT_TYPE_MEMBERSHIP:
				if ($journal->getLocalizedSetting('membershipFeeDescription') != '') {
					return $journal->getLocalizedSetting('membershipFeeDescription');
				} else {
					return __('payment.type.membership');
				}
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				if ($journal->getLocalizedSetting('purchaseArticleFeeDescription') != '') {
					return $journal->getLocalizedSetting('purchaseArticleFeeDescription');
				} else {
					return __('payment.type.purchaseArticle');
				}
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				if ($journal->getLocalizedSetting('purchaseIssueFeeDescription') != '') {
					return $journal->getLocalizedSetting('purchaseIssueFeeDescription');
				} else {
					return __('payment.type.purchaseIssue');
				}
			case PAYMENT_TYPE_SUBMISSION:
				if ($journal->getLocalizedSetting('submissionFeeDescription') != '') {
					return $journal->getLocalizedSetting('submissionFeeDescription');
				} else {
					return __('payment.type.submission');
				}
			case PAYMENT_TYPE_FASTTRACK:
				if ($journal->getLocalizedSetting('fastTrackFeeDescription') != '') {
					return $journal->getLocalizedSetting('fastTrackFeeDescription');
				} else {
					return __('payment.type.fastTrack');
				}
			case PAYMENT_TYPE_PUBLICATION:
				if ($journal->getLocalizedSetting('publicationFeeDescription') != '') {
					return $journal->getLocalizedSetting('publicationFeeDescription');
				} else {
					return __('payment.type.publication');
				}
			case PAYMENT_TYPE_GIFT:
				$giftDao =& DAORegistry::getDAO('GiftDAO');
				$gift =& $giftDao->getGift($this->assocId);

				// Try to return gift details in description
				if ($gift) {
					import('classes.gift.Gift');

					if ($gift->getGiftType() == GIFT_TYPE_SUBSCRIPTION) {
						$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
						$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($gift->getAssocId());

						if ($subscriptionType) {
							return $subscriptionType->getSubscriptionTypeDescription();	
						} else {
							return __('payment.type.gift') . ' ' . __('payment.type.gift.subscription');								
						}
					}
				}

				// Otherwise, generic gift name
				return __('payment.type.gift');
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
