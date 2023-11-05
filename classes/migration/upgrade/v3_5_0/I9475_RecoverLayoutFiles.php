<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I9475_RecoverLayoutFiles.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9475_RecoverLayoutFiles
 *
 * @brief Move old layout files (in case the installation has ever been upgraded from an OJS 2.x) from the SUBMISSION_FILE_PROOF stage to the SUBMISSION_FILE_PRODUCTION_READY
 */

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I9475_RecoverLayoutFiles extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('submission_files')
            // SUBMISSION_FILE_PROOF
            ->where('file_stage', '=', 10)
            ->whereNull('assoc_id')
            ->whereNull('assoc_type')
            // SUBMISSION_FILE_PRODUCTION_READY
            ->update(['file_stage' => 11]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
