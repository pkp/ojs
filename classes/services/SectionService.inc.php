<?php

/**
 * @file classes/services/SectionService.php
*
* Copyright (c) 2014-2017 Simon Fraser University
* Copyright (c) 2000-2017 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class SectionService
* @ingroup services
*
* @brief Helper class that encapsulates section business logic
*/

namespace OJS\Services;

class SectionService {

	/**
	 * Get array of sections
	 *
	 * @param \Journal $journal
	 *
	 * @return array
	 */
	public function getSectionList(\Journal $journal) {
		$sectionDao = \DAORegistry::getDAO('SectionDAO');
		$sectionIterator = $sectionDao->getByContextId($journal);

		$sections = array();
		while ($section = $sectionIterator->next()) {
			$sections[] = array(
				'id' => $section->getId(),
				'title' => $section->getLocalizedTitle(),
			);
		}

		return $sections;
	}
}
