<?php

/**
 * Article.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Article class.
 *
 * $Id$
 */

class Article extends DataObject {

	/** @var array Authors of this article */
	var $authors;

	/** @var array IDs of Authors removed from this article */
	var $removedAuthors;

	/**
	 * Constructor.
	 */
	function Article() {
		parent::DataObject();
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
		// FIXME this is evil
		$alternateLocaleNum = Locale::isAlternateJournalLocale($this->getData('journalId'));
		switch ($alternateLocaleNum) {
			case 1:
				$title = $this->getTitleAlt1();
				break;
			case 2:
				$title = $this->getTitleAlt2();
				break;
		}
		
		if (isset($title) && !empty($title)) {
			return $title;
		} else {
			return $this->getTitle();
		}
	}
	
	/**
	 * Get "localized" article abstract (if applicable).
	 * @return string
	 */
	function getArticleAbstract() {
		$alternateLocaleNum = Locale::isAlternateJournalLocale($this->getData('journalId'));
		switch ($alternateLocaleNum) {
			case 1:
				$abstract = $this->getAbstractAlt1();
				break;
			case 2:
				$abstract = $this->getAbstractAlt2();
				break;
		}
		
		if (isset($abstract) && !empty($abstract)) {
			return $abstract;
		} else {
			return $this->getAbstract();
		}
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
	 * Get user ID of the article submitter.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	
	/**
	 * Set user ID of the article submitter.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}
	
	/**
	 * Return the user of the article submitter.
	 * @return User
	 */
	function getUser() {
		$userDao = &DAORegistry::getDAO('UserDao');
		return $userDao->getUser($this->getUserId());
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
	 * Get title.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set title.
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
	}
	
	/**
	 * Get alternate title #1.
	 * @return string
	 */
	function getTitleAlt1() {
		return $this->getData('titleAlt1');
	}
	
	/**
	 * Set alternate title #1.
	 * @param $titleAlt1 string
	 */
	function setTitleAlt1($titleAlt1) {
		return $this->setData('titleAlt1', $titleAlt1);
	}
	
	/**
	 * Get alternate title #2.
	 * @return string
	 */
	function getTitleAlt2() {
		return $this->getData('titleAlt2');
	}
	
	/**
	 * Set alternate title #2.
	 * @param $titleAlt2 string
	 */
	function setTitleAlt2($titleAlt2) {
		return $this->setData('titleAlt2', $titleAlt2);
	}
	
	/**
	 * Get abstract.
	 * @return string
	 */
	function getAbstract() {
		return $this->getData('abstract');
	}
	
	/**
	 * Set abstract.
	 * @param $abstract string
	 */
	function setAbstract($abstract) {
		return $this->setData('abstract', $abstract);
	}
	
	/**
	 * Get alternate abstract #1.
	 * @return string
	 */
	function getAbstractAlt1() {
		return $this->getData('abstractAlt1');
	}
	
	/**
	 * Set alternate abstract #1.
	 * @param $abstractAlt1 string
	 */
	function setAbstractAlt1($abstractAlt1) {
		return $this->setData('abstractAlt1', $abstractAlt1);
	}
	
	/**
	 * Get alternate abstract #2.
	 * @return string
	 */
	function getAbstractAlt2() {
		return $this->getData('abstractAlt2');
	}
	
	/**
	 * Set alternate abstract #2
	 * @param $abstractAlt2 string
	 */
	function setAbstractAlt2($abstractAlt2) {
		return $this->setData('abstractAlt2', $abstractAlt2);
	}
	
	/**
	 * Get discipline.
	 * @return string
	 */
	function getDiscipline() {
		return $this->getData('discipline');
	}
	
	/**
	 * Set discipline.
	 * @param $discipline string
	 */
	function setDiscipline($discipline) {
		return $this->setData('discipline', $discipline);
	}
	
	/**
	 * Get subject classification.
	 * @return string
	 */
	function getSubjectClass() {
		return $this->getData('subjectClass');
	}
	
	/**
	 * Set subject classification.
	 * @param $subjectClass string
	 */
	function setSubjectClass($subjectClass) {
		return $this->setData('subjectClass', $subjectClass);
	}
	
	/**
	 * Get subject.
	 * @return string
	 */
	function getSubject() {
		return $this->getData('subject');
	}
	
	/**
	 * Set subject.
	 * @param $subject string
	 */
	function setSubject($subject) {
		return $this->setData('subject', $subject);
	}
	
	/**
	 * Get geographical coverage.
	 * @return string
	 */
	function getCoverageGeo() {
		return $this->getData('coverageGeo');
	}
	
	/**
	 * Set geographical coverage.
	 * @param $coverageGeo string
	 */
	function setCoverageGeo($coverageGeo) {
		return $this->setData('coverageGeo', $coverageGeo);
	}
	
	/**
	 * Get chronological coverage.
	 * @return string
	 */
	function getCoverageChron() {
		return $this->getData('coverageChron');
	}
	
	/**
	 * Set chronological coverage.
	 * @param $coverageChron string
	 */
	function setCoverageChron($coverageChron) {
		return $this->setData('coverageChron', $coverageChron);
	}
	
	/**
	 * Get research sample coverage.
	 * @return string
	 */
	function getCoverageSample() {
		return $this->getData('coverageSample');
	}
	
	/**
	 * Set geographical coverage.
	 * @param $coverageSample string
	 */
	function setCoverageSample($coverageSample) {
		return $this->setData('coverageSample', $coverageSample);
	}
	
	/**
	 * Get type (method/approach).
	 * @return string
	 */
	function getType() {
		return $this->getData('type');
	}
	
	/**
	 * Set type (method/approach).
	 * @param $type string
	 */
	function setType($type) {
		return $this->setData('type', $type);
	}
	
	/**
	 * Get language.
	 * @return string
	 */
	function getLanguage() {
		return $this->getData('language');
	}
	
	/**
	 * Set language.
	 * @param $language string
	 */
	function setLanguage($language) {
		return $this->setData('language', $language);
	}
	
	/**
	 * Get sponsor.
	 * @return string
	 */
	function getSponsor() {
		return $this->getData('sponsor');
	}
	
	/**
	 * Set sponsor.
	 * @param $sponsor string
	 */
	function setSponsor($sponsor) {
		return $this->setData('sponsor', $sponsor);
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
	 * Get submission date.
	 * @return date
	 */
	function getDateSubmitted() {
		return $this->getData('dateSubmitted');
	}
	
	/**
	 * Set submission date.
	 * @param $dateSubmitted date
	 */
	function setDateSubmitted($dateSubmitted) {
		return $this->setData('dateSubmitted', $dateSubmitted);
	}
	
	/**
	 * Get the date of the last status modification.
	 * @return date
	 */
	function getDateStatusModified() {
		return $this->getData('dateStatusModified');
	}
	
	/**
	 * Set the date of the last status modification.
	 * @param $dateModified date
	 */
	function setDateStatusModified($dateModified) {
		return $this->setData('dateStatusModified', $dateModified);
	}
	
	/**
	 * Get the date of the last modification.
	 * @return date
	 */
	function getLastModified() {
		return $this->getData('lastModified');
	}
	
	/**
	 * Set the date of the last modification.
	 * @param $dateModified date
	 */
	function setLastModified($dateModified) {
		return $this->setData('lastModified', $dateModified);
	}
	
	/**
	 * Stamp the date of the last modification to the current time.
	 */
	function stampModified() {
		return $this->setLastModified(Core::getCurrentDate());
	}
	
	/**
	 * Stamp the date of the last status modification to the current time.
	 */
	function stampStatusModified() {
		return $this->setDateStatusModified(Core::getCurrentDate());
	}
	
	/**
	 * Get article status.
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}
	
	/**
	 * Set article status.
	 * @param $status int
	 */
	function setStatus($status) {
		return $this->setData('status', $status);
	}
	
	/**
	 * Get submission progress (most recently completed submission step).
	 * @return int
	 */
	function getSubmissionProgress() {
		return $this->getData('submissionProgress');
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
	 * Set submission progress.
	 * @param $submissionProgress int
	 */
	function setSubmissionProgress($submissionProgress) {
		return $this->setData('submissionProgress', $submissionProgress);
	}
	
	/**
	 * Get submission file id.
	 * @return int
	 */
	function getSubmissionFileId() {
		return $this->getData('submissionFileId');
	}
	
	/**
	 * Set submission file id.
	 * @param $submissionFileId int
	 */
	function setSubmissionFileId($submissionFileId) {
		return $this->setData('submissionFileId', $submissionFileId);
	}
	
	/**
	 * Get revised file id.
	 * @return int
	 */
	function getRevisedFileId() {
		return $this->getData('revisedFileId');
	}
	
	/**
	 * Set revised file id.
	 * @param $revisedFileId int
	 */
	function setRevisedFileId($revisedFileId) {
		return $this->setData('revisedFileId', $revisedFileId);
	}
	
	/**
	 * Get review file id.
	 * @return int
	 */
	function getReviewFileId() {
		return $this->getData('reviewFileId');
	}
	
	/**
	 * Set review file id.
	 * @param $reviewFileId int
	 */
	function setReviewFileId($reviewFileId) {
		return $this->setData('reviewFileId', $reviewFileId);
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
	 * Get public article id
	 * @return string
	 */
	function getPublicArticleId() {
		return $this->getData('publicArticleId');
	}

	/**
	 * Set public article id
	 * @param $publicArticleId string
	 */
	function setPublicArticleId($publicArticleId) {
		return $this->setData('publicArticleId', $publicArticleId);
	}
}

?>
