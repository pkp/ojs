<?php

/**
 * @file Announcement.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package announcement 
 * @class Announcement
 *
 * Announcement class.
 * Basic class describing a announcement.
 *
 * $Id$
 */

define('ANNOUNCEMENT_EXPIRE_YEAR_OFFSET_FUTURE',	'+10');


class Announcement extends DataObject {
	//
	// Get/set methods
	//
	
	/**
	 * Get the ID of the announcement.
	 * @return int
	 */
	function getAnnouncementId() {
		return $this->getData('announcementId');
	}
	
	/**
	 * Set the ID of the announcement.
	 * @param $announcementId int
	 */
	function setAnnouncementId($announcementId) {
		return $this->setData('announcementId', $announcementId);
	}

	/**
	 * Get the journal ID of the announcement.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}
	
	/**
	 * Set the journal ID of the announcement.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the announcement type of the announcement.
	 * @return int
	 */
	function getTypeId() {
		return $this->getData('typeId');
	}
	
	/**
	 * Set the announcement type of the announcement.
	 * @param $typeId int
	 */
	function setTypeId($typeId) {
		return $this->setData('typeId', $typeId);
	}

	/**
	 * Get the announcement type name of the announcement.
	 * @return string
	 */
	function getAnnouncementTypeName() {
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		return $announcementTypeDao->getAnnouncementTypeName($this->getData('typeId'));
	}

	/**
	 * Get localized announcement title
	 * @return string
	 */
	function getAnnouncementTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get announcement title.
	 * @param $locale
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}
	
	/**
	 * Set announcement title.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get localized short description
	 * @return string
	 */
	function getAnnouncementDescriptionShort() {
		return $this->getLocalizedData('descriptionShort');
	}

	/**
	 * Get announcement brief description.
	 * @param $locale string
	 * @return string
	 */
	function getDescriptionShort($locale) {
		return $this->getData('descriptionShort', $locale);
	}
	
	/**
	 * Set announcement brief description.
	 * @param $descriptionShort string
	 * @param $locale string
	 */
	function setDescriptionShort($descriptionShort, $locale) {
		return $this->setData('descriptionShort', $descriptionShort, $locale);
	}

	/**
	 * Get localized full description
	 * @return string
	 */
	function getAnnouncementDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get announcement description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}
	
	/**
	 * Set announcement description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}

	/**
	 * Get announcement expiration date.
	 * @return date (YYYY-MM-DD)
	 */
	function getDateExpire() {
		return $this->getData('dateExpire');
	}
	
	/**
	 * Set announcement expiration date.
	 * @param $dateExpire date (YYYY-MM-DD)
	 */
	function setDateExpire($dateExpire) {
		return $this->setData('dateExpire', $dateExpire);
	}

	/**
	 * Get announcement posted date.
	 * @return date (YYYY-MM-DD)
	 */
	function getDatePosted() {
		return $this->getData('datePosted');
	}
	
	/**
	 * Set announcement posted date.
	 * @param $datePosted date (YYYY-MM-DD)
	 */
	function setDatePosted($datePosted) {
		return $this->setData('datePosted', $datePosted);
	}
}

?>
