<?php

/**
 * SuppFile.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Supplementary file class.
 *
 * $Id$
 */

class SuppFile extends ArticleFile {

	/**
	 * Constructor.
	 */
	function SuppFile() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of supplementary file.
	 * @return int
	 */
	function getSuppFileId() {
		return $this->getData('suppFileId');
	}
	
	/**
	 * Set ID of supplementary file.
	 * @param $suppFileId int
	 */
	function setSuppFileId($suppFileId) {
		return $this->setData('suppFileId', $suppFileId);
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
	 * Get creator.
	 * @return string
	 */
	function getCreator() {
		return $this->getData('creator');
	}
	
	/**
	 * Set creator.
	 * @param $creator string
	 */
	function setCreator($creator) {
		return $this->setData('creator', $creator);
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
	 * Get custom type.
	 * @return string
	 */
	function getTypeOther() {
		return $this->getData('typeOther');
	}
	
	/**
	 * Set custom type.
	 * @param $typeOther string
	 */
	function setTypeOther($typeOther) {
		return $this->setData('typeOther', $typeOther);
	}
	
	/**
	 * Get file description.
	 * @return string
	 */
	function getDescription() {
		return $this->getData('description');
	}
	
	/**
	 * Set file description.
	 * @param $description string
	 */
	function setDescription($description) {
		return $this->setData('description', $description);
	}
	
	/**
	 * Get publisher.
	 * @return string
	 */
	function getPublisher() {
		return $this->getData('publisher');
	}
	
	/**
	 * Set publisher.
	 * @param $publisher string
	 */
	function setPublisher($publisher) {
		return $this->setData('publisher', $publisher);
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
	 * Get date created.
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}
	
	/**
	 * Set date created.
	 * @param $dateCreated date
	 */
	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated', $dateCreated);
	}
	
	/**
	 * Get source.
	 * @return string
	 */
	function getSource() {
		return $this->getData('source');
	}
	
	/**
	 * Set source.
	 * @param $source string
	 */
	function setSource($source) {
		return $this->setData('source', $source);
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
	 * Check if file is available to peer reviewers.
	 * @return boolean
	 */
	function getShowReviewers() {
		return $this->getData('showReviewers');
	}
	
	/**
	 * Set if file is available to peer reviewers or not.
	 * @param boolean
	 */
	function setShowReviewers($showReviewers) {
		return $this->setData('showReviewers', $showReviewers);
	}
	
	/**
	 * Get date file was submitted.
	 * @return datetime
	 */
	function getDateSubmitted() {
		return $this->getData('dateSubmitted');
	}
	
	/**
	 * Set date file was submitted.
	 * @param $dateSubmitted datetime
	 */
	function setDateSubmitted($dateSubmitted) {
		return $this->setData('dateSubmitted', $dateSubmitted);
	}
}

?>
