<?php

/**
 * @file classes/article/Author.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Author
 * @ingroup article
 * @see AuthorDAO
 *
 * @brief Article author metadata class.
 */


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
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getSubmissionId();
	}

	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setSubmissionId($articleId);
	}

	/**
	 * Get the localized competing interests statement for this author
	 */
	function getLocalizedCompetingInterests() {
		return $this->getLocalizedData('competingInterests');
	}

	function getAuthorCompetingInterests() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCompetingInterests();
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

	// 20111020 BLH Begin Add	
	/**
	 * Get author suffix (added for eScholarship).
	 * @return string
	 */
	function getEscholSuffix() {
		return $this->getData('eschol_suffix');
	}
	
	/**
	 * Get author organization (added for eScholarship).
	 * @return string
	 */
	function getEscholOrganization() {
		return $this->getData('eschol_organization');
	}	
	// 20111020 BLH End Add
	
    // EXPERIMENTAL IMPORT CODE
	function setEscholSuffix($suffix, $locale) {
		return $this->setData('eschol_suffix', $suffix, $locale);
	}
	function setEscholOrganization($organization, $locale) {
		return $this->setData('eschol_organization', $organization, $locale);
	}
	// END EXPERIMENTAL IMPORT CODE
	
}

?>
