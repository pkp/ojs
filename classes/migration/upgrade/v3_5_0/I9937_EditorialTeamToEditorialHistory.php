<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I9937_EditorialTeamToEditorialHistory.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9937_EditorialTeamToEditorialHistory
 *
 * @brief Migrate/rename editorialTeam to editorialHistory context setting and remove Editorial Team navigation menu item.
 */

namespace APP\migration\upgrade\v3_5_0;

class I9937_EditorialTeamToEditorialHistory extends \PKP\migration\upgrade\v3_5_0\I9937_EditorialTeamToEditorialHistory
{
    protected function getContextSettingsTable(): string
    {
        return 'journal_settings';
    }

}
