<?php

/**
 * ArticleMailTemplate.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * Subclass of MailTemplate for sending emails related to articles.
 * This allows for article-specific functionality like logging, etc.
 *
 * $Id$
 */

class ArticleMailTemplate extends MailTemplate {

	/** @var int ID of the associated article */
	var $articleId;
	
	/** @var int Event type of this email */
	var $eventType;

	/** @var int Associated type of this email */
	var $assocType;
	
	/** @var int Associated ID of this email */
	var $assocId;

	/**
	 * Constructor.
	 * @param $articleId int
	 * @param $emailType int
	 * @param $locale string
	 * @see MailTemplate::MailTemplate()
	 */
	function ArticleMailTemplate($articleId, $emailKey = null, $locale = null) {
		parent::MailTemplate($emailKey, $locale);
		$this->articleId = $articleId;
	}
	
	/**
	 * @see parent::send()
	 */
	function send() {
		if (parent::send()) {
			$this->log();
			return true;
			
		} else {
			return false;
		}
	}
	
	/**
	 * @see parent::sendWithParams()
	 */
	function sendWithParams($paramArray) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();
		
		$this->assignParams($paramArray);
		
		$ret = $this->send();
		if ($ret) {
			$this->log();
		}
		
		$this->setSubject($savedSubject);
		$this->setBody($savedBody);
		
		return $ret;
	}
	
	/**
	 * Add a generic association between this email and some event type / type / ID tuple.
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 */
	function setAssoc($eventType, $assocType, $assocId) {
		$this->eventType = $eventType;
		$this->assocType = $assocType;
		$this->assocId = $assocId;
	}
	
	/**
	 * Save the email in the article email log.
	 */
	function log() {
		$entry = new ArticleEmailLogEntry();
		
		// Log data
		$entry->setEventType($this->eventType);
		$entry->setAssocType($this->assocType);
		$entry->setAssocId($this->assocId);
		
		// Email data
		$entry->setSubject($this->getSubject());
		$entry->setBody($this->getBody());
		$entry->setFrom($this->getFromString());
		$entry->setRecipients($this->getRecipientString());
		$entry->setCcs($this->getCcString());
		$entry->setBccs($this->getBccString());
		
		// Add log entry
		ArticleLog::logEmailEntry($this->articleId, $entry);
	}

}

?>
