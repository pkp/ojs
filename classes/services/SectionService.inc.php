<?php

/**
 * @file classes/services/SectionService.php
*
* Copyright (c) 2014-2018 Simon Fraser University
* Copyright (c) 2000-2018 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class SectionService
* @ingroup services
*
* @brief Helper class that encapsulates section business logic
*/

namespace OJS\Services;

class SectionService extends \PKP\Services\EntityProperties\PKPBaseEntityPropertyService {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct($this);
	}

	/**
	 * Get array of sections
	 *
	 * @param int $contextId
	 *
	 * @return array
	 */
	public function getSectionList($contextId) {
		$sectionDao = \DAORegistry::getDAO('SectionDAO');
		$sectionIterator = $sectionDao->getByContextId($contextId);

		$sections = array();
		while ($section = $sectionIterator->next()) {
			$sections[] = array(
				'id' => $section->getId(),
				'title' => $section->getLocalizedTitle(),
			);
		}

		return $sections;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getProperties()
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

		\HookRegistry::call('Section::getProperties::values', array(&$values, $section, $props, $args));

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($section, $args = null) {
		$props = array (
			'id','abbrev','title','seq',
		);

		\HookRegistry::call('Section::getProperties::summaryProperties', array(&$props, $section, $args));

		return $this->getProperties($section, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($section, $args = null) {
		// No fuller representation of a section is used at this time
		$props = $this->getSummaryProperties($section, $args);

		\HookRegistry::call('Section::getProperties::fullProperties', array(&$props, $section, $args));

		return $props;
	}
}
