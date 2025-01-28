<?php

/**
 * @file plugins/importexport/csv/classes/processors/SectionsProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the section data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;
use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;

class SectionsProcessor
{
    /**
	 * Processes data for Sections
	 *
	 * @param object $data
	 * @param int $journalId
	 *
	 * @return \Section
	 */
	public static function process($data, $journalId)
    {
        $section = CachedEntities::getCachedSection($data->sectionTitle, $data->sectionAbbrev, $data->locale, $journalId);

		if (!is_null($section)) {
			return $section;
		}

        $sectionDao = CachedDaos::getSectionDao();

		/** @var \Section $section */
		$section = $sectionDao->newDataObject();
		$section->setContextId($journalId);
		$section->setSequence(REALLY_BIG_NUMBER);
		$section->setEditorRestricted(false);
		$section->setMetaIndexed(true);
		$section->setMetaReviewed(true);
		$section->setAbstractsNotRequired(false);
		$section->setHideTitle(false);
		$section->setHideAuthor(false);
		$section->setIsInactive(false);
		$section->setTitle($data->sectionTitle, $data->locale);
		$section->setAbbrev(mb_strtoupper(trim($data->sectionAbbrev)), $data->locale);
		$section->setIdentifyType('', $data->locale);
		$section->setPolicy('', $data->locale);

		$sectionDao->insertObject($section);

        return $section;
	}
}
