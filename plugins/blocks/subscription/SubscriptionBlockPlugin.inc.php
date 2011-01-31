<?php

/**
 * @file SubscriptionBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionBlockPlugin
 * @ingroup plugins_blocks_subscription
 *
 * @brief Class for subscription block plugin
 *
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class SubscriptionBlockPlugin extends BlockPlugin {
	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.subscription.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.subscription.description');
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		$journalId = ($journal)?$journal->getId():null;
		if (!$journal) return '';

		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION)
			return '';

		$user =& Request::getUser();
		$userId = ($user)?$user->getId():null;
		$templateMgr->assign('userLoggedIn', isset($userId) ? true : false);

		if (isset($userId)) {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
			$individualSubscription =& $subscriptionDao->getSubscriptionByUserForJournal($userId, $journalId);
			$templateMgr->assign_by_ref('individualSubscription', $individualSubscription);
		}

		// If no individual subscription or if not valid, check for institutional subscription
		if (!isset($individualSubscription) || !$individualSubscription->isValid()) {
			$IP = Request::getRemoteAddr();
			$domain = Request::getRemoteDomain();
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			$subscriptionId = $subscriptionDao->isValidInstitutionalSubscription($domain, $IP, $journalId);
			if ($subscriptionId) {
				$institutionalSubscription =& $subscriptionDao->getSubscription($subscriptionId);
				$templateMgr->assign_by_ref('institutionalSubscription', $institutionalSubscription);
				$templateMgr->assign('userIP', $IP);
			}
		}

		if (isset($individualSubscription) || isset($institutionalSubscription)) {
			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager =& OJSPaymentManager::getManager();
			$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
			$templateMgr->assign('acceptSubscriptionPayments', $acceptSubscriptionPayments);
		}

		return parent::getContents($templateMgr);
	}
}

?>
