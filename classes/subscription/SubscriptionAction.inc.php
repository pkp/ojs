<?php

/**
 * @file classes/subscription/SubscriptionAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionAction
 * @ingroup subscriptions
 *
 * Common actions for subscription management functions.
 */

class SubscriptionAction {
	/**
	 * Send notification email to Subscription Manager when online payment is completed.
	 * @param $request PKPRequest
	 * @param $subscription Subscription
	 * @param $mailTemplateKey string
	 */
	function sendOnlinePaymentNotificationEmail($request, $subscription, $mailTemplateKey) {
		$validKeys = array(
			'SUBSCRIPTION_PURCHASE_INDL',
			'SUBSCRIPTION_PURCHASE_INSTL',
			'SUBSCRIPTION_RENEW_INDL',
			'SUBSCRIPTION_RENEW_INSTL'
		);

		if (!in_array($mailTemplateKey, $validKeys)) return false;

		$journal = $request->getJournal();

		$subscriptionContactName = $journal->getSetting('subscriptionName');
		$subscriptionContactEmail = $journal->getSetting('subscriptionEmail');

		if (empty($subscriptionContactEmail)) {
			$subscriptionContactEmail = $journal->getSetting('contactEmail');
			$subscriptionContactName = $journal->getSetting('contactName');
		}

		if (empty($subscriptionContactEmail)) return false;

		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getById($subscription->getUserId());

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId());

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$role = $roleDao->newDataObject();
		if ($roleDao->getJournalUsersRoleCount($journal->getId(), ROLE_ID_SUBSCRIPTION_MANAGER) > 0) {
			$role->setId(ROLE_ID_SUBSCRIPTION_MANAGER);
			$rolePath = $role->getPath();
		} else {
			$role->setId(ROLE_ID_MANAGER);
			$rolePath = $role->getPath();
		}

		$paramArray = array(
			'subscriptionType' => $subscriptionType->getSummaryString(),
			'userDetails' => $user->getContactSignature(),
			'membership' => $subscription->getMembership()
		);

		switch($mailTemplateKey) {
			case 'SUBSCRIPTION_PURCHASE_INDL':
			case 'SUBSCRIPTION_RENEW_INDL':
				$paramArray['subscriptionUrl'] = $request->url($journal->getPath(), $rolePath, 'editSubscription', 'individual', array($subscription->getId()));
				break;
			case 'SUBSCRIPTION_PURCHASE_INSTL':
			case 'SUBSCRIPTION_RENEW_INSTL':
				$paramArray['subscriptionUrl'] = $request->url($journal->getPath(), $rolePath, 'editSubscription', 'institutional', array($subscription->getId()));
				$paramArray['institutionName'] = $subscription->getInstitutionName();
				$paramArray['institutionMailingAddress'] = $subscription->getInstitutionMailingAddress();
				$paramArray['domain'] = $subscription->getDomain();
				$paramArray['ipRanges'] = $subscription->getIPRangesString();
				break;
		}

		import('lib.pkp.classes.mail.MailTemplate');
		$mail = new MailTemplate($mailTemplateKey);
		$mail->setReplyTo($subscriptionContactEmail, $subscriptionContactName);
		$mail->addRecipient($subscriptionContactEmail, $subscriptionContactName);
		$mail->setSubject($mail->getSubject($journal->getPrimaryLocale()));
		$mail->setBody($mail->getBody($journal->getPrimaryLocale()));
		$mail->assignParams($paramArray);
		$mail->send();
	}
}

?>
