<?php

/**
 * @file classes/services/queryBuilders/StatsPublicationQueryBuilder.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsPublicationQueryBuilder
 *
 * @ingroup query_builders
 *
 * @brief Helper class to construct a query to fetch stats records from the
 *  metrics_submission table.
 */

namespace APP\services\queryBuilders;

use APP\submission\Submission;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\services\queryBuilders\PKPStatsPublicationQueryBuilder;
use PKP\statistics\PKPStatisticsHelper;

class StatsPublicationQueryBuilder extends PKPStatsPublicationQueryBuilder
{
    /** Include records for these issues */
    protected array $issueIds = [];

    public function getSectionColumn(): string
    {
        return 'section_id';
    }

    /**
     * Set the issues to get records for
     */
    public function filterByIssues(array $issueIds): self
    {
        $this->issueIds = $issueIds;
        return $this;
    }

    protected function _getAppSpecificQuery(Builder &$q): void
    {
        if (!empty($this->issueIds)) {
            $issueSubmissionIds = DB::table('publications as p')->select('p.submission_id')->distinct()
                ->from('publications as p')
                ->leftJoin('publication_settings as ps', 'ps.setting_name', '=', DB::raw('\'issueId\''))
                ->where('p.status', Submission::STATUS_PUBLISHED)
                ->whereIn('ps.setting_value', $this->issueIds);
            $q->joinSub($issueSubmissionIds, 'is', function ($join) {
                $join->on('metrics_submission.' . PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID, '=', 'is.submission_id');
            });
        }
    }
}
