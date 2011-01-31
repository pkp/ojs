<?php

/**
 * @file classes/user/UserAction.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
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
		foreach ($commentDao->getByUserId($oldUserId) as $comment) {
			$comment->setUserId($newUserId);
			$commentDao->updateComment($comment);
			unset($comment);
		}

		$noteDao =& DAORegistry::getDAO('NoteDAO');
		$notes =& $noteDao->getByUserId($oldUserId);
		while ($note =& $notes->next()) {
			$note->setUserId($newUserId);
			$noteDao->updateObject($note);
			unset($note);
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
		foreach ($reviewAssignmentDao->getByUserId($oldUserId) as $reviewAssignment) {
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

		// Transfer old user's individual subscriptions for each journal if new user
		// does not have a valid individual subscription for a given journal.
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$oldUserSubscriptions =& $individualSubscriptionDao->getSubscriptionsByUser($oldUserId);

		while ($oldUserSubscription =& $oldUserSubscriptions->next()) {
			$subscriptionJournalId = $oldUserSubscription->getJournalId();
			$oldUserValidSubscription = $individualSubscriptionDao->isValidIndividualSubscription($oldUserId, $subscriptionJournalId);
			if ($oldUserValidSubscription) {
				// Check if new user has a valid subscription for current journal
				$newUserSubscriptionId = $individualSubscriptionDao->getSubscriptionIdByUser($newUserId, $subscriptionJournalId);
				if (empty($newUserSubscriptionId)) {
					// New user does not have this subscription, transfer old user's
					$oldUserSubscription->setUserId($newUserId);
					$individualSubscriptionDao->updateSubscription($oldUserSubscription);
				} elseif (!$individualSubscriptionDao->isValidIndividualSubscription($newUserId, $subscriptionJournalId)) {
					// New user has a subscription but it's invalid. Delete it and
					// transfer old user's valid one
					$individualSubscriptionDao->deleteSubscriptionsByUserIdForJournal($newUserId, $subscriptionJournalId);
					$oldUserSubscription->setUserId($newUserId);
					$individualSubscriptionDao->updateSubscription($oldUserSubscription);
				}
			}
		}

		// Delete any remaining old user's subscriptions not transferred to new user
		$individualSubscriptionDao->deleteSubscriptionsByUserId($oldUserId);

		// Transfer all old user's institutional subscriptions for each journal to
		// new user. New user now becomes the contact person for these.
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$oldUserSubscriptions =& $institutionalSubscriptionDao->getSubscriptionsByUser($oldUserId);

		while ($oldUserSubscription =& $oldUserSubscriptions->next()) {
			$oldUserSubscription->setUserId($newUserId);
			$institutionalSubscriptionDao->updateSubscription($oldUserSubscription);
		}

		// Delete the old user and associated info.
		$sessionDao =& DAORegistry::getDAO('SessionDAO');
		$sessionDao->deleteSessionsByUserId($oldUserId);
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
