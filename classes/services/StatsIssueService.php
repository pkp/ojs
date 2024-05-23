<?php

/**
 * @file classes/services/StatsIssueService.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueService
 *
 * @ingroup services
 *
 * @brief Helper class that encapsulates issue statistics business logic
 */

namespace APP\services;

use APP\core\Application;
use APP\services\queryBuilders\StatsIssueQueryBuilder;
use APP\statistics\StatisticsHelper;
use PKP\plugins\Hook;
use PKP\services\PKPStatsServiceTrait;

class StatsIssueService
{
    use PKPStatsServiceTrait;

    /**
     * A callback to be used with array_filter() to return records for
     * the TOC views.
     */
    public function filterRecordTOC(object $record): bool
    {
        return $record->assoc_type == Application::ASSOC_TYPE_ISSUE;
    }

    /**
     * A callback to be used with array_filter() to return records for
     * the issue galley views.
     */
    public function filterRecordIssueGalley(object $record): bool
    {
        return $record->assoc_type == Application::ASSOC_TYPE_ISSUE_GALLEY;
    }

    /**
     * Get a count of all issues with stats that match the request arguments
     */
    public function getCount(array $args): int
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        unset($args['count']);
        unset($args['offset']);
        $metricsQB = $this->getQueryBuilder($args);

        Hook::call('StatsIssue::getCount::queryBuilder', [&$metricsQB, $args]);

        return $metricsQB->getIssueIds()->getCountForPagination();
    }

    /**
     * Get the issues with total stats that match the request arguments
     */
    public function getTotals(array $args): array
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        $metricsQB = $this->getQueryBuilder($args);

        Hook::call('StatsIssue::getTotals::queryBuilder', [&$metricsQB, $args]);

        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID];
        $metricsQB = $metricsQB->getSum($groupBy);

        $orderDirection = $args['orderDirection'] === StatisticsHelper::STATISTICS_ORDER_ASC ? 'asc' : 'desc';
        $metricsQB->orderBy(StatisticsHelper::STATISTICS_METRIC, $orderDirection);
        return $metricsQB->get()->toArray();
    }

    /**
     * Get metrics by type (toc, issue galley) for an issue
     * Assumes that the issue ID is provided in parameters
     */
    public function getTotalsByType(int $issueId, int $contextId, ?string $dateStart, ?string $dateEnd): array
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = [
            'issueIds' => [$issueId],
            'contextIds' => [$contextId],
            'dateStart' => $dateStart ?? $defaultArgs['dateStart'],
            'dateEnd' => $dateEnd ?? $defaultArgs['dateEnd'],
        ];
        $metricsQB = $this->getQueryBuilder($args);

        Hook::call('StatsIssue::getTotalsByType::queryBuilder', [&$metricsQB, $args]);

        // get toc and galley views for the issue
        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE];
        $metricsQB = $metricsQB->getSum($groupBy);
        $metricsByType = $metricsQB->get()->toArray();

        $tocViews = $issueGalleyViews = 0;
        $tocRecord = array_filter($metricsByType, [$this, 'filterRecordTOC']);
        if (!empty($tocRecord)) {
            $tocViews = (int) current($tocRecord)->metric;
        }
        $issueGalleyRecord = array_filter($metricsByType, [$this, 'filterRecordIssueGalley']);
        if (!empty($issueGalleyRecord)) {
            $issueGalleyViews = current($issueGalleyRecord)->metric;
        }

        return [
            'toc' => $tocViews,
            'galley' => $issueGalleyViews,
        ];
    }

    /**
     * Get default parameters
     */
    public function getDefaultArgs(): array
    {
        return [
            'dateStart' => StatisticsHelper::STATISTICS_EARLIEST_DATE,
            'dateEnd' => date('Y-m-d', strtotime('yesterday')),

            // Require a context to be specified to prevent unwanted data leakage
            // if someone forgets to specify the context.
            'contextIds' => [\PKP\core\PKPApplication::CONTEXT_ID_NONE],
        ];
    }

    /**
     * Get a QueryBuilder object with the passed args
     */
    public function getQueryBuilder(array $args = []): StatsIssueQueryBuilder
    {
        $statsQB = new StatsIssueQueryBuilder();
        $statsQB
            ->filterByContexts($args['contextIds'])
            ->before($args['dateEnd'])
            ->after($args['dateStart']);

        if (!empty(($args['issueIds']))) {
            $statsQB->filterByIssues($args['issueIds']);
        }

        if (!empty(($args['issueGalleyIds']))) {
            $statsQB->filterByIssueGalleys($args['issueGalleyIds']);
        }

        if (!empty($args['assocTypes'])) {
            $statsQB->filterByAssocTypes($args['assocTypes']);
        }

        if (isset($args['count'])) {
            $statsQB->limit($args['count']);
            if (isset($args['offset'])) {
                $statsQB->offset($args['offset']);
            }
        }

        Hook::call('StatsIssue::queryBuilder', [&$statsQB, $args]);

        return $statsQB;
    }
}
