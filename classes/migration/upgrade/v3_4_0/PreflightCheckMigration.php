<?php

/**
 * @file classes/migration/upgrade/v3_4_0/PreflightCheckMigration.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreflightCheckMigration
 * @brief Check for common problems early in the upgrade process.
 */

namespace APP\migration\upgrade\v3_4_0;

class PreflightCheckMigration extends \PKP\migration\upgrade\v3_4_0\PreflightCheckMigration
{
    protected function getContextTable(): string
    {
        return 'journals';
    }

    protected function getContextKeyField(): string
    {
        return 'journal_id';
    }
}
