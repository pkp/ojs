<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I13021_UpdateCrossrefSchema.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I13021_UpdateCrossrefSchema
 *
 * @brief Upgrade Crossref schema in filter_groups.
 */

namespace APP\migration\upgrade\v3_6_0;

use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I13021_UpdateCrossrefSchema extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        DB::table('filter_groups')
            ->whereIn('symbolic', ['issue=>crossref-xml', 'article=>crossref-xml', 'peerReview=>crossref-xml'])
            ->update(['output_type' => 'xml::schema(https://www.crossref.org/schemas/crossref5.5.0.xsd)']);
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
