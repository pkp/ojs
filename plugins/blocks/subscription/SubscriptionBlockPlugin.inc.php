<?php

/**
 * @file SubscriptionBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.blocks.subscription
 * @class SubscriptionBlockPlugin
 *
 * Class for subscription block plugin
 *
 */

import('plugins.BlockPlugin');

class SubscriptionBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'SubscriptionBlockPlugin';
	}

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getNewJournalPluginSettingsFile() {
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
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		$journalId = ($journal)?$journal->getJournalId():null;
		if (!$journal) return '';

		$user = &Request::getUser();
		$userId = ($user)?$user->getUserId():null;
		
		$domain = Request::getRemoteDomain();
		$IP = Request::getRemoteAddr();

		// This replicates the order of SubscriptionDAO::isValidSubscription
		// Checks for valid Subscription and assigns vars accordingly for display				
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');	
		$subscriptionId = false;
		$userHasSubscription = false;
		if ($userId != null) {
			$subscriptionId = $subscriptionDao->isValidSubscriptionByUser($userId, $journalId);
			$userHasSubscription = true;
		} 

		if (!$userHasSubscription && $domain != null) {
			$subscriptionId = $subscriptionDao->isValidSubscriptionByDomain($domain, $journalId);
		}	

		if (!$userHasSubscription && $IP != null) {
			$subscriptionId = $subscriptionDao->isValidSubscriptionByIP($IP, $journalId);
		}

		if ( $subscriptionId !== false ) {
			$subscription =& $subscriptionDao->getSubscription($subscriptionId);
			
			$templateMgr->assign('userHasSubscription', $userHasSubscription);
			if ($userHasSubscription) {
				import('payment.ojs.OJSPaymentManager');
				$paymentManager =& OJSPaymentManager::getManager();
				$subscriptionEnabled = $paymentManager->acceptSubscriptionPayments();
				$templateMgr->assign('subscriptionEnabled', $subscriptionEnabled);
			}
			
			$templateMgr->assign('subscriptionMembership', $subscription->getMembership());
			$templateMgr->assign('subscriptionDateEnd', $subscription->getDateEnd());  
			$templateMgr->assign('subscriptionTypeName', $subscription->getSubscriptionTypeName());
			$templateMgr->assign('userIP', $IP);
			
			return parent::getContents($templateMgr);	
		}

		return '';
	}
}

?>
