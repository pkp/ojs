<?php

/**
 * @file pages/subscriptions/SubscriptionsHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionsHandler
 * @ingroup pages_subscription
 *
 * @brief Handle requests for subscription management.
 */

import('classes.handler.Handler');

class SubscriptionsHandler extends Handler {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER),
			array('index', 'subscriptions', 'subscriptionTypes', 'subscriptionPolicies', 'payments')
		);
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('subscriptions/index.tpl');
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
		$this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptions($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		switch (array_shift($args)) {
			case 'institutional':
				return $templateMgr->fetchAjax(
					'institutionalSubscriptionsGridContainer',
					$dispatcher->url(
						$request, ROUTE_COMPONENT, null,
						'grid.subscriptions.InstitutionalSubscriptionsGridHandler', 'fetchGrid'
					)
				);
			case 'individual':
				return $templateMgr->fetchAjax(
					'individualSubscriptionsGridContainer',
					$dispatcher->url(
						$request, ROUTE_COMPONENT, null,
						'grid.subscriptions.IndividualSubscriptionsGridHandler', 'fetchGrid'
					)
				);
		}
		$dispatcher->handle404();
	}

	/**
	 * Display a list of subscription types for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionTypes($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		return $templateMgr->fetchAjax(
			'subscriptionTypesGridContainer',
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'grid.subscriptions.SubscriptionTypesGridHandler', 'fetchGrid'
			)
		);
	}
	// ---------------------- 8< CRUFT LINE -------------------------------------------

	/**
	 * Display subscriptions summary page for the current journal.
	 */
	function subscriptionsSummary($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$journalId = $journal->getId();

		$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$statusOptions = $individualSubscriptionDao->getStatusOptions();
		$individualStatus = array();

		foreach ($statusOptions as $status => $localeKey) {
			$statusCount = $individualSubscriptionDao->getStatusCount($journalId, $status);
			$individualStatus[] = array(
				'status' => $status,
				'count' => $statusCount,
				'localeKey' => $localeKey
			);
		}

		$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$statusOptions = $institutionalSubscriptionDao->getStatusOptions();
		$institutionalStatus = array();

		foreach ($statusOptions as $status => $localeKey) {
			$statusCount = $institutionalSubscriptionDao->getStatusCount($journalId, $status);
			$institutionalStatus[] = array(
				'status' => $status,
				'count' => $statusCount,
				'localeKey' => $localeKey
			);
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('individualStatus', $individualStatus);
		$templateMgr->assign('institutionalStatus', $institutionalStatus);

		return $templateMgr->fetchJson('subscriptions/subscriptionsSummary.tpl');
	}

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$subscriptionId = empty($args[0]) ? null : (int) $args[0];

		if ($institutional) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		// Ensure subscription is for this journal
		if ($subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getId()) {
			$subscriptionDao->deleteSubscriptionById($subscriptionId);
		}

		$request->redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Renew a subscription.
	 * @param $args array first parameter is the ID of the subscription to renew
	 */
	function renewSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$subscriptionId = empty($args[0]) ? null : (int) $args[0];

		if ($institutional) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		// Ensure subscription is for this journal
		if ($subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getId()) {
			$subscription = $subscriptionDao->getSubscription($subscriptionId);
			if ($subscription) $subscriptionDao->renewSubscription($subscription);
		}

		$request->redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array optional, first parameter is the ID of the subscription to edit
	 */
	function editSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$userId = $request->getUserVar('userId') == null ? null : (int) $request->getUserVar('userId');
		$subscriptionId = empty($args[0]) ? null : (int) $args[0];

		if ($institutional) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		// Ensure subscription is valid and for this journal
		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getId()) || ($subscriptionId == null && $userId)) {
			$templateMgr = TemplateManager::getManager($request);
			$subscriptionCreated = $request->getUserVar('subscriptionCreated') == 1 ? 1 : 0;
			$templateMgr->assign('subscriptionCreated', $subscriptionCreated);

			if ($subscriptionId == null) {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
			} else {
				$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');
			}

			if ($institutional) {
				import('classes.subscription.form.InstitutionalSubscriptionForm');
				$subscriptionForm = new InstitutionalSubscriptionForm($subscriptionId, $userId);
			} else {
				import('classes.subscription.form.IndividualSubscriptionForm');
				$subscriptionForm = new IndividualSubscriptionForm($subscriptionId, $userId);
			}
			if ($subscriptionForm->isLocaleResubmit()) {
				$subscriptionForm->readInputData();
			} else {
				$subscriptionForm->initData();
			}
			return new JSONMessage(true, $subscriptionForm->fetch());
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription($args, $request) {
		$this->editSubscription($args, $request);
	}

	/**
	 * Display a list of users from which to choose a subscriber.
	 */
	function selectSubscriber($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);

		if ($institutional) {
			$pageTitle = 'manager.subscriptions.selectContact';
			$redirect = 'institutional';
		} else {
			$pageTitle = 'manager.subscriptions.selectUser';
			$redirect = 'individual';
		}

		$userDao = DAORegistry::getDAO('UserDAO');

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = PKPString::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo($request, 'users');

		$users = $userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		import('classes.security.Validation');
		$templateMgr->assign('isJournalManager', Validation::isJournalManager());

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign('users', $users);
		$templateMgr->assign('subscriptionId', $request->getUserVar('subscriptionId'));
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('redirect', $redirect);
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		return $templateMgr->fetchJson('subscriptions/users.tpl');
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$subscriptionId = $request->getUserVar('subscriptionId') == null ? null : (int) $request->getUserVar('subscriptionId');

		if ($institutional) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (($subscriptionId != null && $subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getId()) || $subscriptionId == null) {

			if ($institutional) {
				import('classes.subscription.form.InstitutionalSubscriptionForm');
				$subscriptionForm = new InstitutionalSubscriptionForm($subscriptionId);
			} else {
				import('classes.subscription.form.IndividualSubscriptionForm');
				$subscriptionForm = new IndividualSubscriptionForm($subscriptionId);
			}
			$subscriptionForm->readInputData();

			// Check for any special cases before trying to save
			if ($request->getUserVar('addIpRange')) {
				$editData = true;
				$ipRanges = $subscriptionForm->getData('ipRanges');
				$ipRanges[] = '';
				$subscriptionForm->setData('ipRanges', $ipRanges);

			} else if (($delIpRange = $request->getUserVar('delIpRange')) && count($delIpRange) == 1) {
				$editData = true;
				list($delIpRange) = array_keys($delIpRange);
				$delIpRange = (int) $delIpRange;
				$ipRanges = $subscriptionForm->getData('ipRanges');
				array_splice($ipRanges, $delIpRange, 1);
				$subscriptionForm->setData('ipRanges', $ipRanges);
			}

			if (isset($editData)) {
				$templateMgr = TemplateManager::getManager($request);

				if ($subscriptionId == null) {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
				} else {
					$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');
				}

				return new JSONMessage(true, $subscriptionForm->fetch());
			} else {
				if ($subscriptionForm->validate()) {
					$subscriptionForm->execute();
				} else {
					$templateMgr = TemplateManager::getManager($request);

					if ($subscriptionId == null) {
						$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
					} else {
						$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');
					}

					return new JSONMessage(true, $subscriptionForm->fetch());
				}
			}
		}
	}

	/**
	 * Display subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionPolicies($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.form.SubscriptionPolicyForm');

		$templateMgr = TemplateManager::getManager($request);

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$templateMgr->assign('acceptSubscriptionPayments', $paymentManager->acceptSubscriptionPayments());

		$subscriptionPolicyForm = new SubscriptionPolicyForm();
		if ($subscriptionPolicyForm->isLocaleResubmit()) {
			$subscriptionPolicyForm->readInputData();
		} else {
			$subscriptionPolicyForm->initData();
		}
		return new JSONMessage(true, $subscriptionPolicyForm->fetch());
	}

	/**
	 * Save subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveSubscriptionPolicies($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.form.SubscriptionPolicyForm');

		$subscriptionPolicyForm = new SubscriptionPolicyForm();
		$subscriptionPolicyForm->readInputData();

		$templateMgr = TemplateManager::getManager($request);

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$templateMgr->assign('acceptSubscriptionPayments', $paymentManager->acceptSubscriptionPayments());

		if ($subscriptionPolicyForm->validate()) {
			$subscriptionPolicyForm->execute();
			$templateMgr->assign('subscriptionPoliciesSaved', '1');
		}
		return new JSONMessage(true, $subscriptionPolicyForm->fetch());
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
	 * Get the list of individual fields that can be searched by contents.
	 * @return array
	 */
	function getIndividualSearchFieldOptions() {
		return array(
			SUBSCRIPTION_USER => 'manager.subscriptions.user',
			SUBSCRIPTION_MEMBERSHIP => 'manager.subscriptions.membership',
			SUBSCRIPTION_REFERENCE_NUMBER => 'manager.subscriptions.referenceNumber',
			SUBSCRIPTION_NOTES => 'manager.subscriptions.notes'
		);
	}

	/**
	 * Get the list of institutional fields that can be searched by contents.
	 * @return array
	 */
	function getInstitutionalSearchFieldOptions() {
		return array(
			SUBSCRIPTION_INSTITUTION_NAME => 'manager.subscriptions.institutionName',
			SUBSCRIPTION_USER => 'manager.subscriptions.contact',
			SUBSCRIPTION_DOMAIN => 'manager.subscriptions.domain',
			SUBSCRIPTION_IP_RANGE => 'manager.subscriptions.ipRange',
			SUBSCRIPTION_MEMBERSHIP => 'manager.subscriptions.membership',
			SUBSCRIPTION_REFERENCE_NUMBER => 'manager.subscriptions.referenceNumber',
			SUBSCRIPTION_NOTES => 'manager.subscriptions.notes'
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

}

?>
