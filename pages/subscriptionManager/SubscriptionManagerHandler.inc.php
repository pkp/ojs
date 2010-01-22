<?php

/**
 * @file SubscriptionManagerHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionManagerHandler
 * @ingroup pages_subscriptionManager
 *
 * @brief Handle requests for subscription management functions. 
 */

// $Id$


class SubscriptionManagerHandler extends Handler {
	function index() {
		SubscriptionManagerHandler::subscriptions();
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 */
	function subscriptions() {
		SubscriptionManagerHandler::validate();
		SubscriptionManagerHandler::setupTemplate();

		$journal = &Request::getJournal();
		$rangeInfo = &Handler::getRangeInfo('subscriptions');
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$subscriptions = &$subscriptionDao->getSubscriptionsByJournalId($journal->getJournalId(), $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('subscriptions', $subscriptions);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');
	
		// Set search parameters
		foreach (SubscriptionManagerHandler::getSearchFormDuplicateParameters() as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', SubscriptionManagerHandler::getSearchFieldOptions());
		$templateMgr->assign('dateFieldOptions', SubscriptionManagerHandler::getDateFieldOptions());

		$templateMgr->display('subscription/subscriptions.tpl');
	}

	/**
	 * Get the list of parameter names that should be duplicated when
	 * displaying the search form (i.e. made available to the template
	 * based on supplied user data).
	 * @return array
	 */
	function getSearchFormDuplicateParameters() {
		return array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
	}

	/**
	 * Get the list of fields that can be searched by contents.
	 * @return array
	 */
	function getSearchFieldOptions() {
		return array(
			SUBSCRIPTION_USER => 'manager.subscriptions.user',
			SUBSCRIPTION_MEMBERSHIP => 'manager.subscriptions.membership',
			SUBSCRIPTION_DOMAIN => 'manager.subscriptions.domain',
			SUBSCRIPTION_IP_RANGE => 'manager.subscriptions.ipRange'
		);
	}

	/**
	 * Get the list of date fields that can be searched.
	 * @return array
	 */
	function getDateFieldOptions() {
		return array(
			SUBSCRIPTION_DATE_START => 'manager.subscriptions.dateStartSearch',
			SUBSCRIPTION_DATE_END => 'manager.subscriptions.dateEndSearch'
		);
	}

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args) {
		SubscriptionManagerHandler::validate();

		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			$subscriptionId = (int) $args[0];

			$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');

			// Ensure subscription is for this journal
			if ($subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) {
				$subscriptionDao->deleteSubscriptionById($subscriptionId);
			}
		}

		Request::redirect(null, null, 'subscriptions');
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array optional, first parameter is the ID of the subscription to edit
	 */
	function editSubscription($args = array()) {
		SubscriptionManagerHandler::validate();
		SubscriptionManagerHandler::setupTemplate();

		$journal = &Request::getJournal();
		$subscriptionId = !isset($args) || empty($args) ? null : (int) $args[0];
		$userId = Request::getUserVar('userId');
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');

		// Ensure subscription is valid and for this journal
		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) || ($subscriptionId == null && $userId)) {
			import('subscription.form.SubscriptionForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptions'), 'manager.subscriptions'));

			if ($subscriptionId == null) {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
			} else {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');	
			}

