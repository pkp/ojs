<?php

/**
 * @file classes/article/ArtworkFileDAODelegate.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArtworkFileDAODelegate
 * @ingroup article
 * @see ArtworkFile
 *
 * @brief Operations for retrieving and modifying ArtworkFile objects.
 *
 * The SubmissionFileDAO will delegate to this class if it wishes
 * to access ArtworkFile classes.
 */


import('classes.article.ArtworkFile');
import('lib.pkp.classes.submission.SubmissionArtworkFileDAODelegate');

class ArtworkFileDAODelegate extends SubmissionArtworkFileDAODelegate {
	/**
	 * Constructor
	 */
	function ArtworkFileDAODelegate() {
		parent::SubmissionArtworkFileDAODelegate();
	}

	/**
	 * @see SubmissionFileDAODelegate::newDataObject()
	 * @return ArticleFile
	 */
	function newDataObject() {
		return new ArtworkFile();
	}
}

?>
