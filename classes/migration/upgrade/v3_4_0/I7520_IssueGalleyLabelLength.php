<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7520_IssueGalleyLabelLength.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7520_IssueGalleyLabelLength
 *
 * @brief This migration increases the length of the issue galley label column in the database
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class I7520_IssueGalleyLabelLength extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('issue_galleys', function (Blueprint $table) {
            $table->string('label', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        Schema::table('issue_galleys', function (Blueprint $table) {
            $table->string('label', 32)->nullable()->change();
        });
    }
}
