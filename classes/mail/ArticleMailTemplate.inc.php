<?php

/**
 * @file classes/mail/ArticleMailTemplate.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of SubmissionMailTemplate for sending emails related to articles.
 *
 * This allows for article-specific functionality like logging, etc.
 */

import('lib.pkp.classes.mail.SubmissionMailTemplate');
import('lib.pkp.classes.log.SubmissionEmailLogEntry'); // Bring in log constants

class ArticleMailTemplate extends SubmissionMailTemplate {
	/**
	 * @copydoc SubmissionMailTemplate::assignParams()
	 */
	function assignParams($paramArray = array()) {
		$publication = $this->submission->getCurrentPublication();
		if ($sectionId = $publication->getData('sectionId')) {
			$sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var $sectionDao SectionDAO */
			$section = $sectionDao->getById($sectionId);
			if ($section) $paramArray['sectionName'] = strip_tags($section->getLocalizedTitle());
		}
		parent::assignParams($paramArray);
	}
}


