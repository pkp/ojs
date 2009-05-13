<?php

/**
 * @file classes/user/UserAction.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
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

		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$copyeditorSubmissions =& $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($oldUserId);
		while ($copyeditorSubmission =& $copyeditorSubmissions->next()) {
			$initialCopyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
			$finalCopyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
			$initialCopyeditSignoff->setUserId($newUserId);
			$finalCopyeditSignoff->setUserId($newUserId);
			$signoffDao->updateObject($initialCopyeditSignoff);			
			$signoffDao->updateObject($finalCopyeditSignoff);
			unset($copyeditorSubmission);
			unset($initialCopyeditSignoff);
			unset($finalCopyeditSignoff);
		}

		$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$layoutEditorSubmissions =& $layoutEditorSubmissionDao->getSubmissions($oldUserId);
		while ($layoutEditorSubmission =& $layoutEditorSubmissions->next()) {
			$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $layoutEditorSubmission->getArticleId());
			$layoutProofreadSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $layoutEditorSubmission->getArticleId());
			$layoutSignoff->setUserId($newUserId);
			$layoutProofreadSignoff->setUserId($newUserId);
			$signoffDao->updateObject($layoutSignoff);
			$signoffDao->updateObject($layoutProofreadSignoff);
			unset($layoutSignoff);
			unset($layoutProofreadSignoff);
			unset($layoutEditorSubmission);
		}

		$proofreaderSubmissionDao =& DAORegistry::getDAO('ProofreaderSubmissionDAO');
		$proofreaderSubmissions =& $proofreaderSubmissionDao->getSubmissions($oldUserId);
		while ($proofreaderSubmission =& $proofreaderSubmissions->next()) {
			$proofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $proofreaderSubmission->getArticleId());
			$proofSignoff->setUserId($newUserId);
			$signoffDao->updateObject($proofSignoff);
			unset($proofSignoff);
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
