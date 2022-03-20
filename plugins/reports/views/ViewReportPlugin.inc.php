<?php

/**
 * @file plugins/reports/views/ViewReportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ViewReportPlugin
 * @ingroup plugins_reports_views
 *
 * @brief View report plugin
 */

use APP\facades\Repo;
use APP\submission\Submission;
use PKP\plugins\ReportPlugin;

class ViewReportPlugin extends ReportPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        return $success;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'ViewReportPlugin';
    }

    public function getDisplayName()
    {
        return __('plugins.reports.views.displayName');
    }

    public function getDescription()
    {
        return __('plugins.reports.views.description');
    }

    /**
     * @copydoc ReportPlugin::display()
     */
    public function display($args, $request)
    {
        $context = $request->getContext();

        $columns = [
            __('plugins.reports.views.articleId'),
            __('plugins.reports.views.articleTitle'),
            __('issue.issue'),
            __('plugins.reports.views.datePublished'),
            __('plugins.reports.views.abstractViews'),
            __('plugins.reports.views.galleyViews'),
        ];
        $galleyLabels = [];
        $galleyViews = [];
        $galleyViewTotals = [];
        $abstractViewCounts = [];
        $issueIdentifications = [];
        $issueDatesPublished = [];
        $articleTitles = [];
        $articleIssueIdentificationMap = [];

        $submissions = Repo::submission()->getMany(
            Repo::submission()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByStatus([Submission::STATUS_PUBLISHED])
        );
        foreach ($submissions as $submission) {
            $articleId = $submission->getId();
            $issueId = $submission->getCurrentPublication()->getData('issueId');
            $articleTitles[$articleId] = PKPString::regexp_replace("/\r|\n/", '', $submission->getLocalizedTitle());

            // Store the abstract view count
            $abstractViewCounts[$articleId] = $submission->getViews();
            // Make sure we get the issue identification
            $articleIssueIdentificationMap[$articleId] = $issueId;
            if (!isset($issueIdentifications[$issueId])) {
                $issue = Repo::issue()->get($issueId);
                $issueIdentifications[$issueId] = $issue->getIssueIdentification();
                $issueDatesPublished[$issueId] = $issue->getDatePublished();
                unset($issue);
            }

            // For each galley, store the label and the count
            $galleys = $submission->getGalleys();
            $galleyViews[$articleId] = [];
            $galleyViewTotals[$articleId] = 0;
            foreach ($galleys as $galley) {
                $label = $galley->getGalleyLabel();
                $i = array_search($label, $galleyLabels);
                if ($i === false) {
                    $i = count($galleyLabels);
                    $galleyLabels[] = $label;
                }

                // Make sure the array is the same size as in previous iterations
                //  so that we insert values into the right location
                $galleyViews[$articleId] = array_pad($galleyViews[$articleId], count($galleyLabels), '');

                $views = $galley->getViews();
                $galleyViews[$articleId][$i] = $views;
                $galleyViewTotals[$articleId] += $views;
            }
        }

        header('content-type: text/comma-separated-values');
        header('content-disposition: attachment; filename=views-' . date('Ymd') . '.csv');
        $fp = fopen('php://output', 'wt');
        //Add BOM (byte order mark) to fix UTF-8 in Excel
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, array_merge($columns, $galleyLabels));

        ksort($abstractViewCounts);
        foreach ($abstractViewCounts as $articleId => $abstractViewCount) {
            $values = [
                $articleId,
                $articleTitles[$articleId],
                $issueIdentifications[$articleIssueIdentificationMap[$articleId]],
                date('Y-m-d', strtotime($issueDatesPublished[$articleIssueIdentificationMap[$articleId]])),
                $abstractViewCount,
                $galleyViewTotals[$articleId]
            ];

            fputcsv($fp, array_merge($values, $galleyViews[$articleId]));
        }

        fclose($fp);
    }
}
