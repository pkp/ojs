<?php

/**
 * @file classes/plugin/GalleryPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleryPlugin
 * @ingroup plugins
 *
 * @brief Class describing a plugin in the Plugin Gallery.
 */

define('PLUGIN_GALLERY_STATE_AVAILABLE', 0);
define('PLUGIN_GALLERY_STATE_INCOMPATIBLE', 0);
define('PLUGIN_GALLERY_STATE_UPGRADABLE', 1);
define('PLUGIN_GALLERY_STATE_CURRENT', 2);
define('PLUGIN_GALLERY_STATE_NEWER', 3);

class GalleryPlugin extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get the localized name of the plugin
	 * @param $preferredLocale string
	 * @return string
	 */
	function getLocalizedName($preferredLocale = null) {
		return $this->getLocalizedData('name', $preferredLocale);
	}

	/**
	 * Set the name of the plugin
	 * @param $name string
	 * @param $locale string optional
	 */
	function setName($name, $locale = null) {
		$this->setData('name', $name, $locale);
	}

	/**
	 * Get the name of the plugin
	 * @param $locale string optional
	 * @return string
	 */
	function getName($locale = null) {
		return $this->getData('name', $locale);
	}

	/**
	 * Get the homepage for this plugin
	 * @return string
	 */
	function getHomepage() {
		return $this->getData('homepage');
	}

	/**
	 * Set the homepage for this plugin
	 * @param $homepage string
	 */
	function setHomepage($homepage) {
		$this->setData('homepage', $homepage);
	}

	/**
	 * Get the product (symbolic name) for this plugin
	 * @return string
	 */
	function getProduct() {
		return $this->getData('product');
	}

	/**
	 * Set the product (symbolic name) for this plugin
	 * @param $product string
	 */
	function setProduct($product) {
		$this->setData('product', $product);
	}

	/**
	 * Get the category for this plugin
	 * @return string
	 */
	function getCategory() {
		return $this->getData('category');
	}

	/**
	 * Set the category for this plugin
	 * @param $category string
	 */
	function setCategory($category) {
		$this->setData('category', $category);
	}

	/**
	 * Get the newest compatible version of this plugin
	 * @param $pad boolean True iff returned version numbers should be
	 *  padded to 4 terms, e.g. 1.0.0.0 instead of just 1.0
	 * @return string
	 */
	function getVersion($pad = false) {
		$version = $this->getData('version');
		if ($pad) {
			// Ensure there are 4 terms (3 separators)
			$separators = substr_count($version, '.');
			if ($separators<3) $version .= str_repeat('.0', 3-$separators);
		}
		return $version;
	}

	/**
	 * Set the version for this plugin
	 * @param $version string
	 */
	function setVersion($version) {
		$this->setData('version', $version);
	}

	/**
	 * Get the release date of this plugin
	 * @return int
	 */
	function getDate() {
		return $this->getData('date');
	}

	/**
	 * Set the release date for this plugin
	 * @param $date int
	 */
	function setDate($date) {
		$this->setData('date', $date);
	}

	/**
	 * Get the contact name for this plugin
	 * @return string
	 */
	function getContactName() {
		return $this->getData('contactName');
	}

	/**
	 * Set the contact name for this plugin
	 * @param $contactName string
	 */
	function setContactName($contactName) {
		$this->setData('contactName', $contactName);
	}

	/**
	 * Get the contact institution name for this plugin
	 * @return string
	 */
	function getContactInstitutionName() {
		return $this->getData('contactInstitutionName');
	}

	/**
	 * Set the contact institution name for this plugin
	 * @param $contactInstitutionName string
	 */
	function setContactInstitutionName($contactInstitutionName) {
		$this->setData('contactInstitutionName', $contactInstitutionName);
	}

	/**
	 * Get the contact email for this plugin
	 * @return string
	 */
	function getContactEmail() {
		return $this->getData('contactEmail');
	}

	/**
	 * Set the contact email for this plugin
	 * @param $contactEmail string
	 */
	function setContactEmail($contactEmail) {
		$this->setData('contactEmail', $contactEmail);
	}

	/**
	 * Get plugin summary.
	 * @param $locale string optional
	 * @return string
	 */
	function getSummary($locale = null) {
		return $this->getData('summary', $locale);
	}

	/**
	 * Set plugin summary.
	 * @param $summary string
	 * @param $locale string optional
	 */
	function setSummary($summary, $locale = null) {
		$this->setData('summary', $summary, $locale);
	}

	/**
	 * Get plugin description.
	 * @param $locale string optional
	 * @return string
	 */
	function getDescription($locale = null) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set plugin description.
	 * @param $description string
	 * @param $locale string optional
	 */
	function setDescription($description, $locale = null) {
		$this->setData('description', $description, $locale);
	}

	/**
	 * Get plugin installation instructions.
	 * @param $locale string optional
	 * @return string
	 */
	function getInstallationInstructions($locale = null) {
		return $this->getData('installation', $locale);
	}

	/**
	 * Set plugin installation instructions.
	 * @param $installation string
	 * @param $locale string optional
	 */
	function setInstallationInstructions($installation, $locale = null) {
		$this->setData('installation', $installation, $locale);
	}

	/**
	 * Get release description.
	 * @param $locale string optional
	 * @return string
	 */
	function getReleaseDescription($locale = null) {
		return $this->getData('releaseDescription', $locale);
	}

	/**
	 * Set plugin release description.
	 * @param $releaseDescription string
	 * @param $locale string optional
	 */
	function setReleaseDescription($releaseDescription, $locale = null) {
		$this->setData('releaseDescription', $releaseDescription, $locale);
	}

	/**
	 * Get release MD5 checksum.
	 * @return string
	 */
	function getReleaseMD5() {
		return $this->getData('releaseMD5');
	}

	/**
	 * Set plugin release MD5.
	 * @param $releaseMD5 string
	 */
	function setReleaseMD5($releaseMD5) {
		$this->setData('releaseMD5', $releaseMD5);
	}

	/**
	 * Get the certifications for this plugin release
	 * @return array
	 */
	function getReleaseCertifications() {
		return $this->getData('releaseCertifications');
	}

	/**
	 * Set the certifications for this plugin release
	 * @param $certifications array
	 */
	function setReleaseCertifications($certifications) {
		$this->setData('releaseCertifications', $certifications);
	}

	/**
	 * Get the package URL for this plugin release
	 * @return strings
	 */
	function getReleasePackage() {
		return $this->getData('releasePackage');
	}

	/**
	 * Set the package URL for this plugin release
	 * @param $package string
	 */
	function setReleasePackage($releasePackage) {
		$this->setData('releasePackage', $releasePackage);
	}

	/**
	 * Get the localized summary of the plugin.
	 * @return string
	 */
	function getLocalizedSummary() {
		return $this->getLocalizedData('summary');
	}

	/**
	 * Get the localized installation instructions of the plugin.
	 * @return string
	 */
	function getLocalizedInstallationInstructions() {
		return $this->getLocalizedData('installation');
	}

	/**
	 * Get the localized description of the plugin.
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get the localized release description of the plugin.
	 * @return string
	 */
	function getLocalizedReleaseDescription() {
		return $this->getLocalizedData('releaseDescription');
	}

	/**
	 * Determine the version of this plugin that is currently installed,
	 * if any
	 * @return Version|null
	 */
	function getInstalledVersion() {
		$versionDao = DAORegistry::getDAO('VersionDAO'); /* @var $versionDao VersionDAO */
		return $versionDao->getCurrentVersion('plugins.' . $this->getCategory(), $this->getProduct(), true);
	}

	/**
	 * Get the current state of the gallery plugin with respect to this
	 * installation.
	 * @return int PLUGIN_GALLERY_STATE_...
	 */
	function getCurrentStatus() {
		$installedVersion = $this->getInstalledVersion();
		if ($this->getVersion()===null) return PLUGIN_GALLERY_STATE_INCOMPATIBLE;
		if (!$installedVersion) return PLUGIN_GALLERY_STATE_AVAILABLE;
		if ($installedVersion->compare($this->getVersion(true))>0) return PLUGIN_GALLERY_STATE_NEWER;
		if ($installedVersion->compare($this->getVersion(true))<0) return PLUGIN_GALLERY_STATE_UPGRADABLE;
		return PLUGIN_GALLERY_STATE_CURRENT;
	}
}

?>
