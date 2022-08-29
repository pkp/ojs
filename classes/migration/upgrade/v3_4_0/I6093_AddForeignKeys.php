<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6093_AddForeignKeys.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6093_AddForeignKeys
 * @brief Describe upgrade/downgrade operations for introducing foreign key definitions to existing database relationships.
 */

namespace APP\migration\upgrade\v3_4_0;

class I6093_AddForeignKeys extends \PKP\migration\upgrade\v3_4_0\I6093_AddForeignKeys
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
