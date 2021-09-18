<?php

/**
 * @file classes/migration/upgrade/OJSv3_3_1_i6872_UrlPathMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSv3_3_1_i6872_UrlPathMigration
 * @brief A database migration that converts empty string url_paths to null.
 *
 * @see https://github.com/pkp/pkp-lib/issues/6872
 */

namespace APP\migration\upgrade;

use Illuminate\Support\Facades\DB;

class OJSv3_3_1_i6872_UrlPathMigration extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up() : void
    {
        DB::table('publications')->whereNull('url_path')->update(['url_path' => null]);
    }

    /**
     * Reverse the downgrades
     */
    public function down() : void
    {
        // This migration is not destructive. A downgrade should leave these url_paths as null.
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\OJSv3_3_1_i6872_UrlPathMigration', '\OJSv3_3_1_i6872_UrlPathMigration');
}
