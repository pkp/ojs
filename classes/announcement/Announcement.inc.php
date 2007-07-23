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

	function Announcement() {
		parent::DataObject();
	}
	
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
	function getTypeName() {
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		return $announcementTypeDao->getAnnouncementTypeName($this->getData('typeId'));
	}

	/**
	 * Get announcement title.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set announcement title.
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
	}

	/**
	 * Get announcement brief description.
	 * @return string
	 */
	function getDescriptionShort() {
		return $this->getData('descriptionShort');
	}
	
	/**
	 * Set announcement brief description.
	 * @param $descriptionShort string
	 */
	function setDescriptionShort($descriptionShort) {
		return $this->setData('descriptionShort', $descriptionShort);
	}

	/**
	 * Get announcement description.
	 * @return string
	 */
	function getDescription() {
		return $this->getData('description');
	}
	
	/**
	 * Set announcement description.
	 * @param $description string
	 */
	function setDescription($description) {
		return $this->setData('description', $description);
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
