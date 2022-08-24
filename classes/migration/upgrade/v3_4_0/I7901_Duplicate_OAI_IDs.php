<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7901_Duplicate_OAI_IDs.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7901_Duplicate_OAI_IDs
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;

class I7901_Duplicate_OAI_IDs extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        switch (DB::getDriverName()) {
            case 'mysql':
                DB::unprepared(
                    "DELETE dot
                    FROM data_object_tombstones dot
                    JOIN submissions s ON (dot.data_object_id = s.submission_id)
                    JOIN journals j ON (j.journal_id = s.context_id)
                    JOIN publications p ON (s.current_publication_id = p.publication_id)
                    JOIN publication_settings psissue ON (psissue.publication_id = p.publication_id AND psissue.setting_name='issueId' AND psissue.locale='')
                    JOIN issues i ON (CAST(i.issue_id AS CHAR(20)) = psissue.setting_value)
                    WHERE i.published = 1 AND j.enabled = 1 AND p.status = 3"
                );
                break;
            case 'pgsql':
                DB::unprepared(
                    "DELETE FROM data_object_tombstones dot
                    USING submissions s, journals j, publications p, publication_settings psissue, issues i
                    WHERE dot.data_object_id = s.submission_id
                    AND j.journal_id = s.context_id
                    AND s.current_publication_id = p.publication_id
                    AND psissue.publication_id = p.publication_id
                    AND psissue.setting_name='issueId' AND psissue.locale='' AND (CAST(i.issue_id AS CHAR(20)) = psissue.setting_value)
                    AND i.published = 1 AND j.enabled = 1 AND p.status = 3"
                );
                break;
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // The migration deletes bad data, which is not recovered on downgrade.
    }
}
