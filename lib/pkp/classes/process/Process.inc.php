<?php
/**
 * @defgroup process Process
 * Implements process management tools, used e.g. to manage background tasks
 * that can be spawned by web requests to handle expensive operations behind
 * the scenes.
 */

/**
 * @file classes/process/Process.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Process
 * @ingroup process
 * @see ProcessDAO
 *
 * @brief A class representing a running process.
 */

// Process types
define('PROCESS_TYPE_CITATION_CHECKING', 0x01);

import('lib.pkp.classes.core.DataObject');

class Process extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Setters and Getters
	//
	/**
	 * Set the process type
	 * @param $processType integer
	 */
	function setProcessType($processType) {
		$this->setData('processType', (integer)$processType);
	}

	/**
	 * Get the process type
	 * @return integer
	 */
	function getProcessType() {
		return $this->getData('processType');
	}

	/**
	 * Set the starting time of the process
	 * @param $timeStarted integer unix timestamp
	 */
	function setTimeStarted($timeStarted) {
		$this->setData('timeStarted', (integer)$timeStarted);
	}

	/**
	 * Get the starting time of the process
	 * @return integer unix timestamp
	 */
	function getTimeStarted() {
		return $this->getData('timeStarted');
	}

	/**
	 * Set the one-time-key usage flag
	 * @param $obliterated boolean
	 */
	function setObliterated($obliterated) {
		$this->setData('obliterated', (boolean)$obliterated);
	}

	/**
	 * Get the one-time-key usage flag
	 * @return boolean
	 */
	function getObliterated() {
		return $this->getData('obliterated');
	}

	/**
	 * Set additional data to store with the process entry.
	 * @param $additionalData mixed
	 */
	function setAdditionalData($additionalData) {
		$this->setData('additionalData', $additionalData);
	}

	/**
	 * Get the additional data stored with the process entry.
	 * @return mixed
	 */
	function getAdditionalData() {
		return $this->getData('additionalData');
	}
}

?>
