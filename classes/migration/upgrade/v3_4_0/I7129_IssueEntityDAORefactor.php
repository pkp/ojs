<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7129_IssueEntityDAORefactor.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7129_IssueEntityDAORefactor
 *
 * @brief Convert issue DAO to use new repository pattern
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class I7129_IssueEntityDAORefactor extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Remove deprecated setting_type requirement
        Schema::table('issue_settings', function (Blueprint $table) {
            $table->string('setting_type', 6)->nullable()->change();
        });

        // Move current issue status from Issue to Journal
        Schema::table('journals', function (Blueprint $table) {
            $table->bigInteger('current_issue_id')->nullable()->default(null);
            $table->foreign('current_issue_id')->references('issue_id')->on('issues')->onDelete('set null');
            $table->index(['current_issue_id'], 'journals_current_issue_id');
        });
        $this->transferCurrentStatusToJournal();
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('current');
        });
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        // Restore deprecated setting_type requirement
        Schema::table('issue_settings', function (Blueprint $table) {
            $table->string('setting_type', 6)->change();
        });

        // Move current issue status back to Issue from Journal
        Schema::table('issues', function (Blueprint  $table) {
            $table->smallInteger('current')->default(0);
        });
        $this->transferCurrentStatusToIssue();
        Schema::table('journals', function (Blueprint $table) {
            $table->dropForeign('journals_current_issue_id_foreign');
            $table->dropColumn('current_issue_id');
        });
    }

    /**
     * Transfers current issue status from Issue to Journal for each journal
     */
    private function transferCurrentStatusToJournal()
    {
        $contexts = DB::table('journals', 'j')
            ->select('j.journal_id')
            ->get();

        foreach ($contexts as $context) {
            $currentIssue = DB::table('issues', 'i')
                ->select('i.issue_id')
                ->where('i.journal_id', '=', $context->journal_id)
                ->where('i.current', '=', 1)
                ->get()
                ->first();

            if ($currentIssue !== null) {
                DB::table('journals', 'j')
                    ->where('j.journal_id', '=', $context->journal_id)
                    ->update(['current_issue_id' => $currentIssue->issue_id]);
            }
        }
    }

    // Transfer current issue status from Journal to Issue for each journal
    private function transferCurrentStatusToIssue()
    {
        $contexts = DB::table('journals', 'j')
            ->select('j.current_issue_id')
            ->get();

        foreach ($contexts as $context) {
            if ($context->current_issue_id != null) {
                DB::table('issues', 'i')
                    ->where('i.issue_id', '=', $context->current_issue_id)
                    ->update(['i.current' => 1]);
            }
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I7129_IssueEntityDAORefactor', '\I7129_IssueEntityDAORefactor');
}
