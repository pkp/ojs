<?php

/**
 * @file plugins/importexport/csv/classes/processors/UserGroupsProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserGroupsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Process the user groups data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;
use APP\plugins\importexport\csv\classes\cachedAttributes\CachedEntities;

class UserGroupsProcessor
{
    /**
     * Process data for UserGroups
     */
    public static function process(array $roles, int $userId, int $journalId, string $locale): void
    {

        foreach ($roles as $role) {
            $userGroup = CachedEntities::getCachedUserGroupByName($role, $journalId, $locale);
            Repo::userGroup()->assignUserToGroup($userId, $userGroup->getId());
        }
    }
}
