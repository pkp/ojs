<?php

/**
 * @file classes/payment/ojs/OJSPaymentManager.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSPaymentManager
 * @ingroup payment
 * @see OJSQueuedPayment
 *
 * @brief Provides payment management functions.
 *
 */

import('classes.payment.ojs.OJSQueuedPayment');
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
define('PAYMENT_TYPE_GIFT',			0x000000010);

class OJSPaymentManager extends PaymentManager {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function OJSPaymentManager(&$request) {
		parent::PaymentManager($request);
	}

	/**
	 * Determine whether the payment system is configured.
	 * @return boolean true iff configured
	 */
	function isConfigured() {
		$journal =& $this->request->getJournal();
		return parent::isConfigured() && $journal->getSetting('journalPaymentsEnabled');
	}

	/**
	 * Create a queued payment.
	 * @param $journalId int ID of journal payment applies under
	 * @param $type int PAYMENT_TYPE_...
	 * @param $userId int ID of user responsible for payment
	 * @param $assocId int ID of associated entity
	 * @param $amount numeric Amount of currency $currencyCode
	 * @param $currencyCode string optional ISO 4217 currency code
	 * @return QueuedPayment
	 */
	function &createQueuedPayment($journalId, $type, $userId, $assocId, $amount, $currencyCode = null) {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		if (is_null($currencyCode)) $currencyCode = $journalSettingsDao->getSetting($journalId, 'currency');
		$payment = new OJSQueuedPayment($amount, $currencyCode, $userId, $assocId);
		$payment->setJournalId($journalId);
		$payment->setType($type);

		switch ($type) {
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				$payment->setRequestUrl($this->request->url(null, 'article', 'view', $assocId));
				break;
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				$payment->setRequestUrl($this->request->url(null, 'issue', 'view', $assocId));
				break;
			case PAYMENT_TYPE_MEMBERSHIP:
				$payment->setRequestUrl($this->request->url(null, 'user'));
				break;
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$payment->setRequestUrl($this->request->url(null, 'user', 'subscriptions'));
				break;
			case PAYMENT_TYPE_DONATION:
				$payment->setRequestUrl($this->request->url(null, 'donations', 'thankYou'));
				break;
			case PAYMENT_TYPE_FASTTRACK:
			case PAYMENT_TYPE_PUBLICATION:
			case PAYMENT_TYPE_SUBMISSION:
				$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
				$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($assocId);
				if ($authorSubmission->getSubmissionProgress()!=0) {
					$payment->setRequestUrl($this->request->url(null, 'author', 'submit', $authorSubmission->getSubmissionProgress(), array('articleId' => $assocId)));
				} else {
					$payment->setRequestUrl($this->request->url(null, 'author'));
				}
				break;
			case PAYMENT_TYPE_GIFT:
				$payment->setRequestUrl($this->request->url(null, 'gifts', 'thankYou'));
				break;
			default:
				// Invalid payment type
				assert(false);
				break;
		}

		return $payment;
	}

