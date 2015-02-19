<?php

/**
 * @defgroup payment_ojs
 */

/**
 * @file classes/payment/ojs/OJSCompletedPayment.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSCompletedPayment
 * @ingroup payment_ojs
 * @see OJSCompletedPaymentDAO
 *
 * @brief Class describing a payment ready to be in the database.
 *
 */
import('lib.pkp.classes.payment.Payment');

class OJSCompletedPayment extends Payment {
	var $journalId;
	var $paperId;
	var $type;
	var $timestamp;
	var $payMethod;

	/**
	 * Constructor
	 */
	function OJSCompletedPayment() {
		parent::Payment();
	}

	/**
	 * Get/set methods
	 */

	/**
	 * Set the  ID of the payment.
	 * @param $queuedPaymentId int
	 */
	function setCompletedPaymentId($queuedPaymentId) {
		parent::setPaymentId($queuedPaymentId);
	}

	/**
	 * Get the ID of the payment.
	 * @return int
	 */
	function getCompletedPaymentId() {
		return parent::getPaymentId();
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
	 */
	function setJournalId($journalId) {
		$this->journalId = $journalId;
	}

	/**
	 * Set the Payment Type
	 * @param $type int
	 */
	function setType($type) {
		$this->type = $type;
	}

	/**
	 * Set the Payment Type
	 * @return $type int
	 */
	function getType() {
		return $this->type;
	}

	/**
	 * Returns the description of the CompletedPayment.
	 * Pulled from Journal Settings if present, or from locale file otherwise.
	 * For subscriptions, pulls subscription type name.
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
		}
	}

	/**
	 * Returns the description of the CompletedPayment.
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
		}
	}

	/**
	 * Get the row id of the payment.
	 * @return int
	 */
	function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Set the id of payment
	 * @param $dt int/string *nix timestamp or ISO datetime string
	 */
	function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
	}

	/**
	 * Get the  method of payment.
	 * @return String
	 */
	function getPayMethodPluginName() {
		return $this->payMethod;
	}

	/**
	 * Set the method of payment.
	 * @param $journalId String
	 */
	function setPayMethodPluginName($payMethod){
		$this->payMethod = $payMethod;
	}

	/**
	 * Display-related get Methods
	 */

	/**
	 * Check if the type is a membership
	 * @return bool
	 */
	function isMembership() {
		return $this->type == PAYMENT_TYPE_MEMBERSHIP;
	}

	/**
	 * Check if the type is a subscription
	 * @return bool
	 */
	function isSubscription() {
		return ($this->type == PAYMENT_TYPE_RENEW_SUBSCRIPTION || $this->type == PAYMENT_TYPE_PURCHASE_SUBSCRIPTION);
	}

	/**
	 * Get some information about the assocId for display.
	 * @return String
	 */
	function getAssocDescription() {
		if (!$this->assocId) return false;
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
				if (!$subscription) return __('manager.payment.notFound');

				$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
				$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

				$membership = $subscription->getMembership();
				$typeName = $subscriptionType->getSubscriptionTypeName();
				if ($membership) return $typeName . ' ('. $membership . ')';
				return $typeName;
			case PAYMENT_TYPE_SUBMISSION:
			case PAYMENT_TYPE_FASTTRACK:
			case PAYMENT_TYPE_PUBLICATION:
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				// all the article-related payments should output the article title
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				$article =& $articleDao->getArticle($this->assocId, $this->journalId);
				if (!$article) return __('manager.payment.notFound');
				return $article->getLocalizedTitle();
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				// Purchase issue payment should output the issue title
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issue =& $issueDao->getIssueById($this->assocId, $this->journalId);
				if (!$issue) return __('manager.payment.notFound');
				return $issue->getIssueIdentification(false, true);
			case PAYMENT_TYPE_GIFT:
				$giftDao =& DAORegistry::getDAO('GiftDAO');
				$gift =& $giftDao->getGift($this->assocId);

				// Try to get buyer and recipient details
				if ($gift) {
					return __('gifts.buyer') . ': ' . $gift->getBuyerFullName() . ' (' . $gift->getBuyerEmail() . ') ' . __('gifts.recipient') . ': ' . $gift->getRecipientFullName() . ' (' . $gift->getRecipientEmail() . ')';
				} else {
					return false;
				}
			case PAYMENT_TYPE_MEMBERSHIP:
			case PAYMENT_TYPE_DONATION:
				return false;
		}

		return false;
	}
}

?>
