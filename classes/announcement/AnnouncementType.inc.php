<?php

/**
 * AnnouncementType.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package announcement 
 *
 * AnnouncementType class.
 * Basic class describing an announcement type.
 *
 * $Id$
 */

class AnnouncementType extends DataObject {

	function AnnouncementType() {
		parent::DataObject();
	}
	
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
	 * @return int
	 */
	function getTypeName() {
		return $this->getData('typeName');
	}
	
	/**
	 * Set the type of the announcement type.
	 * @param $typeName int
	 */
	function setTypeName($typeName) {
		return $this->setData('typeName', $typeName);
	}

}

?>
