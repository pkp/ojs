<?php

/**
 * SubscriptionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for subscription management functions. 
 *
 * $Id$
 */

class SubscriptionHandler extends ManagerHandler {

	/**
	 * Display a list of subscriptions for the current journal.
	 */
	function subscriptions() {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
		$subscriptions = &$subscriptionDao->getSubscriptionsByJournalId($journal->getJournalId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('subscriptions', $subscriptions);
		$templateMgr->display('manager/subscription/subscriptions.tpl');
	}

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			$subscriptionId = (int) $args[0];
		
			$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');

			// Ensure subscription is for this journal
			if ($subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) {
				$subscriptionDao->deleteSubscriptionById($subscriptionId);
			}
		}
		
		Request::redirect('manager/subscriptions');
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array optional, first parameter is the ID of the subscription to edit
	 */
	function editSubscription($args = array()) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$subscriptionId = !isset($args) || empty($args) ? null : (int) $args[0];
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');

		// Ensure subscription is valid and for this journal
		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) || $subscriptionId == null) {

			import('manager.form.SubscriptionForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array('manager/subscriptions', 'manager.subscriptions'));

			if ($subscriptionId == null) {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
			} else {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');	
			}

			$subscriptionForm = &new SubscriptionForm($subscriptionId);
			$subscriptionForm->initData();
			$subscriptionForm->display();
		
		} else {
				Request::redirect('manager/subscriptions');
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription() {
		SubscriptionHandler::editSubscription();
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription() {
		parent::validate();
		
		import('manager.form.SubscriptionForm');
		
		$journal = &Request::getJournal();
		$subscriptionId = Request::getUserVar('subscriptionId') == null ? null : (int) Request::getUserVar('subscriptionId');
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');

		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) || $subscriptionId == null) {

			$subscriptionForm = &new SubscriptionForm($subscriptionId);
			$subscriptionForm->readInputData();
			
			if ($subscriptionForm->validate()) {
				$subscriptionForm->execute();

				if (Request::getUserVar('createAnother')) {
					parent::setupTemplate(true);

					$templateMgr = &TemplateManager::getManager();
					$templateMgr->append('pageHierarchy', array('manager/subscriptions', 'manager.subscriptions'));
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
					$templateMgr->assign('subscriptionCreated', '1');

					$subscriptionForm = &new SubscriptionForm($subscriptionId);
					$subscriptionForm->initData();
					$subscriptionForm->display();
	
				} else {
					Request::redirect('manager/subscriptions');
				}
				
			} else {
				parent::setupTemplate(true);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array('manager/subscriptions', 'manager.subscriptions'));

				if ($subscriptionId == null) {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
				} else {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');	
				}

				$subscriptionForm->display();
			}
			
		} else {
				Request::redirect('manager/subscriptions');
		}
	}

}

?>
