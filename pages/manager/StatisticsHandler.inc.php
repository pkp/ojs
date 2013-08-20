<?php

/**
 * @file pages/manager/StatisticsHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StatisticsHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for statistics functions.
 */

import('pages.manager.ManagerHandler');

class StatisticsHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function StatisticsHandler() {
		parent::ManagerHandler();
	}
	/**
	 * Display a list of journal statistics.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics() {
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);

		$sectionIds = $journal->getSetting('statisticsSectionIds');
		if (!is_array($sectionIds)) $sectionIds = array();
		$templateMgr->assign('sectionIds', $sectionIds);

		foreach ($this->_getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $journal->getSetting($name));
		}
		$templateMgr->assign('statViews', $journal->getSetting('statViews'));

		$fromDate = mktime(0, 0, 0, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$articleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getId(), null, $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$limitedArticleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('limitedArticleStatistics', $limitedArticleStatistics);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($journal->getId());
		$templateMgr->assign('sections', $sections->toArray());

		$issueStatistics = $journalStatisticsDao->getIssueStatistics($journal->getId(), $fromDate, $toDate);
		$templateMgr->assign('issueStatistics', $issueStatistics);

		$reviewerStatistics = $journalStatisticsDao->getReviewerStatistics($journal->getId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $journalStatisticsDao->getUserStatistics($journal->getId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $journalStatisticsDao->getUserStatistics($journal->getId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION) {
			$allSubscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getId(), null, $toDate);
			$templateMgr->assign('allSubscriptionStatistics', $allSubscriptionStatistics);

			$subscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getId(), $fromDate, $toDate);
			$templateMgr->assign('subscriptionStatistics', $subscriptionStatistics);
		}

		$reportPlugins =& PluginRegistry::loadCategory('reports');
		$templateMgr->assign_by_ref('reportPlugins', $reportPlugins);

		$templateMgr->assign('defaultMetricType', $journal->getSetting('defaultMetricType'));
		$templateMgr->assign('availableMetricTypes', $journal->getMetricTypes(true));

		$templateMgr->assign('helpTopicId', 'journal.managementPages.statsAndReports');

		$templateMgr->display('manager/statistics/index.tpl');
	}

	function saveStatisticsSettings() {
		// The manager wants to save the list of sections used to
		// generate statistics.

		$this->validate();

		$journal =& Request::getJournal();

		$sectionIds = Request::getUserVar('sectionIds');
		if (!is_array($sectionIds)) {
			if (empty($sectionIds)) $sectionIds = array();
			else $sectionIds = array($sectionIds);
		}
		$journal->updateSetting('statisticsSectionIds', $sectionIds);

		$defaultMetricType = Request::getUserVar('defaultMetricType');
		$journal->updateSetting('defaultMetricType', $defaultMetricType);

		Request::redirect(null, null, 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
	}

	function _getPublicStatisticsNames() {
		return array(
			'statNumPublishedIssues',
			'statItemsPublished',
			'statNumSubmissions',
			'statPeerReviewed',
			'statCountAccept',
			'statCountDecline',
			'statCountRevise',
			'statDaysPerReview',
			'statDaysToPublication',
			'statRegisteredUsers',
			'statRegisteredReaders',
			'statSubscriptions',
		);
	}

	function savePublicStatisticsList() {
		$this->validate();

		$journal =& Request::getJournal();
		foreach ($this->_getPublicStatisticsNames() as $name) {
			$journal->updateSetting($name, Request::getUserVar($name)?true:false);
		}
		$journal->updateSetting('statViews', Request::getUserVar('statViews')?true:false);
		Request::redirect(null, null, 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
	}

	function report($args, $request) {
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();

		$pluginName = array_shift($args);
		$reportPlugins =& PluginRegistry::loadCategory('reports');

		if ($pluginName == '' || !isset($reportPlugins[$pluginName])) {
			Request::redirect(null, null, 'statistics');
		}

		$plugin =& $reportPlugins[$pluginName];
		$plugin->display($args, $request);
	}

	/**
	 * Generate statistics reports from passed
	 * request arguments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function generateReport(&$args, &$request) {
		$this->validate();
		$this->setupTemplate(true);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

		$metricType = $request->getUserVar('metricType');
		if (is_scalar($metricType)) $metricType = array($metricType);

		// Retrieve site-level report plugins.
		$reportPlugins =& PluginRegistry::loadCategory('reports', true, CONTEXT_SITE);
		if (!is_array($reportPlugins) || empty($metricType)) {
			$request->redirect(null, null, 'statistics');
		}

		$foundReportPlugin = false;
		foreach ($reportPlugins as $reportPlugin) {
			/* @var $reportPlugin ReportPlugin */
			$pluginMetricTypes = $reportPlugin->getMetricTypes();
			$metricTypeMatches = array_intersect($pluginMetricTypes, $metricType);
			if (!empty($metricTypeMatches)) {
				$foundReportPlugin = true;
				break;
			}
		}

		if (!$foundReportPlugin) $request->redirect(null, null, 'statistics');

		$columns = $request->getUserVar('columns');
		$filters = unserialize($request->getUserVar('filters'));
		if (!$filters) $filters = $request->getUserVar('filters');

		$orderBy = $request->getUserVar('orderBy');
		if ($orderBy) {
			$orderBy = unserialize($orderBy);
			if (!$orderBy) $orderBy = $request->getUserVar('orderBy');
		} else {
			$orderBy = array();
		}

		$metrics = $reportPlugin->getMetrics($metricType, $columns, $filters, $orderBy);

		$allColumnNames = $this->_getColumnNames();
		$columnOrder = array_keys($allColumnNames);
		$columnNames = array();

		foreach ($columnOrder as $column) {
			if (in_array($column, $columns)) {
				$columnNames[$column] = $allColumnNames[$column];
			}

			if ($column == STATISTICS_DIMENSION_ASSOC_TYPE && in_array(STATISTICS_DIMENSION_ASSOC_ID, $columns)) {
				$columnNames['common.title'] = __('common.title');
			}
		}

		// Make sure the metric column will always be present.
		if (!in_array(STATISTICS_METRIC, $columnNames)) $columnNames[STATISTICS_METRIC] = $allColumnNames[STATISTICS_METRIC];

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=statistics-' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'wt');
		fputcsv($fp, array($reportPlugin->getDisplayName()));
		fputcsv($fp, array($reportPlugin->getDescription()));
		fputcsv($fp, array(''));

		fputcsv($fp, $columnNames);
		foreach ($metrics as $record) {
			$row = array();
			foreach ($columnNames as $key => $name) {
				switch ($key) {
					case 'common.title':
						$assocId = $record[STATISTICS_DIMENSION_ASSOC_ID];
						$assocType = $record[STATISTICS_DIMENSION_ASSOC_TYPE];
						$row[] = $this->_getObjectTitle($assocId, $assocType);
						break;
					case STATISTICS_DIMENSION_ASSOC_TYPE:
						$assocType = $record[STATISTICS_DIMENSION_ASSOC_TYPE];
						$row[] = $this->_getObjectTypeString($assocType);
						break;
					case STATISTICS_DIMENSION_CONTEXT_ID:
						$assocId = $record[STATISTICS_DIMENSION_CONTEXT_ID];
						$assocType = ASSOC_TYPE_JOURNAL;
						$row[] = $this->_getObjectTitle($assocId, $assocType);
						break;
					case STATISTICS_DIMENSION_ISSUE_ID:
						$assocId = $record[STATISTICS_DIMENSION_ISSUE_ID];
						$assocType = ASSOC_TYPE_ISSUE;
						$row[] = $this->_getObjectTitle($assocId, $assocType);
						break;
					case STATISTICS_DIMENSION_SUBMISSION_ID:
						$assocId = $record[STATISTICS_DIMENSION_SUBMISSION_ID];
						$assocType = ASSOC_TYPE_ARTICLE;
						$row[] = $this->_getObjectTitle($assocId, $assocType);
						break;
					default:
						$row[] = $record[$key];
				}
			}
			fputcsv($fp, $row);
		}
		fclose($fp);
	}

	/**
	 * Get report column names in correct order.
	 * @return array
	 */
	function _getColumnNames() {
		return array(
			STATISTICS_DIMENSION_ASSOC_ID => __('common.id'),
			STATISTICS_DIMENSION_ASSOC_TYPE => __('common.type'),
			STATISTICS_DIMENSION_SUBMISSION_ID => __('article.article'),
			STATISTICS_DIMENSION_ISSUE_ID => __('issue.issue'),
			STATISTICS_DIMENSION_CONTEXT_ID => __('common.journal'),
			STATISTICS_DIMENSION_CITY => __('manager.statistics.city'),
			STATISTICS_DIMENSION_REGION => __('manager.statistics.region'),
			STATISTICS_DIMENSION_COUNTRY => __('common.country'),
			STATISTICS_DIMENSION_DAY => __('common.day'),
			STATISTICS_DIMENSION_MONTH => __('common.month'),
			STATISTICS_DIMENSION_FILE_TYPE => __('common.fileType'),
			STATISTICS_DIMENSION_METRIC_TYPE => __('common.metric'),
			STATISTICS_METRIC => __('submission.views'),
		);
	}

	/**
	 * Get data object title based on passed
	 * assoc type and id.
	 * @param $assocId int
	 * @param $assocType int
	 * @return string
	 */
	function _getObjectTitle($assocId, $assocType) {
		switch ($assocType) {
			case ASSOC_TYPE_JOURNAL:
				$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
				$journal =& $journalDao->getJournal($assocId);
				return $journal->getLocalizedTitle();
			case ASSOC_TYPE_ISSUE:
				$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
				$issue =& $issueDao->getIssueById($assocId, null, true);
				return $issue->getLocalizedTitle();
			case ASSOC_TYPE_ISSUE_GALLEY:
				$issueGalleyDao =& DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
				$issue =& $issueGalleyDao->getGalley($assocId);
				return $issue->getFileName();
			case ASSOC_TYPE_ARTICLE:
				$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
				$article =& $articleDao->getArticle($assocId, null, true);
				return $article->getLocalizedTitle();
			case ASSOC_TYPE_GALLEY:
				$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
				$galley =& $articleGalleyDao->getGalley($assocId);
				return $galley->getFileName();
			default:
				assert(false);
		}
	}

	/**
	 * Get object type string
	 * @param $assocType int
	 * @return string
	 */
	function _getObjectTypeString($assocType) {
		switch ($assocType) {
			case ASSOC_TYPE_JOURNAL:
				return __('journal.journal');
			case ASSOC_TYPE_ISSUE:
				return __('issue.issue');
			case ASSOC_TYPE_ISSUE_GALLEY:
				return __('editor.issues.galley');
			case ASSOC_TYPE_ARTICLE:
				return __('article.article');
			case ASSOC_TYPE_GALLEY:
				return __('submission.galley');
			default:
				assert(false);
		}
	}
}

?>
