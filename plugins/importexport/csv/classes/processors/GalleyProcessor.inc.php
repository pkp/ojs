<?php

/**
 * @file plugins/importexport/csv/classes/processors/GalleyProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GalleyProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the article galley data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;

class GalleyProcessor
{
    /**
     * Processes initial data for the article galley
	 *
	 * @param int $submissionFileId
	 * @param object $data
	 * @param string $label
	 * @param int $publicationId
	 * @param string $extension
	 *
	 * @return int
     */
    public static function process($submissionFileId, $data, $label, $publicationId, $extension)
    {
        $galleyDao = CachedDaos::getArticleGalleyDao();

		/** @var \ArticleGalley $galley */
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

        $galleyDao->insertObject($galley);
        return $galley->getId();
    }
}
