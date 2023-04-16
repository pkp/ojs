<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I8027_DoiVersioning.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I8027_DoiVersioning
 *
 * @brief Add new DOI versioning context setting
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;

class I8027_DoiVersioning extends \PKP\migration\Migration
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $journalIds = DB::table('journals')
            ->distinct()
            ->get(['journal_id']);
        $insertStatements = $journalIds->reduce(function ($carry, $item) {
            $carry[] = [
                'journal_id' => $item->journal_id,
                'setting_name' => 'doiVersioning',
                'setting_value' => 0
            ];

            return $carry;
        }, []);

        DB::table('journal_settings')
            ->insert($insertStatements);
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        DB::table('journal_settings')
            ->where('setting_name', '=', 'doiVersioning')
            ->delete();
    }
}
