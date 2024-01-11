<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I5885_RenameReviewRemainderSettingsName.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I5885_RenameReviewRemainderSettingsName.php
 *
 * @brief Rename the review remainder settings name
 *
 */

namespace APP\migration\upgrade\v3_5_0;

class I5885_RenameReviewRemainderSettingsName extends \PKP\migration\upgrade\v3_5_0\I5885_RenameReviewRemainderSettingsName
{
    protected function getContextSettingsTable(): string
    {
        return 'journal_settings';
    }
}
