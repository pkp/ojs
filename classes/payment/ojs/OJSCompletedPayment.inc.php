<?php

/**
 * @file OJSCompletedPayment.inc.php
 *
 * Copyright (c) 2006 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package payment.ojs
 * @class OJSCompletedPayment 
 *
 * CompletedPayment class.
 * Class describing a payment ready to be in the database.
 *
 */
import('payment.Payment');

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
	 * Return a string representation of the payment type
	 * @return String
	 */
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
		return $this->type == PAYMENT_TYPE_SUBSCRIPTION;
	}
	 
	 
	/**
	 * Get the username from the userId in the payment
	 * @return String
	 */
	function getUsername() {
		$userId = $this->userId;
		if ( !$userId )
			return false;
		$userDAO = &DAORegistry::getDAO('UserDAO');
		$user =& $userDAO->getUser($userId);
		if ( !$user )
			return false;
		return $user->getUsername();
		
	}

	/**
	 * Get some information about the assocId for display.
	 * @return String
	 */
	function getAssocDescription() {
		if ( !$this->assocId ) return false;
		switch ($this->type) {
			case PAYMENT_TYPE_SUBSCRIPTION:
				$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
				$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');
				
				$subscription =& $subscriptionDAO->getSubscription($this->assocId);
				if ( !$subscription) return Locale::translate('manager.payment.notFound');
				
				$subscriptionType =& $subscriptionTypeDAO->getSubscriptionType($subscription->getTypeId());

				$membership = $subscription->getMembership();
				$typeName = $subscriptionType->getSubscriptionTypeName();
				if ( $membership )
					return $typeName . ' ('. $membership . ')';
				else
					return $typeName;
			case PAYMENT_TYPE_SUBMISSION:
			case PAYMENT_TYPE_FASTTRACK:				
			case PAYMENT_TYPE_PUBLICATION:
			case PAYMENT_TYPE_PAYPERVIEW:
				// all the article-related payments should output the article title
				$articleDAO = &DAORegistry::getDAO('ArticleDAO');
				$article =& $articleDAO->getArticle($this->assocId, $this->journalId);
				if ( !$article ) return Locale::translate('manager.payment.notFound');
				return $article->getArticleTitle();
			case PAYMENT_TYPE_MEMBERSHIP:
			case PAYMENT_TYPE_DONATION:
				return false;
		}
		
		return false;
	}
		
}

?>
