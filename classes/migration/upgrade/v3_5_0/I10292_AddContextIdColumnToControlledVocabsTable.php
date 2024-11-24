<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I10292_AddContextIdColumnToControlledVocabsTable.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10292_AddContextIdColumnToControlledVocabsTable.php
 *
 * @brief Add the column `context_id` to `controlled_vocabs` table for context map.
 *
 */

namespace APP\migration\upgrade\v3_5_0;

class I10292_AddContextIdColumnToControlledVocabsTable extends \PKP\migration\upgrade\v3_5_0\I10292_AddContextIdColumnToControlledVocabsTable
{
    /**
     * @copydoc \PKP\migration\upgrade\v3_5_0\I10292_AddContextIdColumnToControlledVocabsTable::getContextTable()
     */
    protected function getContextTable(): string
    {
        return 'journals';
    }

    /**
     * @copydoc \PKP\migration\upgrade\v3_5_0\I10292_AddContextIdColumnToControlledVocabsTable::getContextPrimaryKey()
     */
    protected function getContextPrimaryKey(): string
    {
        return 'journal_id';
    }
}
