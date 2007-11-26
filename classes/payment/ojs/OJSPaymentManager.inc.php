<?php

/**
 * @file PaymentManager.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package payment
 * @class	OJSPaymentManager
 *
 * Provides payment management functions.
 *
 */

import('payment.ojs.OJSQueuedPayment');
import('payment.PaymentManager');

define('PAYMENT_TYPE_MEMBERSHIP', 0x000000001 ); 
define('PAYMENT_TYPE_SUBSCRIPTION', 0x000000002 );
define('PAYMENT_TYPE_PAYPERVIEW', 0x000000003 ); 
define('PAYMENT_TYPE_DONATION', 0x000000004 ); 
define('PAYMENT_TYPE_SUBMISSION', 0x000000005 );
define('PAYMENT_TYPE_FASTTRACK', 0x000000006 ); 
define('PAYMENT_TYPE_PUBLICATION', 0x000000007 );

class OJSPaymentManager extends PaymentManager {
	function &getManager() {
		static $manager;
		if (!isset($manager)) {
			$manager =& new OJSPaymentManager();
		}
		return $manager;
	}
	
	function isConfigured() {
		$journal =& Request::getJournal();
		return parent::isConfigured() && $journal->getSetting('journalPaymentsEnabled');
	}

	function &createQueuedPayment($journalId, $type, $userId, $assocId, $amount, $currencyCode = null) {
		$journalSettingsDAO =& DAORegistry::getDAO('JournalSettingsDAO');
		if ( is_null($currencyCode) ) $currencyCode = $journalSettingsDAO->getSetting($journalId, 'currency');
		$payment =& new OJSQueuedPayment($amount, $currencyCode, $userId, $assocId);
		$payment->setJournalId($journalId);
		$payment->setType($type);

	 	switch ( $type ) {
			case PAYMENT_TYPE_PAYPERVIEW:
				if ( isset($_SERVER['HTTP_REFERER']) ) {	 		
					$payment->setRequestUrl($_SERVER['HTTP_REFERER'] );
				} else { 
					$payment->setRequestUrl(Request::url(null, 'article', 'view', $assocId ) );
				}	
				break;
			case PAYMENT_TYPE_MEMBERSHIP:
			case PAYMENT_TYPE_SUBSCRIPTION:
				$payment->setRequestUrl(Request::url(null, 'user') );			
				break;				
			case PAYMENT_TYPE_DONATION:
				$payment->setRequestUrl(Request::url(null, 'donations', 'thankYou') );		
				break;
			case PAYMENT_TYPE_FASTTRACK:
			case PAYMENT_TYPE_PUBLICATION:
			case PAYMENT_TYPE_SUBMISSION:
				$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
				$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($assocId);
				if ($authorSubmission->getSubmissionProgress()!=0) {
					$payment->setRequestUrl(Request::url(null, 'author', 'submit', $authorSubmission->getSubmissionProgress(), array('articleId' => $assocId)));
				} else { 
					$payment->setRequestUrl(Request::url(null, 'author') );
				}		
				break;
			default:
				// something went wrong. crud.				
				break;
		}	 	

		return $payment;
	}
	
	function &createCompletedPayment( $queuedPayment, $payMethod ) {
		import('payment.ojs.OJSCompletedPayment');
		$payment =& new OJSCompletedPayment();
		$payment->setJournalId($queuedPayment->getJournalId());
		$payment->setType($queuedPayment->getType());
		$payment->setAmount($queuedPayment->getAmount());
		$payment->setCurrencyCode($queuedPayment->getCurrencyCode());
		$payment->setUserId($queuedPayment->getUserId());
		$payment->setAssocId($queuedPayment->getAssocId());
		$payment->setPayMethodPluginName($payMethod);
		
		return $payment;
	}
	
	function donationEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('donationFeeEnabled');	
	}
	
	function submissionEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('submissionFeeEnabled') && $journal->getSetting('submissionFee') > 0;	
	}
	
	function fastTrackEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('fastTrackFeeEnabled') && $journal->getSetting('fastTrackFee') > 0;
	}
	
	function publicationEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('publicationFeeEnabled') && $journal->getSetting('publicationFee') > 0;
	}	

	function membershipEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('membershipFeeEnabled') && $journal->getSetting('membershipFee') > 0;		
	}
	
	function payPerViewEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('payPerViewFeeEnabled') && $journal->getSetting('payPerViewFee') > 0;
	}
	
	function onlyPdfEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('restrictOnlyPdf');				
	}	
		
	function acceptSubscriptionPayments() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('acceptSubscriptionPayments');	
	}
	
	function &getPaymentPlugin() {
		$journal =& Request::getJournal();
		$paymentMethodPluginName = $journal->getSetting('paymentMethodPluginName');
		$paymentMethodPlugin = null;
		if (!empty($paymentMethodPluginName)) {
			$plugins =& PluginRegistry::loadCategory('paymethod');
			if (isset($plugins[$paymentMethodPluginName])) $paymentMethodPlugin =& $plugins[$paymentMethodPluginName];
		}
		return $paymentMethodPlugin;
	}

	function fulfillQueuedPayment(&$queuedPayment, $payMethodPluginName = null) {
		$returner = false;
		if ($queuedPayment) switch ($queuedPayment->getType()) {
			case PAYMENT_TYPE_MEMBERSHIP:
				$userDao =& DAORegistry::getDAO('UserDAO');
				$user =& $userDao->getUser($queuedPayment->getuserId());
				$userDao->renewMembership($user);
				$returner = true;
				break;
			case PAYMENT_TYPE_SUBSCRIPTION:
				$subscriptionId = $queuedPayment->getAssocId();
				$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');
				$subscription =& $subscriptionDao->getSubscription($subscriptionId);
				if (!$subscription || $subscription->getUserId() != $queuedPayment->getUserId() || $subscription->getJournalId() != $queuedPayment->getJournalId()) {error_log(print_r($subscription, true)); return false;}

				$subscriptionDao->renewSubscription($subscription);
				
				$returner = true;
				break;
			case PAYMENT_TYPE_FASTTRACK:
				$articleDAO =& DAORegistry::getDAO('ArticleDAO');
				$article =& $articleDAO->getArticle($queuedPayment->getAssocId(), $queuedPayment->getJournalId());			
				$article->setFastTracked(true);
				$articleDAO->updateArticle($article);
				$returner = true;
				break;
			case PAYMENT_TYPE_PAYPERVIEW:
			case PAYMENT_TYPE_DONATION:
			case PAYMENT_TYPE_SUBMISSION:
			case PAYMENT_TYPE_PUBLICATION:
				$returner = true;
		}
		$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$completedPayment =& $this->createCompletedPayment($queuedPayment, $payMethodPluginName);
		$completedPaymentDao->insertCompletedPayment($completedPayment);
	
		$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPaymentDao->deleteQueuedPayment($queuedPayment);

		return $returner;
	}
	
	
}

?>
