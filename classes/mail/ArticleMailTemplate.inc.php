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

import('mail.MailTemplate');
import('article.log.ArticleEmailLogEntry'); // Bring in log constants

class ArticleMailTemplate extends MailTemplate {

	/** @var object the associated article */
	var $article;

	/** @var object the associated journal */
	var $journal;

	/** @var int Event type of this email */
	var $eventType;

	/** @var int Associated type of this email */
	var $assocType;
	
	/** @var int Associated ID of this email */
	var $assocId;

	/**
	 * Constructor.
	 * @param $article object
	 * @param $emailType int
	 * @param $locale string
	 * @see MailTemplate::MailTemplate()
	 */
	function ArticleMailTemplate($article, $emailKey = null, $locale = null, $enableAttachments = false) {
		parent::MailTemplate($emailKey, $locale, $enableAttachments);
		$this->article = $article;
	}

	function assignParams($paramArray = array()) {
		$article = &$this->article;
		$journal = isset($this->journal)?$this->journal:Request::getJournal();

		$paramArray['articleTitle'] = $article->getArticleTitle();
		$paramArray['journalName'] = $journal->getTitle();
		$paramArray['sectionName'] = $article->getSectionTitle();
		$paramArray['articleAbstract'] = $article->getArticleAbstract();
		$paramArray['authorString'] = $article->getAuthorString();

		parent::assignParams($paramArray);
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
	function sendWithParams($article, $paramArray) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();
		
		$this->assignParams($article, $paramArray);
		
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
	 * Set the journal this message is associated with.
	 * @param $journal object
	 */
	function setJournal($journal) {
		$this->journal = $journal;
	}

	/**
	 * Save the email in the article email log.
	 */
	function log() {
		import('article.log.ArticleEmailLogEntry');
		import('article.log.ArticleLog');
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
		$article = &$this->article;
		ArticleLog::logEmailEntry($article->getArticleId(), $entry);
	}

}

?>
