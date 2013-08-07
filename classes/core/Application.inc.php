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

define('PHP_REQUIRED_VERSION', '5.2.0');

define('ASSOC_TYPE_ARTICLE',		ASSOC_TYPE_SUBMISSION);
define('ASSOC_TYPE_PUBLISHED_ARTICLE',	ASSOC_TYPE_PUBLISHED_SUBMISSION);

define('ASSOC_TYPE_JOURNAL',		0x0000100);
define('ASSOC_TYPE_SECTION',		0x0000103);
define('ASSOC_TYPE_ISSUE',		0x0000103);
define('ASSOC_TYPE_GALLEY',		0x0000104);
define('ASSOC_TYPE_ISSUE_GALLEY',	0x0000105);
define('ASSOC_TYPE_SUPP_FILE',		0x0000106);

define('CONTEXT_JOURNAL', 1);

// Definitions for the Statistics API...

// Dimensions:
// 1) publication object dimension:
define('STATISTICS_DIMENSION_CONTEXT_ID', 'context_id');
define('STATISTICS_DIMENSION_ISSUE_ID', 'issue_id');
define('STATISTICS_DIMENSION_ARTICLE_ID', 'submission_id');
define('STATISTICS_DIMENSION_ASSOC_TYPE', 'assoc_type');
define('STATISTICS_DIMENSION_ASSOC_ID', 'assoc_id');
define('STATISTICS_DIMENSION_FILE_TYPE', 'file_type');
// 2) time dimension:
define('STATISTICS_DIMENSION_MONTH', 'month');
define('STATISTICS_DIMENSION_DAY', 'day');
// 3) geography dimension:
define('STATISTICS_DIMENSION_COUNTRY', 'country_id');
define('STATISTICS_DIMENSION_REGION', 'region');
define('STATISTICS_DIMENSION_CITY', 'city');
// 4) metric type dimension (non-additive!):
define('STATISTICS_DIMENSION_METRIC_TYPE', 'metric_type');

// Metrics:
define('STATISTICS_METRIC', 'metric');

// Odering:
define('STATISTICS_ORDER_ASC', 'ASC');
define('STATISTICS_ORDER_DESC', 'DESC');

// Global report size limit:
define('STATISTICS_MAX_ROWS', 5000);

// File type to be used in publication object dimension.
define('STATISTICS_FILE_TYPE_HTML', 1);
define('STATISTICS_FILE_TYPE_PDF', 2);
define('STATISTICS_FILE_TYPE_OTHER', 3);

