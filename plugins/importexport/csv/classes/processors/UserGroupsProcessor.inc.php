<?php

/**
 * @file plugins/importexport/csv/classes/processors/UserGroupsProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserGroupsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Process the user groups data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;
use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;

class UserGroupsProcessor
{
    /**
	 * Process data for UserGroups
	 *
	 * @param array $roles
	 * @param int $userId
	 * @param int $journalId
	 * @param string $locale
	 *
	 * @return void
	 */
	public static function process($roles, $userId, $journalId, $locale)
    {
        foreach ($roles as $role) {
            $userGroup = CachedEntities::getCachedUserGroupByName($role, $journalId, $locale);
            CachedDaos::getUserGroupDao()->assignUserToGroup($userId, $userGroup->getId());
        }
	}
}
