<?php

/**
 * DAORegistry.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Class for retrieving DAO objects.
 * Maintains a static list of DAO objects so each DAO is instantiated only once. 
 *
 * $Id$
 */

class DAORegistry {

	/**
	 * Retrieve a reference to the specified DAO.
	 * @param $name string the class name of the requested DAO
	 * @param $dbconn ADONewConnection optional
	 * @return DAO
	 */
	function &getDAO($name, $dbconn = null) {
		static $daos;
		
		if (!isset($daos)) {
			$daos = array();
		}
		
		if (!isset($daos[$name])) {
			// Import the required DAO class.
			import(DAORegistry::getQualifiedDAOName($name));

			// Only instantiate each class of DAO a single time
			$daos[$name] = &new $name();
			if ($dbconn != null) {
				// FIXME Needed by installer but shouldn't access member variable directly
				$daos[$name]->_dataSource = $dbconn;
			}
		}
		
		return $daos[$name];
	}

	/**
	 * Return the fully-qualified (e.g. page.name.ClassNameDAO) name of the
	 * given DAO.
	 * @param $name string
	 * @return string
	 */
	function getQualifiedDAOName($name) {
		// FIXME This function should probably be removed 
		switch ($name) {
			case 'ArticleEmailLogDAO': return 'article.log.ArticleEmailLogDAO';
			case 'ArticleEventLogDAO': return 'article.log.ArticleEventLogDAO';
			case 'ArticleCommentDAO': return 'article.ArticleCommentDAO';
			case 'ArticleDAO': return 'article.ArticleDAO';
			case 'ArticleFileDAO': return 'article.ArticleFileDAO';
			case 'ArticleGalleyDAO': return 'article.ArticleGalleyDAO';
			case 'ArticleNoteDAO': return 'article.ArticleNoteDAO';
			case 'AuthorDAO': return 'article.AuthorDAO';
			case 'PublishedArticleDAO': return 'article.PublishedArticleDAO';
			case 'SuppFileDAO': return 'article.SuppFileDAO';
			case 'DAO': return 'db.DAO';
			case 'XMLDAO': return 'db.XMLDAO';
			case 'HelpTocDAO': return 'help.HelpTocDAO';
			case 'HelpTopicDAO': return 'help.HelpTopicDAO';
			case 'IssueDAO': return 'issue.IssueDAO';
			case 'JournalDAO': return 'journal.JournalDAO';
			case 'JournalSettingsDAO': return 'journal.JournalSettingsDAO';
			case 'SectionDAO': return 'journal.SectionDAO';
			case 'SectionEditorsDAO': return 'journal.SectionEditorsDAO';
			case 'NotificationStatusDAO': return 'journal.NotificationStatusDAO';
			case 'EmailTemplateDAO': return 'mail.EmailTemplateDAO';
			case 'OAIDAO': return 'oai.ojs.OAIDAO';
			case 'ScheduledTaskDAO': return 'scheduledTask.ScheduledTaskDAO';
			case 'ArticleSearchDAO': return 'search.ArticleSearchDAO';
			case 'RoleDAO': return 'security.RoleDAO';
			case 'SessionDAO': return 'session.SessionDAO';
			case 'SiteDAO': return 'site.SiteDAO';
			case 'VersionDAO': return 'site.VersionDAO';
			case 'AuthorSubmissionDAO': return 'submission.author.AuthorSubmissionDAO';
			case 'CopyAssignmentDAO': return 'submission.copyAssignment.CopyAssignmentDAO';
			case 'CopyeditorSubmissionDAO': return 'submission.copyeditor.CopyeditorSubmissionDAO';
			case 'EditAssignmentDAO': return 'submission.editAssignment.EditAssignmentDAO';
			case 'EditorSubmissionDAO': return 'submission.editor.EditorSubmissionDAO';
			case 'LayoutAssignmentDAO': return 'submission.layoutAssignment.LayoutAssignmentDAO';
			case 'LayoutEditorSubmissionDAO': return 'submission.layoutEditor.LayoutEditorSubmissionDAO';
			case 'ProofAssignmentDAO': return 'submission.proofAssignment.ProofAssignmentDAO';
			case 'ProofreaderSubmissionDAO': return 'submission.proofreader.ProofreaderSubmissionDAO';
			case 'ReviewAssignmentDAO': return 'submission.reviewAssignment.ReviewAssignmentDAO';
			case 'ReviewerSubmissionDAO': return 'submission.reviewer.ReviewerSubmissionDAO';
			case 'SectionEditorSubmissionDAO': return 'submission.sectionEditor.SectionEditorSubmissionDAO';
			case 'UserDAO': return 'user.UserDAO';
			case 'RTDAO': return 'rt.ojs.RTDAO';
			case 'CurrencyDAO': return 'subscription.CurrencyDAO';
			case 'SubscriptionDAO': return 'subscription.SubscriptionDAO';
			case 'SubscriptionTypeDAO': return 'subscription.SubscriptionTypeDAO';
			case 'TemporaryFileDAO': return 'file.TemporaryFileDAO';
			case 'CommentDAO': return 'comment.CommentDAO';
			default: fatalError('Unrecognized DAO ' . $name);
		}
		return null;
	}
}

?>
