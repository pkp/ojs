<?php

/**
 * @file classes/payment/ojs/OJSPaymentManager.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSPaymentManager
 * @ingroup payment
 * @see QueuedPayment
 *
 * @brief Provides payment management functions.
 *
 */

import('lib.pkp.classes.payment.QueuedPayment');
import('lib.pkp.classes.payment.PaymentManager');

define('PAYMENT_TYPE_MEMBERSHIP',		0x000000001);
define('PAYMENT_TYPE_RENEW_SUBSCRIPTION',	0x000000002);
define('PAYMENT_TYPE_PURCHASE_ARTICLE',		0x000000003);
define('PAYMENT_TYPE_DONATION',			0x000000004);
define('PAYMENT_TYPE_SUBMISSION',		0x000000005);
define('PAYMENT_TYPE_FASTTRACK',		0x000000006);
define('PAYMENT_TYPE_PUBLICATION',		0x000000007);
define('PAYMENT_TYPE_PURCHASE_SUBSCRIPTION',	0x000000008);
define('PAYMENT_TYPE_PURCHASE_ISSUE',		0x000000009);

class OJSPaymentManager extends PaymentManager {
	/**
	 * Determine whether the payment system is configured.
	 * @return boolean true iff configured
	 */
	function isConfigured() {
		return parent::isConfigured() && $this->_context->getData('paymentsEnabled');
	}

	/**
	 * Create a queued payment.
	 * @param $request PKPRequest
	 * @param $type int PAYMENT_TYPE_...
	 * @param $userId int ID of user responsible for payment
	 * @param $assocId int ID of associated entity
	 * @param $amount float Amount of currency $currencyCode
	 * @param $currencyCode string optional ISO 4217 currency code
	 * @return QueuedPayment
	 */
	function createQueuedPayment($request, $type, $userId, $assocId, $amount, $currencyCode = null) {
		if (is_null($currencyCode)) $currencyCode = $this->_context->getData('currency');
		$payment = new QueuedPayment($amount, $currencyCode, $userId, $assocId);
		$payment->setContextId($this->_context->getId());
		$payment->setType($type);
		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();

		switch ($type) {
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				$payment->setRequestUrl($dispatcher->url($request, ROUTE_PAGE, null, 'article', 'view', $assocId));
				break;
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				$payment->setRequestUrl($dispatcher->url($request, ROUTE_PAGE, null, 'issue', 'view', $assocId));
				break;
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
				$payment->setRequestUrl($dispatcher->url($request, ROUTE_PAGE, null, 'issue', 'current'));
				break;
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$payment->setRequestUrl($dispatcher->url($request, ROUTE_PAGE, null, 'user', 'subscriptions'));
				break;
			case PAYMENT_TYPE_PUBLICATION:
				$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
				$submission = $submissionDao->getById($assocId);
				if ($submission->getSubmissionProgress()!=0) {
					$payment->setRequestUrl($dispatcher->url($request, ROUTE_PAGE, null, 'submission', 'wizard', $submission->getSubmissionProgress(), array('submissionId' => $assocId)));
				} else {
					$payment->setRequestUrl($dispatcher->url($request, ROUTE_PAGE, null, 'authorDashboard', 'submission', $submission->getId()));
				}
				break;
			case PAYMENT_TYPE_MEMBERSHIP: // Deprecated
			case PAYMENT_TYPE_DONATION: // Deprecated
			case PAYMENT_TYPE_FASTTRACK: // Deprecated
			case PAYMENT_TYPE_SUBMISSION: // Deprecated
			default:
				// Invalid payment type
				error_log('Invalid payment type "' . $type . '"');
				assert(false);
				break;
		}

		return $payment;
	}

