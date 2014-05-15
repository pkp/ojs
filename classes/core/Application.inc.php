<?php

/**
 * @file classes/core/Application.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
import('classes.statistics.StatisticsHelper');

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
			'MetricsDAO' => 'classes.statistics.MetricsDAO',
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


	//
	// Statistics API
	//
	/**
	 * Return all metric types supported by this application.
	 *
	 * @return array An array of strings of supported metric type identifiers.
	 */
	function getMetricTypes($withDisplayNames = false) {
		// Retrieve site-level report plugins.
		$reportPlugins =& PluginRegistry::loadCategory('reports', true, CONTEXT_SITE);
		if (!is_array($reportPlugins)) return array();

		// Run through all report plugins and retrieve all supported metrics.
		$metricTypes = array();
		foreach ($reportPlugins as $reportPlugin) { /* @var $reportPlugin ReportPlugin */
			$pluginMetricTypes = $reportPlugin->getMetricTypes();
			if ($withDisplayNames) {
				foreach ($pluginMetricTypes as $metricType) {
					$metricTypes[$metricType] = $reportPlugin->getMetricDisplayType($metricType);
				}
			} else {
				$metricTypes = array_merge($metricTypes, $pluginMetricTypes);
			}
		}

		return $metricTypes;
	}

	/**
	 * Returns the currently configured default metric type for this site.
	 * If no specific metric type has been set for this site then null will
	 * be returned.
	 *
	 * @return null|string A metric type identifier or null if no default metric
	 *   type could be identified.
	 */
	function getDefaultMetricType() {
		$request =& $this->getRequest();
		$site =& $request->getSite();
		if (!is_a($site, 'Site')) return null;
		$defaultMetricType = $site->getSetting('defaultMetricType');

		// Check whether the selected metric type is valid.
		$availableMetrics = $this->getMetricTypes();
		if (empty($defaultMetricType)) {
			// If there is only a single available metric then use it.
			if (count($availableMetrics) === 1) {
				$defaultMetricType = $availableMetrics[0];
			} else {
				return null;
			}
		} else {
			if (!in_array($defaultMetricType, $availableMetrics)) return null;
		}
		return $defaultMetricType;
	}

	/**
	 * Main entry point for OJS statistics reports.
	 *
	 * @see <http://pkp.sfu.ca/wiki/index.php/OJSdeStatisticsConcept#Input_and_Output_Formats_.28Aggregation.2C_Filters.2C_Metrics_Data.29>
	 * for a full specification of the input and output format of this method.
	 *
	 * @param $metricType null|string|array metrics selection
	 *   NB: If you want to use the default metric on journal level then you must
	 *   set $metricType = null and add an explicit filter on a single journal ID.
	 *   Otherwise the default site-level metric will be used.
	 * @param $columns string|array column (aggregation level) selection
	 * @param $filters array report-level filter selection
	 * @param $orderBy array order criteria
	 * @param $range null|DBResultRange paging specification
	 *
	 * @return null|array The selected data as a simple tabular result set or
	 *   null if the given parameter combination is not supported.
	 */
	function getMetrics($metricType = null, $columns = array(), $filter = array(), $orderBy = array(), $range = null) {
		// Check the parameter format.
		if (!(is_array($filter) && is_array($orderBy))) return null;

		// Check whether we are in a journal or site context.
		$journal =& StatisticsHelper::getContext($filter);

		// Identify and canonicalize filtered metric types.
		$defaultSiteMetricType = $this->getDefaultMetricType();
		$siteMetricTypes = $this->getMetricTypes();
		$metricType = StatisticsHelper::canonicalizeMetricTypes($metricType, $journal, $defaultSiteMetricType, $siteMetricTypes);
		if (!is_array($metricType)) return null;
		$metricTypeCount = count($metricType);

		// Canonicalize columns.
		if (is_scalar($columns)) $columns = array($columns);

		// The metric type dimension is not additive. This imposes two important
		// restrictions on valid report descriptions:
		// 1) We need at least one metric Type to be specified.
		if ($metricTypeCount === 0) return null;
		// 2) If we have multiple metrics then we have to force inclusion of
		// the metric type column to avoid aggregation over several metric types.
		if ($metricTypeCount > 1) {
			if (!in_array(STATISTICS_DIMENSION_METRIC_TYPE, $columns)) {
				array_push($columns, STATISTICS_DIMENSION_METRIC_TYPE);
			}
		}

		// Retrieve report plugins.
		if (is_a($journal, 'Journal')) {
			$contextId = $journal->getId();
		} else {
			$contextId = CONTEXT_SITE;
		}
		$reportPlugins =& PluginRegistry::loadCategory('reports', true, $contextId);
		if (!is_array($reportPlugins)) return null;

		// Run through all report plugins and try to retrieve the requested metrics.
		$report = array();
		foreach ($reportPlugins as $reportPlugin) {
			// Check whether one (or more) of the selected metrics can be
			// provided by this plugin.
			$availableMetrics = $reportPlugin->getMetricTypes();
			$availableMetrics = array_intersect($availableMetrics, $metricType);
			if (count($availableMetrics) == 0) continue;

			// Retrieve a (partial) report.
			$partialReport = $reportPlugin->getMetrics($availableMetrics, $columns, $filter, $orderBy, $range);

			// Merge the partial report with the main report.
			$report = array_merge($report, $partialReport);

			// Remove the found metric types from the metric type array.
			$metricType = array_diff($metricType, $availableMetrics);
		}

		// Check whether we found all requested metric types.
		if (count($metricType) > 0) return null;

		// Return the report.
		return $report;
	}

	/**
	* Return metric in the primary metric type
	* for the passed associated object.
	* @param $assocType int
	* @param $assocId int
	* @return int
	*/
	function getPrimaryMetricByAssoc($assocType, $assocId) {
		$filter = array(
			STATISTICS_DIMENSION_ASSOC_ID => $assocId,
			STATISTICS_DIMENSION_ASSOC_TYPE => $assocType);

		$request =& $this->getRequest();
		$journal =& $request->getJournal();
		if ($journal) {
			$filter[STATISTICS_DIMENSION_CONTEXT_ID] = $journal->getId();
		}

		$metric = $this->getMetrics(null, array(), $filter);
		if (is_array($metric)) {
			if (!is_null($metric[0][STATISTICS_METRIC])) return $metric[0][STATISTICS_METRIC];
		}

		return 0;
	}

	/**
	 * Get a mapping of license URL to license locale key for common
	 * creative commons licenses.
	 * @return array
	 */
	function getCCLicenseOptions() {
		return array(
			'http://creativecommons.org/licenses/by-nc-nd/4.0' => 'submission.license.cc.by-nc-nd4',
			'http://creativecommons.org/licenses/by-nc/4.0' => 'submission.license.cc.by-nc4',
			'http://creativecommons.org/licenses/by-nc-sa/4.0' => 'submission.license.cc.by-nc-sa4',
			'http://creativecommons.org/licenses/by-nd/4.0' => 'submission.license.cc.by-nd4',
			'http://creativecommons.org/licenses/by/4.0' => 'submission.license.cc.by4',
			'http://creativecommons.org/licenses/by-sa/4.0' => 'submission.license.cc.by-sa4'
		);
	}

	/**
	 * Get the Creative Commons license badge associated with a given
	 * license URL.
	 * @param $ccLicenseURL URL to creative commons license
	 * @return string HTML code for CC license
	 */
	function getCCLicenseBadge($ccLicenseURL) {
		$licenseKeyMap = array(
			'http://creativecommons.org/licenses/by-nc-nd/4.0' => 'submission.license.cc.by-nc-nd4.footer',
			'http://creativecommons.org/licenses/by-nc/4.0' => 'submission.license.cc.by-nc4.footer',
			'http://creativecommons.org/licenses/by-nc-sa/4.0' => 'submission.license.cc.by-nc-sa4.footer',
			'http://creativecommons.org/licenses/by-nd/4.0' => 'submission.license.cc.by-nd4.footer',
			'http://creativecommons.org/licenses/by/4.0' => 'submission.license.cc.by4.footer',
			'http://creativecommons.org/licenses/by-sa/4.0' => 'submission.license.cc.by-sa4.footer'
		);
		if (isset($licenseKeyMap[$ccLicenseURL])) {
			return __($licenseKeyMap[$ccLicenseURL]);
		}
		return null;
	}
}

?>
