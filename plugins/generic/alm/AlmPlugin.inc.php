<?php

/**
 * @file plugins/generic/alm/AlmPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AlmPlugin
 * @ingroup plugins_generic_alm
 *
 * @brief Alm plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.webservice.WebService');
import('lib.pkp.classes.core.JSONManager');

DEFINE('ALM_BASE_URL', 'http://pkp-alm.lib.sfu.ca/');
DEFINE('ALM_API_URL', 'http://pkp-alm.lib.sfu.ca/api/v3/articles/');

class AlmPlugin extends GenericPlugin {

	/** @var $apiKey string */
	var $_apiKey;


	/**
	 * @see LazyLoadPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;

		$application =& Application::getApplication();
		$request =& $application->getRequest();
		$router =& $request->getRouter();
		$context = $router->getContext($request);

		if ($success && $context) {
			$apiKey = $this->getSetting($context->getId(), 'apiKey');
			if ($apiKey) {
				$this->_apiKey = $apiKey;
				HookRegistry::register('TemplateManager::display',array(&$this, 'templateManagerCallback'));
				HookRegistry::register('Templates::Article::MoreInfo',array(&$this, 'articleMoreInfoCallback'));
				HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
			}
		}
		return $success;
	}

	/**
	 * @see LazyLoadPlugin::getName()
	 */
	function getName() {
		return 'almplugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.alm.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.alm.description');
	}

	/**
	* @see GenericPlugin::getManagementVerbs()
	*/
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.alm.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * @see GenericPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$message = NOTIFICATION_TYPE_SUCCESS;
						$messageParams = array('contents' => __('plugins.generic.alm.settings.saved'));
						return false;
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
			return false;
		}
	}

	/**
	 * Template manager hook callback.
	 * @param $hookName string
	 * @param $params array
	 */
	function templateManagerCallback($hookName, $params) {
		if ($this->getEnabled()) {
			$templateMgr =& $params[0];
			$template = $params[1];
			if ($template == 'article/article.tpl') {
				$additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
				$baseImportPath = Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR;
				$scriptImportString = '<script language="javascript" type="text/javascript" src="';

				$d3import = $scriptImportString . $baseImportPath .
					'js/d3.v3.min.js"></script>';
				$controllerImport = $scriptImportString . $baseImportPath .
					'js/alm.js"></script>';

				$templateMgr->assign('additionalHeadData', $additionalHeadData . "\n" . $d3import . "\n" . $controllerImport);

				$templateMgr->addStyleSheet($baseImportPath . 'css/bootstrap.tooltip.min.css');
				$templateMgr->addStyleSheet($baseImportPath . 'css/almviz.css');
			}
		}
	}

	/**
	 * Template manager filter callback. Adds the article
	 * level metrics markup, if any stats.
	 * @param $output string The rendered page markup.
	 * @param $smarty Smarty
	 * @return boolean
	 */
	function articleMoreInfoCallback($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

		$article =& $smarty->get_template_vars('article');
		assert(is_a($article, 'PublishedArticle'));
		$articleId = $article->getId();

		$downloadStats = $this->_getDownloadStats($request, $articleId);
		// We use a helper method to aggregate stats instead of retrieving the needed
		// aggregation directly from metrics DAO because we need a custom array format.
		list($totalHtml, $totalPdf, $byMonth, $byYear) = $this->_aggregateDownloadStats($downloadStats);
		$downloadJson = $this->_buildDownloadStatsJson($totalHtml, $totalPdf, $byMonth, $byYear);

		$almStatsJson = $this->_getAlmStats($article);
		$json = @json_decode($almStatsJson); // to be used to check for errors
		if (!$almStatsJson || property_exists($json, 'error')) {
			// The ALM stats answer comes with needed article info,
			// so we build this information if no ALM stats response.
			$almStatsJson = $this->_buildRequiredArticleInfoJson($article);
		}

		if ($downloadJson || $almStatsJson) {
			if ($almStatsJson) $smarty->assign('almStatsJson', $almStatsJson);
			if ($downloadJson) $smarty->assign('additionalStatsJson', $downloadJson);

			$baseImportPath = Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() .
				DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;
			$jqueryImportPath = $baseImportPath . 'jquery-1.10.2.min.js';
			$tooltipImportPath = $baseImportPath . 'bootstrap.tooltip.min.js';

			$smarty->assign('jqueryImportPath', $jqueryImportPath);
			$smarty->assign('tooltipImportPath', $tooltipImportPath);

			$metricsHTML = $smarty->fetch($this->getTemplatePath() . 'output.tpl');
			$output .= $metricsHTML;
		}

		return false;
	}

	/**
	 * @see PKPPlugin::getInstallSitePluginSettingsFile()
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	* @see AcronPlugin::parseCronTab()
	*/
	function callbackParseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';

		return false;
	}


	//
	// Private helper methods.
	//
	/**
	* Call web service with the given parameters
	* @param $url string
	* @param $params array GET or POST parameters
	* @param $method string (optional)
	* @return JSON or null in case of error
	*/
	function &_callWebService($url, &$params, $method = 'GET') {
		// Create a request
		if (!is_array($params)) {
			$params = array();
		}

		$params['api_key'] = $this->_apiKey;
		$webServiceRequest = new WebServiceRequest($url, $params, $method);
		// Can't strip slashes from the result, we have a JSON
		// response with escaped characters.
		$webServiceRequest->setCleanResult(false);

		// Configure and call the web service
		$webService = new WebService();
		$result =& $webService->call($webServiceRequest);

		return $result;
	}

	/**
	* Cache miss callback.
	* @param $cache Cache
	* @param $articleId int
	* @return JSON
	*/
	function _cacheMiss(&$cache) {
		$articleId = $cache->getCacheId();
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article =& $articleDao->getArticle($articleId);

		// Construct the parameters to send to the web service
		$searchParams = array(
				'info' => 'history',
		);

		// Call the web service (URL defined at top of this file)
		$resultJson =& $this->_callWebService(ALM_API_URL . 'info:doi/' . $article->getPubId('doi'), $searchParams);
		if (!$resultJson) $resultJson = false;

		$cache->setEntireCache($resultJson);
		return $resultJson;
	}

	/**
	 * Get ALM metrics for the passed
	 * article object.
	 * @param $article Article
	 * @return string JSON message
	 */
	function _getAlmStats($article) {
		$articleId = $article->getId();
		$articlePublishedDate = $article->getDatePublished();
		$cacheManager =& CacheManager::getManager();
		$cache  =& $cacheManager->getCache('alm', $articleId, array(&$this, '_cacheMiss'));

		// If the cache is older than a 1 day in first 30 days, or a week in first 6 months, or older than a month
		$daysSincePublication = floor((time() - strtotime($articlePublishedDate)) / (60 * 60 * 24));
		if ($daysSincePublication <= 30) {
			$daysToStale = 1;
		} elseif ( $daysSincePublication <= 180 ) {
			$daysToStale = 7;
		} else {
			$daysToStale = 29;
		}

		$cachedJson = false;
		// if cache is stale, save the stale results and flush the cache
		if (time() - $cache->getCacheTime() > 60 * 60 * 24 * $daysToStale) {
			$cachedJson = $cache->getContents();
			$cache->flush();
		}

		$resultJson = $cache->getContents();

		// In cases where server is down (we get a false response)
		// it is better to show an old (successful) response than nothing
		if (!$resultJson && $cachedJson) {
			$resultJson = $cachedJson;
			$cache->setEntireCache($cachedJson);
		} elseif (!$resultJson) {
			$cache->flush();
		}

		return $resultJson;
	}

	/**
	 * Get download stats for the passed article id.
	 * @param $request PKPRequest
	 * @param $articleId int
	 * @return array MetricsDAO::getMetrics() result.
	 */
	function _getDownloadStats(&$request, $articleId) {
		// Pull in download stats for each article galley.
		$request =& Application::getRequest();
		$router =& $request->getRouter();
		$context =& $router->getContext($request); /* @var $context Journal */

		$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */

		// Load the metric type constant.
		PluginRegistry::loadCategory('reports');

		// Always merge the old timed views stats with default metrics.
		$metricTypes = array(OJS_METRIC_TYPE_TIMED_VIEWS, $context->getDefaultMetricType());
		$columns = array(STATISTICS_DIMENSION_MONTH, STATISTICS_DIMENSION_FILE_TYPE);
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY, STATISTICS_DIMENSION_SUBMISSION_ID => $articleId);
		$orderBy = array(STATISTICS_DIMENSION_MONTH => STATISTICS_ORDER_ASC);

		return $metricsDao->getMetrics($metricTypes, $columns, $filter, $orderBy);
	}

	/**
	 * Aggregate stats and return data in a format
	 * that can be used to build the statistics JSON response
	 * for the article page.
	 * @param $stats array A _getDownloadStats return value.
	 * @return array
	 */
	function _aggregateDownloadStats($stats) {
		$totalHtml = 0;
		$totalPdf = 0;
		$byMonth = array();
		$byYear = array();

		if (!is_array($stats)) $stats = array();

		foreach ($stats as $record) {
			$views = $record[STATISTICS_METRIC];
			$fileType = $record[STATISTICS_DIMENSION_FILE_TYPE];
			switch($fileType) {
				case STATISTICS_FILE_TYPE_HTML:
					$totalHtml += $views;
					break;
				case STATISTICS_FILE_TYPE_PDF:
					$totalPdf += $views;
					break;
				default:
					// switch is considered a loop for purposes of continue
					continue 2;
			}
			$year = date('Y', strtotime($record[STATISTICS_DIMENSION_MONTH]. '01'));
			$month = date('n', strtotime($record[STATISTICS_DIMENSION_MONTH] . '01'));
			$yearMonth = date('Ym', strtotime($record[STATISTICS_DIMENSION_MONTH] . '01'));

			if (!isset($byYear[$year])) $byYear[$year] = array();
			if (!isset($byYear[$year][$fileType])) $byYear[$year][$fileType] = 0;
			$byYear[$year][$fileType] += $views;

			if (!isset($byMonth[$yearMonth])) $byMonth[$yearMonth] = array();
			if (!isset($byMonth[$yearMonth][$fileType])) $byMonth[$yearMonth][$fileType] = 0;
			$byMonth[$yearMonth][$fileType] += $views;
		}

		return array($totalHtml, $totalPdf, $byMonth, $byYear);
	}

	/**
	 * Get total statistics for JSON response.
	 * @param $totalPdf int
	 * @param $totalHtml int
	 * @return array
	 */
	function _getStatsTotal($totalHtml, $totalPdf) {
		$metrics = array('pdf' => $totalPdf, 'html' => $totalHtml);
		return array_merge($metrics, $this->_getAlmMetricsTemplate());
	}

	/**
	 * Get statistics by time dimension (month or year)
	 * for JSON response.
	 * @param array the download statistics in an array by dimension
	 * @param string month | year
	 */
	function _getStatsByTime($data, $dimension) {
		switch ($dimension) {
			case 'month':
				$isMonthDimension = true;
				break;
			case 'year':
				$isMonthDimension = false;
				break;
			default:
				return null;
		}

		if (count($data)) {
			$byTime = array();
			foreach ($data as $date => $fileTypes) {
				// strtotime sometimes fails on just a year (YYYY) (it treats it as a time (HH:mm))
				// and sometimes on YYYYMM
				// So make sure $date has all 3 parts
				$date = str_pad($date, 8, "01");
				$year = date('Y', strtotime($date));
				if ($isMonthDimension) {
					$month = date('n', strtotime($date));
				}
				$pdfViews = isset($fileTypes[STATISTICS_FILE_TYPE_PDF])? $fileTypes[STATISTICS_FILE_TYPE_PDF] : 0;
				$htmlViews = isset($fileTypes[STATISTICS_FILE_TYPE_HTML])? $fileTypes[STATISTICS_FILE_TYPE_HTML] : 0;

				$partialStats = array(
					'year' => $year,
					'pdf' => $pdfViews,
					'html' => $htmlViews,
					'total' => $pdfViews + $htmlViews
				);

				if ($isMonthDimension) {
					$partialStats['month'] = $month;
				}

				$byTime[] = array_merge($partialStats, $this->_getAlmMetricsTemplate());
			}
		} else {
			$byTime = null;
		}

		return $byTime;
	}

	/**
	 * Get template for ALM metrics JSON response.
	 * @return array
	 */
	function _getAlmMetricsTemplate() {
		return array(
			'shares' => null,
			'groups' => null,
			'comments' => null,
			'likes' => null,
			'citations' => 0
		);
	}

	/**
	 * Build article stats JSON response based
	 * on parameters returned from _aggregateStats().
	 * @param $totalHtml array
	 * @param $totalPdf array
	 * @param $byMonth array
	 * @param $byYear array
	 * @return string JSON response
	 */
	function _buildDownloadStatsJson($totalHtml, $totalPdf, $byMonth, $byYear) {
		$response = array(
			'name' => 'ojsViews',
			'display_name' => __('plugins.generic.alm.thisJournal'),
			'events_url' => null,
			'metrics' => $this->_getStatsTotal($totalHtml, $totalPdf),
			'by_day' => null,
			'by_month' => $this->_getStatsByTime($byMonth, 'month'),
			'by_year' => $this->_getStatsByTime($byYear, 'year')
		);

		// Encode the object.
		$jsonManager = new JSONManager();
		return $jsonManager->encode($response);
	}

	/**
	 * Build the required article information for the
	 * metrics visualization.
	 * @param $article PublishedArticle
	 * @return string JSON response
	 */
	function _buildRequiredArticleInfoJson($article) {
		if ($article->getDatePublished()) {
			$datePublished = $article->getDatePublished();
		} else {
			// Sometimes there is no article getDatePublished, so fallback on the issue's
			$issueDao =& DAORegistry::getDAO('IssueDAO');  /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueByArticleId($article->getId(), $article->getJournalId());
			$datePublished = $issue->getDatePublished();
		}
		$response = array(
			array(
				'publication_date' => date('c', strtotime($datePublished)),
				'doi' => $article->getPubId('doi'),
				'title' => $article->getLocalizedTitle(),
				'sources' => array()
		));

		$jsonManager = new JSONManager();
		return $jsonManager->encode($response);
	}
}

?>
