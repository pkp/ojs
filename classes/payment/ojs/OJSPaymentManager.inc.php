<?php

/**
 * @file classes/payment/ojs/PaymentManager.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSPaymentManager
 * @ingroup payment
 * @see OJSQueuedPayment
 *
 * @brief Provides payment management functions.
 *
 */

//$Id$

import('classes.payment.ojs.OJSQueuedPayment');
import('lib.pkp.classes.payment.PaymentManager');

define('PAYMENT_TYPE_MEMBERSHIP',		0x000000001 );
define('PAYMENT_TYPE_RENEW_SUBSCRIPTION',	0x000000002 );
define('PAYMENT_TYPE_PURCHASE_ARTICLE',		0x000000003 );
define('PAYMENT_TYPE_DONATION',			0x000000004 );
define('PAYMENT_TYPE_SUBMISSION',		0x000000005 );
define('PAYMENT_TYPE_FASTTRACK',		0x000000006 );
define('PAYMENT_TYPE_PUBLICATION',		0x000000007 );
define('PAYMENT_TYPE_PURCHASE_SUBSCRIPTION',	0x000000008 );

class OJSPaymentManager extends PaymentManager {
	function &getManager() {
		static $manager;
		if (!isset($manager)) {
			$manager = new OJSPaymentManager();
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
		$payment = new OJSQueuedPayment($amount, $currencyCode, $userId, $assocId);
		$payment->setJournalId($journalId);
		$payment->setType($type);

	 	switch ( $type ) {
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				$payment->setRequestUrl(Request::url(null, 'article', 'view', $assocId ) );
				break;
			case PAYMENT_TYPE_MEMBERSHIP:
				$payment->setRequestUrl(Request::url(null, 'user') );
				break;
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$payment->setRequestUrl(Request::url(null, 'user', 'subscriptions') );
				break;
			case PAYMENT_TYPE_DONATION:
				$payment->setRequestUrl(Request::url(null, 'donations', 'thankYou') );
				break;
			case PAYMENT_TYPE_FASTTRACK:
			case PAYMENT_TYPE_PUBLICATION:
			case PAYMENT_TYPE_SUBMISSION:
				$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
				$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($assocId);
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
		import('classes.payment.ojs.OJSCompletedPayment');
		$payment = new OJSCompletedPayment();
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

	function purchaseArticleEnabled() {
		$journal =& Request::getJournal();
		return $this->isConfigured() && $journal->getSetting('purchaseArticleFeeEnabled') && $journal->getSetting('purchaseArticleFee') > 0;
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
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
				$subscriptionId = $queuedPayment->getAssocId();
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
				if ($institutionalSubscriptionDao->subscriptionExists($subscriptionId)) {
					$subscription =& $institutionalSubscriptionDao->getSubscription($subscriptionId);
					$institutional = true;
				} else {
					$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
					$subscription =& $individualSubscriptionDao->getSubscription($subscriptionId);
					$institutional = false;
				}
				if (!$subscription || $subscription->getUserId() != $queuedPayment->getUserId() || $subscription->getJournalId() != $queuedPayment->getJournalId()) {
					// FIXME: Is this supposed to be here?
					error_log(print_r($subscription, true));
					return false;
				}
				// Update subscription end date now that payment is completed
				if ($institutional) {
					// Still requires approval from JM/SM since includes domain and IP ranges
					import('classes.subscription.InstitutionalSubscription');
					$subscription->setStatus(SUBSCRIPTION_STATUS_NEEDS_APPROVAL);
					if ($subscription->isNonExpiring()) {
						$institutionalSubscriptionDao->updateSubscription($subscription);
					} else {
						$institutionalSubscriptionDao->renewSubscription($subscription);
					}

					// Notify JM/SM of completed online purchase
					$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
					if ($journalSettingsDao->getSetting($subscription->getJournalId(), 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($subscription, 'SUBSCRIPTION_PURCHASE_INSTL');
					}
				} else {
					import('classes.subscription.IndividualSubscription');
					$subscription->setStatus(SUBSCRIPTION_STATUS_ACTIVE);
					if ($subscription->isNonExpiring()) {
						$individualSubscriptionDao->updateSubscription($subscription);
					} else {
						$individualSubscriptionDao->renewSubscription($subscription);
					}
					// Notify JM/SM of completed online purchase
					$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
					if ($journalSettingsDao->getSetting($subscription->getJournalId(), 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($subscription, 'SUBSCRIPTION_PURCHASE_INDL');
					}
				}
				$returner = true;
				break;
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$subscriptionId = $queuedPayment->getAssocId();
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
				if ($institutionalSubscriptionDao->subscriptionExists($subscriptionId)) {
					$subscription =& $institutionalSubscriptionDao->getSubscription($subscriptionId);
					$institutional = true;
				} else {
					$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
					$subscription =& $individualSubscriptionDao->getSubscription($subscriptionId);
					$institutional = false;
				}
				if (!$subscription || $subscription->getUserId() != $queuedPayment->getUserId() || $subscription->getJournalId() != $queuedPayment->getJournalId()) {
					// FIXME: Is this supposed to be here?
					error_log(print_r($subscription, true));
					return false;
				}
				if ($institutional) {
					$institutionalSubscriptionDao->renewSubscription($subscription);

					// Notify JM/SM of completed online purchase
					$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
					if ($journalSettingsDao->getSetting($subscription->getJournalId(), 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($subscription, 'SUBSCRIPTION_RENEW_INSTL');
					}
				} else {
					$individualSubscriptionDao->renewSubscription($subscription);

					// Notify JM/SM of completed online purchase
					$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
					if ($journalSettingsDao->getSetting($subscription->getJournalId(), 'enableSubscriptionOnlinePaymentNotificationRenewIndividual')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($subscription, 'SUBSCRIPTION_RENEW_INDL');
					}
				}
				$returner = true;
				break;
			case PAYMENT_TYPE_FASTTRACK:
				$articleDAO =& DAORegistry::getDAO('ArticleDAO');
				$article =& $articleDAO->getArticle($queuedPayment->getAssocId(), $queuedPayment->getJournalId());
				$article->setFastTracked(true);
				$articleDAO->updateArticle($article);
				$returner = true;
				break;
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
			case PAYMENT_TYPE_DONATION:
			case PAYMENT_TYPE_SUBMISSION:
			case PAYMENT_TYPE_PUBLICATION:
				$returner = true;
		}
		$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$completedPayment =& $this->createCompletedPayment($queuedPayment, $payMethodPluginName);
		$completedPaymentDao->insertCompletedPayment($completedPayment);

		$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPaymentDao->deleteQueuedPayment($queuedPayment->getQueuedPaymentId());

		return $returner;
	}
}

?>
