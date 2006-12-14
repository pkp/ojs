<?php

/**
 * Ad.inc.php
 *
 * Copyright (c) 2003-2006 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Abstract a phpAdsNew ad.
 *
 * $Id: CounterPlugin.inc.php,v 1.0 2006/10/20 12:28pm
 */

define('AD_TYPE_CONTENT', 1);
define('AD_TYPE_SIDEBAR', 2);
define('AD_TYPE_MASTHEAD', 3);

class Ad extends DataObject {
	var $phpAdsNewConnection;

	function Ad(&$phpAdsNewConnection) {
		$this->phpAdsNewConnection =& $phpAdsNewConnection;
	}

	function getAdId() {
		return $this->getData('adId');
	}

	function setAdId($adId) {
		$this->setData('adId', $adId);
	}

	function getName() {
		return $this->getData('name');
	}
	
	function setName($name) {
		$this->setData('name', $name);
	}

	function getHtml() {
		return $this->phpAdsNewConnection->getAdHtml($this->getAdId());
	}
}

?>
