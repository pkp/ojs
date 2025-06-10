<?php

/*
 * @file classes/migration/upgrade/v3_6_0/I9295_AddPublishedColumnToPublications.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9295_AddPublishedColumnToPublications
 *
 * @brief Add published column to publications table
 */

namespace APP\migration\upgrade\v3_6_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\migration\Migration;

class I9295_AddPublishedColumnToPublications extends Migration
{
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->boolean('published')->default(false)->after('date_published');
        });

        $publishedIssueIds = DB::table('issues')
            ->where('published', true)
            ->pluck('issue_id');

        DB::table('publications')
            ->whereIn('issue_id', $publishedIssueIds)
            ->whereNotNull('date_published')
            ->update(['published' => true]);
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('published');
        });
    }
}
