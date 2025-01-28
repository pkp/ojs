<?php

/**
 * @file plugins/importexport/csv/classes/processors/SectionsProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the section data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;
use APP\plugins\importexport\csv\classes\cachedAttributes\CachedEntities;
use APP\section\Section;

class SectionsProcessor
{
    /**
     * Processes data for Sections
     */
    public static function process(object $data, int $journalId): Section
    {
        $section = CachedEntities::getCachedSection($data->sectionTitle, $data->sectionAbbrev, $journalId);

        if (is_null($section)) {
            $sectionDao = Repo::section()->dao;

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
            $section->setTitle($data->sectionTitle);
            $section->setAbbrev(mb_strtoupper(trim($data->sectionAbbrev)));
            $section->setIdentifyType('');
            $section->setPolicy('');

            $sectionDao->insert($section);
        }

        return $section;
    }
}
