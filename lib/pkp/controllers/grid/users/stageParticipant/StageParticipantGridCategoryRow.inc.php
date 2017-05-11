<?php

/**
 * @file controllers/grid/users/stageParticipant/StageParticipantGridCategoryRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StageParticipantGridCategoryRow
 * @ingroup controllers_grid_users_stageParticipant
 *
 * @brief Stage participant grid category row definition
 */

import('lib.pkp.classes.controllers.grid.GridCategoryRow');

// Link actions
import('lib.pkp.classes.linkAction.request.AjaxModal');

class StageParticipantGridCategoryRow extends GridCategoryRow {
	/** @var Submission **/
	var $_submission;

	/** @var int */
	var $_stageId;

	/**
	 * Constructor
	 */
	function __construct($submission, $stageId) {
		$this->_submission = $submission;
		$this->_stageId = $stageId;
		parent::__construct();
	}

	//
	// Overridden methods from GridCategoryRow
	//
	/**
	 * @copydoc GridCategoryRow::getCategoryLabel()
	 */
	function getCategoryLabel() {
		$userGroup = $this->getData();
		return $userGroup->getLocalizedName();
	}

	//
	// Private methods
	//
	/**
	 * Get the submission for this row (already authorized)
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * Get the stage ID for this grid.
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}
}

?>
