<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_MetricsIssue.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_MetricsIssue
 *
 * @brief Migrate issue stats data from the old DB table metrics into the new DB table metrics_issue.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use PKP\config\Config;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I6782_MetricsIssue extends Migration
{
    private const ASSOC_TYPE_ISSUE = 0x0000103;
    private const ASSOC_TYPE_ISSUE_GALLEY = 0x0000105;

    /**
     * Run the migration.
     */
    public function up(): void
    {
        $dayFormatSql = "DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d')";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $dayFormatSql = "to_date(m.day, 'YYYYMMDD')";
        }

        // The not existing foreign keys should already be moved to the metrics_tmp in I6782_OrphanedMetrics
        // Migrate issue metrics; consider issue TOCs and galley files
        $selectIssueMetrics = DB::table('metrics as m')
            ->select(DB::raw("m.load_id, m.context_id, m.assoc_id, null, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', self::ASSOC_TYPE_ISSUE)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_issue')->insertUsing(['load_id', 'context_id', 'issue_id', 'issue_galley_id', 'date', 'metric'], $selectIssueMetrics);

        $selectIssueGalleyMetrics = DB::table('metrics as m')
            ->join('issue_galleys as ig', 'ig.galley_id', '=', 'm.assoc_id')
            ->select(DB::raw("m.load_id, m.context_id, ig.issue_id, m.assoc_id, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', self::ASSOC_TYPE_ISSUE_GALLEY)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_issue')->insertUsing(['load_id', 'context_id', 'issue_id', 'issue_galley_id', 'date', 'metric'], $selectIssueGalleyMetrics);
    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
