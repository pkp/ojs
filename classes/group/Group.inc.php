<?php

/**
 * Group.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package group
 *
 * Group class.
 * Describes user groups in journals.
 *
 * $Id$
 */

define('GROUP_CONTEXT_EDITORIAL_TEAM',	0x000001);
define('GROUP_CONTEXT_PEOPLE',		0x000002);

class Group extends DataObject {

	/**
	 * Constructor.
	 */
	function Group() {
		parent::DataObject();
	}

	/**
	 * Get localized title of journal group.
	 */
	function getGroupTitle() {
		$alternateLocaleNum = Locale::isAlternateJournalLocale($this->getJournalId());
		$title = null;
		switch ($alternateLocaleNum) {
			case 1: $title = $this->getTitleAlt1(); break;
			case 2: $title = $this->getTitleAlt2(); break;
		}
		// Fall back on the primary locale title.
		if (empty($title)) $title = $this->getTitle();

		return $title;
	}

	//
	// Get/set methods
	//
	
	/**
	 * Get title of group (primary locale)
	 * @return string
	 */
	 function getTitle() {
	 	return $this->getData('title');
	}
	
	/**
	* Set title of group
	* @param $title string
	*/
	function setTitle($title) {
		return $this->setData('title',$title);
	}
	
	/**
	 * Get context of group
	 * @return int
	 */
	 function getContext() {
	 	return $this->getData('context');
	}
	
	/**
	* Set context of group
	* @param $context int
	*/
	function setContext($context) {
		return $this->setData('context',$context);
	}
	
	/**
	 * Get flag indicating whether or not the group is displayed in "About"
	 * @return boolean
	 */
	 function getAboutDisplayed() {
	 	return $this->getData('aboutDisplayed');
	}
	
	/**
	* Set flag indicating whether or not the group is displayed in "About"
	* @param $aboutDisplayed boolean
	*/
	function setAboutDisplayed($aboutDisplayed) {
		return $this->setData('aboutDisplayed',$aboutDisplayed);
	}
	
	/**
	 * Get title of group (alternate locale 1)
	 * @return string
	 */
	 function getTitleAlt1() {
	 	return $this->getData('titleAlt1');
	}
	
	/**
	* Set title of group (alternate locale 1)
	* @param $title string
	*/
	function setTitleAlt1($title) {
		return $this->setData('titleAlt1',$title);
	}
	
	/**
	 * Get title of group (alternate locale 2)
	 * @return string
	 */
	 function getTitleAlt2() {
	 	return $this->getData('titleAlt2');
	}
	
	/**
	* Set title of group (alternate locale 2)
	* @param $title string
	*/
	function setTitleAlt2($title) {
		return $this->setData('titleAlt2',$title);
	}
	
	/**
	 * Get ID of group.
	 * @return int
	 */
	function getGroupId() {
		return $this->getData('groupId');
	}
	
	/**
	 * Set ID of group.
	 * @param $groupId int
	 */
	function setGroupId($groupId) {
		return $this->setData('groupId', $groupId);
	}
	
	/**
	 * Get ID of journal this group belongs to.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}
	
	/**
	 * Set ID of journal this group belongs to.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}
	
	/**
	 * Get sequence of group.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set sequence of group.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
}

?>