// Geography.
define('STATISTICS_UNKNOWN_COUNTRY_ID', 'ZZ');

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
			'SubmissionCommentDAO' => 'lib.pkp.classes.submission.SubmissionCommentDAO',
			'ArticleDAO' => 'classes.article.ArticleDAO',
			'ArticleFileDAO' => 'classes.article.ArticleFileDAO',
			'ArticleGalleyDAO' => 'classes.article.ArticleGalleyDAO',
			'ArticleSearchDAO' => 'classes.search.ArticleSearchDAO',
			'AuthorDAO' => 'classes.article.AuthorDAO',
			'CategoryDAO' => 'classes.journal.categories.CategoryDAO',
			'CommentDAO' => 'lib.pkp.classes.comment.CommentDAO',
			'EditorSubmissionDAO' => 'classes.submission.editor.EditorSubmissionDAO',
			'EmailTemplateDAO' => 'classes.mail.EmailTemplateDAO',
			'FooterCategoryDAO' => 'lib.pkp.classes.context.FooterCategoryDAO',
			'FooterLinkDAO' => 'lib.pkp.classes.context.FooterLinkDAO',
			'GiftDAO' => 'classes.gift.GiftDAO',
			'IndividualSubscriptionDAO' => 'classes.subscription.IndividualSubscriptionDAO',
			'InstitutionalSubscriptionDAO' => 'classes.subscription.InstitutionalSubscriptionDAO',
			'IssueDAO' => 'classes.issue.IssueDAO',
			'IssueGalleyDAO' => 'classes.issue.IssueGalleyDAO',
			'IssueFileDAO' => 'classes.issue.IssueFileDAO',
			'JournalDAO' => 'classes.journal.JournalDAO',
			'JournalSettingsDAO' => 'classes.journal.JournalSettingsDAO',
			'MetricsDAO' => 'classes.statistics.MetricsDAO',
			'NoteDAO' => 'classes.note.NoteDAO',
			'OAIDAO' => 'classes.oai.ojs.OAIDAO',
			'OJSCompletedPaymentDAO' => 'classes.payment.ojs.OJSCompletedPaymentDAO',
			'PluginSettingsDAO' => 'classes.plugins.PluginSettingsDAO',
			'PublishedArticleDAO' => 'classes.article.PublishedArticleDAO',
			'QueuedPaymentDAO' => 'lib.pkp.classes.payment.QueuedPaymentDAO',
			'ReviewAssignmentDAO' => 'lib.pkp.classes.submission.reviewAssignment.ReviewAssignmentDAO',
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
			'SocialMediaDAO' => 'classes.journal.SocialMediaDAO',
			'StageAssignmentDAO' => 'lib.pkp.classes.stageAssignment.StageAssignmentDAO',
			'SubmissionEventLogDAO' => 'classes.log.SubmissionEventLogDAO',
			'SubmissionFileDAO' => 'classes.article.SubmissionFileDAO',
			'SubscriptionDAO' => 'classes.subscription.SubscriptionDAO',
			'SubscriptionTypeDAO' => 'classes.subscription.SubscriptionTypeDAO',
			'UserGroupAssignmentDAO' => 'lib.pkp.classes.security.UserGroupAssignmentDAO',
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
			'articleGalleys',
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
	 * Get the top-level context DAO.
	 */
	static function getContextDAO() {
		return DAORegistry::getDAO('JournalDAO');
	}

	/**
	 * Get the submission DAO.
	 */
	static function getSubmissionDAO() {
		return DAORegistry::getDAO('ArticleDAO');
	}

	/**
	 * Get the DAO for ROLE_ID_SUB_EDITOR roles.
	 */
	static function getSubEditorDAO() {
		return DAORegistry::getDAO('SectionEditorDAO');
	}

	/**
	 * Get the stages used by the application.
	 */
	static function getApplicationStages() {
		// We leave out WORKFLOW_STAGE_ID_PUBLISHED since it technically is not a 'stage'.
		return array(
				WORKFLOW_STAGE_ID_SUBMISSION,
				WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
				WORKFLOW_STAGE_ID_EDITING,
				WORKFLOW_STAGE_ID_PRODUCTION
		);
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
		$reportPlugins =& PluginRegistry::loadCategory('reports', true, 0);
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
		$site = $this->getRequest()->getSite();
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
		$journal = $this->_getJournalContext($filter);

		// Identify and canonicalize filtered metric types.
		$metricType = $this->_canonicalizeMetricTypes($metricType, $journal);
		if (!is_array($metricType)) return null;
		$metricTypeCnt = count($metricType);

		// Canonicalize columns.
		if (is_scalar($columns)) $columns = array($columns);

		// The metric type dimension is not additive. This imposes two important
		// restrictions on valid report descriptions:
		// 1) We need at least one metric Type to be specified.
		if ($metricTypeCnt === 0) return null;
		// 2) If we have multiple metrics then we have to force inclusion of
		// the metric type column to avoid aggregation over several metric types.
		if ($metricTypeCnt > 1) {
			if (!in_array(STATISTICS_DIMENSION_METRIC_TYPE, $columns)) {
				array_push($columns, STATISTICS_DIMENSION_METRIC_TYPE);
			}
		}

		// Retrieve report plugins.
		if (is_a($journal, 'Journal')) {
			$contextId = $journal->getId();
		} else {
			$contextId = 0;
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

		$request = $this->getRequest();
		$journal =& $request->getJournal();
		if ($journal) {
			$filter[STATISTICS_DIMENSION_CONTEXT_ID] = $journal->getId();
		}

		$metric = $this->getMetrics(null, array(), $filter);
		if (is_array($metric)) {
			return $metric[0]['metric'];
		} else {
			return 0;
		}
	}


	//
	// Statistics API: private helper methods.
	//
	/**
	 * Check whether the filter filters on a journal
	 * and if so: retrieve it.
	 *
	 * NB: We do not check filters below the journal level as this would
	 * be unnecessarily complex. We'd have to check whether the given
	 * publication objects are actually from the same journal. This again
	 * would require us to retrieve all journal objects for the filtered
	 * objects, etc.
	 *
	 * @param $filter array
	 * @return null|Journal
	 */
	private function _getJournalContext($filter) {
		// Check whether the report is on journal level.
		$journal = null;
		if (isset($filter[STATISTICS_DIMENSION_CONTEXT_ID])) {
			$journalFilter = $filter[STATISTICS_DIMENSION_CONTEXT_ID];
			if (is_scalar($journalFilter)) {
				// Retrieve the journal.
				$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
				$journal = $journalDao->getById($journalFilter);
			}
		}
		return $journal;
	}

	/**
	 * Identify and canonicalize the filtered metric type.
	 * @param $metricType string|array
	 * @param $journal null|Journal
	 * @return null|array The canonicalized metric type array. Null if an error
	 *  occurred.
	 */
	private function _canonicalizeMetricTypes($metricType, $journal) {
		// Metric type is null: Return the default metric for
		// the filtered context.
		if (is_null($metricType)) {
			if (is_a($journal, 'Journal')) {
				$metricType = $journal->getDefaultMetricType();
			} else {
				$metricType = $this->getDefaultMetricType();
			}
		}

		// Canonicalize the metric type to an array of metric types.
		if (!is_null($metricType)) {
			if (is_scalar($metricType) && $metricType !== '*') {
				// Metric type is a scalar value: Select a single metric.
				$metricType = array($metricType);

			} elseif ($metricType === '*') {
				// Metric type is '*': Select all available metrics.
				if (is_a($journal, 'Journal')) {
					$metricType = $journal->getMetricTypes();
				} else {
					$metricType = $this->getMetricTypes();
				}

			} else {
				// Only arrays are otherwise supported as metric type
				// specification.
				if (!is_array($metricType)) $metricType = null;

				// Metric type is an array: Select multiple metrics. This is the
				// canonical format so no change is required.
			}
		}

		return $metricType;
	}

	/**
	 * Get the file directory array map used by the application.
	 */
	static function getFileDirectories() {
		return array('context' => '/journals/', 'submission' => '/articles/');
	}
}

?>
