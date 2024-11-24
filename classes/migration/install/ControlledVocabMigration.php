<?php

/**
 * @file classes/migration/install/ControlledVocabMigration.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabMigration
 *
 * @brief Describe database table structures.
 */

namespace APP\migration\install;

class ControlledVocabMigration extends \PKP\migration\install\ControlledVocabMigration
{
    /**
     * @copydoc \PKP\migration\install\ControlledVocabMigration::getContextTable()
     */
    protected function getContextTable(): string
    {
        return 'journals';
    }

    /**
     * @copydoc \PKP\migration\install\ControlledVocabMigration::getContextPrimaryKey()
     */
    protected function getContextPrimaryKey(): string
    {
        return 'journal_id';
    }
}
