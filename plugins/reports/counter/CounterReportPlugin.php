<?php

/**
 * @file plugins/reports/counter/CounterReportPlugin.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CounterReportPlugin
 *
 * @brief Counter report plugin
 */

namespace APP\plugins\reports\counter;

use APP\core\Application;
use APP\core\Services;
use APP\notification\NotificationManager;
use APP\statistics\StatisticsHelper;
use APP\template\TemplateManager;
use PKP\notification\PKPNotification;
use PKP\plugins\ReportPlugin;

class CounterReportPlugin extends ReportPlugin
{
    public const COUNTER_CLASS_SUFFIX = '.php';

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
     * Must exist in the report path as {Report}_r{release}.php
     *
     * @return array multidimentional array release => array( report => reportClassName )
     */
    public function getValidReports()
    {
        $reports = [];
        $prefix = "{$this->getReportPath()}/" . classes\CounterReport::COUNTER_CLASS_PREFIX;
        $suffix = self::COUNTER_CLASS_SUFFIX;
        foreach (glob($prefix . '*' . $suffix) as $file) {
            $report_name = substr($file, strlen($prefix), -strlen($suffix));
            $report_class_file = substr($file, strlen($prefix), -strlen(self::COUNTER_CLASS_SUFFIX));
            $reports[$report_name] = $report_class_file;
        }
        return $reports;
    }

    /**
     * Get a COUNTER Reporter Object
     * Must exist in the report path as {Report}_r{release}.php
     *
     * @param string $report Report name
     * @param string $release release identifier
     *
     * @return object
     */
    public function getReporter($report, $release)
    {
        $reportClass = '\\APP\\plugins\\reports\\counter\\classes\\reports\\' . classes\CounterReport::COUNTER_CLASS_PREFIX . $report;
        return class_exists($reportClass) ? new $reportClass($release) : false;
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
        $available = $this->getValidReports();
        $years = $this->_getYears();
        if ($request->getUserVar('type')) {
            $type = (string) $request->getUserVar('type');
            $errormessage = '';
            switch ($type) {
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
                                $reportItems = $reporter->getReportItems([], ['dateStart' => $year . '-01-01', 'dateEnd' => $year . '-12-31']);
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
        $templateManager = TemplateManager::getManager();
        krsort($available);
        $templateManager->assign('pluginName', $this->getName());
        $templateManager->assign('available', $available);
        $templateManager->assign('release', $this->getCurrentRelease());
        $templateManager->assign('years', $years);
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
    * @return array
    */
    public function _getYears()
    {
        $filters = [
            'dateStart' => StatisticsHelper::STATISTICS_EARLIEST_DATE,
            'dateEnd' => date('Y-m-d', strtotime('yesterday')),
            'contextIds' => [Application::get()->getRequest()->getContext()->getId()],
            'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION_FILE]
        ];
        $metricsQB = Services::get('publicationStats')->getQueryBuilder($filters);
        $metricsQB = $metricsQB->getSum([StatisticsHelper::STATISTICS_DIMENSION_YEAR]);
        $metricsQB->orderBy(StatisticsHelper::STATISTICS_DIMENSION_YEAR, StatisticsHelper::STATISTICS_ORDER_ASC);
        $results = $metricsQB->get()->toArray();
        $years = array_map(function ($n) {
            return substr($n, 0, 4);
        }, array_column($results, 'year'));
        return $years;
    }
}
