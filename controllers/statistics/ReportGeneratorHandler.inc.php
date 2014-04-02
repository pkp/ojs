<?php

/**
 * @file controllers/statistics/ReportGeneratorHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportGeneratorHandler
 * @ingroup controllers_statistics
 *
 * @brief Handle requests for report generator functions.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class ReportGeneratorHandler extends Handler {
	/**
	 * Constructor
	 **/
	function ReportGeneratorHandler() {
		parent::Handler();
		$this->addRoleAssignment(
			array(ROLE_ID_JOURNAL_MANAGER),
			array('fetchReportGenerator', 'saveReportGenerator', 'fetchArticlesInfo', 'fetchRegions'));
	}

	/**
	* Fetch form to generate custom reports.
	* @param $args array
	* @param $request Request
	*/
	function fetchReportGenerator(&$args, &$request) {
		$this->setupTemplate();
		$reportGeneratorForm =& $this->_getReportGeneratorForm($request);
		$reportGeneratorForm->initData($request);

		$formContent = $reportGeneratorForm->fetch($request);

		$json = new JSONMessage(true);
		if ($request->getUserVar('refreshForm')) {
			$json->setEvent('refreshForm', $formContent);
		} else {
			$json->setContent($formContent);
		}

		return $json->getString();
	}

	/**
	 * Save form to generate custom reports.
	 * @param $args array
	 * @param $request Request
	 */
	function saveReportGenerator(&$args, &$request) {
		$this->setupTemplate();

		$reportGeneratorForm =& $this->_getReportGeneratorForm($request);
		$reportGeneratorForm->readInputData();
		$json = new JSONMessage(true);
		if ($reportGeneratorForm->validate()) {
			$reportUrl = $reportGeneratorForm->execute($request);
			$json->setAdditionalAttributes(array('reportUrl' => $reportUrl));
		} else {
			$json->setStatus(false);
		}

		return $json->getString();
	}

	/**
	 * Fetch articles title and id from
	 * the passed request variable issue id.
	 * @param $args array
	 * @param $request Request
	 * @return string JSON response
	 */
	function fetchArticlesInfo(&$args, &$request) {
		$this->validate();

		$issueId = (int) $request->getUserVar('issueId');
		import('lib.pkp.classes.core.JSONMessage');
		$json = new JSONMessage();

		if (!$issueId) {
			$json->setStatus(false);
		} else {
			$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$articles =& $articleDao->getPublishedArticles($issueId);
			$articlesInfo = array();
			foreach ($articles as $article) {
				$articlesInfo[] = array('id' => $article->getId(), 'title' => $article->getLocalizedTitle());
			}

			$json->setContent($articlesInfo);
		}

		return $json->getString();
	}

	/**
	* Fetch regions from the passed request
	* variable country id.
	* @param $args array
	* @param $request Request
	* @return string JSON response
	*/
	function fetchRegions(&$args, &$request) {
		$this->validate();

		$countryId = (string) $request->getUserVar('countryId');
		import('lib.pkp.classes.core.JSONMessage');
		$json = new JSONMessage(false);

		if ($countryId) {
			$geoLocationTool =& StatisticsHelper::getGeoLocationTool();
			if ($geoLocationTool) {
				$regions = $geoLocationTool->getRegions($countryId);
				if (!empty($regions)) {
					$regionsData = array();
					foreach ($regions as $id => $name) {
						$regionsData[] = array('id' => $id, 'name' => $name);
					}
					$json->setStatus(true);
					$json->setContent($regionsData);
				}
			}
		}

		return $json->getString();
	}

	/**
	 * @see PKPHandler::setupTemplate()
	 */
	function setupTemplate() {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OJS_MANAGER,
			LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);
	}


	//
	// Private helper methods.
	//
	/**
	 * Get report generator form object.
	 * @return ReportGeneratorForm
	 */
	function &_getReportGeneratorForm(&$request) {
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		$metricType = $request->getUserVar('metricType');
		if (!$metricType) {
			$metricType = $journal->getDefaultMetricType();
		}

		$reportPlugin =& StatisticsHelper::getReportPluginByMetricType($metricType);
		if (!is_scalar($metricType) || !$reportPlugin) {
			fatalError('Invalid metric type.');
		}

		$columns = $reportPlugin->getColumns($metricType);
		$columns = array_flip(array_intersect(array_flip(StatisticsHelper::getColumnNames()), $columns));

		$objects = $reportPlugin->getObjectTypes($metricType);
		$objects = array_flip(array_intersect(array_flip(StatisticsHelper::getObjectTypeString()), $objects));

		$defaultReportTemplates = $reportPlugin->getDefaultReportTemplates($metricType);

		// If the report plugin doesn't works with the file type column,
		// don't load file types.
		if (isset($columns[STATISTICS_DIMENSION_FILE_TYPE])) {
			$fileTypes = StatisticsHelper::getFileTypeString();
		} else {
			$fileTypes = null;
		}

		// Metric type will be presented in header, remove if any.
		if (isset($columns[STATISTICS_DIMENSION_METRIC_TYPE])) unset($columns[STATISTICS_DIMENSION_METRIC_TYPE]);

		$reportTemplate = $request->getUserVar('reportTemplate');

		import('controllers.statistics.form.ReportGeneratorForm');
		$reportGeneratorForm = new ReportGeneratorForm($columns,
			$objects, $fileTypes, $metricType, $defaultReportTemplates, $reportTemplate);

		return $reportGeneratorForm;
	}
}

?>
