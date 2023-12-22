<?php

/**
 * @file classes/statistics/TemporaryTotalsDAO.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TemporaryTotalsDAO
 *
 * @ingroup statistics
 *
 * @brief Operations for retrieving and adding total usage.
 *
 * It considers:
 * issue toc and galley views.
 */

namespace APP\statistics;

use APP\core\Application;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PKP\statistics\PKPTemporaryTotalsDAO;

class TemporaryTotalsDAO extends PKPTemporaryTotalsDAO
{
    /**
     * Get Laravel optimized array of data to insert into the table based on the log entry
     */
    protected function getInsertData(object $entryData): array
    {
        return array_merge(
            parent::getInsertData($entryData),
            [
                'issue_id' => $entryData->issueId,
                'issue_galley_id' => $entryData->issueGalleyId,
            ]
        );
    }

    /**
     * Load usage for issue (TOC and galleys views)
     */
    public function compileIssueMetrics(string $loadId): void
    {
        $date = DateTimeImmutable::createFromFormat('Ymd', substr($loadId, -12, 8));
        DB::table('metrics_issue')->where('load_id', '=', $loadId)->orWhereDate('date', '=', $date)->delete();

        $selectIssueMetrics = DB::table($this->table)
            ->select(DB::raw('load_id, context_id, issue_id, DATE(date) as date, count(*) as metric'))
            ->where('load_id', '=', $loadId)
            ->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE)
            ->groupBy(DB::raw('load_id, context_id, issue_id, DATE(date)'));
        DB::table('metrics_issue')->insertUsing(['load_id', 'context_id', 'issue_id', 'date', 'metric'], $selectIssueMetrics);

        $selectIssueGalleyMetrics = DB::table($this->table)
            ->select(DB::raw('load_id, context_id, issue_id, issue_galley_id, DATE(date) as date, count(*) as metric'))
            ->where('load_id', '=', $loadId)
            ->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE_GALLEY)
            ->groupBy(DB::raw('load_id, context_id, issue_id, issue_galley_id, DATE(date)'));
        DB::table('metrics_issue')->insertUsing(['load_id', 'context_id', 'issue_id', 'issue_galley_id', 'date', 'metric'], $selectIssueGalleyMetrics);
    }
}
