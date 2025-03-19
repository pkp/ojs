<?php

/**
 * @file plugins/importexport/csv/classes/validations/RequiredUserHeaders.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RequiredUserHeaders
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Class to validate headers in the user CSV files
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Validations;

class RequiredUserHeaders
{
    static $userHeaders = [
        'journalPath',
        'firstname',
        'lastname',
        'email',
        'affiliation',
        'country',
        'username',
        'tempPassword',
        'roles',
        'reviewInterests',
    ];

    static $userRequiredHeaders = [
        'journalPath',
        'firstname',
        'lastname',
        'email',
        'roles',
    ];

    /**
     * Validates whether the row contains all headers.
	 *
	 * @param array $row
	 *
	 * @return bool
     */
    public static function validateRowHasAllFields($row)
    {
        return count($row) === count(self::$userHeaders);
    }

    /**
     * Validates whether the row contains all required headers.
	 *
	 * @param object $row
	 *
	 * @return bool
     */
    public static function validateRowHasAllRequiredFields($row)
    {
        foreach(self::$userRequiredHeaders as $requiredHeader) {
            if (!$row->{$requiredHeader}) {
                return false;
            }
        }

        return true;
    }
}
