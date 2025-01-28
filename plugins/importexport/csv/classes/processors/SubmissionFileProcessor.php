<?php

/**
 * @file plugins/importexport/csv/classes/processors/SubmissionFileProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the submission files data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\core\Application;
use APP\facades\Repo;
use PKP\core\Core;
use PKP\core\PKPString;
use PKP\submissionFile\SubmissionFile;

class SubmissionFileProcessor
{
    /**
     * Processes data for the SubmissionFile
     */
    public static function process(
        string $locale,
        int $userId,
        int $submissionId,
        string $filePath,
        int $genreId,
        int $fileId
    ): SubmissionFile {
        $mimeType = PKPString::mime_content_type($filePath);
        $submissionFileDao = Repo::submissionFile()->dao;

        $submissionFile = $submissionFileDao->newDataObject();
        $submissionFile->setData('submissionId', $submissionId);
        $submissionFile->setData('uploaderUserId', $userId);
        $submissionFile->setData('locale', $locale);
        $submissionFile->setData('genreId', $genreId);
        $submissionFile->setData('fileStage', SubmissionFile::SUBMISSION_FILE_PROOF);
        $submissionFile->setData('createdAt', Core::getCurrentDate());
        $submissionFile->setData('updatedAt', Core::getCurrentDate());
        $submissionFile->setData('mimetype', $mimeType);
        $submissionFile->setData('fileId', $fileId);
        $submissionFile->setData('name', pathinfo($filePath, PATHINFO_FILENAME), $locale);

        // Assume open access, no price.
        $submissionFile->setDirectSalesPrice(0);
        $submissionFile->setSalesType('openAccess');

        $submissionFileDao->insert($submissionFile);
        return $submissionFile;
    }

    /**
     * Updates the association information for the submission file
     */
    public static function updateAssocInfo(SubmissionFile $submissionFile, int $galleyId): void
    {
        $submissionFile->setData('assocType', Application::ASSOC_TYPE_REPRESENTATION);
        $submissionFile->setData('assocId', $galleyId);

        $submissionFileDao = Repo::submissionFile()->dao;
        $submissionFileDao->update($submissionFile);
    }
}
