<?php

/**
 * @file classes/core/Application.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Application
 * @ingroup core
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */


import('lib.pkp.classes.core.PKPApplication');

define('PHP_REQUIRED_VERSION', '4.2.0');

define('ASSOC_TYPE_JOURNAL', 0x0000100);
define('ASSOC_TYPE_ARTICLE', 0x0000101);
define('ASSOC_TYPE_ANNOUNCEMENT', 0x0000102);
define('ASSOC_TYPE_SECTION',		0x0000103);
define('ASSOC_TYPE_ISSUE', 0x0000103);
define('ASSOC_TYPE_GALLEY', 0x0000104);
define('ASSOC_TYPE_ISSUE_GALLEY', 0x0000105);
define('ASSOC_TYPE_SUPP_FILE', 0x0000106);

define('CONTEXT_JOURNAL', 1);

class Application extends PKPApplication {
	function Application() {
		parent::PKPApplication();
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
			'AnnouncementDAO' => 'classes.announcement.AnnouncementDAO',
			'AnnouncementTypeDAO' => 'classes.announcement.AnnouncementTypeDAO',
			'ArticleEmailLogDAO' => 'classes.article.log.ArticleEmailLogDAO',
			'ArticleEventLogDAO' => 'classes.article.log.ArticleEventLogDAO',
			'ArticleCommentDAO' => 'classes.article.ArticleCommentDAO',
			'ArticleDAO' => 'classes.article.ArticleDAO',
			'ArticleFileDAO' => 'classes.article.ArticleFileDAO',
			'ArticleGalleyDAO' => 'classes.article.ArticleGalleyDAO',
			'ArticleNoteDAO' => 'classes.article.ArticleNoteDAO', // DEPRECATED
			'ArticleSearchDAO' => 'classes.search.ArticleSearchDAO',
			'AuthorDAO' => 'classes.article.AuthorDAO',
			'AuthorSubmissionDAO' => 'classes.submission.author.AuthorSubmissionDAO',
			'CategoryDAO' => 'classes.journal.categories.CategoryDAO',
			'CommentDAO' => 'lib.pkp.classes.comment.CommentDAO',
			'CopyeditorSubmissionDAO' => 'classes.submission.copyeditor.CopyeditorSubmissionDAO',
			'EditAssignmentDAO' => 'classes.submission.editAssignment.EditAssignmentDAO',
			'EditorSubmissionDAO' => 'classes.submission.editor.EditorSubmissionDAO',
			'EmailTemplateDAO' => 'classes.mail.EmailTemplateDAO',
			'GiftDAO' => 'classes.gift.GiftDAO',
			'GroupDAO' => 'lib.pkp.classes.group.GroupDAO',
			'GroupMembershipDAO' => 'lib.pkp.classes.group.GroupMembershipDAO',
			'IndividualSubscriptionDAO' => 'classes.subscription.IndividualSubscriptionDAO',
			'InstitutionalSubscriptionDAO' => 'classes.subscription.InstitutionalSubscriptionDAO',
			'IssueDAO' => 'classes.issue.IssueDAO',
			'IssueGalleyDAO' => 'classes.issue.IssueGalleyDAO',
			'IssueFileDAO' => 'classes.issue.IssueFileDAO',
			'JournalDAO' => 'classes.journal.JournalDAO',
			'JournalSettingsDAO' => 'classes.journal.JournalSettingsDAO',
			'JournalStatisticsDAO' => 'classes.journal.JournalStatisticsDAO',
			'LayoutEditorSubmissionDAO' => 'classes.submission.layoutEditor.LayoutEditorSubmissionDAO',
			'NoteDAO' => 'classes.note.NoteDAO',
			'OAIDAO' => 'classes.oai.ojs.OAIDAO',
			'OJSCompletedPaymentDAO' => 'classes.payment.ojs.OJSCompletedPaymentDAO',
			'PluginSettingsDAO' => 'classes.plugins.PluginSettingsDAO',
			'ProofreaderSubmissionDAO' => 'classes.submission.proofreader.ProofreaderSubmissionDAO',
			'PublishedArticleDAO' => 'classes.article.PublishedArticleDAO',
			'QueuedPaymentDAO' => 'lib.pkp.classes.payment.QueuedPaymentDAO',
			'ReviewAssignmentDAO' => 'classes.submission.reviewAssignment.ReviewAssignmentDAO',
			'ReviewerSubmissionDAO' => 'classes.submission.reviewer.ReviewerSubmissionDAO',
			'ReviewFormDAO' => 'lib.pkp.classes.reviewForm.ReviewFormDAO',
			'ReviewFormElementDAO' => 'lib.pkp.classes.reviewForm.ReviewFormElementDAO',
			'ReviewFormResponseDAO' => 'lib.pkp.classes.reviewForm.ReviewFormResponseDAO',
			'RoleDAO' => 'classes.security.RoleDAO',
			'RTDAO' => 'classes.rt.ojs.RTDAO',
			'ScheduledTaskDAO' => 'lib.pkp.classes.scheduledTask.ScheduledTaskDAO',
			'SectionDAO' => 'classes.journal.SectionDAO',
			'SectionEditorsDAO' => 'classes.journal.SectionEditorsDAO',
			'SectionEditorSubmissionDAO' => 'classes.submission.sectionEditor.SectionEditorSubmissionDAO',
			'SignoffDAO' => 'classes.signoff.SignoffDAO',
			'SubscriptionDAO' => 'classes.subscription.SubscriptionDAO',
			'SubscriptionTypeDAO' => 'classes.subscription.SubscriptionTypeDAO',
			'SuppFileDAO' => 'classes.article.SuppFileDAO',
			'UserDAO' => 'classes.user.UserDAO',
			'UserSettingsDAO' => 'classes.user.UserSettingsDAO'
		));
	}

	/**
	 * Get the list of plugin categories for this application.
	 */
	function getPluginCategories() {
		return array(
			// NB: Meta-data plug-ins are first in the list as this
			// will make them being loaded (and installed) first.
			// This is necessary as several other plug-in categories
			// depend on meta-data. This is a very rudimentary type of
			// dependency management for plug-ins.
			'metadata',
			'auth',
			'blocks',
			// NB: 'citationFormats' is an obsolete category for backwards
			// compatibility only. This will be replaced by 'citationOutput',
			// see #5156.
			'citationFormats',
			'citationLookup',
			'citationOutput',
			'citationParser',
			'gateways',
			'generic',
			'implicitAuth',
			'importexport',
			'oaiMetadataFormats',
			'paymethod',
			'pubIds',
			'reports',
			'themes'
		);
	}

	/**
	 * Instantiate the help object for this application.
	 * @return object
	 */
	function &instantiateHelp() {
		import('classes.help.Help');
		$help = new Help();
		return $help;
	}
}

?>
