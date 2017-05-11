<?php

/**
 * @file controllers/statistics/ReportGeneratorHandler.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportGeneratorHandler
 * @ingroup controllers_statistics
 *
 * @brief Handle requests for report generator functions.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');
import('classes.statistics.StatisticsHelper');

class ReportGeneratorHandler extends Handler {
	/**
	 * Constructor
	 **/
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('fetchReportGenerator', 'saveReportGenerator', 'fetchArticlesInfo', 'fetchRegions'));
	}

	/**
	* Fetch form to generate custom reports.
	* @param $args array
	* @param $request Request
	 * @return JSONMessage JSON object
	*/
	function fetchReportGenerator($args, $request) {
		$this->setupTemplate($request);
		$reportGeneratorForm = $this->_getReportGeneratorForm($request);
		$reportGeneratorForm->initData($request);

		$formContent = $reportGeneratorForm->fetch($request);

		$json = new JSONMessage(true);
		if ($request->getUserVar('refreshForm')) {
			$json->setEvent('refreshForm', $formContent);
		} else {
			$json->setContent($formContent);
		}

		return $json;
	}

	/**
	 * Save form to generate custom reports.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function saveReportGenerator($args, $request) {
		$this->setupTemplate($request);

		$reportGeneratorForm = $this->_getReportGeneratorForm($request);
		$reportGeneratorForm->readInputData();
		$json = new JSONMessage(true);
		if ($reportGeneratorForm->validate()) {
			$reportUrl = $reportGeneratorForm->execute($request);
			$json->setAdditionalAttributes(array('reportUrl' => $reportUrl));
		} else {
			$json->setStatus(false);
		}

		return $json;
	}

	/**
	 * Fetch articles title and id from
	 * the passed request variable issue id.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchArticlesInfo($args, $request) {
		$this->validate();

		$issueId = (int) $request->getUserVar('issueId');
		import('lib.pkp.classes.core.JSONMessage');

		if (!$issueId) {
			return new JSONMessage(false);
		} else {
			$articleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$articles = $articleDao->getPublishedArticles($issueId);
			$articlesInfo = array();
			foreach ($articles as $article) {
				$articlesInfo[] = array('id' => $article->getId(), 'title' => $article->getLocalizedTitle());
			}

			return new JSONMessage(true, $articlesInfo);
		}
	}

	/**
	 * Fetch regions from the passed request
	 * variable country id.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchRegions($args, $request) {
		$this->validate();

		$countryId = (string) $request->getUserVar('countryId');
		import('lib.pkp.classes.core.JSONMessage');

		if ($countryId) {
			$statsHelper = new StatisticsHelper();
			$geoLocationTool = $statsHelper->getGeoLocationTool();
			if ($geoLocationTool) {
				$regions = $geoLocationTool->getRegions($countryId);
				if (!empty($regions)) {
					$regionsData = array();
					foreach ($regions as $id => $name) {
						$regionsData[] = array('id' => $id, 'name' => $name);
					}
					return new JSONMessage(true, $regionsData);
				}
			}
		}

		return new JSONMessage(false);
	}

	/**
	 * @see PKPHandler::setupTemplate()
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_APP_SUBMISSION);
	}


	//
	// Private helper methods.
	//
	/**
	 * Get report generator form object.
	 * @return ReportGeneratorForm
	 */
	function &_getReportGeneratorForm($request) {
		$router = $request->getRouter();
		$context = $router->getContext($request);

		$metricType = $request->getUserVar('metricType');
		if (!$metricType) {
			$metricType = $context->getDefaultMetricType();
		}

		$statsHelper = new StatisticsHelper();
		$reportPlugin = $statsHelper->getReportPluginByMetricType($metricType);
		if (!is_scalar($metricType) || !$reportPlugin) {
			fatalError('Invalid metric type.');
		}

		$columns = $reportPlugin->getColumns($metricType);
		$columns = array_flip(array_intersect(array_flip($statsHelper->getColumnNames()), $columns));

		$optionalColumns = $reportPlugin->getOptionalColumns($metricType);
		$optionalColumns = array_flip(array_intersect(array_flip($statsHelper->getColumnNames()), $optionalColumns));

		$objects = $reportPlugin->getObjectTypes($metricType);
		$objects = array_flip(array_intersect(array_flip($statsHelper->getObjectTypeString()), $objects));

		$defaultReportTemplates = $reportPlugin->getDefaultReportTemplates($metricType);

		// If the report plugin doesn't works with the file type column,
		// don't load file types.
		if (isset($columns[STATISTICS_DIMENSION_FILE_TYPE])) {
			$fileTypes = $statsHelper->getFileTypeString();
		} else {
			$fileTypes = null;
		}

		// Metric type will be presented in header, remove if any.
		if (isset($columns[STATISTICS_DIMENSION_METRIC_TYPE])) unset($columns[STATISTICS_DIMENSION_METRIC_TYPE]);

		$reportTemplate = $request->getUserVar('reportTemplate');

		import('controllers.statistics.form.ReportGeneratorForm');
		$reportGeneratorForm = new ReportGeneratorForm($columns, $optionalColumns,
			$objects, $fileTypes, $metricType, $defaultReportTemplates, $reportTemplate);

		return $reportGeneratorForm;
	}
}

?>
