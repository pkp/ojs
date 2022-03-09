<?php

/**
 * @file classes/services/queryBuilders/StatsIssueQueryBuilder.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueQueryBuilder
 * @ingroup query_builders
 *
 * @brief Helper class to construct a query to fetch issue stats records from the
 *  metrics_issue table.
 */

namespace APP\services\queryBuilders;

use APP\core\Application;
use APP\statistics\StatisticsHelper;
use Illuminate\Support\Facades\DB;
use PKP\plugins\HookRegistry;
use PKP\services\queryBuilders\PKPStatsQueryBuilder;

class StatsIssueQueryBuilder extends PKPStatsQueryBuilder
{
    /** Include records for one of these object types: ASSOC_TYPE_ISSUE, ASSOC_TYPE_ISSUE_GALLEY */
    protected array $assocTypes = [];

    /** Include records for these issues */
    protected array $issueIds = [];

    /** Include records for these issues galleys */
    protected array $issueGalleyIds = [];

    /**
     * Set the issues to get records for
     */
    public function filterByIssues(array|int $issueIds): self
    {
        $this->issueIds = is_array($issueIds) ? $issueIds : [$issueIds];
        return $this;
    }

    /**
     * Set the issues to get records for
     */
    public function filterByIssueGalleys(array|int $issueGalleyIds): self
    {
        $this->issueGalleyIds = is_array($issueGalleyIds) ? $issueGalleyIds : [$issueGalleyIds];
        return $this;
    }

    /**
     * Set the assocTypes to get records for
     */
    public function filterByAssocTypes(array|int $assocTypes): self
    {
        $this->assocTypes = is_array($assocTypes) ? $assocTypes : [$assocTypes];
        return $this;
    }

    /**
     * @copydoc PKPStatsQueryBuilder::getSelectColumns()
     */
    protected function getSelectColumns(array $selectColumns): array
    {
        $selectColumns = parent::getSelectColumns($selectColumns);

        // consider PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE because it can be used in reports
        if (in_array(StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE, $selectColumns)) {
            foreach ($selectColumns as $i => $selectColumn) {
                if ($selectColumn == StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE) {
                    $assocTypeIssue = Application::ASSOC_TYPE_ISSUE;
                    $assocTypeIssueGalley = Application::ASSOC_TYPE_ISSUE_GALLEY;
                    $selectColumns[$i] = DB::raw("CASE WHEN issue_galley_id IS NULL THEN '{$assocTypeIssue}' ELSE '{$assocTypeIssueGalley}' END AS assoc_type");
                    break;
                }
            }
        }

        return $selectColumns;
    }

    /**
     * @copydoc PKPStatsQueryBuilder::_getObject()
     */
    protected function _getObject(): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('metrics_issue');

        if (!empty($this->contextIds)) {
            $q->whereIn(StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID, $this->contextIds);
        }

        if (!empty($this->issueIds)) {
            $q->whereIn(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID, $this->issueIds);
        }

        if (!empty($this->issueGalleyIds)) {
            $q->whereIn(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID, $this->issueGalleyIds);
        }

        if (!empty($this->assocTypes)) {
            if (in_array(Application::ASSOC_TYPE_ISSUE, $this->assocTypes)) {
                $q->whereNull(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID);
            } elseif (in_array(Application::ASSOC_TYPE_ISSUE_GALLEY, $this->assocTypes)) {
                $q->whereNotNull(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID);
            }
        }

        $q->whereBetween(StatisticsHelper::STATISTICS_DIMENSION_DATE, [$this->dateStart, $this->dateEnd]);

        HookRegistry::call('StatsIssue::queryObject', [&$q, $this]);

        return $q;
    }
}
