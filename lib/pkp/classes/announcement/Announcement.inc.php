<?php

/**
 * @defgroup announcement Announcement
 * Implements announcements that can be presented to website visitors.
 */

/**
 * @file classes/announcement/Announcement.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Announcement
 * @ingroup announcement
 * @see AnnouncementDAO
 *
 * @brief Basic class describing a announcement.
 */

class Announcement extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//
	/**
	 * Get assoc ID for this annoucement.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set assoc ID for this annoucement.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Get assoc type for this annoucement.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set assoc type for this annoucement.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
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
		$this->setData('typeId', $typeId);
	}

	/**
	 * Get the announcement type name of the announcement.
	 * @return string
	 */
	function getAnnouncementTypeName() {
		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		return $announcementTypeDao->getAnnouncementTypeName($this->getData('typeId'));
	}

	/**
	 * Get localized announcement title
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get full localized announcement title including type name
	 * @return string
	 */
	function getLocalizedTitleFull() {
		$typeName = $this->getAnnouncementTypeName();
		$title = $this->getLocalizedTitle();

		if (!empty($typeName)) {
			return $typeName . ': ' . $title;
		} else {
			return $title;
		}
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
		$this->setData('title', $title, $locale);
	}

	/**
	 * Get localized short description
	 * @return string
	 */
	function getLocalizedDescriptionShort() {
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
		$this->setData('descriptionShort', $descriptionShort, $locale);
	}

	/**
	 * Get localized full description
	 * @return string
	 */
	function getLocalizedDescription() {
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
		$this->setData('description', $description, $locale);
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
		$this->setData('dateExpire', $dateExpire);
	}

	/**
	 * Get announcement posted date.
	 * @return date (YYYY-MM-DD)
	 */
	function getDatePosted() {
		return date('Y-m-d', strtotime($this->getData('datePosted')));
	}

	/**
	 * Get announcement posted datetime.
	 * @return datetime (YYYY-MM-DD HH:MM:SS)
	 */
	function getDatetimePosted() {
		return $this->getData('datePosted');
	}

	/**
	 * Set announcement posted date.
	 * @param $datePosted date (YYYY-MM-DD)
	 */
	function setDatePosted($datePosted) {
		$this->setData('datePosted', $datePosted);
	}

	/**
	 * Set announcement posted datetime.
	 * @param $datetimePosted date (YYYY-MM-DD HH:MM:SS)
	 */
	function setDatetimePosted($datetimePosted) {
		$this->setData('datePosted', $datetimePosted);
	}
}

?>
