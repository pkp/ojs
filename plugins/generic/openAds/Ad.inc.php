<?php

/**
 * @file plugins/generic/openAds/Ad.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2009 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Ad
 * @ingroup plugins_generic_openAds
 *
 * @brief Abstract an OpenAds ad.
 */

define('AD_TYPE_CONTENT', 1);
define('AD_TYPE_SIDEBAR', 2);
define('AD_TYPE_MASTHEAD', 3);

class Ad extends DataObject {
	var $openAdsConnection;

	/**
	 * Constructor
	 * @param $openAdsConnection object
	 */
	function Ad(&$openAdsConnection) {
		$this->openAdsConnection =& $openAdsConnection;
	}

	/**
	 * Get the openAds ad ID for this ad.
	 * @return string
	 */
	function getAdId() {
		return $this->getData('adId');
	}

	/**
	 * Set the openAds ad ID for this ad.
	 * @param $adId string
	 */
	function setAdId($adId) {
		$this->setData('adId', $adId);
	}

	/**
	 * Get this ad's name.
	 * @return string
	 */
	function getName() {
		return $this->getData('name');
	}

	/**
	 * Set this ad's name.
	 * @param $name string
	 */
	function setName($name) {
		$this->setData('name', $name);
	}

	/**
	 * Using the openAds connection, get the include HTML for this ad.
	 * @return string
	 */
	function getHtml() {
		return $this->openAdsConnection->getAdHtml($this->getAdId());
	}
}

?>
