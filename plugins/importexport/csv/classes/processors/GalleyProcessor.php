<?php

/**
 * @file plugins/importexport/csv/classes/processors/GalleyProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GalleyProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the article galley data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;

class GalleyProcessor
{
    /**
     * Processes initial data for the article galley
     */
    public static function process(
        int $submissionFileId,
        object $data,
        string $label,
        int $publicationId,
        string $extension
    ): int {
        $galleyDao = Repo::galley()->dao;

        $galley = $galleyDao->newDataObject();
        $galley->setData('submissionFileId', $submissionFileId);
        $galley->setData('publicationId', $publicationId);
        $galley->setLabel($label);
        $galley->setLocale($data->locale);
        $galley->setIsApproved(true);
        $galley->setSequence(REALLY_BIG_NUMBER);
        $galley->setName(mb_strtoupper($extension), $data->locale);

        if ($data->doi) {
            $galley->setStoredPubId('doi', $data->doi);
        }

        $galleyDao->insert($galley);
        return $galley->getId();
    }
}
