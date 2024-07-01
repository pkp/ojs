<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I5885_RenameReviewReminderSettingsName.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I5885_RenameReviewReminderSettingsName.php
 *
 * @brief Rename the review reminder settings name
 *
 */

namespace APP\migration\upgrade\v3_5_0;

class I5885_RenameReviewReminderSettingsName extends \PKP\migration\upgrade\v3_5_0\I5885_RenameReviewReminderSettingsName
{
    protected function getContextSettingsTable(): string
    {
        return 'journal_settings';
    }
}
