<?php

/**
 * @file pages/dashboard/DashboardHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DashboardHandler
 *
 * @ingroup pages_dashboard
 *
 * @brief Handle requests for user's dashboard.
 */

namespace APP\pages\dashboard;

use APP\components\forms\dashboard\SubmissionFilters;
use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\pages\dashboard\PKPDashboardHandler;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;

class DashboardHandler extends PKPDashboardHandler
{
    /**
     * Setup variables for the template
     *
     * @param Request $request
     */
    public function setupIndex($request)
    {
        parent::setupIndex($request);

        $templateMgr = TemplateManager::getManager($request);

        // OJS specific, might need to be adjusted for OMP/OPS
        $context = $request->getContext();

        $paymentManager = Application::get()->getPaymentManager($context);

        $pageInitConfig = $templateMgr->getState('pageInitConfig');
        $pageInitConfig['publicationSettings']['submissionPaymentsEnabled'] = $paymentManager->publicationEnabled();

        $pageInitConfig['recommendations'] = ReviewerRecommendation::query()
            ->withContextId($context->getId())
            ->get()
            ->select(['reviewerRecommendationId', 'status', 'value', 'title'])
            ->values()
            ->toArray();
        $pageInitConfig['publicationSettings']['countIssues'] = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getCount();
        $pageInitConfig['publicationSettings']['issuePublishedStatus'] = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getQueryBuilder()
            ->pluck('published', Repo::issue()->dao->primaryKeyColumn)
            ->toArray();

        $templateMgr->setState(['pageInitConfig' => $pageInitConfig]);
    }


    protected function getSubmissionFiltersForm($userRoles, $context)
    {
        $sections = Repo::section()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();

        $categories = Repo::category()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();

        return new SubmissionFilters(
            $context,
            $userRoles,
            $sections,
            $categories
        );
    }
}
