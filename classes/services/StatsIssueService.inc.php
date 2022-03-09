<?php

/**
 * @file classes/services/StatsIssueService.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueService
 * @ingroup services
 *
 * @brief Helper class that encapsulates issue statistics business logic
 */

namespace APP\services;

use APP\core\Application;
use APP\statistics\StatisticsHelper;
use PKP\core\PKPString;
use PKP\plugins\HookRegistry;

class StatsIssueService
{
    /**
     * A callback to be used with array_map() to return all
     * issue IDs from the records.
     */
    public function filterIssueIds(object $record): int
    {
        return $record->issue_id;
    }

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
     * Get total count of issues matching the given parameters
     */
    public function getTotalCount(array $args): int
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        $metricsQB = $this->getQueryBuilder($args);

        HookRegistry::call('StatsIssue::getTotalCount::queryBuilder', [&$metricsQB, $args]);

        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID];
        $metricsQB = $metricsQB->getSum($groupBy);

        return $metricsQB->get()->count();
    }

    /**
     * Get total metrics for every issue, ordered by metrics, return just the requested offset
     */
    public function getTotalMetrics(array $args): array
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        $metricsQB = $this->getQueryBuilder($args);

        HookRegistry::call('StatsIssue::getTotalMetrics::queryBuilder', [&$metricsQB, $args]);

        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID];
        $metricsQB = $metricsQB->getSum($groupBy);

        $args['orderDirection'] === StatisticsHelper::STATISTICS_ORDER_ASC ? 'asc' : 'desc';
        $metricsQB->orderBy(StatisticsHelper::STATISTICS_METRIC, $args['orderDirection']);

        if (isset($args['count'])) {
            $metricsQB->limit($args['count']);
            if (isset($args['offset'])) {
                $metricsQB->offset($args['offset']);
            }
        }

        return $metricsQB->get()->toArray();
    }

    /**
     * Get metrics by type (toc, issue galley) for an issue
     * Assumes that the issue ID is provided in parameters
     */
    public function getMetricsByType(array $args): array
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        $metricsQB = $this->getQueryBuilder($args);

        HookRegistry::call('StatsIssue::getMetricsByType::queryBuilder', [&$metricsQB, $args]);

        // get toc and galley views for the issue
        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE];
        $metricsQB = $metricsQB->getSum($groupBy);
        return $metricsQB->get()->toArray();
    }

    /**
     * Get the sum of a set of metrics broken down by day or month
     *
     * @param string $timelineInterval STATISTICS_DIMENSION_MONTH or STATISTICS_DIMENSION_DAY
     * @param array $args Filter the records to include. See self::getQueryBuilder()
     *
     */
    public function getTimeline(string $timelineInterval, array $args = []): array
    {
        $defaultArgs = array_merge($this->getDefaultArgs(), ['orderDirection' => StatisticsHelper::STATISTICS_ORDER_ASC]);
        $args = array_merge($defaultArgs, $args);
        $timelineQB = $this->getQueryBuilder($args);

        HookRegistry::call('StatsIssue::::getTimeline::queryBuilder', [&$timelineQB, $args]);

        $timelineQO = $timelineQB
            ->getSum([$timelineInterval])
            ->orderBy($timelineInterval, $args['orderDirection']);

        $result = $timelineQO->get();

        $dateValues = [];
        foreach ($result as $row) {
            $row = (array) $row;
            $date = $row[$timelineInterval];
            if ($timelineInterval === StatisticsHelper::STATISTICS_DIMENSION_MONTH) {
                $date = substr($date, 0, 7);
            }
            $dateValues[$date] = (int) $row['metric'];
        }

        $timeline = $this->getEmptyTimelineIntervals($args['dateStart'], $args['dateEnd'], $timelineInterval);

        $timeline = array_map(function ($entry) use ($dateValues) {
            foreach ($dateValues as $date => $value) {
                if ($entry['date'] === $date) {
                    $entry['value'] = $value;
                    break;
                }
            }
            return $entry;
        }, $timeline);

        return $timeline;
    }

    /**
     * Get all time segments (months or days) between the start and end date
     * with empty values.
     *
     * @param string $timelineInterval STATISTICS_DIMENSION_MONTH or STATISTICS_DIMENSION_DAY
     *
     * @return array of time segments in ASC order
     */
    public function getEmptyTimelineIntervals(string $startDate, string $endDate, string $timelineInterval): array
    {
        if ($timelineInterval === StatisticsHelper::STATISTICS_DIMENSION_MONTH) {
            $dateFormat = 'Y-m';
            $labelFormat = 'F Y';
            $interval = 'P1M';
        } elseif ($timelineInterval === StatisticsHelper::STATISTICS_DIMENSION_DAY) {
            $dateFormat = 'Y-m-d';
            $labelFormat = PKPString::convertStrftimeFormat(Application::get()->getRequest()->getContext()->getLocalizedDateFormatLong());
            $interval = 'P1D';
        }

        $startDate = new \DateTime($startDate);
        $endDate = new \DateTime($endDate);

        $timelineIntervals = [];
        while ($startDate->format($dateFormat) <= $endDate->format($dateFormat)) {
            $timelineIntervals[] = [
                'date' => $startDate->format($dateFormat),
                'label' => date($labelFormat, $startDate->getTimestamp()),
                'value' => 0,
            ];
            $startDate->add(new \DateInterval($interval));
        }

        return $timelineIntervals;
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
    public function getQueryBuilder(array $args = []): \APP\services\queryBuilders\StatsIssueQueryBuilder
    {
        $statsQB = new \APP\services\queryBuilders\StatsIssueQueryBuilder();
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

        HookRegistry::call('StatsIssue::queryBuilder', [&$statsQB, $args]);

        return $statsQB;
    }
}