	/**
	 * Create a completed payment from a queued payment.
	 * @param $queuedPayment QueuedPayment Payment to complete.
	 * @param $payMethod string Name of payment plugin used.
	 * @param $userId int User ID to attribute payment to (if unspecified, will be taken from queued payment)
	 * @return CompletedPayment
	 */
	function createCompletedPayment($queuedPayment, $payMethod, $userId = null) {
		import('lib.pkp.classes.payment.CompletedPayment');
		$payment = new CompletedPayment();
		$payment->setContextId($queuedPayment->getContextId());
		$payment->setType($queuedPayment->getType());
		$payment->setAmount($queuedPayment->getAmount());
		$payment->setCurrencyCode($queuedPayment->getCurrencyCode());

		if ($userId) $payment->setUserId($userId);
		else $payment->setUserId($queuedPayment->getUserId());

		$payment->setAssocId($queuedPayment->getAssocId());
		$payment->setPayMethodPluginName($payMethod);

		return $payment;
	}

	/**
	 * Determine whether publication fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function publicationEnabled() {
		return $this->isConfigured() && $this->_context->getData('publicationFee') > 0;
	}

	/**
	 * Determine whether publication fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function membershipEnabled() {
		return $this->isConfigured() && $this->_context->getData('membershipFee') > 0;
	}

	/**
	 * Determine whether article purchase fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function purchaseArticleEnabled() {
		return $this->isConfigured() && $this->_context->getData('purchaseArticleFee') > 0;
	}

	/**
	 * Determine whether issue purchase fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function purchaseIssueEnabled() {
		return $this->isConfigured() && $this->_context->getData('purchaseIssueFee') > 0;
	}

	/**
	 * Determine whether PDF-only article purchase fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function onlyPdfEnabled() {
		return $this->isConfigured() && $this->_context->getData('restrictOnlyPdf');
	}

	/**
	 * Get the payment plugin.
	 * @return PaymethodPlugin
	 */
	function getPaymentPlugin() {
		$paymentMethodPluginName = $this->_context->getData('paymentPluginName');
		$paymentMethodPlugin = null;
		if (!empty($paymentMethodPluginName)) {
			$plugins = PluginRegistry::loadCategory('paymethod');
			if (isset($plugins[$paymentMethodPluginName])) $paymentMethodPlugin = $plugins[$paymentMethodPluginName];
		}
		return $paymentMethodPlugin;
	}

