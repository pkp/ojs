<?php

/**
 * @file plugins/reports/counter/CounterReportPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterReportPlugin
 * @ingroup plugins_reports_counter
 *
 * @brief Counter report plugin
 */

define('OJS_METRIC_TYPE_LEGACY_COUNTER', 'ojs::legacyCounterPlugin');

define('COUNTER_CLASS_SUFFIX', '.inc.php');

import('lib.pkp.classes.plugins.ReportPlugin');
import('plugins.reports.counter.classes.CounterReport');

class CounterReportPlugin extends ReportPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getLocaleFilename($locale)
	 */
	function getLocaleFilename($locale) {
		$localeFilenames = parent::getLocaleFilename($locale);
		// Add dynamic locale keys.
		foreach (glob($this->getPluginPath() . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . '*.xml') as $file) {
			if (!in_array($file, $localeFilenames)) {
				$localeFilenames[] = $file;
			}
		}
		return $localeFilenames;
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'CounterReportPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.counter');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.reports.counter.description');
	}

	/**
	 * Get the latest counter release
	 * @return string
	 */
	function getCurrentRelease() {
		return '4.1';
	}

	/**
	 * List the valid reports
	 * Must exist in the report path as {Report}_r{release}.inc.php
	 * @return array multidimentional array release => array( report => reportClassName )
	 */
	function getValidReports() {
		$reports = array();
		$prefix = $this->getReportPath().DIRECTORY_SEPARATOR.COUNTER_CLASS_PREFIX;
		$suffix = COUNTER_CLASS_SUFFIX;
		foreach (glob($prefix.'*'.$suffix) as $file) {
			$report_name = substr($file, strlen($prefix), -strlen($suffix));
			$report_class_file = substr($file, strlen($prefix), -strlen(COUNTER_CLASS_SUFFIX));
			$reports[$report_name] = $report_class_file;
		}
		return $reports;
	}

	/**
	 * Get a COUNTER Reporter Object
	 * Must exist in the report path as {Report}_r{release}.inc.php
	 * @param $report string Report name
	 * @param $release string release identifier
	 * @return object
	 */
	function getReporter($report, $release) {
		$reportClass = COUNTER_CLASS_PREFIX.$report;
		$reportClasspath = 'plugins.reports.counter.classes.reports.';
		$reportPath = str_replace('.', DIRECTORY_SEPARATOR, $reportClasspath);
		if (file_exists($reportPath.$reportClass.COUNTER_CLASS_SUFFIX)) {
			import($reportPath.$reportClass);
			$reporter = new $reportClass($release);
			return $reporter;
		}
		return false;
	}

	/**
	 * Get classes path for this plugin.
	 * @return string Path to plugin's classes
	 */
	function getClassPath() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'classes';
	}


	/**
	 * Return the report path
	 * @return string
	 */
	function getReportPath() {
		return $this->getClassPath().DIRECTORY_SEPARATOR.'reports';
	}

	/**
	 * @see ReportPlugin::display()
	 */
	function display($args, $request) {
		// We need these constants
		import('classes.statistics.StatisticsHelper');

		$available = $this->getValidReports();
		$years = $this->_getYears();
		if ($request->getUserVar('type')) {
			$type = (string) $request->getUserVar('type');
			$errormessage = '';
			switch ($type) {
				case 'report':
				case 'reportxml':
					// Legacy COUNTER Release 3
					if (!Validation::isSiteAdmin()) {
						// Legacy reports are site-wide
						Validation::redirectLogin();
					}
					import('plugins.reports.counter.classes.LegacyJR1');
					$r3jr1 = new LegacyJR1($this->getTemplatePath());
					$r3jr1->display($request);
					return;
				case 'fetch':
					// Modern COUNTER Releases
					// must provide a release, report, and year parameter
					$release = $request->getUserVar('release');
					$report = $request->getUserVar('report');
					$year = $request->getUserVar('year');
					if ($release && $report && $year) {
						// release, report and year parameters must be sane
						if ($release == $this->getCurrentRelease() && isset($available[$report]) && in_array($year, $years)) {
							// try to get the report
							$reporter = $this->getReporter($report, $release);
							if ($reporter) {
								// default report parameters with a yearlong range
								$reportItems = $reporter->getReportItems(array(), array(STATISTICS_DIMENSION_MONTH => array('from' => $year.'01', 'to' => $year.'12')));
								if ($reportItems) {
									$xmlResult = $reporter->createXML($reportItems);
									if ($xmlResult) {
										header('content-type: text/xml');
										header('content-disposition: attachment; filename=counter-'. $release . '-' . $report . '-' . date('Ymd') . '.xml');
										print $xmlResult;
										return;
									} else {
										$errormessage = __('plugins.reports.counter.error.noXML');
									}
								} else {
									$errormessage = __('plugins.reports.counter.error.noResults');
								}
							}
						}
					}
					// fall through to default case with error message
					if (!$errormessage) {
						$errormessage = __('plugins.reports.counter.error.badParameters');
					}
				default:
					if (!$errormessage) {
						$errormessage = __('plugins.reports.counter.error.badRequest');
					}
					$user = $request->getUser();
					import('classes.notification.NotificationManager');
					$notificationManager = new NotificationManager();
					$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => $errormessage));
			}
		}
		$legacyYears = $this->_getYears(true);
		$templateManager = TemplateManager::getManager();
		krsort($available);
		$templateManager->assign('pluginName', $this->getName());
		$templateManager->assign('available', $available);
		$templateManager->assign('release', $this->getCurrentRelease());
		$templateManager->assign('years', $years);
		// legacy reports are site-wide, so only site admins have access
		$templateManager->assign('showLegacy', Validation::isSiteAdmin());
		if (!empty($legacyYears)) $templateManager->assign('legacyYears', $legacyYears);
		$templateManager->display($this->getTemplateResource('index.tpl'));
	}

	/**
	* Get the years for which log entries exist in the DB.
	* @param $useLegacyStats boolean Use the old counter plugin data.
	* @return array
	*/
	function _getYears($useLegacyStats = false) {
		if ($useLegacyStats) {
			$metricType = OJS_METRIC_TYPE_LEGACY_COUNTER;
			$filter = array();
		} else {
			$metricType = OJS_METRIC_TYPE_COUNTER;
			$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_SUBMISSION_FILE);
		}
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$results = $metricsDao->getMetrics($metricType, array(STATISTICS_DIMENSION_MONTH), $filter);
		$years = array();
		foreach($results as $record) {
			$year = substr($record['month'], 0, 4);
			if (in_array($year, $years)) continue;
			$years[] = $year;
		}
		return $years;
	}

}


