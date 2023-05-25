<?php


/**
 * @file classes/migration/upgrade/v3_4_0/I8933_EventLogLocalized.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I8933_EventLogLocalized.php
 *
 * @brief Extends the event log migration with the correct table names for OJS.
 */

namespace APP\migration\upgrade\v3_4_0;

class I8933_EventLogLocalized extends \PKP\migration\upgrade\v3_4_0\I8933_EventLogLocalized
{
    protected function getContextTable(): string
    {
        return 'journals';
    }

    protected function getContextIdColumn(): string
    {
        return 'journal_id';
    }
}
