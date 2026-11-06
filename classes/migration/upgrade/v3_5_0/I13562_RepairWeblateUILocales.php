<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I13562_RepairWeblateUILocales.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I13562_RepairWeblateUILocales
 *
 * @brief Repair locales left behind or overwritten by I9707_WeblateUILocales.
 */

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class I13562_RepairWeblateUILocales extends \PKP\migration\upgrade\v3_5_0\I13562_RepairWeblateUILocales
{
    protected function getContextTable(): string
    {
        return 'journals';
    }

    protected function getContextSettingsTable(): string
    {
        return 'journal_settings';
    }

    protected function getContextIdColumn(): string
    {
        return 'journal_id';
    }

    protected function getSubmissionLocaleTables(): array
    {
        return parent::getSubmissionLocaleTables() + [
            'publication_galleys' => fn (Builder $query, int $submissionId) => $query->whereIn('publication_id', $this->getPublicationIds($submissionId)),
            'publication_galley_settings' => fn (Builder $query, int $submissionId) => $query->whereIn(
                'galley_id',
                DB::table('publication_galleys')->select('galley_id')->whereIn('publication_id', $this->getPublicationIds($submissionId))
            ),
        ];
    }
}
