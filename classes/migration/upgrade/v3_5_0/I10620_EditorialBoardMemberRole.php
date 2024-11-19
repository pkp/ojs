<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I10620_EditorialBoardMemberRole.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10620_EditorialBoardMemberRole
 *
 * @brief Add new Editorial Board Member user group.
 */

namespace APP\migration\upgrade\v3_5_0;

class I10620_EditorialBoardMemberRole extends \PKP\migration\upgrade\v3_5_0\I10620_EditorialBoardMemberRole
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
