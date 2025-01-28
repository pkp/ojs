<?php

/**
 * @file plugins/importexport/csv/classes/processors/SubmissionFileProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the submission files data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;

class SubmissionFileProcessor
{
    /**
	 * Processes data for the SubmissionFile
	 *
	 * @param string $locale
	 * @param int $userId
	 * @param int $submissionId
	 * @param string $filePath
	 * @param int $genreId
	 * @param int $fileId
	 *
	 * @return \SubmissionFile
	 */
	public static function process($locale, $userId, $submissionId, $filePath, $genreId, $fileId)
    {
		$mimeType = \PKPString::mime_content_type($filePath);
		$submissionFileDao = CachedDaos::getSubmissionFileDao();

		/** @var \SubmissionFile $submissionFile */
		$submissionFile = $submissionFileDao->newDataObject();
		$submissionFile->setData('submissionId', $submissionId);
		$submissionFile->setData('uploaderUserId', $userId);
		$submissionFile->setData('locale', $locale);
		$submissionFile->setData('genreId', $genreId);
		$submissionFile->setData('fileStage', SUBMISSION_FILE_PROOF);
		$submissionFile->setData('createdAt', \Core::getCurrentDate());
		$submissionFile->setData('updatedAt', \Core::getCurrentDate());
		$submissionFile->setData('mimetype', $mimeType);
		$submissionFile->setData('fileId', $fileId);
		$submissionFile->setData('name', pathinfo($filePath, PATHINFO_FILENAME), $locale);

		// Assume open access, no price.
		$submissionFile->setDirectSalesPrice(0);
		$submissionFile->setSalesType('openAccess');

		$submissionFileDao->insertObject($submissionFile);
        return $submissionFile;
	}

    /**
     * Updates the association information for the submission file
	 *
	 * @param \SubmissionFile $submissionFile
	 * @param int $galleyId
	 *
	 * @return void
	 */
    public static function updateAssocInfo($submissionFile, $galleyId)
    {
        $submissionFile->setData('assocType', ASSOC_TYPE_REPRESENTATION);
        $submissionFile->setData('assocId', $galleyId);

        $submissionFileDao = CachedDaos::getSubmissionFileDao();
        $submissionFileDao->updateObject($submissionFile);
    }
}
