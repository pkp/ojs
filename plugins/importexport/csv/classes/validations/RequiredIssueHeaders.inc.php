<?php

/**
 * @file plugins/importexport/csv/classes/validations/RequiredIssueHeaders.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RequiredIssueHeaders
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Class to validate headers in the issue CSV files
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Validations;

class RequiredIssueHeaders
{
    static $issueHeaders = [
        'journalPath',
        'locale',
        'articleTitle',
        'articlePrefix',
        'articleSubtitle',
        'articleAbstract',
        'articleGalleyFilename',
        'authors',
        'keywords',
        'subjects',
        'coverage',
        'categories',
        'doi',
        'coverImageFilename',
        'coverImageAltText',
        'suppFilenames',
        'suppLabels',
        'genreName',
        'sectionTitle',
        'sectionAbbrev',
        'issueTitle',
        'issueVolume',
        'issueNumber',
        'issueYear',
        'issueDescription',
        'datePublished',
        'startPage',
        'endPage',
    ];

    static $issueRequiredHeaders = [
        'journalPath',
        'locale',
        'articleTitle',
        'articleAbstract',
        'articleGalleyFilename',
        'authors',
        'issueTitle',
        'issueVolume',
        'issueNumber',
        'issueYear',
        'datePublished',
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
        return count($row) === count(self::$issueHeaders);
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
        foreach(self::$issueRequiredHeaders as $requiredHeader) {
            if (!$row->{$requiredHeader}) {
                return false;
            }
        }

        return true;
    }
}
