<?php

/**
 * @file classes/core/OJSApplication.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSApplication
 * @ingroup core
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */

// $Id$


import('core.PKPApplication');

define('ASSOC_TYPE_JOURNAL',	0x0000100);
define('ASSOC_TYPE_ARTICLE',	0x0000101);

define('CONTEXT_JOURNAL', 1);

class OJSApplication extends PKPApplication {
	function OJSApplication() {
		parent::PKPApplication();
	}

	function initialize(&$application) {
		PKPApplication::initialize($application);
		import('i18n.Locale');
	}

	/**
	 * Get the dispatcher implementation singleton
	 * @return Dispatcher
	 */
	function &getDispatcher() {
		$dispatcher =& parent::getDispatcher();

		// Inject application-specific configuration
		$dispatcher->addRouterName('core.OJSPageRouter', ROUTE_PAGE);
		return $dispatcher;
	}

	/**
	 * Get the "context depth" of this application, i.e. the number of
	 * parts of the URL after index.php that represent the context of
	 * the current request (e.g. Journal [1], or Conference and
	 * Scheduled Conference [2]).
	 * @return int
	 */
	function getContextDepth() {
		return 1;
	}

	function getContextList() {
		return array('journal');
	}
	/**
	 * Get the symbolic name of this application
	 * @return string
	 */
	function getName() {
		return 'ojs2';
	}

	/**
	 * Get the locale key for the name of this application.
	 * @return string
	 */
	function getNameKey() {
		return('common.openJournalSystems');
	}

	/**
	 * Get the URL to the XML descriptor for the current version of this
	 * application.
	 * @return string
	 */
	function getVersionDescriptorUrl() {
		return('http://pkp.sfu.ca/ojs/xml/ojs-version.xml');
	}

	/**
	 * Get the map of DAOName => full.class.Path for this application.
	 * @return array
	 */
	function getDAOMap() {
		return array_merge(parent::getDAOMap(), array(
			'AnnouncementDAO' => 'announcement.AnnouncementDAO',
			'AnnouncementTypeDAO' => 'announcement.AnnouncementTypeDAO',
			'ArticleEmailLogDAO' => 'article.log.ArticleEmailLogDAO',
			'ArticleEventLogDAO' => 'article.log.ArticleEventLogDAO',
			'ArticleCommentDAO' => 'article.ArticleCommentDAO',
			'ArticleDAO' => 'article.ArticleDAO',
			'ArticleFileDAO' => 'article.ArticleFileDAO',
			'ArticleGalleyDAO' => 'article.ArticleGalleyDAO',
			'ArticleNoteDAO' => 'article.ArticleNoteDAO',
			'ArticleSearchDAO' => 'search.ArticleSearchDAO',
			'AuthorDAO' => 'article.AuthorDAO',
			'AuthorSubmissionDAO' => 'submission.author.AuthorSubmissionDAO',
			'CommentDAO' => 'comment.CommentDAO',
			'CopyeditorSubmissionDAO' => 'submission.copyeditor.CopyeditorSubmissionDAO',
			'EditAssignmentDAO' => 'submission.editAssignment.EditAssignmentDAO',
			'EditorSubmissionDAO' => 'submission.editor.EditorSubmissionDAO',
			'EmailTemplateDAO' => 'mail.EmailTemplateDAO',
			'GroupDAO' => 'group.GroupDAO',
			'GroupMembershipDAO' => 'group.GroupMembershipDAO',
			'IssueDAO' => 'issue.IssueDAO',
			'JournalDAO' => 'journal.JournalDAO',
			'JournalSettingsDAO' => 'journal.JournalSettingsDAO',
			'JournalStatisticsDAO' => 'journal.JournalStatisticsDAO',
			'LayoutEditorSubmissionDAO' => 'submission.layoutEditor.LayoutEditorSubmissionDAO',
			'OAIDAO' => 'oai.ojs.OAIDAO',
			'OJSCompletedPaymentDAO' => 'payment.ojs.OJSCompletedPaymentDAO',
			'PluginSettingsDAO' => 'plugins.PluginSettingsDAO',
			'ProofreaderSubmissionDAO' => 'submission.proofreader.ProofreaderSubmissionDAO',
			'PublishedArticleDAO' => 'article.PublishedArticleDAO',
			'QueuedPaymentDAO' => 'payment.QueuedPaymentDAO',
			'ReviewAssignmentDAO' => 'submission.reviewAssignment.ReviewAssignmentDAO',
			'ReviewerSubmissionDAO' => 'submission.reviewer.ReviewerSubmissionDAO',
			'ReviewFormDAO' => 'reviewForm.ReviewFormDAO',
			'ReviewFormElementDAO' => 'reviewForm.ReviewFormElementDAO',
			'ReviewFormResponseDAO' => 'reviewForm.ReviewFormResponseDAO',
			'RoleDAO' => 'security.RoleDAO',
			'RTDAO' => 'rt.ojs.RTDAO',
			'SectionDAO' => 'journal.SectionDAO',
			'SectionEditorsDAO' => 'journal.SectionEditorsDAO',
			'SuppFileDAO' => 'article.SuppFileDAO',
			'ScheduledTaskDAO' => 'scheduledTask.ScheduledTaskDAO',
			'SectionEditorSubmissionDAO' => 'submission.sectionEditor.SectionEditorSubmissionDAO',
			'SubscriptionDAO' => 'subscription.SubscriptionDAO',
			'IndividualSubscriptionDAO' => 'subscription.IndividualSubscriptionDAO',
			'InstitutionalSubscriptionDAO' => 'subscription.InstitutionalSubscriptionDAO',
			'SubscriptionTypeDAO' => 'subscription.SubscriptionTypeDAO',
			'UserDAO' => 'user.UserDAO',
			'UserSettingsDAO' => 'user.UserSettingsDAO'
		));
	}

	/**
	 * Get the list of plugin categories for this application.
	 */
	function getPluginCategories() {
		return array(
			'auth',
			'blocks',
			'citationFormats',
			'gateways',
			'generic',
			'implicitAuth',
			'importexport',
			'paymethod',
			'reports',
			'themes'
		);
	}

	/**
	 * Instantiate the help object for this application.
	 * @return object
	 */
	function &instantiateHelp() {
		import('help.Help');
		$help = new Help();
		return $help;
	}
}

?>
