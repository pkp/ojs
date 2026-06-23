<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I7527_IdentityMetadata.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7527_IdentityMetadata
 *
 * @brief Add the journal-specific identity (ISSN, publisher, publisher location) to the stamped
 *   publication metadata, and stamp published issues.
 */

namespace APP\migration\upgrade\v3_6_0;

use Illuminate\Support\Facades\DB;

class I7527_IdentityMetadata extends \PKP\migration\upgrade\v3_6_0\I7527_IdentityMetadata
{
    protected function getIdentitySettings(string $settingsTable, string $idColumn, int $contextId): array
    {
        $settings = parent::getIdentitySettings($settingsTable, $idColumn, $contextId);

        // onlineIssn and printIssn share their name between journal settings and publication fields.
        // publisherInstitution is the journal setting name; it is stamped as 'publisher'.
        $rename = ['publisherInstitution' => 'publisher'];
        $rows = DB::table($settingsTable)
            ->where($idColumn, $contextId)
            ->whereIn('setting_name', ['onlineIssn', 'printIssn', 'publisherInstitution'])
            ->where('setting_value', '!=', '')
            ->pluck('setting_value', 'setting_name');

        foreach ($rows as $name => $value) {
            $settings[$rename[$name] ?? $name] = $value;
        }

        // Publisher location is stored by the CitationStyleLanguage plugin
        $publisherLocation = DB::table('plugin_settings')
            ->where('plugin_name', 'citationstylelanguageplugin')
            ->where('context_id', $contextId)
            ->where('setting_name', 'publisherLocation')
            ->value('setting_value');
        if ($publisherLocation !== null && $publisherLocation !== '') {
            $settings['publisherLocation'] = $publisherLocation;
        }

        return $settings;
    }

    protected function stampRelatedObjects(int $contextId, array $names, array $scalars): void
    {
        $issueIds = DB::table('issues')
            ->where('journal_id', $contextId)
            ->where('published', 1)
            ->pluck('issue_id');
        $this->stamp('issue_settings', 'issue_id', $issueIds, $names, $scalars);
    }
}
