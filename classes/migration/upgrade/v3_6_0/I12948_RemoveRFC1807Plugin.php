<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I12948_RemoveRFC1807Plugin.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class I12948_RemoveRFC1807Plugin
 *
 * @brief Remove the rfc1807 OAI metadata format plugin from the database.
 */

namespace APP\migration\upgrade\v3_6_0;

use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I12948_RemoveRFC1807Plugin extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        DB::table('versions')
            ->where('product_type', '=', 'plugins.oaiMetadataFormats')
            ->where('product', '=', 'rfc1807')
            ->delete();
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
