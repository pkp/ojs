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
	
	/**
	 * Returns the description of the QueuedPayment.  
	 * Pulled from Journal Settings if present, or from locale file otherwise.
	 * For subscriptions, pulls subscription type name.
	 * @return string 
	 */
	function getName() {
		$journalDAO =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDAO->getJournal($this->getJournalId());
		
		switch ($this->type) {
			case PAYMENT_TYPE_SUBSCRIPTION:
				$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
				$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');
				
				$subscription =& $subscriptionDAO->getSubscription($this->assocId);
				if ( !$subscription) return Locale::translate('payment.type.subscription');
				
				$subscriptionType =& $subscriptionTypeDAO->getSubscriptionType($subscription->getTypeId());

				return Locale::translate('payment.type.subscription') . '(' . $subscriptionType->getSubscriptionTypeName() . ')';
			case PAYMENT_TYPE_DONATION:
				if ( $journal->getLocalizedSetting('donationFeeName') != '') {
					return $journal->getLocalizedSetting('donationFeeName');
				} else { 	
					return Locale::translate('payment.type.donation');
				}			
			case PAYMENT_TYPE_MEMBERSHIP:	
				if ( $journal->getLocalizedSetting('membershipFeeName') != '') {
					return $journal->getLocalizedSetting('membershipFeeName');
				} else { 	
					return Locale::translate('payment.type.membership');
				}
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				if ( $journal->getLocalizedSetting('purchaseArticleFeeName') != '' ) {
					return $journal->getLocalizedSetting('purchaseArticleFeeName');
				} else { 	
					return Locale::translate('payment.type.purchaseArticle');
				}
			case PAYMENT_TYPE_SUBMISSION:
				if ( $journal->getLocalizedSetting('submissionFeeName') != '' ) {
					return $journal->getLocalizedSetting('submissionFeeName');
				} else { 	
					return Locale::translate('payment.type.submission');
				}
			case PAYMENT_TYPE_FASTTRACK:
				if ( $journal->getLocalizedSetting('fastTrackFeeName') != '' ) {
					return $journal->getLocalizedSetting('fastTrackFeeName');
				} else { 	
					return Locale::translate('payment.type.fastTrack');
				}				
			case PAYMENT_TYPE_PUBLICATION:
				if ( $journal->getLocalizedSetting('publicationFeeName') != '' ) {
					return $journal->getLocalizedSetting('publicationFeeName');
				} else { 	
					return Locale::translate('payment.type.publication');
				}
		}
	}		
	
	/**
	 * Returns the description of the QueuedPayment.  
	 * Pulled from Journal Settings if present, or from locale file otherwise.
	 * For subscriptions, pulls subscription type name.
	 * @return string 
	 */
	function getDescription() {
		$journalDAO =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDAO->getJournal($this->getJournalId());
		
		switch ($this->type) {
			case PAYMENT_TYPE_SUBSCRIPTION:
				$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
				$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');
				
				$subscription =& $subscriptionDAO->getSubscription($this->assocId);
				if ( !$subscription) return Locale::translate('payment.type.subscription');
				
				$subscriptionType =& $subscriptionTypeDAO->getSubscriptionType($subscription->getTypeId());
				return $subscriptionType->getSubscriptionTypeDescription();
			case PAYMENT_TYPE_DONATION:
				if ( $journal->getLocalizedSetting('donationFeeDescription') != '') {
					return $journal->getLocalizedSetting('donationFeeDescription');
				} else { 	
					return Locale::translate('payment.type.donation');
				}						
			case PAYMENT_TYPE_MEMBERSHIP:	
				if ( $journal->getLocalizedSetting('membershipFeeDescription') != '') {
					return $journal->getLocalizedSetting('membershipFeeDescription');
				} else { 	
					return Locale::translate('payment.type.membership');
				}				
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				if ( $journal->getLocalizedSetting('purchaseArticleFeeDescription') != '') {
					return $journal->getLocalizedSetting('purchaseArticleFeeDescription');
				} else { 	
					return Locale::translate('payment.type.purchaseArticle');
				}
			case PAYMENT_TYPE_SUBMISSION:
				if ( $journal->getLocalizedSetting('submissionFeeDescription') != '' ) {
					return $journal->getLocalizedSetting('submissionFeeDescription');
				} else { 	
					return Locale::translate('payment.type.submission');
				}
			case PAYMENT_TYPE_FASTTRACK:
				if ( $journal->getLocalizedSetting('fastTrackFeeDescription') != '' ) {
					return $journal->getLocalizedSetting('fastTrackFeeDescription');
				} else { 	
					return Locale::translate('payment.type.fastTrack');
				}								
			case PAYMENT_TYPE_PUBLICATION:
				if ( $journal->getLocalizedSetting('publicationFeeDescription') != '' ) {
					return $journal->getLocalizedSetting('publicationFeeDescription');
				} else { 	
					return Locale::translate('payment.type.publication');
				}
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
