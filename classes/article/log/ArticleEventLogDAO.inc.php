<?php

/**
 * @file classes/article/log/ArticleEventLogDAO.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleEventLogDAO
 * @ingroup article_log
 * @see EventLogDAO
 *
 * @brief Extension to EventLogDAO for article-specific log entries.
 */


import('lib.pkp.classes.log.EventLogDAO');
import('classes.article.log.ArticleEventLogEntry');

class ArticleEventLogDAO extends EventLogDAO {
	function ArticleEventLogDAO() {
		parent::EventLogDAO();
	}

	function newDataObject() {
		return new ArticleEventLogEntry();
	}
}

?>
