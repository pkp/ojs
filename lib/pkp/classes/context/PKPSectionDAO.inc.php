<?php

/**
 * @file classes/context/PKPSectionDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSectionDAO
 * @ingroup context
 * @see PKPSection
 *
 * @brief Operations for retrieving and modifying Section objects.
 */

abstract class PKPSectionDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Create a new data object.
	 * @return PKPSection
	 */
	abstract function newDataObject();

	/**
	 * Retrieve a section by ID.
	 * @param $sectionId int
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return Section
	 */
	abstract function getById($sectionId, $contextId = null);

	/**
	 * Generate a new PKPSection object from row.
	 * @param $row array
	 * @return PKPSection
	 */
	function _fromRow($row) {
		$section = $this->newDataObject();

		$section->setReviewFormId($row['review_form_id']);
		$section->setEditorRestricted($row['editor_restricted']);
		$section->setSequence($row['seq']);

		return $section;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title');
	}

	/**
	 * Delete a section.
	 * @param $section Section
	 */
	function deleteObject($section) {
		return $this->deleteById($section->getId(), $section->getContextId());
	}

	/**
	 * Delete a section by ID.
	 * @param $sectionId int
	 * @param $journalId int optional
	 */
	abstract function deleteById($sectionId, $contextId = null);

	/**
	 * Delete sections by context ID
	 * NOTE: This does not necessarily delete dependent entries.
	 * @param $contextId int
	 */
	function deleteByContextId($contextId) {
		$sections = $this->getByContextId($contextId);
		while ($section = $sections->next()) {
			$this->deleteObject($section);
		}
	}

	/**
	 * Retrieve all sections for a context.
	 * @return DAOResultFactory containing Sections ordered by sequence
	 */
	abstract function getByContextId($contextId, $rangeInfo = null);
}

?>
