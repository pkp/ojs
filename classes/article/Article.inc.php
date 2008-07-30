<?php

/**
 * @defgroup article
 */
 
/**
 * @file classes/article/Article.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class Article
 * @ingroup article
 * @see ArticleDAO
 *
 * @brief Article class.
 */

// $Id$


// Submission status constants
define('STATUS_ARCHIVED', 0);
define('STATUS_QUEUED', 1);
// define('STATUS_SCHEDULED', 2); // #2187: Scheduling queue removed.
define('STATUS_PUBLISHED', 3);
define('STATUS_DECLINED', 4);

// AuthorSubmission::getSubmissionStatus will return one of these in place of QUEUED:
define ('STATUS_QUEUED_UNASSIGNED', 5);
define ('STATUS_QUEUED_REVIEW', 6);
define ('STATUS_QUEUED_EDITING', 7);
define ('STATUS_INCOMPLETE', 8);

// Author display in ToC
define ('AUTHOR_TOC_DEFAULT', 0);
define ('AUTHOR_TOC_HIDE', 1);
define ('AUTHOR_TOC_SHOW', 2);

// Article RT comments
define ('COMMENTS_SECTION_DEFAULT', 0);
define ('COMMENTS_DISABLE', 1);
define ('COMMENTS_ENABLE', 2);

import('document.Document');

class Article extends Document {

	/** @var array Authors of this article */
	var $authors;

	/** @var array IDs of Authors removed from this article */
	var $removedAuthors;

	/**
	 * Constructor.
	 */
	function Article() {
		parent::Document();
		$this->authors = array();
		$this->removedAuthors = array();
	}

	/**
	 * Add an author.
	 * @param $author Author
	 */
	function addAuthor($author) {
		if ($author->getArticleId() == null) {
			$author->setArticleId($this->getArticleId());
		}
		if ($author->getSequence() == null) {
			$author->setSequence(count($this->authors) + 1);
		}
		array_push($this->authors, $author);
	}

	/**
	 * Remove an author.
	 * @param $authorId ID of the author to remove
	 * @return boolean author was removed
	 */
	function removeAuthor($authorId) {
		$found = false;

		if ($authorId != 0) {
			// FIXME maintain a hash of ID to author for quicker get/remove
			$authors = array();
			for ($i=0, $count=count($this->authors); $i < $count; $i++) {
				if ($this->authors[$i]->getAuthorId() == $authorId) {
					array_push($this->removedAuthors, $authorId);
					$found = true;
				} else {
					array_push($authors, $this->authors[$i]);
				}
			}
			$this->authors = $authors;
		}
		return $found;
	}

	/**
	 * Get "localized" article title (if applicable).
	 * @return string
	 */
	function getArticleTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get "localized" article abstract (if applicable).
	 * @return string
	 */
	function getArticleAbstract() {
		return $this->getLocalizedData('abstract');
	}

	/**
	 * Return string of author names, separated by the specified token
	 * @param $lastOnly boolean return list of lastnames only (default false)
	 * @param $separator string separator for names (default comma+space)
	 * @return string
	 */
	function getAuthorString($lastOnly = false, $separator = ', ') {
		$str = '';
		foreach ($this->authors as $a) {
			if (!empty($str)) {
				$str .= $separator;
			}
			$str .= $lastOnly ? $a->getLastName() : $a->getFullName();
		}
		return $str;
	}

	/**
	 * Return a list of author email addresses.
	 * @return array
	 */
	function getAuthorEmails() {
		import('mail.Mail');
		$returner = array();
		foreach ($this->authors as $a) {
			$returner[] = Mail::encodeDisplayName($a->getFullName()) . ' <' . $a->getEmail() . '>';
		}
		return $returner;
	}

	/**
	 * Return first author
	 * @param $lastOnly boolean return lastname only (default false)
	 * @return string
	 */
	function getFirstAuthor($lastOnly = false) {
		$author = $this->authors[0];
		return $lastOnly ? $author->getLastName() : $author->getFullName();
	}


	//
	// Get/set methods
	//

	/**
	 * Get all authors of this article.
	 * @return array Authors
	 */
	function &getAuthors() {
		return $this->authors;
	}

	/**
	 * Get a specific author of this article.
	 * @param $authorId int
	 * @return array Authors
	 */
	function &getAuthor($authorId) {
		$author = null;

		if ($authorId != 0) {
			for ($i=0, $count=count($this->authors); $i < $count && $author == null; $i++) {
				if ($this->authors[$i]->getAuthorId() == $authorId) {
					$author = &$this->authors[$i];
				}
			}
		}
		return $author;
	}

	/**
	 * Get the IDs of all authors removed from this article.
	 * @return array int
	 */
	function &getRemovedAuthors() {
		return $this->removedAuthors;
	}

	/**
	 * Set authors of this article.
	 * @param $authors array Authors
	 */
	function setAuthors($authors) {
		return $this->authors = $authors;
	}

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
	 * Return the localized discipline
	 * @return string
	 */
	function getArticleDiscipline() {
		return $this->getLocalizedData('discipline');
	}

	/**
	 * Return the localized subject classification
	 * @return string
	 */
	function getArticleSubjectClass() {
		return $this->getLocalizedData('subjectClass');
	}

	/**
	 * Return the localized subject
	 * @return string
	 */
	function getArticleSubject() {
		return $this->getLocalizedData('subject');
	}

