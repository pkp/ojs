<?php

/**
 * @file pages/management/PKPToolsHandler.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPToolsHandler
 * @ingroup pages_management
 *
 * @brief Handle requests for Tool pages.
 */

// Import the base ManagementHandler.
import('lib.pkp.pages.management.ManagementHandler');

define('IMPORTEXPORT_PLUGIN_CATEGORY', 'importexport');

class PKPToolsHandler extends ManagementHandler {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('tools', 'statistics', 'importexport')
		);
	}


	//
	// Public handler methods.
	//
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_SUBMISSION);
	}

	/**
	 * Route to other Tools operations
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function tools($args, $request) {
		$path = array_shift($args);
		switch ($path) {
			case '':
			case 'index':
				$this->index($args, $request);
				break;
			case 'statistics':
				$this->statistics($args, $request);
				break;
			case 'report':
				$this->report($args, $request);
				break;
			case 'reportGenerator':
				$this->reportGenerator($args, $request);
				break;
			case 'generateReport':
				$this->generateReport($args, $request);
				break;
			case 'saveStatisticsSettings':
				return $this->saveStatisticsSettings($args, $request);
			default:
				assert(false);
			}
	}

	/**
	 * Display tools index page.
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function index($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$templateMgr->display('management/tools/index.tpl');
	}

	/**
	 * Import or export data.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function importexport($args, $request) {
		$this->setupTemplate($request, true);

		PluginRegistry::loadCategory(IMPORTEXPORT_PLUGIN_CATEGORY);
		$templateMgr = TemplateManager::getManager($request);

		if (array_shift($args) === 'plugin') {
			$pluginName = array_shift($args);
			$plugin = PluginRegistry::getPlugin(IMPORTEXPORT_PLUGIN_CATEGORY, $pluginName);
			if ($plugin) return $plugin->display($args, $request);
		}
		$templateMgr->assign('plugins', PluginRegistry::getPlugins(IMPORTEXPORT_PLUGIN_CATEGORY));
		return $templateMgr->fetchJson('management/tools/importexport.tpl');
	}

	/**
	 * Display the statistics area.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function statistics($args, $request) {
		$this->setupTemplate($request);
		$context = $request->getContext();

		$templateMgr = TemplateManager::getManager($request);

		$application = Application::getApplication();
		$templateMgr->assign('appSettings', $this->hasAppStatsSettings());
		$templateMgr->assign('contextObjectName', __($application->getNameKey()));

		$reportPlugins = PluginRegistry::loadCategory('reports');
		$templateMgr->assign('reportPlugins', $reportPlugins);

		$templateMgr->assign('defaultMetricType', $context->getSetting('defaultMetricType'));
		$availableMetricTypes = $context->getMetricTypes(true);
		$templateMgr->assign('availableMetricTypes', $availableMetricTypes);
		if (count($availableMetricTypes) > 1) {
			$templateMgr->assign('showMetricTypeSelector', true);
		}

		return $templateMgr->fetchJson('management/tools/statistics.tpl');
	}

	/**
	 * Delegates to plugins operations
	 * related to report generation.
	 * @param $args array
	 * @param $request Request
	 */
	function report($args, $request) {
		$this->setupTemplate($request);

		$pluginName = $request->getUserVar('pluginName');
		$reportPlugins = PluginRegistry::loadCategory('reports');

		if ($pluginName == '' || !isset($reportPlugins[$pluginName])) {
			$request->redirect(null, null, 'management', 'statistics');
		}

		$plugin = $reportPlugins[$pluginName];
		$plugin->display($args, $request);
	}

	/**
	 * Display page to generate custom reports.
	 * @param $args array
	 * @param $request Request
	 */
	function reportGenerator($args, $request) {
		$this->setupTemplate($request);

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_EDITOR);

		$templateMgr = TemplateManager::getManager();
		$templateMgr->display('management/tools/reportGenerator.tpl');
	}


	/**
	 * Generate statistics reports from passed
	 * request arguments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function generateReport($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

		$router = $request->getRouter();
		$context = $router->getContext($request);
		import('classes.statistics.StatisticsHelper');
		$statsHelper = new StatisticsHelper();

		$metricType = $request->getUserVar('metricType');
		if (is_null($metricType)) {
			$metricType = $context->getDefaultMetricType();
		}

		// Generates only one metric type report at a time.
		if (is_array($metricType)) $metricType = current($metricType);
		if (!is_scalar($metricType)) $metricType = null;

		$reportPlugin = $statsHelper->getReportPluginByMetricType($metricType);
		if (!$reportPlugin || is_null($metricType)) {
			$request->redirect(null, null, 'tools', 'statistics');
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

		$allColumnNames = $statsHelper->getColumnNames();
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

				// Give a chance for subclasses to set the row values.
				if ($returner = $this->getReportRowValue($key, $record)) {
					$row[] = $returner;
					continue;
				}

				switch ($key) {
					case 'common.title':
						$assocId = $record[STATISTICS_DIMENSION_ASSOC_ID];
						$assocType = $record[STATISTICS_DIMENSION_ASSOC_TYPE];
						$row[] = $this->getObjectTitle($assocId, $assocType);
						break;
					case STATISTICS_DIMENSION_ASSOC_TYPE:
						$assocType = $record[STATISTICS_DIMENSION_ASSOC_TYPE];
						$row[] = $statsHelper->getObjectTypeString($assocType);
						break;
					case STATISTICS_DIMENSION_CONTEXT_ID:
						$assocId = $record[STATISTICS_DIMENSION_CONTEXT_ID];
						$assocType = Application::getContextAssocType();
						$row[] = $this->getObjectTitle($assocId, $assocType);
						break;
					case STATISTICS_DIMENSION_SUBMISSION_ID:
						if (isset($record[STATISTICS_DIMENSION_SUBMISSION_ID])) {
							$assocId = $record[STATISTICS_DIMENSION_SUBMISSION_ID];
							$assocType = ASSOC_TYPE_SUBMISSION;
							$row[] = $this->getObjectTitle($assocId, $assocType);
						} else {
							$row[] = '';
						}
						break;
					case STATISTICS_DIMENSION_REGION:
						if (isset($record[STATISTICS_DIMENSION_REGION]) && isset($record[STATISTICS_DIMENSION_COUNTRY])) {
							$geoLocationTool = $statsHelper->getGeoLocationTool();
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
					case STATISTICS_DIMENSION_PKP_SECTION_ID:
						$sectionId = null;
						if (isset($record[STATISTICS_DIMENSION_PKP_SECTION_ID])) {
							$sectionId = $record[STATISTICS_DIMENSION_PKP_SECTION_ID];
						}
						if ($sectionId) {
							$row[] = $this->getObjectTitle($sectionId, ASSOC_TYPE_SECTION);
						} else {
							$row[] = '';
						}
						break;
					case STATISTICS_DIMENSION_FILE_TYPE:
						if ($record[$key]) {
							$row[] = $statsHelper->getFileTypeString($record[$key]);
						} else {
							$row[] = '';
						}
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

	/**
	 * Save statistics settings.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function saveStatisticsSettings($args, $request) {
		$router = $request->getRouter();
		$context = $router->getContext($request);

		$defaultMetricType = $request->getUserVar('defaultMetricType');
		$context->updateSetting('defaultMetricType', $defaultMetricType);

		$notificationManager = new NotificationManager();
		$user = $request->getUser();
		$notificationManager->createTrivialNotification($user->getId());

		return new JSONMessage();
	}


	//
	// Protected methods.
	//
	/**
	 * Get the row value based on the column key (usually assoc types)
	 * and the current record.
	 * @param $key string|int
	 * @param $record array
	 * @return string
	 */
	protected function getReportRowValue($key, $record) {
		return null;
	}

	/**
	 * Get data object title based on passed
	 * assoc type and id.
	 * @param $assocId int
	 * @param $assocType int
	 * @return string
	 */
	protected function getObjectTitle($assocId, $assocType) {
		switch ($assocType) {
			case Application::getContextAssocType():
				$contextDao = Application::getContextDAO(); /* @var $contextDao ContextDAO */
				$context = $contextDao->getById($assocId);
				if (!$context) break;
				return $context->getLocalizedName();
			case ASSOC_TYPE_SUBMISSION:
				$submissionDao = Application::getSubmissionDAO(); /* @var $submissionDao SubmissionDAO */
				$submission = $submissionDao->getById($assocId, null, true);
				if (!$submission) break;
				return $submission->getLocalizedTitle();
			case ASSOC_TYPE_SECTION:
				$sectionDao = Application::getSectionDAO();
				$section = $sectionDao->getById($assocId);
				if (!$section) break;
				return $section->getLocalizedTitle();
			case ASSOC_TYPE_SUBMISSION_FILE:
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
				$submissionFile = $submissionFileDao->getLatestRevision($assocId);
				if (!$submissionFile) break;
				return $submissionFile->getFileLabel();
		}

		return __('manager.statistics.reports.objectNotFound');
	}

	/**
	 * Override and return true if application has
	 * more statistics settings than the defined in library.
	 * @return boolean
	 */
	protected function hasAppStatsSettings() {
		return false;
	}

}

?>
