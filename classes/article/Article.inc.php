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
			$author->setSequence(count($this->authors));
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
	
}

?>
