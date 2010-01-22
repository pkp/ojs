<?php

/**
 * @defgroup rt
 */
 

/**
 * @file classes/rt/RT.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RT
 * @ingroup rt
 * @see RTDAO
 *
 * @brief Class to process and respond to Reading Tools requests.
 */

// $Id$


import('rt.RTStruct');

class RT {

	/** @var $version RTVersion current version */
	var $version;

	/** @var $enabled boolean */
	var $enabled;

	/** @var $abstract boolean */
	var $abstract;

	/** @var $captureCite boolean */
	var $captureCite;

	/** @var $viewMetadata boolean */
	var $viewMetadata;

	/** @var $supplementaryFiles boolean */
	var $supplementaryFiles;

	/** @var $printerFriendly boolean */
	var $printerFriendly;

	/** @var $authorBio boolean */
	var $authorBio;

	/** @var $defineTerms boolean */
	var $defineTerms;

	/** @var $emailAuthor boolean */
	var $emailAuthor;

	/** @var $emailOthers boolean */
	var $emailOthers;

	/** @var $findingReferences boolean */
	var $findingReferences;

	/**
	 * Getter/Setter functions
	 */

	function setEnabled($enabled) {
		$this->enabled = $enabled;
	}

	function getEnabled() {
		return $this->enabled;
	}

	function setVersion($version) {
		$this->version = $version;
	}

	function &getVersion() {
		$returner =& $this->version;
		return $returner;
	}

	function setCaptureCite($captureCite) {
		$this->captureCite = $captureCite;
	}

	function getCaptureCite() {
		return $this->captureCite;
	}

	function setAbstract($abstract) {
		$this->abstract = $abstract;
	}

	function getAbstract() {
		return $this->abstract;
	}

	function setViewMetadata($viewMetadata) {
		$this->viewMetadata = $viewMetadata;
	}

	function getViewMetadata() {
		return $this->viewMetadata;
	}

	function setSupplementaryFiles($supplementaryFiles) {
		$this->supplementaryFiles = $supplementaryFiles;
	}

	function getSupplementaryFiles() {
		return $this->supplementaryFiles;
	}

	function setPrinterFriendly($printerFriendly) {
		$this->printerFriendly = $printerFriendly;
	}

	function getPrinterFriendly() {
		return $this->printerFriendly;
	}

	function setAuthorBio($authorBio) {
		$this->authorBio = $authorBio;
	}

	function getAuthorBio() {
		return $this->authorBio;
	}

	function setDefineTerms($defineTerms) {
		$this->defineTerms = $defineTerms;
	}

	function getDefineTerms() {
		return $this->defineTerms;
	}

	function setEmailAuthor($emailAuthor) {
		$this->emailAuthor = $emailAuthor;
	}

	function getEmailAuthor() {
		return $this->emailAuthor;
	}

	function setEmailOthers($emailOthers) {
		$this->emailOthers = $emailOthers;
	}

	function getEmailOthers() {
		return $this->emailOthers;
	}

	function setFindingReferences($findingReferences) {
		$this->findingReferences = $findingReferences;
	}

	function getFindingReferences() {
		return $this->findingReferences;
	}
}

?>
