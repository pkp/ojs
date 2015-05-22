<?php

/**
 * @file pages/manager/StatisticsHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	function statistics($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$templateMgr =& TemplateManager::getManager($request);

		// Get the statistics year
		$statisticsYear = (int) $request->getUserVar('statisticsYear');

		// Ensure that the requested statistics year is within a sane range
		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$lastYear = strftime('%Y');
		$firstDate = $journalStatisticsDao->getFirstActivityDate($journal->getId());
		if (!$firstDate) $firstYear = $lastYear;
		else $firstYear = strftime('%Y', $firstDate);
		if ($statisticsYear < $firstYear || $statisticsYear > $lastYear) {
			// Request out of range; redirect to the current year's statistics
			return $request->redirect(null, null, null, null, array('statisticsYear' => strftime('%Y')));
		}

		$templateMgr->assign('statisticsYear', $statisticsYear);
		$templateMgr->assign('firstYear', $firstYear);
		$templateMgr->assign('lastYear', $lastYear);

		$sectionIds = $journal->getSetting('statisticsSectionIds');
		if (!is_array($sectionIds)) $sectionIds = array();
		$templateMgr->assign('sectionIds', $sectionIds);

		foreach ($this->_getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $journal->getSetting($name));
		}
		$templateMgr->assign('statViews', $journal->getSetting('statViews'));

		$fromDate = mktime(0, 0, 0, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

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

	function savePublicStatisticsList() {
		$this->validate();

		$journal =& Request::getJournal();
		foreach ($this->_getPublicStatisticsNames() as $name) {
			$journal->updateSetting($name, Request::getUserVar($name)?true:false);
		}
		$journal->updateSetting('statViews', Request::getUserVar('statViews')?true:false);
		Request::redirect(null, null, 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
	}

	/**
	 * Delegates to plugins operations
	 * related to report generation.
	 * @param $args array
	 * @param $request Request
	 */
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
	 * Display page to generate custom reports.
	 * @param $args array
	 * @param $request Request
	 */
	function reportGenerator(&$args, &$request) {
		$this->validate();
		$this->setupTemplate();

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OJS_EDITOR);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->display('manager/statistics/reportGenerator.tpl');
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

		$router =& $request->getRouter();
		$context =& $router->getContext($request); /* @var $context Journal */

		$metricType = $request->getUserVar('metricType');
		if (is_null($metricType)) {
			$metricType = $context->getDefaultMetricType();
		}

		// Generates only one metric type report at a time.
		if (is_array($metricType)) $metricType = current($metricType);
		if (!is_scalar($metricType)) $metricType = null;

		$reportPlugin =& StatisticsHelper::getReportPluginByMetricType($metricType);
		if (!$reportPlugin || is_null($metricType)) {
			$request->redirect(null, null, 'statistics');
		}

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

		$allColumnNames = StatisticsHelper::getColumnNames();
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
		fputcsv($fp, array(__('common.metric') . ': ' . $metricType));
		fputcsv($fp, array(__('manager.statistics.reports.reportUrl') . ': ' . $request->getCompleteUrl()));
		fputcsv($fp, array(''));

		// Just for better displaying.
		$columnNames = array_merge(array(''), $columnNames);

		fputcsv($fp, $columnNames);
		foreach ($metrics as $record) {
			$row = array();
			foreach ($columnNames as $key => $name) {
				if (empty($name)) {
					// Column just for better displaying.
					$row[] = '';
					continue;
				}
				switch ($key) {
					case 'common.title':
						$assocId = $record[STATISTICS_DIMENSION_ASSOC_ID];
						$assocType = $record[STATISTICS_DIMENSION_ASSOC_TYPE];
						$row[] = $this->_getObjectTitle($assocId, $assocType);
						break;
					case STATISTICS_DIMENSION_ASSOC_TYPE:
						$assocType = $record[STATISTICS_DIMENSION_ASSOC_TYPE];
						$row[] = StatisticsHelper::getObjectTypeString($assocType);
						break;
					case STATISTICS_DIMENSION_CONTEXT_ID:
						$assocId = $record[STATISTICS_DIMENSION_CONTEXT_ID];
						$assocType = ASSOC_TYPE_JOURNAL;
						$row[] = $this->_getObjectTitle($assocId, $assocType);
						break;
					case STATISTICS_DIMENSION_ISSUE_ID:
						if (isset($record[STATISTICS_DIMENSION_ISSUE_ID])) {
							$assocId = $record[STATISTICS_DIMENSION_ISSUE_ID];
							$assocType = ASSOC_TYPE_ISSUE;
							$row[] = $this->_getObjectTitle($assocId, $assocType);
						} else {
							$row[] = '';
						}
						break;
					case STATISTICS_DIMENSION_SUBMISSION_ID:
						if (isset($record[STATISTICS_DIMENSION_SUBMISSION_ID])) {
							$assocId = $record[STATISTICS_DIMENSION_SUBMISSION_ID];
							$assocType = ASSOC_TYPE_ARTICLE;
							$row[] = $this->_getObjectTitle($assocId, $assocType);
						} else {
							$row[] = '';
						}
						break;
					case STATISTICS_DIMENSION_REGION:
						if (isset($record[STATISTICS_DIMENSION_REGION]) && isset($record[STATISTICS_DIMENSION_COUNTRY])) {
							$geoLocationTool =& StatisticsHelper::getGeoLocationTool();
							if ($geoLocationTool) {
								$regions = $geoLocationTool->getRegions($record[STATISTICS_DIMENSION_COUNTRY]);
								$regionId = $record[STATISTICS_DIMENSION_REGION];
								if (strlen($regionId) == 1) $regionId = '0' . $regionId;
								if (isset($regions[$regionId])) {
									$row[] = $regions[$regionId];
									break;
								}
							}
						}
						$row[] = '';
						break;
					default:
						$row[] = $record[$key];
						break;
				}
			}
			fputcsv($fp, $row);
		}
		fclose($fp);
	}


	//
	// Private helper methods.
	//
	/**
	 * Get public statistics names.
	 * @return array
	 */
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

	/**
	 * Get data object title based on passed
	 * assoc type and id. If no object, return
	 * a default title.
	 * @param $assocId int
	 * @param $assocType int
	 * @return string
	 */
	function _getObjectTitle($assocId, $assocType) {
		switch ($assocType) {
			case ASSOC_TYPE_JOURNAL:
				$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
				$journal =& $journalDao->getJournal($assocId);
				if (!$journal) break;
				return $journal->getLocalizedTitle();
			case ASSOC_TYPE_ISSUE:
				$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
				$issue =& $issueDao->getIssueById($assocId, null, true);
				if (!$issue) break;
				$title = $issue->getLocalizedTitle();
				if (!$title) {
					$title = $issue->getIssueIdentification();
				}
				return $title;
			case ASSOC_TYPE_ISSUE_GALLEY:
				$issueGalleyDao =& DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
				$issueGalley =& $issueGalleyDao->getGalley($assocId);
				if (!$issueGalley) break;
				return $issueGalley->getFileName();
			case ASSOC_TYPE_ARTICLE:
				$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
				$article =& $articleDao->getArticle($assocId, null, true);
				if (!$article) break;
				return $article->getLocalizedTitle();
			case ASSOC_TYPE_GALLEY:
				$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
				$galley =& $articleGalleyDao->getGalley($assocId);
				if (!$galley) break;
				return $galley->getFileName();
			default:
				assert(false);
		}

		return __('manager.statistics.reports.objectNotFound');
	}
}

?>
