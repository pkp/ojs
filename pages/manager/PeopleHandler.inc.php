<?php

/**
 * @file pages/manager/PeopleHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PeopleHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for people management functions.
 */

import('pages.manager.ManagerHandler');

class PeopleHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function PeopleHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display list of people in the selected role.
	 * @param $args array first parameter is the role ID to display
	 * @param $request PKPRequest
	 */
	function people($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$roleDao =& DAORegistry::getDAO('RoleDAO');

		if ($request->getUserVar('roleSymbolic')!=null) $roleSymbolic = $request->getUserVar('roleSymbolic');
		else $roleSymbolic = isset($args[0])?$args[0]:'all';

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = isset($sortDirection) ? $sortDirection : SORT_DIRECTION_ASC;

		if ($roleSymbolic != 'all' && String::regexp_match_get('/^(\w+)s$/', $roleSymbolic, $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				$request->redirect(null, null, null, 'all');
			}
			$role = $roleDao->newDataObject();
			$role->setId($roleId);
			$roleName = $role->getRoleName(true);

		} else {
			$roleId = 0;
			$roleName = 'manager.people.allUsers';
		}

		$journal =& $request->getJournal();
		$templateMgr =& TemplateManager::getManager($request);

		$searchType = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = $this->getRangeInfo($request, 'users');

		if ($roleId) {
			$users =& $roleDao->getUsersByRoleId($roleId, $journal->getId(), $searchType, $search, $searchMatch, $rangeInfo, $sort, $sortDirection);
			$templateMgr->assign('roleId', $roleId);
		} else {
			$users =& $roleDao->getUsersByJournalId($journal->getId(), $searchType, $search, $searchMatch, $rangeInfo, $sort, $sortDirection);
		}

		$templateMgr->assign('currentUrl', $request->url(null, null, 'people', 'all'));
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', $request->getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		$templateMgr->assign_by_ref('roleSettings', $this->retrieveRoleAssignmentPreferences($journal->getId()));

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $journal->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($journal->getId()) : null);
		}
		$fieldOptions = Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_INTERESTS => 'user.interests',
			USER_FIELD_EMAIL => 'user.email'
		);
		if ($roleId == ROLE_ID_REVIEWER) $fieldOptions = array_merge(array(USER_FIELD_INTERESTS => 'user.interests'), $fieldOptions);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		$role = $roleDao->newDataObject();
		$role->setId($roleId);
		$templateMgr->assign('rolePath', $role->getPath());
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);

		$session =& $request->getSession();
		$session->setSessionVar('enrolmentReferrer', $request->getRequestedArgs());

		$templateMgr->display('manager/people/enrollment.tpl');
	}

	/**
	 * Search for users to enroll in a specific role.
	 * @param $args array first parameter is the selected role ID
	 * @param $request PKPRequest
	 */
	function enrollSearch($args, $request) {
		$this->validate();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$roleId = (int)(isset($args[0])?$args[0]:$request->getUserVar('roleId'));
		$journal = $journalDao->getByPath($request->getRequestedJournalPath());

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = $request->getUserVar('sortDirection');

		$templateMgr =& TemplateManager::getManager($request);

		$this->setupTemplate($request, true);

		$searchType = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = $this->getRangeInfo($request, 'users');

		$users =& $userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo, $sort);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		$templateMgr->assign_by_ref('roleSettings', $this->retrieveRoleAssignmentPreferences($journal->getId()));

		$templateMgr->assign('roleId', $roleId);
		$role = $roleDao->newDataObject();
		$role->setId($roleId);
		$templateMgr->assign('roleName', $role->getRoleName());
		$fieldOptions = Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		);
		if ($roleId == ROLE_ID_REVIEWER) $fieldOptions = array_merge(array(USER_FIELD_INTERESTS => 'user.interests'), $fieldOptions);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', $request->getUser());
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('sort', $sort);

		$session =& $request->getSession();
		$referrerUrl = $session->getSessionVar('enrolmentReferrer');
			$templateMgr->assign('enrolmentReferrerUrl', isset($referrerUrl) ? $request->url(null,'manager','people',$referrerUrl) : $request->url(null,'manager'));
			$session->unsetSessionVar('enrolmentReferrer');

		$templateMgr->display('manager/people/searchUsers.tpl');
	}

	/**
	 * Show users with no role.
	 */
	function showNoRole($args, &$request) {
		$this->validate();

		$userDao =& DAORegistry::getDAO('UserDAO');

		$templateMgr =& TemplateManager::getManager($request);

		parent::setupTemplate($request, true);

		$rangeInfo = $this->getRangeInfo($request, 'users');

		$users =& $userDao->getUsersWithNoRole(true, $rangeInfo);

		$templateMgr->assign('omitSearch', true);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', $request->getUser());
		$templateMgr->display('manager/people/searchUsers.tpl');
	}

	/**
	 * Enroll a user in a role.
	 */
	function enroll($args, $request) {
		$this->validate();
		$roleId = (int)(isset($args[0])?$args[0]:$request->getUserVar('roleId'));

		// Get a list of users to enroll -- either from the
		// submitted array 'users', or the single user ID in
		// 'userId'
		$users = $request->getUserVar('users');
		if (!isset($users) && $request->getUserVar('userId') != null) {
			$users = array($request->getUserVar('userId'));
		}

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getByPath($request->getRequestedJournalPath());
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$role = $roleDao->newDataObject();
		$role->setId($roleId);
		$rolePath = $role->getPath();

		if ($users != null && is_array($users) && $rolePath != '' && $rolePath != 'admin') {
			for ($i=0; $i<count($users); $i++) {
				if (!$roleDao->userHasRole($journal->getId(), $users[$i], $roleId)) {
					$role = new Role();
					$role->setJournalId($journal->getId());
					$role->setUserId($users[$i]);
					$role->setRoleId($roleId);

					$roleDao->insertRole($role);
				}
			}
		}

		$request->redirect(null, null, 'people', (empty($rolePath) ? null : $rolePath . 's'));
	}

	/**
	 * Unenroll a user from a role.
	 */
	function unEnroll($args, $request) {
		$roleId = (int) array_shift($args);
		$journalId = (int) $request->getUserVar('journalId');
		$userId = (int) $request->getUserVar('userId');

		$this->validate();

		$journal =& $request->getJournal();
		if ($roleId != ROLE_ID_SITE_ADMIN && (Validation::isSiteAdmin() || $journalId = $journal->getId())) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->deleteRoleByUserId($userId, $journalId, $roleId);
		}

		$role = $roleDao->newDataObject();
		$role->setId($roleId);
		$request->redirect(null, null, 'people', $role->getPath() . 's');
	}

	/**
	 * Show form to synchronize user enrollment with another journal.
	 */
	function enrollSyncSelect($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$rolePath = isset($args[0]) ? $args[0] : '';
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);
		if ($roleId) {
			$role = $roleDao->newDataObject();
			$role->setId($roleId);
			$roleName = $role->getRoleName(true);
		} else {
			$rolePath = '';
			$roleName = '';
		}

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalTitles =& $journalDao->getTitles();

		$journal =& $request->getJournal();
		unset($journalTitles[$journal->getId()]);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('rolePath', $rolePath);
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign('journalOptions', $journalTitles);
		$templateMgr->display('manager/people/enrollSync.tpl');
	}

	/**
	 * Synchronize user enrollment with another journal.
	 */
	function enrollSync($args, $request) {
		$this->validate();

		$journal =& $request->getJournal();
		$rolePath = $request->getUserVar('rolePath');
		$syncJournal = $request->getUserVar('syncJournal');

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);

		if ((!empty($roleId) || $rolePath == 'all') && !empty($syncJournal)) {
			$roles =& $roleDao->getRolesByJournalId($syncJournal == 'all' ? null : $syncJournal, $roleId);
			while ($role = $roles->next()) {
				$role->setJournalId($journal->getId());
				if ($role->getPath() != 'admin' && !$roleDao->userHasRole($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
		}
		$role = $roleDao->newDataObject();
		$role->setId($roleId);
		$request->redirect(null, null, 'people', $role->getPath());
	}

	/**
	 * Display form to create a new user.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createUser($args, &$request) {
		$this->editUser($args, $request);
	}

	/**
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 */
	function suggestUsername($args, $request) {
		$this->validate();
		$suggestion = Validation::suggestUsername(
			$request->getUserVar('firstName'),
			$request->getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Display form to create/edit a user profile.
	 * @param $args array optional, if set the first parameter is the ID of the user to edit
	 */
	function editUser($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$journal =& $request->getJournal();

		$userId = isset($args[0])?$args[0]:null;

		$templateMgr =& TemplateManager::getManager($request);

		if ($userId !== null && !Validation::canAdminister($journal->getId(), $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', $request->url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		import('classes.manager.form.UserManagementForm');

		$templateMgr->assign_by_ref('roleSettings', $this->retrieveRoleAssignmentPreferences($journal->getId()));

		$templateMgr->assign('currentUrl', $request->url(null, null, 'people', 'all'));
		$userForm = new UserManagementForm($userId);

		if ($userForm->isLocaleResubmit()) {
			$userForm->readInputData();
		} else {
			$userForm->initData($args, $request);
		}
		$userForm->display();
	}

	/**
	 * Allow the Journal Manager to merge user accounts, including attributed articles etc.
	 */
	function mergeUsers($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$templateMgr =& TemplateManager::getManager($request);

		$oldUserIds = (array) $request->getUserVar('oldUserIds');
		$newUserId = $request->getUserVar('newUserId');

		// Ensure that we have administrative priveleges over the specified user(s).
		$canAdministerAll = true;
		foreach ($oldUserIds as $oldUserId) {
			if (!Validation::canAdminister($journalId, $oldUserId)) $canAdministerAll = false;
		}

		if (
			(!empty($oldUserIds) && !$canAdministerAll) ||
			(!empty($newUserId) && !Validation::canAdminister($journalId, $newUserId))
		) {
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', $request->url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		if (!empty($oldUserIds) && !empty($newUserId)) {
			import('classes.user.UserAction');
			foreach ($oldUserIds as $oldUserId) {
				UserAction::mergeUsers($oldUserId, $newUserId);
			}
			$request->redirect(null, 'manager');
		}

		// The manager must select one or both IDs.
		if ($request->getUserVar('roleSymbolic')!=null) $roleSymbolic = $request->getUserVar('roleSymbolic');
		else $roleSymbolic = isset($args[0])?$args[0]:'all';

		if ($roleSymbolic != 'all' && String::regexp_match_get('/^(\w+)s$/', $roleSymbolic, $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				$request->redirect(null, null, null, 'all');
			}
			$role = $roleDao->newDataObject();
			$role->setId($roleId);
			$roleName = $role->getRoleName(true);
		} else {
			$roleId = 0;
			$roleName = 'manager.people.allUsers';
		}

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = $request->getUserVar('sortDirection');

		$searchType = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} else if (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = $this->getRangeInfo($request, 'users');

		if ($roleId) {
			$users =& $roleDao->getUsersByRoleId($roleId, $journalId, $searchType, $search, $searchMatch, $rangeInfo, $sort);
			$templateMgr->assign('roleId', $roleId);
		} else {
			$users =& $roleDao->getUsersByJournalId($journalId, $searchType, $search, $searchMatch, $rangeInfo, $sort);
		}

		$templateMgr->assign_by_ref('roleSettings', $this->retrieveRoleAssignmentPreferences($journal->getId()));

		$templateMgr->assign('currentUrl', $request->url(null, null, 'people', 'all'));
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', $request->getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $journal->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($journalId) : null);
		}
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email',
			USER_FIELD_INTERESTS => 'user.interests'
		));
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('oldUserIds', $oldUserIds);
		$role = $roleDao->newDataObject();
		$role->setId($roleId);
		$templateMgr->assign('rolePath', $role->getPath());
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('manager/people/selectMergeUser.tpl');
	}

	/**
	 * Disable a user's account.
	 * @param $args array the ID of the user to disable
	 */
	function disableUser($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$userId = isset($args[0])?$args[0]:$request->getUserVar('userId');
		$user =& $request->getUser();
		$journal =& $request->getJournal();

		if ($userId != null && $userId != $user->getId()) {
			if (!Validation::canAdminister($journal->getId(), $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->assign('pageTitle', 'manager.people');
				$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
				$templateMgr->assign('backLink', $request->url(null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
				return $templateMgr->display('common/error.tpl');
			}
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getById($userId);
			if ($user) {
				$user->setDisabled(1);
				$user->setDisabledReason($request->getUserVar('reason'));
			}
			$userDao->updateObject($user);
		}

		$request->redirect(null, null, 'people', 'all');
	}

	/**
	 * Enable a user's account.
	 * @param $args array the ID of the user to enable
	 */
	function enableUser($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$userId = isset($args[0])?$args[0]:null;
		$user =& $request->getUser();

		if ($userId != null && $userId != $user->getId()) {
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getById($userId, true);
			if ($user) {
				$user->setDisabled(0);
			}
			$userDao->updateObject($user);
		}

		$request->redirect(null, null, 'people', 'all');
	}

	/**
	 * Remove a user from all roles for the current journal.
	 * @param $args array the ID of the user to remove
	 */
	function removeUser($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$userId = isset($args[0])?$args[0]:null;
		$user =& $request->getUser();
		$journal =& $request->getJournal();

		if ($userId != null && $userId != $user->getId()) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->deleteRoleByUserId($userId, $journal->getId());
		}

		$request->redirect(null, null, 'people', 'all');
	}

	/**
	 * Save changes to a user profile.
	 */
	function updateUser($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$journal =& $request->getJournal();
		$userId = $request->getUserVar('userId');

		if (!empty($userId) && !Validation::canAdminister($journal->getId(), $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', $request->url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		import('classes.manager.form.UserManagementForm');

		$userForm = new UserManagementForm($userId);

		$userForm->readInputData();

		if ($userForm->validate()) {
			$userForm->execute();

			if ($request->getUserVar('createAnother')) {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->assign('currentUrl', $request->url(null, null, 'people', 'all'));
				$templateMgr->assign('userCreated', true);
				unset($userForm);
				$userForm = new UserManagementForm();
				$userForm->initData($args, $request);
				$userForm->display();

			} else {
				if ($source = $request->getUserVar('source')) $request->redirectUrl($source);
				else $request->redirect(null, null, 'people', 'all');
			}
		} else {
			$userForm->display();
		}
	}

	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 */
	function userProfile($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('currentUrl', $request->url(null, null, 'people', 'all'));

		$userDao =& DAORegistry::getDAO('UserDAO');
		$userId = isset($args[0]) ? $args[0] : 0;
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getById($userId);
		} else {
			$user = $userDao->getByUsername($userId);
		}

		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->assign('backLink', $request->url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			$templateMgr->display('common/error.tpl');
		} else {
			$site =& $request->getSite();
			$journal =& $request->getJournal();

			$isSiteAdmin = Validation::isSiteAdmin();
			$templateMgr->assign('isSiteAdmin', $isSiteAdmin);
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roles =& $roleDao->getRolesByUserId($user->getId(), $isSiteAdmin?null:$journal->getId());
			$templateMgr->assign_by_ref('userRoles', $roles);
			if ($isSiteAdmin) {
				// We'll be displaying all roles, so get ready to display
				// journal names other than the current journal.
				$journalDao =& DAORegistry::getDAO('JournalDAO');
				$journalTitles =& $journalDao->getTitles();
				$templateMgr->assign_by_ref('journalTitles', $journalTitles);
			}

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = null;
			if ($user->getCountry() != '') {
				$country = $countryDao->getCountry($user->getCountry());
			}
			$templateMgr->assign('country', $country);

			$templateMgr->assign('userInterests', $user->getInterestString());

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign('localeNames', AppLocale::getAllLocales());
			$templateMgr->display('manager/people/userProfile.tpl');
		}
	}
}

?>
