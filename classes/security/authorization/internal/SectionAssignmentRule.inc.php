<?php
/**
 * @file classes/security/authorization/internal/SectionAssignmentRule.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionAssignmentRule
 * @ingroup security_authorization_internal
 *
 * @brief Class to check if there is an assignment
 * between user and a section.
 *
 */

class SectionAssignmentRule {

	//
	// Public static methods.
	//
	/**
	 * Check if a series editor user is assigned to a section.
	 * @param $contextId
	 * @param $sectionId
	 * @param $userId
	 * @return boolean
	 */
	function effect($contextId, $sectionId, $userId) {
		$sectionEditorsDao = DAORegistry::getDAO('SectionEditorsDAO');
		if ($sectionEditorsDao->editorExists($contextId, $sectionId, $userId)) {
			return true;
		} else {
			return false;
		}
	}
}

?>
