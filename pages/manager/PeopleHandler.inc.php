<?php

/**
 * PeopleHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for people management functions. 
 *
 * $Id$
 */

class PeopleHandler extends ManagerHandler {

	/**
	 * Display list of people in the selected role.
	 * @param $args array first parameter is the role ID to display
	 */	
	function people($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if (Request::getUserVar('roleSymbolic')!=null) $roleSymbolic = Request::getUserVar('roleSymbolic');
		else $roleSymbolic = isset($args[0])?$args[0]:'all';

		if ($roleSymbolic != 'all' && String::regexp_match_get('/^(\w+)s$/', $roleSymbolic, $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				Request::redirect(null, null, null, 'all');
			}
			$roleName = $roleDao->getRoleName($roleId, true);
			
		} else {
			$roleId = 0;
			$roleName = 'manager.people.allUsers';
		}
		
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		
		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
			
		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users');

		if ($roleId) {
			$users = &$roleDao->getUsersByRoleId($roleId, $journal->getJournalId(), $searchType, $search, $searchMatch, $rangeInfo);
			$templateMgr->assign('roleId', $roleId);
			switch($roleId) {
				case ROLE_ID_JOURNAL_MANAGER:
					$helpTopicId = 'journal.roles.journalManager';
					break;
				case ROLE_ID_EDITOR:
					$helpTopicId = 'journal.roles.editor';
					break;
				case ROLE_ID_SECTION_EDITOR:
					$helpTopicId = 'journal.roles.sectionEditor';
					break;
				case ROLE_ID_LAYOUT_EDITOR:
					$helpTopicId = 'journal.roles.layoutEditor';
					break;
				case ROLE_ID_REVIEWER:
					$helpTopicId = 'journal.roles.reviewer';
					break;
				case ROLE_ID_COPYEDITOR:
					$helpTopicId = 'journal.roles.copyeditor';
					break;
				case ROLE_ID_PROOFREADER:
					$helpTopicId = 'journal.roles.proofreader';
					break;
				case ROLE_ID_AUTHOR:
					$helpTopicId = 'journal.roles.author';
					break;
				case ROLE_ID_READER:
					$helpTopicId = 'journal.roles.reader';
					break;
				default:
					$helpTopicId = 'journal.roles.index';
					break;
			}
		} else {
			$users = &$roleDao->getUsersByJournalId($journal->getJournalId(), $searchType, $search, $searchMatch, $rangeInfo);
			$helpTopicId = 'journal.users.allUsers';
		}
		
		$templateMgr->assign('currentUrl', Request::url(null, null, 'people', 'all'));
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);
		
		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $searchInitial);

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $journal->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($journal->getJournalId()) : null);
		}
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email',
			USER_FIELD_INTERESTS => 'user.interests'
		));
		$templateMgr->assign('rolePath', $roleDao->getRolePath($roleId));
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->display('manager/people/enrollment.tpl');
	}
	
	/**
	 * Search for users to enroll in a specific role.
	 * @param $args array first parameter is the selected role ID
	 */
	function enrollSearch($args) {
		parent::validate();

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$roleId = (int)(isset($args[0])?$args[0]:Request::getUserVar('roleId'));
		$journal = &$journalDao->getJournalByPath(Request::getRequestedJournalPath());

		$templateMgr = &TemplateManager::getManager();

		parent::setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
			
		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users');

		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);
		
		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $searchInitial);

		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('helpTopicId', 'journal.users.index');
		$templateMgr->display('manager/people/searchUsers.tpl');
	}
	
	/**
	 * Enroll a user in a role.
	 */
	function enroll($args) {
		parent::validate();
		$roleId = (int)(isset($args[0])?$args[0]:Request::getUserVar('roleId'));

		// Get a list of users to enroll -- either from the
		// submitted array 'users', or the single user ID in
		// 'userId'
		$users = Request::getUserVar('users');
		if (!isset($users) && Request::getUserVar('userId') != null) {
			$users = array(Request::getUserVar('userId'));
		}
		
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &$journalDao->getJournalByPath(Request::getRequestedJournalPath());
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$rolePath = $roleDao->getRolePath($roleId);
		
		if ($users != null && is_array($users) && $rolePath != '' && $rolePath != 'admin') {
			for ($i=0; $i<count($users); $i++) {
				if (!$roleDao->roleExists($journal->getJournalId(), $users[$i], $roleId)) {
					$role = &new Role();
					$role->setJournalId($journal->getJournalId());
					$role->setUserId($users[$i]);
					$role->setRoleId($roleId);
				
					$roleDao->insertRole($role);
				}
			}
		}
			
		Request::redirect(null, null, 'people', (empty($rolePath) ? null : $rolePath . 's'));
	}
	
	/**
	 * Unenroll a user from a role.
	 */
	function unEnroll($args) {
		$roleId = isset($args[0])?$args[0]:0;
		parent::validate();
			
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &$journalDao->getJournalByPath(Request::getRequestedJournalPath());
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		if ($roleId != $roleDao->getRoleIdFromPath('admin')) {
			$roleDao->deleteRoleByUserId(Request::getUserVar('userId'), $journal->getJournalId(), $roleId);
		}
		
		Request::redirect(null, null, 'people');
	}
	
	/**
	 * Show form to synchronize user enrollment with another journal.
	 */
	function enrollSyncSelect($args) {
		parent::validate();
		
		$rolePath = isset($args[0]) ? $args[0] : '';
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);
		if ($roleId) {
			$roleName = $roleDao->getRoleName($roleId, true);
		} else {
			$rolePath = '';
			$roleName = '';
		}
		
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journalTitles = &$journalDao->getJournalTitles();
		
		$journal = &Request::getJournal();
		unset($journalTitles[$journal->getJournalId()]);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('rolePath', $rolePath);
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign('journalOptions', $journalTitles);
		$templateMgr->display('manager/people/enrollSync.tpl');
	}
	
	/**
	 * Synchronize user enrollment with another journal.
	 */
	function enrollSync($args) {
		parent::validate();
		
		$journal = &Request::getJournal();
		$rolePath = Request::getUserVar('rolePath');
		$syncJournal = Request::getUserVar('syncJournal');
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);
		
		if ((!empty($roleId) || $rolePath == 'all') && !empty($syncJournal)) {
			$roles = &$roleDao->getRolesByJournalId($syncJournal == 'all' ? null : $syncJournal, $roleId);
			while (!$roles->eof()) {
				$role = &$roles->next();
				$role->setJournalId($journal->getJournalId());
				if ($role->getRolePath() != 'admin' && !$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
		}
		
		Request::redirect(null, null, 'people', $roleDao->getRolePath($roleId));
	}
	
	/**
	 * Display form to create a new user.
	 */
	function createUser() {
		PeopleHandler::editUser();
	}
	
	/**
	 * Display form to create/edit a user profile.
	 * @param $args array optional, if set the first parameter is the ID of the user to edit
	 */
	function editUser($args = array()) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();

		$userId = isset($args[0])?$args[0]:null;

		$templateMgr = &TemplateManager::getManager();

		if (!Validation::canAdminister($journal->getJournalId(), $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		import('manager.form.UserManagementForm');
		
		$templateMgr->assign('currentUrl', Request::url(null, null, 'people', 'all'));
		$userForm = &new UserManagementForm($userId);
		$userForm->initData();
		$userForm->display();
	}
	
	/**
	 * Disable a user's account.
	 * @param $args array the ID of the user to disable
	 */
	function disableUser($args) {
		parent::validate();
		parent::setupTemplate(true);

		$userId = isset($args[0])?$args[0]:Request::getUserVar('userId');
		$user = &Request::getUser();
		$journal = &Request::getJournal();

		if ($userId != null && $userId != $user->getUserId()) {
			if (!Validation::canAdminister($journal->getJournalId(), $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'manager.people');
				$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
				$templateMgr->assign('backLink', Request::url(null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
				return $templateMgr->display('common/error.tpl');
			}
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUser($userId);
			if ($user) {
				$user->setDisabled(1);
				$user->setDisabledReason(Request::getUserVar('reason'));
			}
			$userDao->updateUser($user);
		}

		Request::redirect(null, null, 'people', 'all');
	}

	/**
	 * Enable a user's account.
	 * @param $args array the ID of the user to enable
	 */
	function enableUser($args) {
		parent::validate();
		parent::setupTemplate(true);

		$userId = isset($args[0])?$args[0]:null;
		$user = &Request::getUser();

		if ($userId != null && $userId != $user->getUserId()) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUser($userId, true);
			if ($user) {
				$user->setDisabled(0);
			}
			$userDao->updateUser($user);
		}

		Request::redirect(null, null, 'people', 'all');
	}

	/**
	 * Remove a user from all roles for the current journal.
	 * @param $args array the ID of the user to remove
	 */
	function removeUser($args) {
		parent::validate();
		parent::setupTemplate(true);

		$userId = isset($args[0])?$args[0]:null;
		$user = &Request::getUser();
		$journal = &Request::getJournal();

		if ($userId != null && $userId != $user->getUserId()) {
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$roleDao->deleteRoleByUserId($userId, $journal->getJournalId());
		}

		Request::redirect(null, null, 'people', 'all');
	}

	/**
	 * Save changes to a user profile.
	 */
	function updateUser() {
		parent::validate();

		$journal = &Request::getJournal();
		$userId = Request::getUserVar('userId');

		if (!empty($userId) && !Validation::canAdminister($journal->getJournalId(), $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		import('manager.form.UserManagementForm');

		$userForm = &new UserManagementForm($userId);
		$userForm->readInputData();
		
		if ($userForm->validate()) {
			$userForm->execute();
			
			if (Request::getUserVar('createAnother')) {
				// C
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::url(null, null, 'people', 'all'));
				$templateMgr->assign('userCreated', true);
				$userForm = &new UserManagementForm();
				$userForm->initData();
				$userForm->display();
				
			} else {
				Request::redirect(null, null, 'people', 'all');
			}
			
		} else {
			parent::setupTemplate(true);
			$userForm->display();
		}
	}
	
	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 */
	function userProfile($args) {
		parent::validate();
		parent::setupTemplate(true);
			
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::url(null, null, 'people', 'all'));
		$templateMgr->assign('helpTopicId', 'journal.users.index');
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$userId = isset($args[0]) ? $args[0] : 0;
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getUser($userId);
		} else {
			$user = $userDao->getUserByUsername($userId);
		}
		
		
		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->assign('backLink', Request::url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			$templateMgr->display('common/error.tpl');
			
		} else {
			$site = &Request::getSite();
			$journal = &Request::getJournal();
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $journal->getJournalId());

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = null;
			if ($user->getCountry() != '') {
				$country = $countryDao->getCountry($user->getCountry());
			}
			$templateMgr->assign('country', $country);

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign_by_ref('userRoles', $roles);
			$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->display('manager/people/userProfile.tpl');
		}
	}
	
	/**
	 * Send an email to a user or group of users.
	 */
	function email($args) {
		parent::validate();

		parent::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.users.emailUsers');

		$userDao = &DAORegistry::getDAO('UserDAO');

		$site = &Request::getSite();
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.MailTemplate');
		$email = &new MailTemplate(Request::getUserVar('template'), Request::getUserVar('locale'));
		
		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			Request::redirect(null, Request::getRequestedPage());
		} else {
			$email->assignParams(); // FIXME Forces default parameters to be assigned (should do this automatically in MailTemplate?)
			if (!Request::getUserVar('continued')) {
				if (count($email->getRecipients())==0) $email->addRecipient($user->getEmail(), $user->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, 'email'), array(), 'manager/people/email.tpl');
		}
	}

	/**
	 * Select a template to send to a user or group of users.
	 */
	function selectTemplate($args) {
		parent::validate();

		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();

		$site = &Request::getSite();
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$locale = Request::getUserVar('locale');
		if (!isset($locale) || $locale == null) $locale = Locale::getLocale();

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates = &$emailTemplateDao->getEmailTemplates($locale, $journal->getJournalId());

		$templateMgr->assign_by_ref('emailTemplates', $emailTemplates);
		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('locales', $journal->getSetting('supportedLocales'));
		$templateMgr->assign('localeNames', Locale::getAllLocales());
		$templateMgr->assign('persistAttachments', Request::getUserVar('persistAttachments'));
		$templateMgr->assign('to', Request::getUserVar('to'));
		$templateMgr->assign('cc', Request::getUserVar('cc'));
		$templateMgr->assign('bcc', Request::getUserVar('bcc'));
		$templateMgr->assign('helpTopicId', 'journal.users.emailUsers');
		$templateMgr->display('manager/people/selectTemplate.tpl');
	}

	/**
	 * Sign in as another user.
	 * @param $args array ($userId)
	 */
	function signInAsUser($args) {
		parent::validate();
		
		if (isset($args[0]) && !empty($args[0])) {
			$userId = (int)$args[0];
			$journal = &Request::getJournal();

			if (!Validation::canAdminister($journal->getJournalId(), $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'manager.people');
				$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
				$templateMgr->assign('backLink', Request::url(null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
				return $templateMgr->display('common/error.tpl');
			}

			$userDao = &DAORegistry::getDAO('UserDAO');
			$newUser = &$userDao->getUser($userId);
			$session = &Request::getSession();

			// FIXME Support "stack" of signed-in-as user IDs?
			if (isset($newUser) && $session->getUserId() != $newUser->getUserId()) {
				$session->setSessionVar('signedInAs', $session->getUserId());
				$session->setSessionVar('userId', $userId);
				$session->setUserId($userId);
				$session->setSessionVar('username', $newUser->getUsername());
				Request::redirect(null, 'user');
			}
		}
		Request::redirect(null, Request::getRequestedPage());
	}
	
	/**
	 * Restore original user account after signing in as a user.
	 */
	function signOutAsUser() {
		Handler::validate();
		
		$session = &Request::getSession();
		$signedInAs = $session->getSessionVar('signedInAs');
		
		if (isset($signedInAs) && !empty($signedInAs)) {
			$signedInAs = (int)$signedInAs;
			
			$userDao = &DAORegistry::getDAO('UserDAO');
			$oldUser = &$userDao->getUser($signedInAs);
			
			$session->unsetSessionVar('signedInAs');
			
			if (isset($oldUser)) {
				$session->setSessionVar('userId', $signedInAs);
				$session->setUserId($signedInAs);
				$session->setSessionVar('username', $oldUser->getUsername());
			}
		}
		
		Request::redirect(null, Request::getRequestedPage());
	}
}

?>
