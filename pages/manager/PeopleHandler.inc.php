<?php

/**
 * PeopleHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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
			
		if (isset($args[0]) && $args[0] != 'all' && String::regexp_match_get('/^(\w+)s$/', $args[0], $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				Request::redirect('manager/people/all');
			}
			$roleName = $roleDao->getRoleName($roleId, true);
			
		} else {
			$roleId = 0;
			$roleName = 'manager.people.allUsers';
		}
		
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		
		if ($roleId) {
			$users = &$roleDao->getUsersByRoleId($roleId, $journal->getJournalId());
			$templateMgr->assign('roleId', $roleId);
		} else {
			$users = &$roleDao->getUsersByJournalId($journal->getJournalId());
		}
		
		$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/people/all');
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign('users', $users);
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnTimeliness', $journal->getSetting('rateReviewerOnTimeliness'));
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('timelinessRatings', $journal->getSetting('rateReviewerOnTimeliness') ? $reviewAssignmentDao->getAverageTimelinessRatings($journal->getJournalId()) : null);
			$templateMgr->assign('qualityRatings', $journal->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($journal->getJournalId()) : null);
		}
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

		$roleId = isset($args[0])?$args[0]:0;
		$journal = &$journalDao->getJournalByPath(Request::getRequestedJournalPath());

		$templateMgr = &TemplateManager::getManager();

		parent::setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$search_initial = Request::getUserVar('search_initial');			if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
		}
		else if (isset($search_initial)) {
			$searchType = USER_FIELD_INITIAL;
			$search = $search_initial;
		}

		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search);

		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username'
		));
		$templateMgr->assign('users', $users);

		$templateMgr->display('manager/people/searchUsers.tpl');
	}
	
	/**
	 * Enroll a user in a role.
	 */
	function enroll() {
		parent::validate();

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
		$rolePath = $roleDao->getRolePath(Request::getUserVar('roleId'));
		
		if ($users != null && is_array($users) && $rolePath != '' && $rolePath != 'admin') {
			for ($i=0; $i<count($users); $i++) {
				if (!$roleDao->roleExists($journal->getJournalId(), $users[$i], Request::getUserVar('roleId'))) {
					$role = &new Role();
					$role->setJournalId($journal->getJournalId());
					$role->setUserId($users[$i]);
					$role->setRoleId(Request::getUserVar('roleId'));
				
					$roleDao->insertRole($role);
				}
			}
		}
			
		Request::redirect('manager/people' . (empty($rolePath) ? '' : '/' . $rolePath . 's'));
	}
	
	/**
	 * Unenroll a user from a role.
	 */
	function unEnroll() {
		parent::validate();
			
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &$journalDao->getJournalByPath(Request::getRequestedJournalPath());
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		if (Request::getUserVar('roleId') != $roleDao->getRoleIdFromPath('admin')) {
			$roleDao->deleteRoleByUserId(Request::getUserVar('userId'), $journal->getJournalId(), Request::getUserVar('roleId'));
		}
		
		Request::redirect('manager/people');
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
		
		import('manager.form.UserManagementForm');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/people/all');
		$userForm = &new UserManagementForm(!isset($args) || empty($args) ? null : $args[0]);
		$userForm->initData();
		$userForm->display();
	}
	
	/**
	 * Save changes to a user profile.
	 */
	function updateUser() {
		parent::validate();
		
		import('manager.form.UserManagementForm');
		
		$userForm = &new UserManagementForm(Request::getUserVar('userId'));
		$userForm->readInputData();
		
		if ($userForm->validate()) {
			$userForm->execute();
			
			if (Request::getUserVar('createAnother')) {
				// C
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/people/all');
				$templateMgr->assign('userCreated', true);
				$userForm = &new UserManagementForm();
				$userForm->initData();
				$userForm->display();
				
			} else {
				Request::redirect('manager/people/all');
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
		$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/people/all');
		
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
			$templateMgr->assign('backLink', Request::getPageUrl() . '/manager/people/all');
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			$templateMgr->display('common/error.tpl');
			
		} else {
			$site = &Request::getSite();
			$journal = &Request::getJournal();
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $journal->getJournalId());
			
			$templateMgr->assign('user', $user);
			$templateMgr->assign('userRoles', $roles);
			$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->display('manager/people/userProfile.tpl');
		}
	}
	
	/**
	 * Import a set of users from an uploaded data file.
	 * @param $args set first param to "import" to do the file import
	 */
	function importUsers($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('db.UserXMLParser');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/importUsers');
		
		if (isset($args[0]) && $args[0] == 'confirm') {
			$sendNotify = (bool) Request::getUserVar('sendNotify');
			$continueOnError = (bool) Request::getUserVar('continueOnError');
		
			if (($userFile = FileManager::getUploadedFilePath('userFile')) !== false) {
				// Import the uploaded file
				$journal = &Request::getJournal();
				$parser = &new UserXMLParser($journal->getJournalId());
				$users = &$parser->parseData($userFile);
				
				$i = 0;
				$usersRoles = array();
				foreach ($users as $user) {
					$usersRoles[$i] = array();
					foreach ($user->getRoles() as $role) {
						array_push($usersRoles[$i], $role->getRoleName());
					}
					$i++;
				}
				
				$templateMgr->assign('roleOptions',
					array(
						'' => 'manager.people.doNotEnroll',
						'manager' => 'user.role.manager',
						'editor' => 'user.role.editor',
						'sectionEditor' => 'user.role.sectionEditor',
						'layoutEditor' => 'user.role.layoutEditor',
						'reviewer' => 'user.role.reviewer',
						'copyeditor' => 'user.role.copyeditor',
						'proofreader' => 'user.role.proofreader',
						'author' => 'user.role.author',
						'reader' => 'user.role.reader'
					)
				);
				$templateMgr->assign('users', $users);
				$templateMgr->assign('usersRoles', $usersRoles);
				$templateMgr->assign('sendNotify', $sendNotify);
				$templateMgr->assign('continueOnError', $continueOnError);
				
				// Show confirmation form
				$templateMgr->display('manager/people/importUsersConfirm.tpl');
			} else {
				// No file was uploaded
				$templateMgr->assign('error', 'manager.people.importUsers.noFileError');
				$templateMgr->assign('sendNotify', $sendNotify);
				$templateMgr->assign('continueOnError', $continueOnError);
				$templateMgr->display('manager/people/importUsers.tpl');
			}
		} else if (isset($args[0]) && $args[0] == 'import')  {
			$userKeys = Request::getUserVar('userKeys');
			$sendNotify = (bool) Request::getUserVar('sendNotify');
			$continueOnError = (bool) Request::getUserVar('continueOnError');
				
			$users = array();
			foreach ($userKeys as $i) {
				$newUser = &new ImportedUser();
				if (($firstName = Request::getUserVar($i.'_firstName')) !== '') {
					$newUser->setFirstName($firstName);
				}
				if (($middleName = Request::getUserVar($i.'_middleName')) !== '') {
					$newUser->setMiddleName($middleName);
				}
				if (($lastName = Request::getUserVar($i.'_lastName')) !== '') {
					$newUser->setLastName($lastName);
				}
				if (($username = Request::getUserVar($i.'_username')) !== '') {
					$newUser->setUsername($username);
				}
				if (($password = Request::getUserVar($i.'_password')) !== '') {
					$newUser->setPassword($password);
				}
				if (($unencryptedPassword = Request::getUserVar($i.'_unencryptedPassword')) !== '') {
					$newUser->setUnencryptedPassword($unencryptedPassword);
				}
				if (($email = Request::getUserVar($i.'_email')) !== '') {
					$newUser->setEmail($email);
				}
			
				$newUserRoles = Request::getUserVar($i.'_roles');
				if (is_array($newUserRoles) && count($newUserRoles) > 0) {
					foreach ($newUserRoles as $newUserRole) {
						if ($newUserRole != '') {
							$role = &new Role();
							$role->setRoleId(RoleDAO::getRoleIdFromPath($newUserRole));
							$newUser->AddRole($role);
						}
					}
				}
				array_push($users, $newUser);
			}
		
			$journal = &Request::getJournal();
			$parser = &new UserXMLParser($journal->getJournalId());
			$parser->setUsersToImport($users);
			
			if (!$parser->importUsers($sendNotify, $continueOnError)) {
				// Failures occurred
				$templateMgr->assign('isError', true);
				$templateMgr->assign('errors', $parser->getErrors());
			}
			$templateMgr->assign('importedUsers', $parser->getImportedUsers());
			$templateMgr->display('manager/people/importUsersResults.tpl');
		} else {
			// Show upload form
			$templateMgr->display('manager/people/importUsers.tpl');
		}
	}
	
	/**
	 * Email a user or group of users.
	 */
	function emailUsers($args = null) {
		parent::validate();
		parent::setupTemplate(true);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
	
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		
		if (isset($args[0]) && $args[0] != 'display') {
			$recipientType = $args[0];
			if ($recipientType == 'user') {
				$recipientValue = $args[1];
			} else if ($recipientType == 'group') {
				$userIds = Request::getUserVar('userIds');
				if (is_array($userIds) && count($userIds) > 0) {
					$recipientValue = join(',', Request::getUserVar('userIds'));
				} else {
					$recipientValue = '';
				}
			} else if ($recipientType == 'role') {
				$rolePath = Request::getUserVar('role');
				$recipientValue = $roleDao->getRoleIdFromPath($rolePath);
			}
			
			$emailTemplates = &$emailTemplateDao->getEmailTemplates(Locale::getLocale(), $journal->getJournalId());
			
			$templateMgr->assign('recipientType', $recipientType);
			$templateMgr->assign('recipientValue', $recipientValue);
			$templateMgr->assign('emailTemplates', $emailTemplates);
			
			$templateMgr->display('manager/people/emailUsersChooseTemplate.tpl');
			
		} else if (isset($args[0]) && $args[0] == 'display') {
			$emailKey = Request::getUserVar('emailKey');
			$emailLocale = Request::getUserVar('emailLocale');
			if ($emailKey != '') {
				$email = new MailTemplate($emailKey, $emailLocale);
			} else {
				$email = new MailTemplate();
			}
			
			$email->displayEditForm('/');
		} else {
			$users = &$roleDao->getUsersByJournalId($journal->getJournalId());
			
			$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/people/emailUsers');
			$templateMgr->assign('users', $users);
			$templateMgr->assign('roleOptions',
				array(
					'manager' => 'user.role.manager',
					'editor' => 'user.role.editor',
					'sectionEditor' => 'user.role.sectionEditor',
					'layoutEditor' => 'user.role.layoutEditor',
					'reviewer' => 'user.role.reviewer',
					'copyeditor' => 'user.role.copyeditor',
					'proofreader' => 'user.role.proofreader',
					'author' => 'user.role.author',
					'reader' => 'user.role.reader'
				)
			);
			$templateMgr->display('manager/people/emailUsers.tpl');
		}
	}
	
	/**
	 * Sign in as another user.
	 * @param $args array ($userId)
	 */
	function signInAsUser($args) {
		parent::validate();
		
		if (isset($args[0])) {
			$userId = (int)$args[0];
		
			// FIXME Verify that user ID is valid
			$session = &Request::getSession();
			$session->setSessionVar('signedInAs', $session->getUserId());
			$session->setSessionVar('userId', $userId);
			$session->setUserId($userId);
			Request::redirect('user');
			
		} else {
			Request::redirect('manager');
		}
	}
	
	/**
	 * Restore original user account after signing in as a user.
	 */
	function signOutAsUser() {
		Handler::validate();
		
		$session = &Request::getSession();
		$signedInAs = $session->getSessionVar('signedInAs');
		
		if (isset($signedInAs) && !empty($signedInAs)) {
			$session->unsetSessionVar('signedInAs');
			$session->setSessionVar('userId', $signedInAs);
			$session->setUserId($signedInAs);
		}
		
		Request::redirect('manager');
	}
}

?>
