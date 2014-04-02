<?php

/**
 * @file classes/article/log/ArticleEmailLogDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleEmailLogDAO
 * @ingroup article_log
 * @see EmailLogDAO
 *
 * @brief Extension to EmailLogDAO for article-specific log entries.
 */


import('lib.pkp.classes.log.EmailLogDAO');
import('classes.article.log.ArticleEmailLogEntry');

class ArticleEmailLogDAO extends EmailLogDAO {
	function ArticleEmailLogDAO() {
		parent::EmailLogDAO();
	}

	function newDataObject() {
		return new ArticleEmailLogEntry();
	}
}

?>
