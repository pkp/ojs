<?php

/**
 * @file plugins/importexport/csv/classes/processors/SubjectsProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubjectsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the subjects data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;

class SubjectsProcessor
{
    /**
	 * Processes data for Keywords
	 *
	 * @param object $data
	 * @param int $publicationId
	 *
	 * @return void
	 */
	public static function process($data, $publicationId)
    {
		$subjectsList = [$data->locale => array_map('trim', explode(';', $data->subjects))];

		if (count($subjectsList[$data->locale]) > 0) {
			$submissionSubjectDao = CachedDaos::getSubmissionSubjectDao();
			$submissionSubjectDao->insertSubjects($subjectsList, $publicationId);
		}
	}
}
