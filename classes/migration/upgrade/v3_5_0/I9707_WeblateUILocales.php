<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I9707_WeblateUILocales.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9707_WeblateUILocales
 *
 * @brief Map old UI locales to Weblate locales
 */

namespace APP\migration\upgrade\v3_5_0;

class I9707_WeblateUILocales extends \PKP\migration\upgrade\v3_5_0\I9707_WeblateUILocales
{
    protected string $CONTEXT_TABLE = 'journals';
    protected string $CONTEXT_SETTINGS_TABLE = 'journal_settings';
    protected string $CONTEXT_COLUMN = 'journal_id';
}
