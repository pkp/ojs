<?php

/**
 * EmailTemplate.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * EmailTemplate class.
 * Describes basic email template properties.
 *
 * $Id$
 */

class EmailTemplate extends DataObject {

	/**
	 * Constructor.
	 */
	function EmailTemplate() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of journal.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}
	
	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}
	
	/**
	 * Get ID of email template.
	 * @return int
	 */
	function getEmailId() {
		return $this->getData('emailId');
	}
	
	/**
	 * Set ID of email template.
	 * @param $emailId int
	 */
	function setEmailId($emailId) {
		return $this->setData('emailId', $emailId);
	}
	
	/**
	 * Get key of email template.
	 * @return string
	 */
	function getEmailKey() {
		return $this->getData('emailKey');
	}
	
	/**
	 * Set key of email template.
	 * @param $emailKey string
	 */
	function setEmailKey($emailKey) {
		return $this->setData('emailKey', $emailKey);
	}
	
	/**
	 * Get subject of email template.
	 * @return string
	 */
	function getSubject() {
		return $this->getData('subject');
	}
	
	/**
	 * Set subject of journal.
	 * @param $subject string
	 */
	function setSubject($subject) {
		return $this->setData('subject', $subject);
	}
	
	/**
	 * Get body of email template.
	 * @return string
	 */
	function getBody() {
		return $this->getData('body');
	}
	
	/**
	 * Set body of email template.
	 * @param $body string
	 */
	function setBody($body) {
		return $this->setData('body', $body);
	}
	
	/**
	 * Get the enabled setting of email template.
	 * @return boolean
	 */
	function getEnabled() {
		return $this->getData('enabled');
	}
	
	/**
	 * Set the enabled setting of email template.
	 * @param $enabled boolean
	 */
	function setEnabled($enabled) {
		return $this->setData('enabled', $enabled);
	}
	
}

?>