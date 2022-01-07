<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7596_RemoveNonExpiring.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7596_RemoveNonExpiring
 * @brief Remove the subscription non_expiring column if it exists.
 * By OJS 3.3.0-x the non-expiring state of the subscription was determined by
 * the NULL status of the duration column, but not all code had been updated to
 * reflect this. Issue 7596 converts the outstanding cases to check the
 * duration column instead, and this migration removes the superfluous column.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class I7596_RemoveNonExpiring extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('subscription_types', function (Blueprint $table) {
            $table->dropColumn('non_expiring');
        });
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        Schema::table('subscription_types', function (Blueprint $table) {
            $table->smallInteger('non_expiring')->default(0);
        });
    }
}