	/**
	 * Return the localized geographical coverage
	 * @return string
	 */
	function getArticleCoverageGeo() {
		return $this->getLocalizedData('coverageGeo');
	}

	/**
	 * Return the localized chronological coverage
	 * @return string
	 */
	function getArticleCoverageChron() {
		return $this->getLocalizedData('coverageChron');
	}

	/**
	 * Return the localized sample coverage
	 * @return string
	 */
	function getArticleCoverageSample() {
		return $this->getLocalizedData('coverageSample');
	}

	/**
	 * Return the localized type (method/approach)
	 * @return string
	 */
	function getArticleType() {
		return $this->getLocalizedData('type');
	}

	/**
	 * Return the localized sponsor
	 * @return string
	 */
	function getArticleSponsor() {
		return $this->getLocalizedData('sponsor');
	}

	/**
	 * Get the localized article cover filename
	 * @return string
	 */
	function getArticleFileName() {
		return $this->getLocalizedData('fileName');
	}

	/**
	 * Get the localized article cover width
	 * @return string
	 */
	function getArticleWidth() {
		return $this->getLocalizedData('width');
	}

	/**
	 * Get the localized article cover height
	 * @return string
	 */
	function getArticleHeight() {
		return $this->getLocalizedData('height');
	}

	/**
	 * Get the localized article cover filename on the uploader's computer
	 * @return string
	 */
	function getArticleOriginalFileName() {
		return $this->getLocalizedData('originalFileName');
	}

	/**
	 * Get the localized article cover alternate text
	 * @return string
	 */
	function getArticleCoverPageAltText() {
		return $this->getLocalizedData('coverPageAltText');
	}

	/**
	 * Get the localized article cover filename
	 * @return string
	 */
	function getArticleShowCoverPage() {
		return $this->getLocalizedData('showCoverPage');
	}

	/**
	 * Get comments to editor.
	 * @return string
	 */
	function getCommentsToEditor() {
		return $this->getData('commentsToEditor');
	}

	/**
	 * Set comments to editor.
	 * @param $commentsToEditor string
	 */
	function setCommentsToEditor($commentsToEditor) {
		return $this->setData('commentsToEditor', $commentsToEditor);
	}

	/**
	 * Get current review round.
	 * @return int
	 */
	function getCurrentRound() {
		return $this->getData('currentRound');
	}

	/**
	 * Set current review round.
	 * @param $currentRound int
	 */
	function setCurrentRound($currentRound) {
		return $this->setData('currentRound', $currentRound);
	}

	/**
	 * Get editor file id.
	 * @return int
	 */
	function getEditorFileId() {
		return $this->getData('editorFileId');
	}

	/**
	 * Set editor file id.
	 * @param $editorFileId int
	 */
	function setEditorFileId($editorFileId) {
		return $this->setData('editorFileId', $editorFileId);
	}

	/**
	 * Get copyedit file id.
	 * @return int
	 */
	function getCopyeditFileId() {
		return $this->getData('copyeditFileId');
	}

	/**
	 * Set copyedit file id.
	 * @param $copyeditFileId int
	 */
	function setCopyeditFileId($copyeditFileId) {
		return $this->setData('copyeditFileId', $copyeditFileId);
	}

	/**
	 * get expedited
	 * @return boolean
	 */
	function getFastTracked() {
		return $this->getData('fastTracked');
	}
	 
	/**
	 * set fastTracked
	 * @param $fastTracked boolean
	 */
	function setFastTracked($fastTracked) {
		return $this->setData('fastTracked',$fastTracked);
	}	

	/**
	 * Return boolean indicating if author should be hidden in issue ToC.
	 * @return boolean
	 */
	function getHideAuthor() {
		return $this->getData('hideAuthor');
	}

	/**
	 * Set if author should be hidden in issue ToC.
	 * @param $hideAuthor boolean
	 */
	function setHideAuthor($hideAuthor) {
		return $this->setData('hideAuthor', $hideAuthor);
	}

	/**
	 * Return locale string corresponding to RT comments status.
	 * @return string
	 */
	function getCommentsStatusString() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return 'article.comments.disable';
			case COMMENTS_ENABLE:
				return 'article.comments.enable';
			default:
				return 'article.comments.sectionDefault';
		}
	}

	/**
	 * Return boolean indicating if article RT comments should be enabled.
	 * Checks both the section and article comments status. Article status
	 * overrides section status.
	 * @return int 
	 */
	function getEnableComments() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return false;
			case COMMENTS_ENABLE:
				return true;
			case COMMENTS_SECTION_DEFAULT:
				$sectionDao =& DAORegistry::getDAO('SectionDAO');
				$section =& $sectionDao->getSection($this->getSectionId(), $this->getJournalId());
				if ($section->getDisableComments()) {
					return false;
				} else {
					return true;
				}
		}
	}

	/**
	 * Get an associative array matching RT comments status codes with locale strings.
	 * @return array comments status => localeString
	 */
	function &getCommentsStatusOptions() {
		static $commentsStatusOptions = array(
			COMMENTS_SECTION_DEFAULT => 'article.comments.sectionDefault',
			COMMENTS_DISABLE => 'article.comments.disable',
			COMMENTS_ENABLE => 'article.comments.enable'
		);
		return $commentsStatusOptions;
	}
}

?>
