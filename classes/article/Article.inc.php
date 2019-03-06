<?php

/**
 * @defgroup article Article
 * Articles, OMP's extension of the generic Submission class in lib-pkp, are
 * implemented here.
 */

/**
 * @file classes/article/Article.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Article
 * @ingroup article
 * @see ArticleDAO
 *
 * @brief Article class.
 */

// Author display in ToC
define ('AUTHOR_TOC_DEFAULT', 0);
define ('AUTHOR_TOC_HIDE', 1);
define ('AUTHOR_TOC_SHOW', 2);

import('lib.pkp.classes.submission.Submission');

class Article extends Submission {

	//
	// Get/set methods
	//

	/**
	 * Get the value of a license field from the containing context.
	 * @param $locale string Locale code
	 * @param $field PERMISSIONS_FIELD_...
	 * @return string|null
	 */
	function _getContextLicenseFieldValue($locale, $field) {
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($this->getContextId());
		$fieldValue = null; // Scrutinizer
		switch ($field) {
			case PERMISSIONS_FIELD_LICENSE_URL:
				$fieldValue = $context->getData('licenseURL');
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_HOLDER:
				switch($context->getData('copyrightHolderType')) {
					case 'author':
						$fieldValue = array($context->getPrimaryLocale() => $this->getAuthorString(false));
						break;
					case 'context':
					case null:
						$fieldValue = $context->getName(null);
						break;
					default:
						$fieldValue = $context->getData('copyrightHolderOther');
						break;
				}
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_YEAR:
				// Default copyright year to current year
				$fieldValue = date('Y');

				// Override based on context settings
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticle = $publishedArticleDao->getByArticleId($this->getId());
				if ($publishedArticle) {
					switch($context->getData('copyrightYearBasis')) {
						case 'submission':
							// override to the submission's year if published as you go
							$fieldValue = date('Y', strtotime($publishedArticle->getDatePublished()));
							break;
						case 'issue':
							if ($publishedArticle->getIssueId()) {
								// override to the issue's year if published as issue-based
								$issueDao =& DAORegistry::getDAO('IssueDAO');
								$issue = $issueDao->getByArticleId($this->getId());
								if ($issue && $issue->getDatePublished()) {
									$fieldValue = date('Y', strtotime($issue->getDatePublished()));
								}
							}
							break;
						default: assert(false);
					}
				}
				break;
			default: assert(false);
		}

		// Return the fetched license field
		if ($locale === null || !is_array($fieldValue)) return $fieldValue;
		if (isset($fieldValue[$locale])) return $fieldValue[$locale];
		return null;
	}

	/**
	 * Return the "best" article ID -- If a public article ID is set,
	 * use it; otherwise use the internal article Id.
	 * @return string
	 */
	function getBestArticleId() {
		$publicArticleId = $this->getStoredPubId('publisher-id');
		if (!empty($publicArticleId)) return $publicArticleId;
		return $this->getId();
	}

	/**
	 * Get ID of journal.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the context ID.
	 * @return int
	 */
	function getContextId() {
		return $this->getJournalId();
	}

	/**
	 * Set the context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setJournalId($contextId);
	}

	/**
	 * Get ID of article's section.
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}

	/**
	 * Set ID of article's section.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
	}

	/**
	 * Get title of article's section.
	 * @return string
	 */
	function getSectionTitle() {
		return $this->getData('sectionTitle');
	}

	/**
	 * Set title of article's section.
	 * @param $sectionTitle string
	 */
	function setSectionTitle($sectionTitle) {
		return $this->setData('sectionTitle', $sectionTitle);
	}

	/**
	 * Get section abbreviation.
	 * @return string
	 */
	function getSectionAbbrev() {
		return $this->getData('sectionAbbrev');
	}

	/**
	 * Set section abbreviation.
	 * @param $sectionAbbrev string
	 */
	function setSectionAbbrev($sectionAbbrev) {
		return $this->setData('sectionAbbrev', $sectionAbbrev);
	}

	/**
	 * Get the localized cover page server-side file name
	 * @return string
	 */
	function getLocalizedCoverImage() {
		return $this->getLocalizedData('coverImage');
	}

	/**
	 * get cover page server-side file name
	 * @param $locale string
	 * @return string|array
	 */
	function getCoverImage($locale) {
		return $this->getData('coverImage', $locale);
	}

	/**
	 * set cover page server-side file name
	 * @param $coverImage string
	 * @param $locale string
	 */
	function setCoverImage($coverImage, $locale) {
		$this->setData('coverImage', $coverImage, $locale);
	}

	/**
	 * Get the localized cover page alternate text
	 * @return string
	 */
	function getLocalizedCoverImageAltText() {
		return $this->getLocalizedData('coverImageAltText');
	}

	/**
	 * get cover page alternate text
	 * @param $locale string
	 * @return string
	 */
	function getCoverImageAltText($locale) {
		return $this->getData('coverImageAltText', $locale);
	}

	/**
	 * set cover page alternate text
	 * @param $coverImageAltText string
	 * @param $locale string
	 */
	function setCoverImageAltText($coverImageAltText, $locale) {
		$this->setData('coverImageAltText', $coverImageAltText, $locale);
	}

	/**
	 * Get a full URL to the localized cover image
	 *
	 * @return string
	 */
	function getLocalizedCoverImageUrl() {
		$coverImage = $this->getLocalizedCoverImage();
		if (!$coverImage) {
			return '';
		}

		$request = Application::get()->getRequest();

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		return $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($this->getContextId()) . '/' . $coverImage;
	}

	/**
	 * Get full URLs all cover images
	 *
	 * @return array
	 */
	function getCoverImageUrls() {
		$coverImages = $this->getCoverImage(null);
		if (empty($coverImages)) {
			return array();
		}

		$request = Application::get()->getRequest();
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		$urls = array();

		foreach ($coverImages as $locale => $coverImage) {
			$urls[$locale] = sprintf('%s/%s/%s', $request->getBaseUrl(), $publicFileManager->getContextFilesPath($this->getJournalId()), $coverImage);
		}

		return $urls;
	}
}
