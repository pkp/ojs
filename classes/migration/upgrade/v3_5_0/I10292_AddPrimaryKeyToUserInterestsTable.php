<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I9892_FloatToDecimalColumnTypeUpdate.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10292_AddPrimaryKeyToUserInterestsTable
 *
 * @brief Add primary key column to `user_interests` table
 *
 * @see
 */

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class I10292_AddPrimaryKeyToUserInterestsTable extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_interests', function (Blueprint $table) {
            $table->bigIncrements('user_interest_id')->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_interests', function (Blueprint $table) {
            $table->dropColumn('user_interest_id');
        });
    }
}
