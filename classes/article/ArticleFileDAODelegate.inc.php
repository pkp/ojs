<?php

/**
 * @file classes/article/ArticleFileDAODelegate.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleFileDAODelegate
 * @ingroup article
 * @see ArticleFile
 * @see SubmissionFileDAO
 *
 * @brief Operations for retrieving and modifying ArticleFile objects.
 *
 * The SubmissionFileDAO will delegate to this class if it wishes
 * to access ArticleFile classes.
 */


import('classes.article.ArticleFile');
import('lib.pkp.classes.submission.SubmissionFileDAODelegate');

class ArticleFileDAODelegate extends SubmissionFileDAODelegate {
	/**
	 * Constructor
	 */
	function ArticleFileDAODelegate() {
		parent::SubmissionFileDAODelegate();
	}

	/**
	 * @see SubmissionFileDAODelegate::newDataObject()
	 * @return SubmissionFile
	 */
	function newDataObject() {
		return new ArticleFile();
	}
}

?>
