<?php

/**
 * @file PeopleHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PeopleHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for people management functions. 
 */

// $Id$


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
		$search = Request::getUserVar('search');
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
				case ROLE_ID_SUBSCRIPTION_MANAGER:
					$helpTopicId = 'journal.roles.subscriptionManager';
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
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $journal->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($journal->getJournalId()) : null);
		}
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$fieldOptions = Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_INTERESTS => 'user.interests',
			USER_FIELD_EMAIL => 'user.email'
		);
		if ($roleId == ROLE_ID_REVIEWER) $fieldOptions = array_merge(array(USER_FIELD_INTERESTS => 'user.interests'), $fieldOptions);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		$templateMgr->assign('rolePath', $roleDao->getRolePath($roleId));
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
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
		$search = Request::getUserVar('search');
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
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign('roleName', $roleDao->getRoleName($roleId));
		$fieldOptions = Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		);
		if ($roleId == ROLE_ID_REVIEWER) $fieldOptions = array_merge(array(USER_FIELD_INTERESTS => 'user.interests'), $fieldOptions);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
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
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 */
	function suggestUsername() {
		parent::validate();
		$suggestion = Validation::suggestUsername(
			Request::getUserVar('firstName'),
			Request::getUserVar('lastName')
		);
		echo $suggestion;
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

		if ($userId !== null && !Validation::canAdminister($journal->getJournalId(), $userId)) {
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
		if ($userForm->isLocaleResubmit()) {
			$userForm->readInputData();
		} else {
			$userForm->initData();
		}
		$userForm->display();
	}

	/**
	 * Allow the Journal Manager to merge user accounts, including attributed articles etc.
	 */
	function mergeUsers($args) {
		parent::validate();
		parent::setupTemplate(true);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();
		$templateMgr =& TemplateManager::getManager();

		$oldUserId = Request::getUserVar('oldUserId');
		$newUserId = Request::getUserVar('newUserId');

		// Ensure that we have administrative priveleges over the specified user(s).
		if (
			(!empty($oldUserId) && !Validation::canAdminister($journalId, $oldUserId)) ||
			(!empty($newUserId) && !Validation::canAdminister($journalId, $newUserId))
		) {
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		if (!empty($oldUserId) && !empty($newUserId)) {
			// Both user IDs have been selected. Merge the accounts.

			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			foreach ($articleDao->getArticlesByUserId($oldUserId) as $article) {
				$article->setUserId($newUserId);
				$articleDao->updateArticle($article);
				unset($article);
			}

			$commentDao =& DAORegistry::getDAO('CommentDAO');
			foreach ($commentDao->getCommentsByUserId($oldUserId) as $comment) {
				$comment->setUserId($newUserId);
				$commentDao->updateComment($comment);
				unset($comment);
			}

			$articleNoteDao =& DAORegistry::getDAO('ArticleNoteDAO');
			$articleNotes =& $articleNoteDao->getArticleNotesByUserId($oldUserId);
			while ($articleNote =& $articleNotes->next()) {
				$articleNote->setUserId($newUserId);
				$articleNoteDao->updateArticleNote($articleNote);
				unset($articleNote);
			}

			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByUserId($oldUserId);
			while ($editAssignment =& $editAssignments->next()) {
				$editAssignment->setEditorId($newUserId);
				$editAssignmentDao->updateEditAssignment($editAssignment);
				unset($editAssignment);
			}

			$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
			$editorSubmissionDao->transferEditorDecisions($oldUserId, $newUserId);

			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getReviewAssignmentsByUserId($oldUserId) as $reviewAssignment) {
				$reviewAssignment->setReviewerId($newUserId);
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				unset($reviewAssignment);
			}

			$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
			$copyeditorSubmissions =& $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($oldUserId);
			while ($copyeditorSubmission =& $copyeditorSubmissions->next()) {
				$copyeditorSubmission->setCopyeditorId($newUserId);
				$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
				unset($copyeditorSubmission);
			}

			$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
			$layoutEditorSubmissions =& $layoutEditorSubmissionDao->getSubmissions($oldUserId);
			while ($layoutEditorSubmission =& $layoutEditorSubmissions->next()) {
				$layoutAssignment =& $layoutEditorSubmission->getLayoutAssignment();
				$layoutAssignment->setEditorId($newUserId);
				$layoutEditorSubmissionDao->updateSubmission($layoutEditorSubmission);
				unset($layoutAssignment);
				unset($layoutEditorSubmission);
			}

			$proofreaderSubmissionDao =& DAORegistry::getDAO('ProofreaderSubmissionDAO');
			$proofreaderSubmissions =& $proofreaderSubmissionDao->getSubmissions($oldUserId);
			while ($proofreaderSubmission =& $proofreaderSubmissions->next()) {
				$proofAssignment =& $proofreaderSubmission->getProofAssignment();
				$proofAssignment->setProofreaderId($newUserId);
				$proofreaderSubmissionDao->updateSubmission($proofreaderSubmission);
				unset($proofAssignment);
				unset($proofreaderSubmission);
			}

			$articleEmailLogDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
			$articleEmailLogDao->transferArticleLogEntries($oldUserId, $newUserId);
			$articleEventLogDao =& DAORegistry::getDAO('ArticleEventLogDAO');
			$articleEventLogDao->transferArticleLogEntries($oldUserId, $newUserId);

			$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
			foreach ($articleCommentDao->getArticleCommentsByUserId($oldUserId) as $articleComment) {
				$articleComment->setAuthorId($newUserId);
				$articleCommentDao->updateArticleComment($articleComment);
				unset($articleComment);
			}

			$accessKeyDao =& DAORegistry::getDAO('AccessKeyDAO');
			$accessKeyDao->transferAccessKeys($oldUserId, $newUserId);

			// Delete the old user and associated info.
			$sessionDao =& DAORegistry::getDAO('SessionDAO');
			$sessionDao->deleteSessionsByUserId($oldUserId);
			$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');
			$subscriptionDao->deleteSubscriptionsByUserId($oldUserId);
			$temporaryFileDao =& DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFileDao->deleteTemporaryFilesByUserId($oldUserId);
			$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
			$notificationStatusDao->deleteNotificationStatusByUserId($oldUserId);
			$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
			$userSettingsDao->deleteSettings($oldUserId);
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
			$groupMembershipDao->deleteMembershipByUserId($oldUserId);
			$sectionEditorsDao =& DAORegistry::getDAO('SectionEditorsDAO');
			$sectionEditorsDao->deleteEditorsByUserId($oldUserId);

			// Transfer old user's roles
			$roles =& $roleDao->getRolesByUserId($oldUserId);
			foreach ($roles as $role) {
				if (!$roleDao->roleExists($role->getJournalId(), $newUserId, $role->getRoleId())) {
					$role->setUserId($newUserId);
					$roleDao->insertRole($role);
				}
			}
			$roleDao->deleteRoleByUserId($oldUserId);

			$userDao->deleteUserById($oldUserId);

			Request::redirect(null, 'manager');
		}

		if (!empty($oldUserId)) {
			// Get the old username for the confirm prompt.
			$oldUser =& $userDao->getUser($oldUserId);
			$templateMgr->assign('oldUsername', $oldUser->getUsername());
			unset($oldUser);
		}

		// The manager must select one or both IDs.
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

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
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
			$users = &$roleDao->getUsersByRoleId($roleId, $journalId, $searchType, $search, $searchMatch, $rangeInfo);
			$templateMgr->assign('roleId', $roleId);
		} else {
			$users = &$roleDao->getUsersByJournalId($journalId, $searchType, $search, $searchMatch, $rangeInfo);
		}

		$templateMgr->assign('currentUrl', Request::url(null, null, 'people', 'all'));
		$templateMgr->assign('helpTopicId', 'journal.managementPages.mergeUsers');
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
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
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->assign('oldUserId', $oldUserId);
		$templateMgr->assign('rolePath', $roleDao->getRolePath($roleId));
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->display('manager/people/selectMergeUser.tpl');
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
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::url(null, null, 'people', 'all'));
				$templateMgr->assign('userCreated', true);
				$userForm = &new UserManagementForm();
				$userForm->initData();
				$userForm->display();

			} else {
				if ($source = Request::getUserVar('source')) Request::redirectUrl($source);
				else Request::redirect(null, null, 'people', 'all');
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
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->display('manager/people/userProfile.tpl');
		}
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
