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
	 * Display subscriptions summary page for the current journal.
	 */
	function subscriptionsSummary($request) {
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

		$templateMgr->display('subscription/subscriptionsSummary.tpl');
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 */
	function subscriptions($request, $institutional = false) {
		$journal = $request->getJournal();
		$rangeInfo = PKPHandler::getRangeInfo($request, 'subscriptions');

		if ($institutional) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			$templateFile = 'subscription/institutionalSubscriptions.tpl';
			$fieldOptions = SubscriptionAction::getInstitutionalSearchFieldOptions();
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
			$templateFile = 'subscription/individualSubscriptions.tpl';
			$fieldOptions = SubscriptionAction::getIndividualSearchFieldOptions();
		}

		// Subscription status
		$statusOptions = $subscriptionDao->getStatusOptions();
		$filterStatus = $request->getUserVar('filterStatus') == 0 ? null : $request->getUserVar('filterStatus');

		// Get the user's search conditions, if any
		$searchField = $request->getUserVar('searchField');
		$dateSearchField = $request->getUserVar('dateSearchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$subscriptions = $subscriptionDao->getSubscriptionsByJournalId($journal->getId(), $filterStatus, $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $rangeInfo);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('subscriptions', $subscriptions);

		// Set search parameters
		foreach (SubscriptionAction::getSearchFormDuplicateParameters() as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('filterStatus', $request->getUserVar('filterStatus'));
		$templateMgr->assign('statusOptions', array(0 => 'manager.subscriptions.allStatus') + $statusOptions);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		$templateMgr->assign('dateFieldOptions', SubscriptionAction::getDateFieldOptions());

		$templateMgr->display($templateFile);
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

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args, $request, $institutional = false) {
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
	}

	/**
	 * Renew a subscription.
	 * @param $args array first parameter is the ID of the subscription to renew
	 */
	function renewSubscription($args, $request, $institutional = false) {
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
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array second parameter is the ID of the subscription to edit
	 */
	function editSubscription($args, $request, $institutional = false) {
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
			$subscriptionForm->display();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription($args, $request, $institutional = false) {
		SubscriptionAction::editSubscription($args, $request, $institutional);
	}

	/**
	 * Display a list of users from which to choose a subscriber/subscription contact.
	 */
	function selectSubscriber($args, $request, $institutional = false) {
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
		$templateMgr->display('subscription/users.tpl');
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription($args, $request, $institutional = false) {
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

				$subscriptionForm->display();
			} else {
				if ($subscriptionForm->validate()) {
					$subscriptionForm->execute();
					return true;
				} else {
					$templateMgr = TemplateManager::getManager($request);

					if ($subscriptionId == null) {
						$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.createTitle');
					} else {
						$templateMgr->assign('subscriptionTitle', 'manager.subscriptions.editTitle');
					}

					$subscriptionForm->display();
					return false;
				}
			}
		}
	}

	/**
	 * Display a list of subscription types for the current journal.
	 */
	function subscriptionTypes($request) {
		$journal = $request->getJournal();
		$rangeInfo = Handler::getRangeInfo($request, 'subscriptionTypes');
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes = $subscriptionTypeDao->getSubscriptionTypesByJournalId($journal->getId(), $rangeInfo);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('subscriptionTypes', $subscriptionTypes);

		$templateMgr->display('subscription/subscriptionTypes.tpl');
	}

	/**
	 * Rearrange the order of subscription types.
	 */
	function moveSubscriptionType($args, $request) {
		$subscriptionTypeId = $request->getUserVar('id');
		$journal = $request->getJournal();

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getSubscriptionType($subscriptionTypeId);

		if ($subscriptionType && $subscriptionType->getJournalId() == $journal->getId()) {
			$direction = $request->getUserVar('dir');

			if ($direction != null) {
				// moving with up or down arrow
				$isDown = ($direction=='d');

				$subscriptionType->setSequence($subscriptionType->getSequence()+($isDown?1.5:-1.5));
			} else {
				// Dragging and dropping onto another journal
				$prevId = $request->getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevSubscriptionType = $subscriptionTypeDao->getSubscriptionType($prevId);
					$prevSeq = $prevSubscriptionType->getSequence();
				}

				$subscriptionType->setSequence($prevSeq + .5);
			}

			$subscriptionTypeDao->updateSubscriptionType($subscriptionType);
			$subscriptionTypeDao->resequenceSubscriptionTypes($subscriptionType->getJournalId());
		}
	}

	/**
	 * Delete a subscription type.
	 * @param $args array first parameter is the ID of the subscription type to delete
	 */
	function deleteSubscriptionType($args, $request) {
		$subscriptionTypeId = (int) array_shift($args);
		$journal = $request->getJournal();

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');

		// Ensure subscription type is for this journal
		if ($subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getId()) {
			$subscriptionTypeDao->deleteSubscriptionTypeById($subscriptionTypeId);
		}
	}

	/**
	 * Display form to edit a subscription type.
	 * @param $args array optional, first parameter is the ID of the subscription type to edit
	 */
	function editSubscriptionType($args, $request) {
		$journal = $request->getJournal();
		$subscriptionTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');

		// Ensure subscription type is valid and for this journal
		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getId()) || $subscriptionTypeId == null) {

			import('classes.subscription.form.SubscriptionTypeForm');

			$templateMgr = TemplateManager::getManager($request);
			$subscriptionTypeCreated = $request->getUserVar('subscriptionTypeCreated') == 1 ? 1 : 0;
			$templateMgr->assign('subscriptionTypeCreated', $subscriptionTypeCreated);

			if ($subscriptionTypeId == null) {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
			} else {
				$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');
			}

			$subscriptionTypeForm = new SubscriptionTypeForm($subscriptionTypeId);
			if ($subscriptionTypeForm->isLocaleResubmit()) {
				$subscriptionTypeForm->readInputData();
			} else {
				$subscriptionTypeForm->initData();
			}
			$subscriptionTypeForm->display();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Save changes to a subscription type.
	 */
	function updateSubscriptionType($request) {
		import('classes.subscription.form.SubscriptionTypeForm');

		$journal = $request->getJournal();
		$subscriptionTypeId = $request->getUserVar('typeId') == null ? null : (int) $request->getUserVar('typeId');
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');

		if (($subscriptionTypeId != null && $subscriptionTypeDao->getSubscriptionTypeJournalId($subscriptionTypeId) == $journal->getId()) || $subscriptionTypeId == null) {

			$subscriptionTypeForm = new SubscriptionTypeForm($subscriptionTypeId);
			$subscriptionTypeForm->readInputData();

			if ($subscriptionTypeForm->validate()) {
				$subscriptionTypeForm->execute();
				return true;
			} else {
				$templateMgr = TemplateManager::getManager($request);

				if ($subscriptionTypeId == null) {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.createTitle');
				} else {
					$templateMgr->assign('subscriptionTypeTitle', 'manager.subscriptionTypes.editTitle');
				}

				$subscriptionTypeForm->display();
				return false;
			}
		}
	}

	/**
	 * Display subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionPolicies($args, $request) {
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
		$subscriptionPolicyForm->display();
	}

	/**
	 * Save subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveSubscriptionPolicies($args, $request) {
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
			$subscriptionPolicyForm->display();
		} else {
			$subscriptionPolicyForm->display();
		}
	}

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
		$subscriptionType = $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

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
