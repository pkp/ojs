<?php
/**
 * @file classes/core/RuntimeEnvironment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RuntimeEnvironment
 * @ingroup core
 *
 * @brief Class that describes a runtime environment.
 */


class RuntimeEnvironment {
	/** @var string */
	var $_phpVersionMin;

	/** @var string */
	var $_phpVersionMax;

	/** @var array */
	var $_phpExtensions;

	/** @var array */
	var $_externalPrograms;

	function __construct($phpVersionMin = PHP_REQUIRED_VERSION, $phpVersionMax = null, $phpExtensions = array(), $externalPrograms = array()) {
		$this->_phpVersionMin = $phpVersionMin;
		$this->_phpVersionMax = $phpVersionMax;
		$this->_phpExtensions = $phpExtensions;
		$this->_externalPrograms = $externalPrograms;
	}

	//
	// Setters and Getters
	//
	/**
	 * Get the min required PHP version
	 * @return string
	 */
	function getPhpVersionMin() {
		return $this->_phpVersionMin;
	}

	/**
	 * Get the max required PHP version
	 * @return string
	 */
	function getPhpVersionMax() {
		return $this->_phpVersionMax;
	}

	/**
	 * Get the required PHP extensions
	 * @return array
	 */
	function getPhpExtensions() {
		return $this->_phpExtensions;
	}

	/**
	 * Get the required external programs
	 * @return array
	 */
	function getExternalPrograms() {
		return $this->_externalPrograms;
	}


	//
	// Public methods
	//
	/**
	 * Checks whether the current runtime environment is
	 * compatible with the specified parameters.
	 * @return boolean
	 */
	function isCompatible() {
		// Check PHP version
		if (!is_null($this->_phpVersionMin) && !checkPhpVersion($this->_phpVersionMin)) return false;
		if (!is_null($this->_phpVersionMax) && version_compare(PHP_VERSION, $this->_phpVersionMax) === 1) return false;

		// Check PHP extensions
		foreach($this->_phpExtensions as $requiredExtension) {
			if(!extension_loaded($requiredExtension)) return false;
		}

		// Check external programs
		foreach($this->_externalPrograms as $requiredProgram) {
			$externalProgram = Config::getVar('cli', $requiredProgram);
			if (!file_exists($externalProgram)) return false;
			if (function_exists('is_executable')) {
				if (!is_executable($externalProgram)) return false;
			}
		}

		// Compatibility check was successful
		return true;
	}
}
?>
