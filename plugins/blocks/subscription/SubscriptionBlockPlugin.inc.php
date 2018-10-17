<?php

/**
 * @file plugins/blocks/subscription/SubscriptionBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
		return __('plugins.block.subscription.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.subscription.description');
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @param $request PKPRequest
	 * @return $string
	 */
	function getContents($templateMgr, $request = null) {
		$journal = $request->getJournal();
		if (!$journal) return '';

		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION)
			return '';

		$user = $request->getUser();
		$userId = ($user)?$user->getId():null;
		$templateMgr->assign('userLoggedIn', isset($userId) ? true : false);

		if (isset($userId)) {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
			$individualSubscription = $subscriptionDao->getByUserIdForJournal($userId, $journal->getId());
			$templateMgr->assign('individualSubscription', $individualSubscription);
		}

		// If no individual subscription or if not valid, check for institutional subscription
		if (!isset($individualSubscription) || !$individualSubscription->isValid()) {
			$ip = $request->getRemoteAddr();
			$domain = $request->getRemoteDomain();
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			$subscriptionId = $subscriptionDao->isValidInstitutionalSubscription($domain, $ip, $journal->getId());
			if ($subscriptionId) {
				$institutionalSubscription = $subscriptionDao->getById($subscriptionId);
				$templateMgr->assign(array(
					'institutionalSubscription' => $institutionalSubscription,
					'userIP' => $ip,
				));
			}
		}

		$paymentManager = Application::getPaymentManager($journal);

		if (isset($individualSubscription) || isset($institutionalSubscription)) {
			$templateMgr->assign('acceptSubscriptionPayments', $paymentManager->isConfigured());
		}

		return parent::getContents($templateMgr, $request);
	}
}


