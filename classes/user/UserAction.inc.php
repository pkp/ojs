<?php

/**
 * @file classes/user/UserAction.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAction
 * @ingroup user
 * @see User
 *
 * @brief UserAction class.
 */

// $Id$


class UserAction {

	/**
	 * Constructor.
	 */
	function UserAction() {
	}

	/**
	 * Actions.
	 */

	/**
	 * Merge user accounts, including attributed articles etc.
	 */
	function mergeUsers($oldUserId, $newUserId) {
		// Need both user ids for merge
		if (empty($oldUserId) || empty($newUserId)) {
			return false;
		}

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

		// Transfer old user's valid subscriptions if new user does not
		// have similar subscriptions of if they're invalid
 		$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');
		$oldUserSubscriptions =& $subscriptionDao->getSubscriptionsByUser($oldUserId);

		while ($oldUserSubscription =& $oldUserSubscriptions->next()) {
			$subscriptionJournalId = $oldUserSubscription->getJournalId();
			$oldUserValidSubscription = $subscriptionDao->isValidSubscriptionByUser($oldUserId, $subscriptionJournalId);

			if ($oldUserValidSubscription) {
				// Check if new user has a valid subscription for journal
				$newUserSubscriptionId = $subscriptionDao->getSubscriptionIdByUser($newUserId, $subscriptionJournalId);
				if (empty($newUserSubscriptionId)) {
					// New user does not have this subscription, transfer old user's
					$oldUserSubscription->setUserId($newUserId);
					$subscriptionDao->updateSubscription($oldUserSubscription);
				} elseif (!$subscriptionDao->isValidSubscriptionByUser($newUserId, $subscriptionJournalId)) {
					// New user has a subscription but it's invalid. Delete it and
					// transfer old user's valid one
					$subscriptionDao->deleteSubscriptionByUserIdForJournal($newUserId, $subscriptionJournalId);
					$oldUserSubscription->setUserId($newUserId);
					$subscriptionDao->updateSubscription($oldUserSubscription);
				}
			}
		}	

		// Delete any remaining oldUser subscriptions not transferred to new user 
		$subscriptionDao->deleteSubscriptionsByUserId($oldUserId);

		// Delete the old user and associated info.
		$sessionDao =& DAORegistry::getDAO('SessionDAO');
		$sessionDao->deleteSessionsByUserId($oldUserId);
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
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$roles =& $roleDao->getRolesByUserId($oldUserId);
		foreach ($roles as $role) {
			if (!$roleDao->roleExists($role->getJournalId(), $newUserId, $role->getRoleId())) {
				$role->setUserId($newUserId);
				$roleDao->insertRole($role);
			}
		}
		$roleDao->deleteRoleByUserId($oldUserId);

		$userDao->deleteUserById($oldUserId);

		return true;
	}

}

?>
