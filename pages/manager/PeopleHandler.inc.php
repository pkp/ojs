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

		if (Request::getUserVar('roleSymbolic')!=null) $roleSymbolic = Request::getUserVar('roleSymbolic');
		else $roleSymbolic = isset($args[0])?$args[0]:'all';

		if ($roleSymbolic != 'all' && String::regexp_match_get('/^(\w+)s$/', $roleSymbolic, $matches)) {
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
		
		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$search_initial = Request::getUserVar('search_initial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
		}
		else if (isset($search_initial)) {
			$searchType = USER_FIELD_INITIAL;
			$search = $search_initial;
		}

		if ($roleId) {
			$users = &$roleDao->getUsersByRoleId($roleId, $journal->getJournalId(), $searchType, $search, $searchMatch, true);
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
			$users = &$roleDao->getUsersByJournalId($journal->getJournalId(), $searchType, $search, $searchMatch, true);
			$helpTopicId = 'journal.users.allUsers';
		}
		
		$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/people/all');
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign('users', $users);
		$templateMgr->assign('thisUser', Request::getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

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
			USER_FIELD_INTERESTS => 'user.interests'
		));
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

		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, true);

		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username'
		));
		$templateMgr->assign('users', $users);
		$templateMgr->assign('thisUser', Request::getUser());
		$templateMgr->assign('helpTopicId', 'journal.users.index');
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
	 * Disable a user's account.
	 * @param $args array the ID of the user to disable
	 */
	function disableUser($args) {
		parent::validate();
		parent::setupTemplate(true);

		$userId = isset($args[0])?$args[0]:null;
		$user = &Request::getUser();

		if ($userId != null && $userId != $user->getUserId()) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUser($userId);
			if ($user) {
				$user->setDisabled(true);
			}
			$userDao->updateUser(&$user);
		}

		Request::redirect('manager/people/all');
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
				$user->setDisabled(false);
			}
			$userDao->updateUser(&$user);
		}

		Request::redirect('manager/people/all');
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

		Request::redirect('manager/people/all');
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
		$templateMgr->assign('helpTopicId', 'journal.users.importUsers');
		
		if (isset($args[0]) && $args[0] == 'confirm') {
			$sendNotify = (bool) Request::getUserVar('sendNotify');
			$continueOnError = (bool) Request::getUserVar('continueOnError');

			import('file.FileManager');
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
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			Request::redirect(Request::getUserVar('redirectUrl'));
		} else {
			if (!Request::getUserVar('continued')) {
				if (count($email->getRecipients())==0) $email->addRecipient($user->getEmail(), $user->getFullName());
			}
			$email->displayEditForm(Request::getPageUrl() . '/manager/people', array(), 'manager/people/email.tpl');
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

		$templateMgr->assign('emailTemplates', $emailTemplates);
		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('locales', $journal->getSetting('supportedLocales'));
		$templateMgr->assign('localeNames', Locale::getAllLocales());
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
