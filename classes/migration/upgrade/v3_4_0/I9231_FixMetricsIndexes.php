<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I9231_FixMetricsIndexes.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9231_FixMetricsIndexes
 *
 * @brief Use smaller data type for load_id, and use city column prefix index for MySQL.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as Schema;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I9231_FixMetricsIndexes extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Decrease the size of the column load_id to 50 characters
        $loadIdTables = [
            'metrics_context',
            'metrics_submission',
            'metrics_issue',
            'metrics_counter_submission_daily',
            'metrics_counter_submission_institution_daily',
            'metrics_submission_geo_daily',
            'usage_stats_total_temporary_records',
            'usage_stats_unique_item_investigations_temporary_records',
            'usage_stats_unique_item_requests_temporary_records',
            'usage_stats_institution_temporary_records'
        ];
        foreach ($loadIdTables as $loadIdTable) {
            Schema::table($loadIdTable, function (Blueprint $table) {
                $table->string('load_id', 50)->change();
            });
        }

        // Drop the too big unique indexes
        // msgd_uc_load_context_submission_c_r_c_date and
        // msgm_uc_context_submission_c_r_c_month,
        // and create new ones using city column prefix for MySQL
        Schema::table('metrics_submission_geo_daily', function (Blueprint $table) {
            $table->dropUnique('msgd_uc_load_context_submission_c_r_c_date');
            switch (DB::getDriverName()) {
                case 'mysql':
                    $table->unique([DB::raw('load_id, context_id, submission_id, country, region, city(80), date')], 'msgd_uc_load_context_submission_c_r_c_date');
                    break;
                case 'pgsql':
                    $table->unique(['load_id', 'context_id', 'submission_id', 'country', 'region', 'city', 'date'], 'msgd_uc_load_context_submission_c_r_c_date');
                    break;
            }
        });
        Schema::table('metrics_submission_geo_monthly', function (Blueprint $table) {
            $table->dropUnique('msgm_uc_context_submission_c_r_c_month');
            switch (DB::getDriverName()) {
                case 'mysql':
                    $table->unique([DB::raw('context_id, submission_id, country, region, city(80), month')], 'msgm_uc_context_submission_c_r_c_month');
                    break;
                case 'pgsql':
                    $table->unique(['context_id', 'submission_id', 'country', 'region', 'city', 'month'], 'msgm_uc_context_submission_c_r_c_month');
                    break;
            }
        });
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
