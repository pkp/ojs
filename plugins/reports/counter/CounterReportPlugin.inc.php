<?php

/**
 * @file plugins/reports/counter/CounterReportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CounterReportPlugin
 * @ingroup plugins_reports_counter
 *
 * @brief Counter report plugin
 */

define('OJS_METRIC_TYPE_LEGACY_COUNTER', 'ojs::legacyCounterPlugin');

define('COUNTER_CLASS_SUFFIX', '.inc.php');

import('plugins.reports.counter.classes.CounterReport');

use APP\notification\NotificationManager;
use APP\template\TemplateManager;
use PKP\notification\PKPNotification;

use PKP\plugins\ReportPlugin;
use PKP\statistics\PKPStatisticsHelper;

class CounterReportPlugin extends ReportPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success) {
            $this->addLocaleData();
        }
        return $success;
    }

    /**
     * @see PKPPlugin::getName()
     */
    public function getName()
    {
        return 'CounterReportPlugin';
    }

    /**
     * @see PKPPlugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.reports.counter');
    }

    /**
     * @see PKPPlugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.reports.counter.description');
    }

    /**
     * Get the latest counter release
     *
     * @return string
     */
    public function getCurrentRelease()
    {
        return '4.1';
    }

    /**
     * List the valid reports
     * Must exist in the report path as {Report}_r{release}.inc.php
     *
     * @return array multidimentional array release => array( report => reportClassName )
     */
    public function getValidReports()
    {
        $reports = [];
        $prefix = "{$this->getReportPath()}/" . COUNTER_CLASS_PREFIX;
        $suffix = COUNTER_CLASS_SUFFIX;
        foreach (glob($prefix . '*' . $suffix) as $file) {
            $report_name = substr($file, strlen($prefix), -strlen($suffix));
            $report_class_file = substr($file, strlen($prefix), -strlen(COUNTER_CLASS_SUFFIX));
            $reports[$report_name] = $report_class_file;
        }
        return $reports;
    }

    /**
     * Get a COUNTER Reporter Object
     * Must exist in the report path as {Report}_r{release}.inc.php
     *
     * @param string $report Report name
     * @param string $release release identifier
     *
     * @return object
     */
    public function getReporter($report, $release)
    {
        $reportClass = COUNTER_CLASS_PREFIX . $report;
        $reportClasspath = 'plugins.reports.counter.classes.reports.';
        $reportPath = str_replace('.', '/', $reportClasspath);
        if (file_exists($reportPath . $reportClass . COUNTER_CLASS_SUFFIX)) {
            import($reportPath . $reportClass);
            $reporter = new $reportClass($release);
            return $reporter;
        }
        return false;
    }

    /**
     * Get classes path for this plugin.
     *
     * @return string Path to plugin's classes
     */
    public function getClassPath()
    {
        return "{$this->getPluginPath()}/classes";
    }


    /**
     * Return the report path
     *
     * @return string
     */
    public function getReportPath()
    {
        return "{$this->getClassPath()}/reports";
    }

    /**
     * @see ReportPlugin::display()
     */
    public function display($args, $request)
    {
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
                    $r3jr1 = new LegacyJR1($this);
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
                                $reportItems = $reporter->getReportItems([], [PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH => ['from' => $year . '01', 'to' => $year . '12']]);
                                if ($reportItems) {
                                    $xmlResult = $reporter->createXML($reportItems);
                                    if ($xmlResult) {
                                        header('content-type: text/xml');
                                        header('content-disposition: attachment; filename=counter-' . $release . '-' . $report . '-' . date('Ymd') . '.xml');
                                        echo $xmlResult;
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
                    // no break
                default:
                    if (!$errormessage) {
                        $errormessage = __('plugins.reports.counter.error.badRequest');
                    }
                    $user = $request->getUser();
                    $notificationManager = new NotificationManager();
                    $notificationManager->createTrivialNotification($user->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => $errormessage]);
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
        if (!empty($legacyYears)) {
            $templateManager->assign('legacyYears', $legacyYears);
        }
        $templateManager->assign([
            'breadcrumbs' => [
                [
                    'id' => 'reports',
                    'name' => __('manager.statistics.reports'),
                    'url' => $request->getRouter()->url($request, null, 'stats', 'reports'),
                ],
                [
                    'id' => 'counter',
                    'name' => __('plugins.reports.counter')
                ],
            ],
            'pageTitle', __('plugins.reports.counter')
        ]);
        $templateManager->display($this->getTemplateResource('index.tpl'));
    }

    /**
    * Get the years for which log entries exist in the DB.
    *
    * @param bool $useLegacyStats Use the old counter plugin data.
    *
    * @return array
    */
    public function _getYears($useLegacyStats = false)
    {
        if ($useLegacyStats) {
            $metricType = OJS_METRIC_TYPE_LEGACY_COUNTER;
            $filter = [];
        } else {
            $metricType = METRIC_TYPE_COUNTER;
            $filter = [PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_SUBMISSION_FILE];
        }
        $metricsDao = DAORegistry::getDAO('MetricsDAO'); /** @var MetricsDAO $metricsDao */
        $results = $metricsDao->getMetrics($metricType, [PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH], $filter);
        $years = [];
        foreach ($results as $record) {
            $year = substr($record['month'], 0, 4);
            if (in_array($year, $years)) {
                continue;
            }
            $years[] = $year;
        }
        return $years;
    }
}