	/**
	 * Fulfill a queued payment.
	 * @param $request PKPRequest
	 * @param $queuedPayment QueuedPayment
	 * @param $payMethodPluginName string Name of payment plugin.
	 * @return mixed Dependent on payment type.
	 */
	function fulfillQueuedPayment($request, $queuedPayment, $payMethodPluginName = null) {
		$returner = false;
		$journal = $request->getContext();
		if ($queuedPayment) switch ($queuedPayment->getType()) {
			case PAYMENT_TYPE_MEMBERSHIP:
				$userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
				$user = $userDao->getById($queuedPayment->getUserId());
				$dateEnd = $user->getSetting('dateEndMembership', 0);
				if (!$dateEnd) $dateEnd = 0;

				// if the membership is expired, extend it to today + 1 year
				$time = time();
				if ($dateEnd < $time ) $dateEnd = $time;

				$dateEnd = mktime(23, 59, 59, date("m", $dateEnd), date("d", $dateEnd), date("Y", $dateEnd)+1);
				$user->updateSetting('dateEndMembership', $dateEnd, 'date', 0);
				$returner = true;
				break;
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
				$subscriptionId = $queuedPayment->getAssocId();
				$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $institutionalSubscriptionDao InstitutionalSubscriptionDAO */
				$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $individualSubscriptionDao IndividualSubscriptionDAO */
				if ($institutionalSubscriptionDao->subscriptionExists($subscriptionId)) {
					$subscription = $institutionalSubscriptionDao->getById($subscriptionId);
					$institutional = true;
				} else {
					$subscription = $individualSubscriptionDao->getById($subscriptionId);
					$institutional = false;
				}
				if (!$subscription || $subscription->getUserId() != $queuedPayment->getUserId() || $subscription->getJournalId() != $queuedPayment->getContextId()) {
					fatalError('Subscription integrity checks fail!');
					return false;
				}
				// Update subscription end date now that payment is completed
				if ($institutional) {
					// Still requires approval from JM/SM since includes domain and IP ranges
					import('classes.subscription.InstitutionalSubscription');
					$subscription->setStatus(SUBSCRIPTION_STATUS_NEEDS_APPROVAL);
					if ($subscription->isNonExpiring()) {
						$institutionalSubscriptionDao->updateObject($subscription);
					} else {
						$institutionalSubscriptionDao->renewSubscription($subscription);
					}

					// Notify JM/SM of completed online purchase
					if ($journal->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($request, $subscription, 'SUBSCRIPTION_PURCHASE_INSTL');
					}
				} else {
					import('classes.subscription.IndividualSubscription');
					$subscription->setStatus(SUBSCRIPTION_STATUS_ACTIVE);
					if ($subscription->isNonExpiring()) {
						$individualSubscriptionDao->updateObject($subscription);
					} else {
						$individualSubscriptionDao->renewSubscription($subscription);
					}
					// Notify JM/SM of completed online purchase
					if ($journal->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($request, $subscription, 'SUBSCRIPTION_PURCHASE_INDL');
					}
				}
				$returner = true;
				break;
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$subscriptionId = $queuedPayment->getAssocId();
				$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $institutionalSubscriptionDao InstitutionalSubscriptionDAO */
				$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $individualSubscriptionDao IndividualSubscriptionDAO */
				if ($institutionalSubscriptionDao->subscriptionExists($subscriptionId)) {
					$subscription = $institutionalSubscriptionDao->getById($subscriptionId);
					$institutional = true;
				} else {
					$subscription = $individualSubscriptionDao->getById($subscriptionId);
					$institutional = false;
				}
				if (!$subscription || $subscription->getUserId() != $queuedPayment->getUserId() || $subscription->getJournalId() != $queuedPayment->getContextId()) {
					return false;
				}
				if ($institutional) {
					$institutionalSubscriptionDao->renewSubscription($subscription);

					// Notify JM/SM of completed online purchase
					if ($journal->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($request, $subscription, 'SUBSCRIPTION_RENEW_INSTL');
					}
				} else {
					$individualSubscriptionDao->renewSubscription($subscription);

					// Notify JM/SM of completed online purchase
					if ($journal->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual')) {
						import('classes.subscription.SubscriptionAction');
						SubscriptionAction::sendOnlinePaymentNotificationEmail($request, $subscription, 'SUBSCRIPTION_RENEW_INDL');
					}
				}
				$returner = true;
				break;
			case PAYMENT_TYPE_DONATION:
				assert(false); // Deprecated
				$returner = true;
				break;
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
			case PAYMENT_TYPE_PURCHASE_ISSUE:
			case PAYMENT_TYPE_SUBMISSION:
			case PAYMENT_TYPE_PUBLICATION:
				$returner = true;
				break;
			default:
				// Invalid payment type
				assert(false);
		}
		$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /* @var $completedPaymentDao OJSCompletedPaymentDAO */
		$completedPayment = $this->createCompletedPayment($queuedPayment, $payMethodPluginName, $request->getUser()->getId());
		$completedPaymentDao->insertObject($completedPayment);

		$queuedPaymentDao = DAORegistry::getDAO('QueuedPaymentDAO'); /* @var $queuedPaymentDao QueuedPaymentDAO */
		$queuedPaymentDao->deleteById($queuedPayment->getId());

		return $returner;
	}

	/**
	 * Fetch the name of the description.
	 * @return string
	 */
	function getPaymentName($payment) {
		switch ($payment->getType()) {
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $institutionalSubscriptionDao InstitutionalSubscriptionDAO */

				if ($institutionalSubscriptionDao->subscriptionExists($payment->getAssocId())) {
					$subscription = $institutionalSubscriptionDao->getById($payment->getAssocId());
				} else {
					$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $individualSubscriptionDao IndividualSubscriptionDAO */
					$subscription = $individualSubscriptionDao->getById($payment->getAssocId());
				}
				if (!$subscription) return __('payment.type.subscription');

				$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
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
}


