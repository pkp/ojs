<?php

/**
 * Version.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 *
 * Version class.
 * Describes system version history.
 *
 * $Id$
 */

class Version extends DataObject {

	/**
	 * Constructor.
	 */
	function Version() {
		parent::DataObject();
	}
	
	/**
	 * Compare this version with another version.
	 * Returns:
	 *     < 0 if this version is lower
	 *     0 if they are equal
	 *     > 0 if this version is higher
	 * @param $version string/Version the version to compare against
	 * @return int
	 */
	function compare($version) {
		if (is_object($version)) {
			return $this->compare($version->getVersionString());
		}
		return version_compare($this->getVersionString(), $version);
	}
	
	/**
	 * Static method to return a new version from a version string of the form "W.X.Y.Z".
	 * @param $versionString string
	 * @return Version
	 */
	function &fromString($versionString) {
		$version = &new Version();
		
		$versionArray = explode('.', $versionString);
		$version->setMajor(isset($versionArray[0]) ? (int) $versionArray[0] : 0);
		$version->setMinor(isset($versionArray[1]) ? (int) $versionArray[1] : 0);
		$version->setRevision(isset($versionArray[2]) ? (int) $versionArray[2] : 0);
		$version->setBuild(isset($versionArray[3]) ? (int) $versionArray[3] : 0);
		$version->setDateInstalled(Core::getCurrentDate());
		
		return $version;
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get major version.
	 * @return int
	 */
	function getMajor() {
		return $this->getData('major');
	}
	
	/**
	 * Set major version.
	 * @param $major int
	 */
	function setMajor($major) {
		return $this->setData('major', $major);
	}
	
	/**
	 * Get minor version.
	 * @return int
	 */
	function getMinor() {
		return $this->getData('minor');
	}
	
	/**
	 * Set minor version.
	 * @param $minor int
	 */
	function setMinor($minor) {
		return $this->setData('minor', $minor);
	}
	
	/**
	 * Get revision version.
	 * @return int
	 */
	function getRevision() {
		return $this->getData('revision');
	}
	
	/**
	 * Set revision version.
	 * @param $revision int
	 */
	function setRevision($revision) {
		return $this->setData('revision', $revision);
	}
	
	/**
	 * Get build version.
	 * @return int
	 */
	function getBuild() {
		return $this->getData('build');
	}
	
	/**
	 * Set build version.
	 * @param $build int
	 */
	function setBuild($build) {
		return $this->setData('build', $build);
	}
	
	/**
	 * Get date installed.
	 * @return date
	 */
	function getDateInstalled() {
		return $this->getData('dateInstalled');
	}
	
	/**
	 * Set date installed.
	 * @param $dateInstalled date
	 */
	function setDateInstalled($dateInstalled) {
		return $this->setData('dateInstalled', $dateInstalled);
	}
	
	/**
	 * Check if current version.
	 * @return boolean
	 */
	function getCurrent() {
		return $this->getData('current');
	}
	
	/**
	 * Set if current version.
	 * @param $current boolean
	 */
	function setcurrent($current) {
		return $this->setData('current', $current);
	}
	
	/**
	 * Return complete version string.
	 * @return string
	 */
	function getVersionString() {
		return sprintf('%d.%d.%d.%d', $this->getMajor(), $this->getMinor(), $this->getRevision(), $this->getBuild());
	}
	
}

?>
