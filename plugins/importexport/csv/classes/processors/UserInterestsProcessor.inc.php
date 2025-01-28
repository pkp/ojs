<?php

/**
 * @file plugins/importexport/csv/classes/processors/UserInterestsProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserInterestsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Process the user interests data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;

class UserInterestsProcessor
{
    /**
	 * Process data for Users
	 *
	 * @param array $reviewInterests
	 * @param int $userId
	 *
	 * @return void
	 */
	public static function process($reviewInterests, $userId)
    {
        $userInterestDao = CachedDaos::getUserInterestDAO();
        $userInterestDao->setUserInterests($reviewInterests, $userId);
	}
}
