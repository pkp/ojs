<?php

/**
 * @file classes/mail/ArticleMailTemplate.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of SubmissionMailTemplate for sending emails related to articles.
 *
 * This allows for article-specific functionality like logging, etc.
 */

import('lib.pkp.classes.mail.SubmissionMailTemplate');
import('classes.log.SubmissionEmailLogEntry'); // Bring in log constants

class ArticleMailTemplate extends SubmissionMailTemplate {
	/**
	 * Constructor.
	 * @param $article object
	 * @param $emailType string optional
	 * @param $locale string optional
	 * @param $enableAttachments boolean optional
	 * @param $journal object optional
	 * @param $includeSignature boolean optional
	 * @see SubmissionMailTemplate::SubmissionMailTemplate()
	 */
	function ArticleMailTemplate($article, $emailKey = null, $locale = null, $enableAttachments = null, $journal = null, $includeSignature = true) {
		parent::SubmissionMailTemplate($article, $emailKey, $locale, $enableAttachments, $journal, $includeSignature);
	}

	function assignParams($paramArray = array()) {
		$paramArray['sectionName'] = strip_tags($this->submission->getSectionTitle());
		parent::assignParams($paramArray);
	}

	/**
	 *  Send this email to all assigned section editors in the given stage
	 * @param $articleId int
	 * @param $stageId int
	 */
	function toAssignedSectionEditors($articleId, $stageId) {
		return $this->toAssignedSubEditors($articleId, $stageId);
	}

	/**
	 *  CC this email to all assigned section editors in the given stage
	 * @param $articleId int
	 * @param $stageId int
	 * @return array of Users
	 */
	function ccAssignedSectionEditors($articleId, $stageId) {
		return $this->ccAssignedSubEditors($articleId, $stageId);
	}

	/**
	 *  BCC this email to all assigned section editors in the given stage
	 * @param $articleId int
	 * @param $stageId int
	 */
	function bccAssignedSectionEditors($articleId, $stageId) {
		return $this->bccAssignedSubEditors($articleId, $stageId);
	}
}

?>
