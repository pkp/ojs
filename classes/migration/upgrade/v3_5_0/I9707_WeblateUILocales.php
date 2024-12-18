<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I9707_WeblateUILocales.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9707_WeblateUILocales
 *
 * @brief Map old UI locales to Weblate locales
 */

namespace APP\migration\upgrade\v3_5_0;

class I9707_WeblateUILocales extends \PKP\migration\upgrade\v3_5_0\I9707_WeblateUILocales
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
