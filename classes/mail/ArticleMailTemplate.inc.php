<?php

/**
 * @file classes/mail/ArticleMailTemplate.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	 * @copydoc SubmissionMailTemplate::assignParams()
	 */
	function assignParams($paramArray = array()) {
		$paramArray['sectionName'] = strip_tags($this->submission->getSectionTitle());
		parent::assignParams($paramArray);
	}
}


