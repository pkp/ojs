<?php

/**
 * @file controllers/grid/files/signoff/form/FileAuditorForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CopyeditingUserForm
 * @ingroup controllers_grid_files_signoff
 *
 * @brief Form to add files to the copyediting files grid
 */

import('lib.pkp.controllers.grid.files.signoff.form.PKPFileAuditorForm');

class FileAuditorForm extends PKPFileAuditorForm {
	/** @var int */
	var $_galleyId;

	/**
	 * Constructor.
	 */
	function FileAuditorForm($submission, $fileStage, $stageId, $symbolic, $eventType, $assocId = null, $galleyId = null) {
		parent::PKPFileAuditorForm($submission, $fileStage, $stageId, $symbolic, $eventType, $assocId);
		$this->_galleyId = $galleId;
	}

	// Getters and Setters.
	/**
	 * Get the galley id
	 * @return int
	 */
	function getGalleyId() {
		return $this->_galleyId;
	}

	//
	// Overridden template methods.
	//
	/**
	 * Initialize variables
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {

		parent::initData($args, $request);

		if ($this->getGalleyId()) {
			$this->setData('galleyId', $this->getGalleyId());
		}
	}
}

?>
