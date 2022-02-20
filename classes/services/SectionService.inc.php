<?php

/**
 * @file classes/services/SectionService.php
*
* Copyright (c) 2014-2021 Simon Fraser University
* Copyright (c) 2000-2021 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class SectionService
* @ingroup services
*
* @brief Helper class that encapsulates section business logic
*/

namespace APP\services;

use APP\core\Application;
use APP\core\Services;
use PKP\db\DAORegistry;
use PKP\plugins\HookRegistry;
use PKP\services\interfaces\EntityPropertyInterface;

use PKP\services\PKPSchemaService;

class SectionService implements EntityPropertyInterface
{
    /**
     * Get array of sections
     *
     * @param int $contextId
     * @param bool $activeOnly Exclude inactive sections
     * 	from the section list that is returned
     *
     * @return array
     */
    public function getSectionList($contextId, $activeOnly = false)
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $sectionIterator = $sectionDao->getByContextId($contextId);

        $sections = [];
        while ($section = $sectionIterator->next()) {
            if (!$activeOnly || ($activeOnly && !$section->getIsInactive())) {
                $sections[] = [
                    'id' => $section->getId(),
                    'title' => $section->getLocalizedTitle(),
                    'group' => $section->getIsInactive(),
                ];
            }
        }

        return $sections;
    }

    /**
     * @copydoc \PKP\services\interfaces\EntityPropertyInterface::getProperties()
     *
     * @param null|mixed $args
     */
    public function getProperties($section, $props, $args = null)
    {
        $values = [];
        foreach ($props as $prop) {
            switch ($prop) {
                case 'id':
                    $values[$prop] = (int) $section->getId();
                    break;
                case 'abbrev':
                    $values[$prop] = $section->getAbbrev(null);
                    break;
                case 'title':
                    $values[$prop] = $section->getTitle(null);
                    break;
                case 'seq':
                    $values[$prop] = (int) $section->getSequence();
                    break;
            }
        }

        $locales = Application::get()->getRequest()->getContext()->getSupportedFormLocales();
        $values = Services::get('schema')->addMissingMultilingualValues(PKPSchemaService::SCHEMA_GALLEY, $values, $locales);

        HookRegistry::call('Section::getProperties::values', [&$values, $section, $props, $args]);

        ksort($values);

        return $values;
    }

    /**
     * @copydoc \PKP\services\interfaces\EntityPropertyInterface::getSummaryProperties()
     *
     * @param null|mixed $args
     */
    public function getSummaryProperties($section, $args = null)
    {
        $props = [
            'id','abbrev','title','seq',
        ];

        HookRegistry::call('Section::getProperties::summaryProperties', [&$props, $section, $args]);

        return $this->getProperties($section, $props, $args);
    }

    /**
     * @copydoc \PKP\services\interfaces\EntityPropertyInterface::getFullProperties()
     *
     * @param null|mixed $args
     */
    public function getFullProperties($section, $args = null)
    {
        // No fuller representation of a section is used at this time
        $props = $this->getSummaryProperties($section, $args);

        HookRegistry::call('Section::getProperties::fullProperties', [&$props, $section, $args]);

        return $props;
    }

    /**
     * Add a new section
     *
     * This does not check if the user is authorized to add a section, or
     * validate or sanitize this section.
     *
     * @param Section $section
     * @param Journal $context
     *
     * @return Section
     */
    public function addSection($section, $context)
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */

        // Don't allow sections to be added to any other context
        $section->setJournalId($context->getId());

        $sectionDao->insertObject($section);

        return $section;
    }
}
