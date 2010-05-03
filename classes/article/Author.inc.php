<?php

/**
 * @file classes/article/Author.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Author
 * @ingroup article
 * @see AuthorDAO
 *
 * @brief Article author metadata class.
 */

// $Id$


import('lib.pkp.classes.submission.PKPAuthor');

class Author extends PKPAuthor {

	/**
	 * Constructor.
	 */
	function Author() {
		parent::PKPAuthor();
	}


	//
	// Get/set methods
	//

	/**
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}

	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}

	/**
	 * Get the localized competing interests statement for this author
	 */
	function getAuthorCompetingInterests() {
		return $this->getLocalizedData('competingInterests');
	}

	/**
	 * Get author competing interests.
	 * @param $locale string
	 * @return string
	 */
	function getCompetingInterests($locale) {
		return $this->getData('competingInterests', $locale);
	}

	/**
	 * Set author competing interests.
	 * @param $competingInterests string
	 * @param $locale string
	 */
	function setCompetingInterests($competingInterests, $locale) {
		return $this->setData('competingInterests', $competingInterests, $locale);
	}
}

?>
