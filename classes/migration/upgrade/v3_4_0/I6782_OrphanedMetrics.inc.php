<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_OrphanedMetrics.inc.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_OrphanedMetrics
 * @brief Migrate metrics data from objects that do not exist any more into the temporary table.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class I6782_OrphanedMetrics extends \PKP\migration\upgrade\v3_4_0\I6782_OrphanedMetrics
{
    private const ASSOC_TYPE_CONTEXT = 0x0000100;
    private const ASSOC_TYPE_ISSUE = 0x0000103;
    private const ASSOC_TYPE_ISSUE_GALLEY = 0x0000105;

    protected function getContextAssocType(): int
    {
        return self::ASSOC_TYPE_CONTEXT;
    }

    protected function getContextTable(): string
    {
        return 'journals';
    }

    protected function getContextKeyField(): string
    {
        return 'journal_id';
    }

    protected function getRepresentationTable(): string
    {
        return 'publication_galleys';
    }

    protected function getRepresentationKeyField(): string
    {
        return 'galley_id';
    }

    /**
     * Run the migration.
     *
     * assoc_object_type, assoc_object_id, and pkp_section_id will not be considered here, because they are not relevant for the migration
     */
    public function up(): void
    {
        parent::up();

        $metricsColumns = Schema::getColumnListing('metrics_tmp');

        // Metrics issue IDs
        // as m.assoc_id
        $orphanedIds = DB::table('metrics AS m')->leftJoin('issues AS i', 'm.assoc_id', '=', 'i.issue_id')->where('m.assoc_type', '=', self::ASSOC_TYPE_ISSUE)->whereNull('i.issue_id')->distinct()->pluck('m.assoc_id');
        $orphandedIssues = DB::table('metrics')->select($metricsColumns)->where('assoc_type', '=', self::ASSOC_TYPE_ISSUE)->whereIn('assoc_id', $orphanedIds);
        DB::table('metrics_tmp')->insertUsing($metricsColumns, $orphandedIssues);
        DB::table('metrics')->where('assoc_type', '=', self::ASSOC_TYPE_ISSUE)->whereIn('assoc_id', $orphanedIds)->delete();

        // Clean orphaned metrics issue galley IDs
        $orphanedIds = DB::table('metrics AS m')->leftJoin('issue_galleys AS ig', 'm.assoc_id', '=', 'ig.galley_id')->where('m.assoc_type', '=', self::ASSOC_TYPE_ISSUE_GALLEY)->whereNull('ig.galley_id')->distinct()->pluck('m.assoc_id');
        $orphandedIssuesGalleys = DB::table('metrics')->select($metricsColumns)->where('assoc_type', '=', self::ASSOC_TYPE_ISSUE_GALLEY)->whereIn('assoc_id', $orphanedIds);
        DB::table('metrics_tmp')->insertUsing($metricsColumns, $orphandedIssuesGalleys);
        DB::table('metrics')->where('assoc_type', '=', self::ASSOC_TYPE_ISSUE_GALLEY)->whereIn('assoc_id', $orphanedIds)->delete();
    }
}
