<?php

/**
 * SubscriptionTypeHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for subscription type management functions. 
 *
 * $Id$
 */

class SubscriptionTypeHandler extends ManagerHandler {

	/**
	 * Display a list of subscription types for the current journal.
	 */
	function subscriptionTypes() {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes = &$subscriptionTypeDao->getSubscriptionTypesByJournalId($journal->getJournalId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('subscriptionTypes', $subscriptionTypes);
		$templateMgr->display('manager/subscription/subscriptionTypes.tpl');
	}

	/**
	 * Delete a subscription type.
	 * @param $args array first parameter is the ID of the subscription type to delete
	 */
	function deleteSubscriptionType($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			$subscriptionTypeId = (int) $args[0];
		
			$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');

			// Ensure subscription type is for this journal
			if ($subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) {
				$subscriptionTypeDao->deleteSubscriptionTypeById($subscriptionTypeId);
			}
		}
		
		Request::redirect('manager/subscriptionTypes');
	}

	/**
	 * Display form to edit a subscription type.
	 * @param $args array optional, first parameter is the ID of the subscription type to edit
	 */
	function editSubscriptionType($args = array()) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$subscriptionTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');

		// Ensure subscription type is valid and for this journal
		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) || $subscriptionTypeId == null) {

			import('manager.form.SubscriptionTypeForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array('manager/subscriptionTypes', 'manager.subscriptionTypes'));

			if ($subscriptionTypeId == null) {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
			} else {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');	
			}

			$subscriptionTypeForm = &new SubscriptionTypeForm($subscriptionTypeId);
			$subscriptionTypeForm->initData();
			$subscriptionTypeForm->display();
		
		} else {
				Request::redirect('manager/subscriptionTypes');
		}
	}

	/**
	 * Display form to create new subscription type.
	 */
	function createSubscriptionType() {
		SubscriptionTypeHandler::editSubscriptionType();
	}

	/**
	 * Save changes to a subscription type.
	 */
	function updateSubscriptionType() {
		parent::validate();
		
		import('manager.form.SubscriptionTypeForm');
		
		$journal = &Request::getJournal();
		$subscriptionTypeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');

		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) || $subscriptionTypeId == null) {

			$subscriptionTypeForm = &new SubscriptionTypeForm($subscriptionTypeId);
			$subscriptionTypeForm->readInputData();
			
			if ($subscriptionTypeForm->validate()) {
				$subscriptionTypeForm->execute();

				if (Request::getUserVar('createAnother')) {
					parent::setupTemplate(true);

					$templateMgr = &TemplateManager::getManager();
					$templateMgr->append('pageHierarchy', array('manager/subscriptionTypes', 'manager.subscriptionTypes'));
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
					$templateMgr->assign('subscriptionTypeCreated', '1');

					$subscriptionTypeForm = &new SubscriptionTypeForm($subscriptionTypeId);
					$subscriptionTypeForm->initData();
					$subscriptionTypeForm->display();
	
				} else {
					Request::redirect('manager/subscriptionTypes');
				}
				
			} else {
				parent::setupTemplate(true);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array('manager/subscriptionTypes', 'manager.subscriptionTypes'));

				if ($subscriptionTypeId == null) {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
				} else {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');	
				}

				$subscriptionTypeForm->display();
			}
			
		} else {
				Request::redirect('manager/subscriptionTypes');
		}
	}

}

?>
