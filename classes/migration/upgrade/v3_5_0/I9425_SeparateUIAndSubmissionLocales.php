<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I9425_SeparateUIAndSubmissionLocales.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9425_SeparateUIAndSubmissionLocales
 *
 * @brief pkp/pkp-lib#9425 Make submission language selection and metadata forms independent from website language settings
 */

namespace APP\migration\upgrade\v3_5_0;

class I9425_SeparateUIAndSubmissionLocales extends \PKP\migration\upgrade\v3_5_0\I9425_SeparateUIAndSubmissionLocales
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
