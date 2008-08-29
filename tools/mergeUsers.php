<?php

/**
 * @file mergeUsers.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class mergeUsers
 * @ingroup tools
 *
 * @brief CLI tool for merging two OJS 2 user accounts.
 */

// $Id$


define('INDEX_FILE_LOCATION', dirname(dirname(__FILE__)) . '/index.php');
require(dirname(dirname(__FILE__)) . '/lib/pkp/classes/cliTool/CliTool.inc.php');

class mergeUsers extends CommandLineTool {

	/** @var $username1 string */
	var $username1;

	/** @var $username2 string */
	var $username2;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function mergeUsers($argv = array()) {
		parent::CommandLineTool($argv);

		if (!isset($this->argv[0]) || !isset($this->argv[1]) ) {
			$this->usage();
			exit(1);
		}

		$this->username1 = $this->argv[0];
		$this->username2 = $this->argv[1];
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "OJS 2 merge users tool\n"
			. "Use this tool to merge two OJS 2 user accounts.\n\n"
			. "Usage: {$this->scriptName} [username1] [username2]\n"
			. "username1      The first user to merge.\n"
			. "username2      The second user to merge. All roles and content associated\n"
			. "               with this user account will be transferred to the user account\n"
			. "               that corresponds to username1. The user account that corresponds\n"
			. "               to username2 will be deleted.\n";
	}

	/**
	 * Execute the merge users command.
	 */
	function execute() {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$oldUser =& $userDao->getUserbyUsername($this->username2);
		$newUser =& $userDao->getUserbyUsername($this->username1);

		$oldUserId = isset($oldUser) ? $oldUser->getUserId() : null;
		$newUserId = isset($newUser) ? $newUser->getUserId() : null;

		if (empty($oldUserId)) {
			printf("Error: '%s' is not a valid username.\n",
				$this->username2);
			exit;
		}

		if (empty($newUserId)) {
			printf("Error: '%s' is not a valid username.\n",
				$this->username1);
			exit;	
		}

		// Both user IDs are valid. Merge the accounts.
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

		printf("Merge completed: '%s' merged into '%s'.\n",
			$this->username2,
			$this->username1
		);
	}
}

$tool = &new mergeUsers(isset($argv) ? $argv : array());
$tool->execute();
?>