	/**
	 * Create a completed payment from a queued payment.
	 * @param $queuedPayment QueuedPayment Payment to complete.
	 * @param $payMethod string Name of payment plugin used.
	 * @return OJSCompletedPayment
	 */
	function &createCompletedPayment($queuedPayment, $payMethod) {
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

	/**
	 * Determine whether donations are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function donationEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('donationFeeEnabled');
	}

	/**
	 * Determine whether submission fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function submissionEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('submissionFeeEnabled') && $journal->getSetting('submissionFee') > 0;
	}

	/**
	 * Determine whether fast track fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function fastTrackEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('fastTrackFeeEnabled') && $journal->getSetting('fastTrackFee') > 0;
	}

	/**
	 * Determine whether publication fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function publicationEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('publicationFeeEnabled') && $journal->getSetting('publicationFee') > 0;
	}

	/**
	 * Determine whether publication fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function membershipEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('membershipFeeEnabled') && $journal->getSetting('membershipFee') > 0;
	}

	/**
	 * Determine whether article purchase fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function purchaseArticleEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('purchaseArticleFeeEnabled') && $journal->getSetting('purchaseArticleFee') > 0;
	}

	/**
	 * Determine whether issue purchase fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function purchaseIssueEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('purchaseIssueFeeEnabled') && $journal->getSetting('purchaseIssueFee') > 0;
	}

	/**
	 * Determine whether PDF-only article purchase fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function onlyPdfEnabled() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('restrictOnlyPdf');
	}

	/**
	 * Determine whether subscription fees are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function acceptSubscriptionPayments() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('acceptSubscriptionPayments');
	}

	/**
	 * Determine whether gift payments are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function acceptGiftPayments() {
		$journal =& $this->request->getJournal();
		return $this->acceptGiftSubscriptionPayments();
	}

	/**
	 * Determine whether gift subscription payments are enabled.
	 * @return boolean true iff this fee is enabled.
	 */
	function acceptGiftSubscriptionPayments() {
		$journal =& $this->request->getJournal();
		return $this->isConfigured() && $journal->getSetting('acceptGiftSubscriptionPayments');
	}

	/**
	 * Get the payment plugin.
	 * @return PaymentPlugin
	 */
	function &getPaymentPlugin() {
		$journal =& $this->request->getJournal();
		$paymentMethodPluginName = $journal->getSetting('paymentMethodPluginName');
		$paymentMethodPlugin = null;
		if (!empty($paymentMethodPluginName)) {
			$plugins =& PluginRegistry::loadCategory('paymethod');
			if (isset($plugins[$paymentMethodPluginName])) $paymentMethodPlugin =& $plugins[$paymentMethodPluginName];
		}
		return $paymentMethodPlugin;
	}

	/**
	 * Fulfill a queued payment.
	 * @param $queuedPayment QueuedPayment
	 * @param $payMethodPluginName string Name of payment plugin.
	 * @return mixed Dependent on payment type.
	 */
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
				$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
				if ($institutionalSubscriptionDao->subscriptionExists($subscriptionId)) {
					$subscription =& $institutionalSubscriptionDao->getSubscription($subscriptionId);
					$institutional = true;
				} else {
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
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				$article =& $articleDao->getArticle($queuedPayment->getAssocId(), $queuedPayment->getJournalId());
				$article->setFastTracked(true);
				$articleDao->updateArticle($article);
				$returner = true;
				break;
			case PAYMENT_TYPE_GIFT:
				$giftId = $queuedPayment->getAssocId();
				$giftDao =& DAORegistry::getDAO('GiftDAO');
				$gift =& $giftDao->getGift($giftId);
				if (!$gift) return false;

				$journalDao =& DAORegistry::getDAO('JournalDAO');
				$journalId = $gift->getAssocId();
				$journal =& $journalDao->getById($journalId);
				if (!$journal) return false;

				// Check if user account corresponding to recipient email exists in the system
				$userDao =& DAORegistry::getDAO('UserDAO');
				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$recipientFirstName = $gift->getRecipientFirstName();
				$recipientEmail = $gift->getRecipientEmail();

				$newUserAccount = false;

				if ($userDao->userExistsByEmail($recipientEmail)) {
					// User already has account, check if enrolled as reader in journal
					$user =& $userDao->getUserByEmail($recipientEmail);
					$userId = $user->getId();

					if (!$roleDao->userHasRole($journalId, $userId, ROLE_ID_READER)) {
						// User not enrolled as reader, enroll as reader
						$role = new Role();
						$role->setJournalId($journalId);
						$role->setUserId($userId);
						$role->setRoleId(ROLE_ID_READER);
						$roleDao->insertRole($role);
					}
				} else {
					// User does not have an account. Create one and enroll as reader.
					$recipientLastName = $gift->getRecipientLastName();

					$username = Validation::suggestUsername($recipientFirstName, $recipientLastName);
					$password = Validation::generatePassword();

					$user = new User();
					$user->setUsername($username);
					$user->setPassword(Validation::encryptCredentials($username, $password));
					$user->setFirstName($recipientFirstName);
					$user->setMiddleName($gift->getRecipientMiddleName());
					$user->setLastName($recipientLastName);
					$user->setEmail($recipientEmail);
					$user->setDateRegistered(Core::getCurrentDate());

					$userDao->insertUser($user);
					$userId = $user->getId();

					$role = new Role();
					$role->setJournalId($journalId);
					$role->setUserId($userId);
					$role->setRoleId(ROLE_ID_READER);
					$roleDao->insertRole($role);

					$newUserAccount = true;
				}

				// Update gift status (make it redeemable) and add recipient user account reference
				import('classes.gift.Gift');
				$gift->setStatus(GIFT_STATUS_NOT_REDEEMED);
				$gift->setRecipientUserId($userId);
				$giftDao->updateObject($gift);

				// Send gift available email to recipient, cc buyer
				$giftNoteTitle = $gift->getGiftNoteTitle();
				$buyerFullName = $gift->getBuyerFullName();
				$giftNote = $gift->getGiftNote();
				$giftLocale = $gift->getLocale();

				AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, $giftLocale);
				$giftDetails = $gift->getGiftName($giftLocale);
				$giftJournalName = $journal->getTitle($giftLocale);
				$giftContactSignature = $journal->getSetting('contactName');

				import('classes.mail.MailTemplate');
				$mail = new MailTemplate('GIFT_AVAILABLE', $giftLocale);
				$mail->setReplyTo(null);
				$mail->assignParams(array(
					'giftJournalName' => $giftJournalName,
					'giftNoteTitle' => $giftNoteTitle,
					'recipientFirstName' => $recipientFirstName,
					'buyerFullName' => $buyerFullName,
					'giftDetails' => $giftDetails,
					'giftNote' => $giftNote,
					'giftContactSignature' => $giftContactSignature
				));
				$mail->addRecipient($recipientEmail, $user->getFullName());
				$mail->addCc($gift->getBuyerEmail(), $gift->getBuyerFullName());
				$mail->send();
				unset($mail);

				// Send gift login details to recipient
				$params = array(
					'giftJournalName' => $giftJournalName,
					'recipientFirstName' => $recipientFirstName,
					'buyerFullName' => $buyerFullName,
					'giftDetails' => $giftDetails,
					'giftUrl' => $request->url($journal->getPath(), 'user', 'gifts'),
					'username' => $user->getUsername(),
					'giftContactSignature' => $giftContactSignature
				);

				if ($newUserAccount) {
					$mail = new MailTemplate('GIFT_USER_REGISTER', $giftLocale);
					$params['password'] = $password;
				} else {
					$mail = new MailTemplate('GIFT_USER_LOGIN', $giftLocale);
				}

				$mail->setReplyTo(null);
				$mail->assignParams($params);
				$mail->addRecipient($recipientEmail, $user->getFullName());
				$mail->send();
				unset($mail);

				$returner = true;
				break;
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
			case PAYMENT_TYPE_PURCHASE_ISSUE:
			case PAYMENT_TYPE_DONATION:
			case PAYMENT_TYPE_SUBMISSION:
			case PAYMENT_TYPE_PUBLICATION:
				$returner = true;
				break;
			default:
				// Invalid payment type
				assert(false);
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
