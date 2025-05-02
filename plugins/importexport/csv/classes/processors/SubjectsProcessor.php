<?php

/**
 * @file plugins/importexport/csv/classes/processors/SubjectsProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubjectsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the subjects data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\plugins\importexport\csv\classes\cachedAttributes\CachedDaos;

class SubjectsProcessor
{
    /**
     * Processes data for Keywords
     */
    public static function process(object $data, int $publicationId): void
    {
        $subjectsList = [$data->locale => array_map('trim', explode(';', $data->subjects))];

        if (count($subjectsList[$data->locale]) > 0) {
            $submissionSubjectDao = CachedDaos::getSubmissionSubjectDao();
            $submissionSubjectDao->insertSubjects($subjectsList, $publicationId);
        }
    }
}
