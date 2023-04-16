<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6807_SetLastModified.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6807_SetLastModified
 *
 * @brief Update last modification dates where they are not yet set.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;

class I6807_SetLastModified extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // pkp/pkp-lib#6807 Make sure all submission/issue last modification dates are set
        DB::statement('UPDATE issues SET last_modified = date_published WHERE last_modified IS NULL');
        DB::statement('UPDATE submissions SET last_modified = NOW() WHERE last_modified IS NULL');
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        // We don't have the data to downgrade and downgrades are unwanted here anyway.
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I6807_SetLastModified', '\I6807_SetLastModified');
}
