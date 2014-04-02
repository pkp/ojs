<?php

/**
 * @file plugins/generic/referral/Referral.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Referral
 * @ingroup plugins_generic_referral
 * @see ReferralDAO
 *
 * @brief Basic class describing a referral.
 */

define('REFERRAL_STATUS_NEW',		0x00000001);
define('REFERRAL_STATUS_ACCEPT',	0x00000002);
define('REFERRAL_STATUS_DECLINE',	0x00000003);

class Referral extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the article ID of the referral.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}

	/**
	 * Set the article ID of the referral.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}

	/**
	 * Get the URL of the referral.
	 * @return string
	 */
	function getURL() {
		return $this->getData('url');
	}

	/**
	 * Set the URL of the referral.
	 * @param $url string
	 */
	function setURL($url) {
		return $this->setData('url', $url);
	}

	/**
	 * Get the status flag of the referral (REFERRAL_STATUS_...).
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Get the locale key corresponding to this referral's status
	 */
	function getStatusKey() {
		switch ($this->getStatus()) {
			case REFERRAL_STATUS_NEW: return 'plugins.generic.referral.status.new';
			case REFERRAL_STATUS_ACCEPT: return 'plugins.generic.referral.status.accept';
			case REFERRAL_STATUS_DECLINE: return 'plugins.generic.referral.status.decline';
		}
	}

	/**
	 * Set the status flag of the referral.
	 * @param $status int REFERRAL_STATUS_...
	 */
	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get the date added of the referral.
	 * @return boolean
	 */
	function getDateAdded() {
		return $this->getData('dateAdded');
	}

	/**
	 * Set the date added of the referral.
	 * @param $dateAdded date
	 */
	function setDateAdded($dateAdded) {
		return $this->setData('dateAdded', $dateAdded);
	}

	/**
	 * Get the name of the referral.
	 * @return string
	 */
	function getReferralName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the name of the referral.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the name of the referral.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get the link count of the referral.
	 * @return int
	 */
	function getLinkCount() {
		return $this->getData('linkCount');
	}

	/**
	 * Set the link count of the referral.
	 * @param $linkCount int
	 */
	function setLinkCount($linkCount) {
		return $this->setData('linkCount', $linkCount);
	}
}

?>
