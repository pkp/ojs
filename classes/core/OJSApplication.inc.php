<?php

/**
 * @file classes/core/OJSApplication.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
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

class OJSApplication extends PKPApplication {
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
	 * Determine whether or not the request is cacheable.
	 * @return boolean
	 */
	function isCacheable() {
		if (defined('SESSION_DISABLE_INIT')) return false;
		if (!Config::getVar('general', 'installed')) return false;
		if (!empty($_POST) || Validation::isLoggedIn()) return false;
		if (!Config::getVar('cache', 'web_cache')) return false;
		if (!Request::isPathInfoEnabled()) {
			$ok = array('journal', 'page', 'op', 'path');
			if (!empty($_GET) && count(array_diff(array_keys($_GET), $ok)) != 0) {
				return false;
			}
		} else {
			if (!empty($_GET)) return false;
		}

		if (in_array(Request::getRequestedPage(), array(
			'about', 'announcement', 'help', 'index', 'information', 'rt', 'issue', ''
		))) return true;

		return false;
	}

	/**
	 * Get the filename to use for cached content for the current request.
	 * @return string
	 */
	function getCacheFilename() {
		static $cacheFilename;
		if (!isset($cacheFilename)) {
			if (Request::isPathInfoEnabled()) {
				$id = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'index';
				$id .= '-' . Locale::getLocale();
			} else {
				$id = Request::getUserVar('journal') . '-' . Request::getUserVar('page') . '-' . Request::getUserVar('op') . '-' . Request::getUserVar('path') . '-' . Locale::getLocale();
			}
			$path = dirname(dirname(dirname(__FILE__)));
			$cacheFilename = $path . '/cache/wc-' . md5($id) . '.html';
		}
		return $cacheFilename;
	}

	/**
	 * Get the map of DAOName => full.class.Path for this application.
	 * @return array
	 */
	function getDAOMap() {
		return array(
			'ArticleEmailLogDAO' => 'article.log.ArticleEmailLogDAO',
			'ArticleEventLogDAO' => 'article.log.ArticleEventLogDAO',
			'ArticleCommentDAO' => 'article.ArticleCommentDAO',
			'ArticleDAO' => 'article.ArticleDAO',
			'ArticleFileDAO' => 'article.ArticleFileDAO',
			'ArticleGalleyDAO' => 'article.ArticleGalleyDAO',
			'ArticleNoteDAO' => 'article.ArticleNoteDAO',
			'AuthorDAO' => 'article.AuthorDAO',
			'CaptchaDAO' => 'captcha.CaptchaDAO',
			'PublishedArticleDAO' => 'article.PublishedArticleDAO',
			'SuppFileDAO' => 'article.SuppFileDAO',
			'DAO' => 'db.DAO',
			'XMLDAO' => 'db.XMLDAO',
			'HelpTocDAO' => 'help.HelpTocDAO',
			'HelpTopicDAO' => 'help.HelpTopicDAO',
			'IssueDAO' => 'issue.IssueDAO',
			'JournalDAO' => 'journal.JournalDAO',
			'CountryDAO' => 'i18n.CountryDAO',
			'JournalStatisticsDAO' => 'journal.JournalStatisticsDAO',
			'JournalSettingsDAO' => 'journal.JournalSettingsDAO',
			'SectionDAO' => 'journal.SectionDAO',
			'SectionEditorsDAO' => 'journal.SectionEditorsDAO',
			'NotificationStatusDAO' => 'journal.NotificationStatusDAO',
			'EmailTemplateDAO' => 'mail.EmailTemplateDAO',
			'OAIDAO' => 'oai.ojs.OAIDAO',
			'ScheduledTaskDAO' => 'scheduledTask.ScheduledTaskDAO',
			'ArticleSearchDAO' => 'search.ArticleSearchDAO',
			'RoleDAO' => 'security.RoleDAO',
			'SessionDAO' => 'session.SessionDAO',
			'SiteDAO' => 'site.SiteDAO',
			'SiteSettingsDAO' => 'site.SiteSettingsDAO',
			'VersionDAO' => 'site.VersionDAO',
			'AuthorSubmissionDAO' => 'submission.author.AuthorSubmissionDAO',
			'CopyAssignmentDAO' => 'submission.copyAssignment.CopyAssignmentDAO',
			'CopyeditorSubmissionDAO' => 'submission.copyeditor.CopyeditorSubmissionDAO',
			'EditAssignmentDAO' => 'submission.editAssignment.EditAssignmentDAO',
			'EditorSubmissionDAO' => 'submission.editor.EditorSubmissionDAO',
			'LayoutAssignmentDAO' => 'submission.layoutAssignment.LayoutAssignmentDAO',
			'LayoutEditorSubmissionDAO' => 'submission.layoutEditor.LayoutEditorSubmissionDAO',
			'ProofAssignmentDAO' => 'submission.proofAssignment.ProofAssignmentDAO',
			'ProofreaderSubmissionDAO' => 'submission.proofreader.ProofreaderSubmissionDAO',
			'ReviewAssignmentDAO' => 'submission.reviewAssignment.ReviewAssignmentDAO',
			'ReviewerSubmissionDAO' => 'submission.reviewer.ReviewerSubmissionDAO',
			'SectionEditorSubmissionDAO' => 'submission.sectionEditor.SectionEditorSubmissionDAO',
			'UserDAO' => 'user.UserDAO',
			'UserSettingsDAO' => 'user.UserSettingsDAO',
			'RTDAO' => 'rt.ojs.RTDAO',
			'CurrencyDAO' => 'currency.CurrencyDAO',
			'SubscriptionDAO' => 'subscription.SubscriptionDAO',
			'SubscriptionTypeDAO' => 'subscription.SubscriptionTypeDAO',
			'AnnouncementDAO' => 'announcement.AnnouncementDAO',
			'AnnouncementTypeDAO' => 'announcement.AnnouncementTypeDAO',
			'TemporaryFileDAO' => 'file.TemporaryFileDAO',
			'CommentDAO' => 'comment.CommentDAO',
			'AuthSourceDAO' => 'security.AuthSourceDAO',
			'AccessKeyDAO' => 'security.AccessKeyDAO',
			'PluginSettingsDAO' => 'plugins.PluginSettingsDAO',
			'GroupDAO' => 'group.GroupDAO',
			'GroupMembershipDAO' => 'group.GroupMembershipDAO',
			'QueuedPaymentDAO' => 'payment.QueuedPaymentDAO',
			'OJSCompletedPaymentDAO' => 'payment.ojs.OJSCompletedPaymentDAO',
			'ReviewFormDAO' => 'reviewForm.ReviewFormDAO',
			'ReviewFormElementDAO' => 'reviewForm.ReviewFormElementDAO',
			'ReviewFormResponseDAO' => 'reviewForm.ReviewFormResponseDAO'
		);
	}
}

?>
