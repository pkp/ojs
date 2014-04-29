<?php

/**
 * @file classes/mail/ArticleMailTemplate.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of MailTemplate for sending emails related to articles.
 *
 * This allows for article-specific functionality like logging, etc.
 */

import('classes.mail.MailTemplate');
import('classes.article.log.ArticleEmailLogEntry'); // Bring in log constants

class ArticleMailTemplate extends MailTemplate {

	/** @var object the associated article */
	var $article;

	/** @var object the associated journal */
	var $journal;

	/** @var int Event type of this email */
	var $eventType;

	/**
	 * Constructor.
	 * @param $article object
	 * @param $emailType string optional
	 * @param $locale string optional
	 * @param $enableAttachments boolean optional
	 * @param $journal object optional
	 * @param $includeSignature boolean optional
	 * @param $ignorePostedData boolean optional
	 * @see MailTemplate::MailTemplate()
	 */
	function ArticleMailTemplate($article, $emailKey = null, $locale = null, $enableAttachments = null, $journal = null, $includeSignature = true, $ignorePostedData = false) {
		parent::MailTemplate($emailKey, $locale, $enableAttachments, $journal, $includeSignature, $ignorePostedData);
		$this->article = $article;
	}

	function assignParams($paramArray = array()) {
		$article =& $this->article;
		$journal = isset($this->journal)?$this->journal:Request::getJournal();

		$paramArray['articleTitle'] = strip_tags($article->getLocalizedTitle());
		$paramArray['articleId'] = $article->getId();
		$paramArray['journalName'] = strip_tags($journal->getLocalizedTitle());
		$paramArray['sectionName'] = strip_tags($article->getSectionTitle());
		$paramArray['articleAbstract'] = String::html2text($article->getLocalizedAbstract());
		$paramArray['authorString'] = strip_tags($article->getAuthorString());

		parent::assignParams($paramArray);
	}

	/**
	 * @see parent::send()
	 */
	function send($request = null) {
		if (parent::send(false)) {
			if (!isset($this->skip) || !$this->skip) $this->log($request);
			if ($request) {
				$user =& $request->getUser();
				if ($user && $this->attachmentsEnabled) $this->_clearAttachments($user->getId());
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @see parent::sendWithParams()
	 * @param $paramArray array
	 * @param $request object
	 */
	function sendWithParams($paramArray, $request) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();

		$this->assignParams($paramArray);

		$ret = $this->send($request);

		$this->setSubject($savedSubject);
		$this->setBody($savedBody);

		return $ret;
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
	 * @param $request object
	 */
	function log($request = null) {
		$articleEmailLogDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
		$entry = $articleEmailLogDao->newDataObject();
		$article =& $this->article;

		// Log data
		$entry->setEventType($this->eventType);
		$entry->setSubject($this->getSubject());
		$entry->setBody($this->getBody());
		$entry->setFrom($this->getFromString(false));
		$entry->setRecipients($this->getRecipientString());
		$entry->setCcs($this->getCcString());
		$entry->setBccs($this->getBccString());

		// Add log entry
		import('classes.article.log.ArticleLog');
		$logEntryId = ArticleLog::logEmail($article->getId(), $entry, $request);

		// Add attachments
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());
		foreach ($this->getAttachmentFiles() as $attachment) {
			$articleFileManager->temporaryFileToArticleFile(
				$attachment,
				ARTICLE_FILE_ATTACHMENT,
				$logEntryId
			);
		}
	}

	function toAssignedEditors($articleId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
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
