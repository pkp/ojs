<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6306_EnableCategories.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6306_EnableCategories
 */

namespace APP\migration\upgrade\v3_4_0;

class I6306_EnableCategories extends \PKP\migration\upgrade\v3_4_0\I6306_EnableCategories
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
}
