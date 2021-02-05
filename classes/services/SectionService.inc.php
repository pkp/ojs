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

namespace APP\Services;

use \Services;
use \PKP\Services\interfaces\EntityPropertyInterface;

class SectionService implements EntityPropertyInterface {

	/**
	 * Get array of sections
	 *
	 * @param int $contextId
	 * @param boolean $activeOnly Exclude inactive sections 
	 * 	from the section list that is returned
	 *
	 * @return array
	 */
	public function getSectionList($contextId, $activeOnly = false) {
		$sectionDao = \DAORegistry::getDAO('SectionDAO'); /* $sectionDao SectionDAO */
		$sectionIterator = $sectionDao->getByContextId($contextId);

		$sections = array();
		while ($section = $sectionIterator->next()) {
			if (!$activeOnly || ($activeOnly && !$section->getIsInactive())) {
				$sections[] = array(
					'id' => $section->getId(),
					'title' => $section->getLocalizedTitle(),
					'group' => $section->getIsInactive(),
				);
			}
		}

		return $sections;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($section, $props, $args = null) {
		$values = array();
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

		$locales = $args['request']->getContext()->getSupportedFormLocales();
		$values = Services::get('schema')->addMissingMultilingualValues(SCHEMA_GALLEY, $values, $locales);

		\HookRegistry::call('Section::getProperties::values', array(&$values, $section, $props, $args));

		ksort($values);

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($section, $args = null) {
		$props = array (
			'id','abbrev','title','seq',
		);

		\HookRegistry::call('Section::getProperties::summaryProperties', array(&$props, $section, $args));

		return $this->getProperties($section, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($section, $args = null) {
		// No fuller representation of a section is used at this time
		$props = $this->getSummaryProperties($section, $args);

		\HookRegistry::call('Section::getProperties::fullProperties', array(&$props, $section, $args));

		return $props;
	}

	/**
	 * Add a new section
	 *
	 * This does not check if the user is authorized to add a section, or
	 * validate or sanitize this section.
	 *
	 * @param $section Section
	 * @param $context Journal
	 * @return Section
	 */
	public function addSection($section, $context) {
		$sectionDao = \DAORegistry::getDAO('SectionDAO'); /* $sectionDao SectionDAO */

		// Don't allow sections to be added to any other context
		$section->setJournalId($context->getId());

		$sectionDao->insertObject($section);

		return $section;
	}
}
