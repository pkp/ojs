<?php

/**
 * @file classes/mail/ArticleMailTemplate.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of MailTemplate for sending emails related to articles.
 *
 * This allows for article-specific functionality like logging, etc.
 */

// $Id$


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
	 * @param $emailType string optional
	 * @param $locale string optional
	 * @param $enableAttachments boolean optional
	 * @param $journal object optional
	 * @param $includeSignature boolean optional
	 * @see MailTemplate::MailTemplate()
	 */
	function ArticleMailTemplate($article, $emailKey = null, $locale = null, $enableAttachments = null, $journal = null, $includeSignature = true) {
		parent::MailTemplate($emailKey, $locale, $enableAttachments, $journal, $includeSignature);
		$this->article = $article;
	}

	function assignParams($paramArray = array()) {
		$article = &$this->article;
		$journal = isset($this->journal)?$this->journal:Request::getJournal();

		$paramArray['articleTitle'] = strip_tags($article->getArticleTitle());
		$paramArray['articleId'] = $article->getArticleId();
		$paramArray['journalName'] = strip_tags($journal->getJournalTitle());
		$paramArray['sectionName'] = strip_tags($article->getSectionTitle());
		$paramArray['articleAbstract'] = strip_tags($article->getArticleAbstract());
		$paramArray['authorString'] = strip_tags($article->getAuthorString());

		parent::assignParams($paramArray);
	}

	/**
	 * @see parent::send()
	 */
	function send() {
		if (parent::send(false)) {
			if (!isset($this->skip) || !$this->skip) $this->log();
			$user =& Request::getUser();
			if ($this->attachmentsEnabled) $this->_clearAttachments($user->getUserId());
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
		$entry = &new ArticleEmailLogEntry();
		$article = &$this->article;

		// Log data
		$entry->setEventType($this->eventType);
		$entry->setAssocType($this->assocType);
		$entry->setAssocId($this->assocId);

		// Email data
		$entry->setSubject($this->getSubject());
		$entry->setBody($this->getBody());
		$entry->setFrom($this->getFromString(false));
		$entry->setRecipients($this->getRecipientString());
		$entry->setCcs($this->getCcString());
		$entry->setBccs($this->getBccString());

		// Add log entry
		$logEntryId = ArticleLog::logEmailEntry($article->getArticleId(), $entry);

		// Add attachments
		import('file.ArticleFileManager');
		$articleFileManager =& new ArticleFileManager($article->getArticleId());
		foreach ($this->getAttachmentFiles() as $attachment) {
			$articleFileManager->temporaryFileToArticleFile(
				$attachment,
				ARTICLE_FILE_ATTACHMENT,
				$logEntryId
			);
		}
	}

	function ccAssignedEditors($articleId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function toAssignedReviewingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getReviewingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function toAssignedEditingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function ccAssignedReviewingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getReviewingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function ccAssignedEditingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}
}

?>
