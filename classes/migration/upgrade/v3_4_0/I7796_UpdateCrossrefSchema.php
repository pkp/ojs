<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7796_UpdateCrossrefSchema.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7796_UpdateCrossrefSchema
 *
 * @brief Upgrade Crossref schema in filter_groups.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I7796_UpdateCrossrefSchema extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        DB::table('filter_groups')
            ->where('output_type', 'xml::schema(https://www.crossref.org/schemas/crossref4.3.6.xsd)')
            ->update(['output_type' => 'xml::schema(https://www.crossref.org/schemas/crossref5.3.1.xsd)']);
    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
