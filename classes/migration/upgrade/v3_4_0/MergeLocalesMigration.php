<?php

/**
 * @file classes/migration/upgrade/v3_4_0/MergeLocalesMigration.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MergeLocalesMigration
 *
 * @brief Change Locales from locale_countryCode localization folder notation to locale localization folder notation
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class MergeLocalesMigration extends \PKP\migration\upgrade\v3_4_0\MergeLocalesMigration
{
    protected string $CONTEXT_TABLE = 'journals';
    protected string $CONTEXT_SETTINGS_TABLE = 'journal_settings';
    protected string $CONTEXT_COLUMN = 'journal_id';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        parent::up();

        // issue_galleys
        $issueGalleys = DB::table('issue_galleys')
            ->get();

        foreach ($issueGalleys as $issueGalley) {
            $this->updateSingleValueLocale($issueGalley->locale, 'issue_galleys', 'locale', 'galley_id', $issueGalley->galley_id);
        }

        // publication_galleys
        $publicationGalleys = DB::table('publication_galleys')
            ->get();

        foreach ($publicationGalleys as $publicationGalley) {
            $this->updateSingleValueLocale($publicationGalley->locale, 'publication_galleys', 'locale', 'galley_id', $publicationGalley->galley_id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }

    public static function getSettingsTables(): Collection
    {
        return collect([
            'issue_galley_settings' => ['galley_id', 'issue_galley_setting_id'],
            'issue_settings' => ['issue_id', 'issue_setting_id'],
            'journal_settings' => ['journal_id', 'journal_setting_id'],
            'publication_galley_settings' => ['galley_id', 'publication_galley_setting_id'],
            'section_settings' => ['section_id', 'section_setting_id'],
            'static_page_settings' => ['static_page_id', 'static_page_setting_id'],
            'subscription_type_settings' => ['type_id', 'subscription_type_setting_id'],
        ])->merge(parent::getSettingsTables());
    }
}
