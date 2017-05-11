<?php

/**
 * @file classes/site/Version.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Version
 * @ingroup site
 * @see VersionDAO
 *
 * @brief Describes system version history.
 */


class Version extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct($major, $minor, $revision, $build, $dateInstalled, $current,
			$productType, $product, $productClassName, $lazyLoad, $sitewide) {

		parent::__construct();

		// Initialize object
		$this->setMajor($major);
		$this->setMinor($minor);
		$this->setRevision($revision);
		$this->setBuild($build);
		$this->setDateInstalled($dateInstalled);
		$this->setCurrent($current);
		$this->setProductType($productType);
		$this->setProduct($product);
		$this->setProductClassName($productClassName);
		$this->setLazyLoad($lazyLoad);
		$this->setSitewide($sitewide);
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
	 * @param $productType string
	 * @param $product string
	 * @param $productClass string
	 * @param $lazyLoad integer
	 * @param $sitewide integer
	 * @return Version
	 */
	static function fromString($versionString, $productType = null, $product = null, $productClass = '', $lazyLoad = 0, $sitewide = 1) {
		$versionArray = explode('.', $versionString);

		if(!$product && !$productType) {
			$application = PKPApplication::getApplication();
			$product = $application->getName();
			$productType = 'core';
		}

		$version = new Version(
			(isset($versionArray[0]) ? (int) $versionArray[0] : 0),
			(isset($versionArray[1]) ? (int) $versionArray[1] : 0),
			(isset($versionArray[2]) ? (int) $versionArray[2] : 0),
			(isset($versionArray[3]) ? (int) $versionArray[3] : 0),
			Core::getCurrentDate(),
			1,
			$productType,
			$product,
			$productClass,
			$lazyLoad,
			$sitewide
		);

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
		$this->setData('major', $major);
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
		$this->setData('minor', $minor);
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
		$this->setData('revision', $revision);
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
		$this->setData('build', $build);
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
		$this->setData('dateInstalled', $dateInstalled);
	}

	/**
	 * Check if current version.
	 * @return int
	 */
	function getCurrent() {
		return $this->getData('current');
	}

	/**
	 * Set if current version.
	 * @param $current int
	 */
	function setCurrent($current) {
		$this->setData('current', $current);
	}

	/**
	 * Get product type.
	 * @return string
	 */
	function getProductType() {
		return $this->getData('productType');
	}

	/**
	 * Set product type.
	 * @param $productType string
	 */
	function setProductType($productType) {
		$this->setData('productType', $productType);
	}

	/**
	 * Get product name.
	 * @return string
	 */
	function getProduct() {
		return $this->getData('product');
	}

	/**
	 * Set product name.
	 * @param $product string
	 */
	function setProduct($product) {
		$this->setData('product', $product);
	}

	/**
	 * Get the product's class name
	 * @return string
	 */
	function getProductClassName() {
		return $this->getData('productClassName');
	}

	/**
	 * Set the product's class name
	 * @param $productClassName string
	 */
	function setProductClassName($productClassName) {
		$this->setData('productClassName', $productClassName);
	}

	/**
	 * Get the lazy load flag for this product
	 * @return boolean
	 */
	function getLazyLoad() {
		return $this->getData('lazyLoad');
	}

	/**
	 * Set the lazy load flag for this product
	 * @param $lazyLoad boolean
	 */
	function setLazyLoad($lazyLoad) {
		$this->setData('lazyLoad', $lazyLoad);
	}

	/**
	 * Get the sitewide flag for this product
	 * @return boolean
	 */
	function getSitewide() {
		return $this->getData('sitewide');
	}

	/**
	 * Set the sitewide flag for this product
	 * @param $sitewide boolean
	 */
	function setSitewide($sitewide) {
		$this->setData('sitewide', $sitewide);
	}

	/**
	 * Return complete version string.
	 * @numeric boolean True (default) iff a numeric (comparable) version is to be returned.
	 * @return string
	 */
	function getVersionString($numeric = true) {
		$numericVersion = sprintf('%d.%d.%d.%d', $this->getMajor(), $this->getMinor(), $this->getRevision(), $this->getBuild());
		if (!$numeric && $this->getProduct() == 'omp' && preg_match('/^0\.9\.9\./', $numericVersion)) return ('1.0 Beta');
		if (!$numeric && $this->getProduct() == 'ojs2' && preg_match('/^2\.9\.0\./', $numericVersion)) return ('3.0 Alpha 1');
		if (!$numeric && $this->getProduct() == 'ojs2' && preg_match('/^2\.9\.9\.0/', $numericVersion)) return ('3.0 Beta 1');

		return $numericVersion;
	}
}

?>
