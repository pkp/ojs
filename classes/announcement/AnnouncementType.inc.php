<?php

/**
 * @file classes/announcement/AnnouncementType.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementType
 * @ingroup announcement 
 * @see AnnouncementTypeDAO
 *
 * @brief Basic class describing an announcement type.
 */

// $Id$


class AnnouncementType extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the announcement type.
	 * @return int
	 */
	function getTypeId() {
		return $this->getData('typeId');
	}

	/**
	 * Set the ID of the announcement type.
	 * @param $typeId int
	 */
	function setTypeId($typeId) {
		return $this->setData('typeId', $typeId);
	}

	/**
	 * Get the journal ID of the announcement type.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the announcement type.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the type of the announcement type.
	 * @return string
	 */
	function getAnnouncementTypeName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the type of the announcement type.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the type of the announcement type.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

}

?>
