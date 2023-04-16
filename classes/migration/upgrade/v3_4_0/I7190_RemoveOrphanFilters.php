<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7190_RemoveOrphanFilters.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7190_RemoveOrphanFilters
 *
 * @brief Remove old filters which have been left behind
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I7190_RemoveOrphanFilters extends \PKP\migration\Migration
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        DB::table('filter_groups')
            ->whereNotIn(
                'symbolic',
                [
                    'mods34=>mods34-xml',
                    'SubmissionArtworkFile=>native-xml',
                    'SupplementaryFile=>native-xml',
                    'native-xml=>SubmissionArtworkFile',
                    'native-xml=>SupplementaryFile'
                ]
            )
            ->delete();

        DB::table('filters')
            ->whereNotExists(
                fn (Builder $query) => $query
                    ->from('filter_groups', 'fg')
                    ->whereColumn('fg.filter_group_id', '=', 'filters.filter_group_id')
            )
            ->delete();
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
