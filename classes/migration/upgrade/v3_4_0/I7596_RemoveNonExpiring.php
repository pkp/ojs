<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7596_RemoveNonExpiring.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7596_RemoveNonExpiring
 *
 * @brief Remove the subscription non_expiring column if it exists.
 * By OJS 3.3.0-x the non-expiring state of the subscription was determined by
 * the NULL status of the duration column, but not all code had been updated to
 * reflect this. Issue 7596 converts the outstanding cases to check the
 * duration column instead, and this migration removes the superfluous column.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class I7596_RemoveNonExpiring extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Installations that began with OJS 3.3.0 will not have this column.
        // Older installations will.
        if (Schema::hasColumn('subscription_types', 'non_expiring')) {
            Schema::table('subscription_types', function (Blueprint $table) {
                $table->dropColumn('non_expiring');
            });
        }
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        // Regardless of whether the column existed before the migration was
        // executed, OJS 3.3.x did not use it and it should not be re-added.
    }
}
