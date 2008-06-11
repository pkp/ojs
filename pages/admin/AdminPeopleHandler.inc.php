<?php

/**
 * @file AdminPeopleHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 * @class AdminPeopleHandler
 *
 * Handle requests for people management functions. 
 *
 * $Id$
 */

class AdminPeopleHandler extends AdminHandler {

	/**
	 * Allow the Site Administrator to merge user accounts, including attributed articles etc.
	 */
	function mergeUsers($args) {
		parent::validate();
		parent::setupTemplate(true);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$templateMgr =& TemplateManager::getManager();

		$oldUserId = Request::getUserVar('oldUserId');
		$newUserId = Request::getUserVar('newUserId');

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

			Request::redirect(null, 'admin', 'mergeUsers');
		}

		if (!empty($oldUserId)) {
			// Get the old username for the confirm prompt.
			$oldUser =& $userDao->getUser($oldUserId);
			$templateMgr->assign('oldUsername', $oldUser->getUsername());
			unset($oldUser);
		}

		// The administrator must select one or both IDs.
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
			$roleName = 'admin.mergeUsers.allUsers';
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
			$users = &$roleDao->getUsersByRoleId($roleId, null, $searchType, $search, $searchMatch, $rangeInfo);
			$templateMgr->assign('roleId', $roleId);
		} else {
			$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);
		}

		$templateMgr->assign('currentUrl', Request::url(null, null, 'mergeUsers'));
		$templateMgr->assign('helpTopicId', 'site.administrativeFunctions');
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
		$templateMgr->display('admin/selectMergeUser.tpl');
	}

}

?>
