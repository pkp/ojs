<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7470_FixDeprecatedFileStage.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7470_FixDeprecatedFileStage.php
 *
 * @brief Redirect deprecated file stages that remained after the OJS 2 > 3 migration.
 *
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I7470_FixDeprecatedFileStage extends Migration
{
    public function up(): void
    {
        DB::table('submission_files')
            // From SUBMISSION_FILE_FAIR_COPY
            ->where('file_stage', 7)
            // To \PKP\submissionFile::SUBMISSION_FILE_FINAL
            ->update(['file_stage' => 6]);
        DB::table('submission_files')
            // From SUBMISSION_FILE_EDITOR
            ->where('file_stage', 8)
            // To \PKP\submissionFile::SUBMISSION_FILE_COPYEDIT
            ->update(['file_stage' => 9]);
    }

    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
