<?php

/**
 * @file classes/article/ArticleFile.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleFile
 * @ingroup article
 * @see ArticleFileDAO
 *
 * @brief Article file class.
 */

// $Id$


import('lib.pkp.classes.submission.SubmissionFile');

class ArticleFile extends SubmissionFile {

	/**
	 * Constructor.
	 */
	function ArticleFile() {
		parent::SubmissionFile();
	}

	/**
	 * Return absolute path to the file on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($this->getArticleId());
		$journalId = $article->getJournalId();

		return Config::getVar('files', 'files_dir') . '/journals/' . $journalId .
		'/articles/' . $this->getArticleId() . '/' . $this->getType() . '/' . $this->getFileName();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getSubmissionId();
	}

	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setSubmissionId($articleId);
	}

	/**
	 * Check if the file may be displayed inline.
	 * @return boolean
	 */
	function isInlineable() {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		return $articleFileDao->isInlineable($this);
	}
}

?>
