<?php

/**
 * @file classes/services/queryBuilders/StatsIssueQueryBuilder.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueQueryBuilder
 *
 * @ingroup query_builders
 *
 * @brief Helper class to construct a query to fetch issue stats records from the
 *  metrics_issue table.
 */

namespace APP\services\queryBuilders;

use APP\core\Application;
use APP\statistics\StatisticsHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\plugins\Hook;
use PKP\services\queryBuilders\PKPStatsQueryBuilder;

class StatsIssueQueryBuilder extends PKPStatsQueryBuilder
{
    /** Include records for one of these object types: Application::ASSOC_TYPE_ISSUE, Application::ASSOC_TYPE_ISSUE_GALLEY */
    protected array $assocTypes = [];

    /** Include records for these issues */
    protected array $issueIds = [];

    /** Include records for these issues galleys */
    protected array $issueGalleyIds = [];

    /**
     * Set the issues to get records for
     */
    public function filterByIssues(array $issueIds): self
    {
        $this->issueIds = $issueIds;
        return $this;
    }

    /**
     * Set the issues to get records for
     */
    public function filterByIssueGalleys(array $issueGalleyIds): self
    {
        $this->issueGalleyIds = $issueGalleyIds;
        return $this;
    }

    /**
     * Set the assocTypes to get records for
     */
    public function filterByAssocTypes(array $assocTypes): self
    {
        $this->assocTypes = $assocTypes;
        return $this;
    }

    /**
     * Get issue IDs
     */
    public function getIssueIds(): Builder
    {
        return $this->_getObject()
            ->select([StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID])
            ->groupBy(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID);
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
    protected function _getObject(): Builder
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

        if ($this->limit > 0) {
            $q->limit($this->limit);
            if ($this->offset > 0) {
                $q->offset($this->offset);
            }
        }

        Hook::call('StatsIssue::queryObject', [&$q, $this]);

        return $q;
    }
}
