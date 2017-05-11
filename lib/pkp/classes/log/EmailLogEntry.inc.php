<?php

/**
 * @file classes/log/EmailLogEntry.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailLogEntry
 * @ingroup log
 * @see EmailLogDAO
 *
 * @brief Describes an entry in the email log.
 */


class EmailLogEntry extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Get user ID of sender.
	 * @return int
	 */
	function getSenderId() {
		return $this->getData('senderId');
	}

	/**
	 * Set user ID of sender.
	 * @param $senderId int
	 */
	function setSenderId($senderId) {
		$this->setData('senderId', $senderId);
	}

	/**
	 * Get date email was sent.
	 * @return datestamp
	 */
	function getDateSent() {
		return $this->getData('dateSent');
	}

	/**
	 * Set date email was sent.
	 * @param $dateSent datestamp
	 */
	function setDateSent($dateSent) {
		$this->setData('dateSent', $dateSent);
	}

	/**
	 * Get IP address of sender.
	 * @return string
	 */
	function getIPAddress() {
		return $this->getData('ipAddress');
	}

	/**
	 * Set IP address of sender.
	 * @param $ipAddress string
	 */
	function setIPAddress($ipAddress) {
		$this->setData('ipAddress', $ipAddress);
	}

	/**
	 * Get event type.
	 * @return int
	 */
	function getEventType() {
		return $this->getData('eventType');
	}

	/**
	 * Set event type.
	 * @param $eventType int
	 */
	function setEventType($eventType) {
		$this->setData('eventType', $eventType);
	}

	/**
	 * Get associated type.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set associated type.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get associated ID.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set associated ID.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Return the full name of the sender (not necessarily the same as the from address).
	 * @return string
	 */
	function getSenderFullName() {
		$senderFullName =& $this->getData('senderFullName');

		if(!isset($senderFullName)) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$senderFullName = $userDao->getUserFullName($this->getSenderId(), true);
		}

		return ($senderFullName ? $senderFullName : '');
	}

	/**
	 * Return the email address of sender.
	 * @return string
	 */
	function getSenderEmail() {
		$senderEmail =& $this->getData('senderEmail');

		if(!isset($senderEmail)) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$senderEmail = $userDao->getUserEmail($this->getSenderId(), true);
		}

		return ($senderEmail ? $senderEmail : '');
	}


	//
	// Email data
	//

	function getFrom() {
		return $this->getData('from');
	}

	function setFrom($from) {
		$this->setData('from', $from);
	}

	function getRecipients() {
		return $this->getData('recipients');
	}

	function setRecipients($recipients) {
		$this->setData('recipients', $recipients);
	}

	function getCcs() {
		return $this->getData('ccs');
	}

	function setCcs($ccs) {
		$this->setData('ccs', $ccs);
	}

	function getBccs() {
		return $this->getData('bccs');
	}

	function setBccs($bccs) {
		$this->setData('bccs', $bccs);
	}

	function getSubject() {
		return $this->getData('subject');
	}

	function setSubject($subject) {
		$this->setData('subject', $subject);
	}

	function getBody() {
		return $this->getData('body');
	}

	function setBody($body) {
		$this->setData('body', $body);
	}

	/**
	 * Returns the subject of the message with a prefix explaining the event type
	 * @return string Prefixed subject
	 */
	function getPrefixedSubject() {
		return __('submission.event.subjectPrefix') . ' ' . $this->getSubject();
	}
}

?>