			$subscriptionForm = &new SubscriptionForm($subscriptionId, $userId);
			$subscriptionForm->initData();
			$subscriptionForm->display();

		} else {
				Request::redirect(null, null, 'subscriptions');
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription() {
		SubscriptionManagerHandler::editSubscription();
	}

	/**
	 * Display a list of users from which to choose a subscriber.
	 */
	function selectSubscriber() {
		SubscriptionManagerHandler::validate();
		$templateMgr = &TemplateManager::getManager();
		SubscriptionManagerHandler::setupTemplate();
		$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptions'), 'manager.subscriptions'));

		$userDao = &DAORegistry::getDAO('UserDAO');

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users');

		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		$templateMgr->assign('isJournalManager', false);

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');
		$templateMgr->assign('subscriptionId', Request::getUserVar('subscriptionId'));
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->display('subscription/users.tpl');
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription() {
		SubscriptionManagerHandler::validate();

		import('subscription.form.SubscriptionForm');

		$journal = &Request::getJournal();
		$subscriptionId = Request::getUserVar('subscriptionId') == null ? null : (int) Request::getUserVar('subscriptionId');
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');

		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getJournalId()) || $subscriptionId == null) {

			$subscriptionForm = &new SubscriptionForm($subscriptionId);
			$subscriptionForm->readInputData();

			if ($subscriptionForm->validate()) {
				$subscriptionForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, 'selectSubscriber', null, array('subscriptionCreated', 1));
				} else {
					Request::redirect(null, null, 'subscriptions');
				}

			} else {
				SubscriptionManagerHandler::setupTemplate();

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptions'), 'manager.subscriptions'));

				if ($subscriptionId == null) {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
				} else {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');	
				}

				$subscriptionForm->display();
			}

		} else {
				Request::redirect(null, null, 'subscriptions');
		}
	}

	/**
	 * Display a list of subscription types for the current journal.
	 */
	function subscriptionTypes() {
		SubscriptionManagerHandler::validate();
		SubscriptionManagerHandler::setupTemplate(true);

		$journal = &Request::getJournal();
		$rangeInfo = &Handler::getRangeInfo('subscriptionTypes');
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes = &$subscriptionTypeDao->getSubscriptionTypesByJournalId($journal->getJournalId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('subscriptionTypes', $subscriptionTypes);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

		$templateMgr->display('subscription/subscriptionTypes.tpl');
	}

	/**
	 * Rearrange the order of subscription types.
	 */
	function moveSubscriptionType($args) {
		SubscriptionManagerHandler::validate();

		$subscriptionTypeId = isset($args[0])?$args[0]:0;
		$journal = &Request::getJournal();

		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = &$subscriptionTypeDao->getSubscriptionType($subscriptionTypeId);

		if ($subscriptionType && $subscriptionType->getJournalId() == $journal->getJournalId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$subscriptionType->setSequence($subscriptionType->getSequence()+($isDown?1.5:-1.5));
			$subscriptionTypeDao->updateSubscriptionType($subscriptionType);
			$subscriptionTypeDao->resequenceSubscriptionTypes($subscriptionType->getJournalId());
		}

		Request::redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Delete a subscription type.
	 * @param $args array first parameter is the ID of the subscription type to delete
	 */
	function deleteSubscriptionType($args) {
		SubscriptionManagerHandler::validate();

		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			$subscriptionTypeId = (int) $args[0];

			$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');

			// Ensure subscription type is for this journal
			if ($subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) {
				$subscriptionTypeDao->deleteSubscriptionTypeById($subscriptionTypeId);
			}
		}

		Request::redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Display form to edit a subscription type.
	 * @param $args array optional, first parameter is the ID of the subscription type to edit
	 */
	function editSubscriptionType($args = array()) {
		SubscriptionManagerHandler::validate();
		SubscriptionManagerHandler::setupTemplate(true);

		$journal = &Request::getJournal();
		$subscriptionTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');

		// Ensure subscription type is valid and for this journal
		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) || $subscriptionTypeId == null) {

			import('subscription.form.SubscriptionTypeForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptionTypes'), 'manager.subscriptionTypes'));

			if ($subscriptionTypeId == null) {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
			} else {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');	
			}

			$subscriptionTypeForm = &new SubscriptionTypeForm($subscriptionTypeId);
			if ($subscriptionTypeForm->isLocaleResubmit()) {
				$subscriptionTypeForm->readInputData();
			} else {
				$subscriptionTypeForm->initData();
			}
			$subscriptionTypeForm->display();

		} else {
				Request::redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display form to create new subscription type.
	 */
	function createSubscriptionType() {
		SubscriptionManagerHandler::editSubscriptionType();
	}

	/**
	 * Save changes to a subscription type.
	 */
	function updateSubscriptionType() {
		SubscriptionManagerHandler::validate();

		import('subscription.form.SubscriptionTypeForm');

		$journal = &Request::getJournal();
		$subscriptionTypeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');

		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getJournalId()) || $subscriptionTypeId == null) {

			$subscriptionTypeForm = &new SubscriptionTypeForm($subscriptionTypeId);
			$subscriptionTypeForm->readInputData();

			if ($subscriptionTypeForm->validate()) {
				$subscriptionTypeForm->execute();

				if (Request::getUserVar('createAnother')) {
					SubscriptionManagerHandler::setupTemplate(true);

					$templateMgr = &TemplateManager::getManager();
					$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptionTypes'), 'manager.subscriptionTypes'));
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
					$templateMgr->assign('subscriptionTypeCreated', '1');

					$subscriptionTypeForm = &new SubscriptionTypeForm($subscriptionTypeId);
					$subscriptionTypeForm->initData();
					$subscriptionTypeForm->display();

				} else {
					Request::redirect(null, null, 'subscriptionTypes');
				}

			} else {
				SubscriptionManagerHandler::setupTemplate(true);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptionTypes'), 'manager.subscriptionTypes'));

				if ($subscriptionTypeId == null) {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
				} else {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');	
				}

				$subscriptionTypeForm->display();
			}

		} else {
				Request::redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display subscription policies for the current journal.
	 */
	function subscriptionPolicies() {
		SubscriptionManagerHandler::validate();
		SubscriptionManagerHandler::setupTemplate(true);

		import('subscription.form.SubscriptionPolicyForm');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		$subscriptionPolicyForm = &new SubscriptionPolicyForm();
		if ($subscriptionPolicyForm->isLocaleResubmit()) {
			$subscriptionPolicyForm->readInputData();
		} else {
			$subscriptionPolicyForm->initData();
		}
		$subscriptionPolicyForm->display();
	}

	/**
	 * Save subscription policies for the current journal.
	 */
	function saveSubscriptionPolicies($args = array()) {
		SubscriptionManagerHandler::validate();

		import('subscription.form.SubscriptionPolicyForm');

		$subscriptionPolicyForm = &new SubscriptionPolicyForm();
		$subscriptionPolicyForm->readInputData();

		if ($subscriptionPolicyForm->validate()) {
			$subscriptionPolicyForm->execute();

			SubscriptionManagerHandler::setupTemplate(true);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');
			$templateMgr->assign('subscriptionPoliciesSaved', '1');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$subscriptionPolicyForm->display();
		} else {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$subscriptionPolicyForm->display();
		}
	}

	/**
	 * Validate that user has permissions to manage subscriptions for the
	 * selected journal. Redirects to user index page if not properly
	 * authenticated.
	 */
	function validate() {
		parent::validate();
		$journal =& Request::getJournal();
		if (!$journal || !Validation::isSubscriptionManager()) {
			Validation::redirectLogin();
		}
	}

	/**
	 * Display form to create a user profile.
	 * @param $args array optional
	 */
	function createUser($args = array()) {
		SubscriptionManagerHandler::validate();
		SubscriptionManagerHandler::setupTemplate(true);

		$journal = &Request::getJournal();

		$templateMgr = &TemplateManager::getManager();

		import('manager.form.UserManagementForm');

		$templateMgr->assign('currentUrl', Request::url(null, null, 'createUser'));
		$userForm = &new UserManagementForm();
		if ($userForm->isLocaleResubmit()) {
			$userForm->readInputData();
		} else {
			$userForm->initData();
		}
		$userForm->display();
	}

	/**
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 */
	function suggestUsername() {
		SubscriptionManagerHandler::validate();
		$suggestion = Validation::suggestUsername(
			Request::getUserVar('firstName'),
			Request::getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Save changes to a user profile.
	 */
	function updateUser() {
		SubscriptionManagerHandler::validate();

		$journal = &Request::getJournal();

		import('manager.form.UserManagementForm');

		$userForm = &new UserManagementForm();
		$userForm->readInputData();

		if ($userForm->validate()) {
			$userForm->execute();

			if (Request::getUserVar('createAnother')) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::url(null, null, 'index'));
				$templateMgr->assign('userCreated', true);
				$userForm = &new UserManagementForm();
				$userForm->initData();
				$userForm->display();

			} else {
				Request::redirect(null, null, 'selectSubscriber');
			}

		} else {
			SubscriptionManagerHandler::setupTemplate(true);
			$userForm->display();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'subscriptionManager'), 'subscriptionManager.subscriptionManagement'))
				: array(array(Request::url(null, 'user'), 'navigation.user'))
		);
	}
}

?>
