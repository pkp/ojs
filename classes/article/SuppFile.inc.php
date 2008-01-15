<?php

/**
 * @file SuppFile.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 * @class SuppFile
 *
 * Supplementary file class.
 *
 * $Id$
 */

import('article.ArticleFile');

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
	 * Get public ID of supplementary file.
	 * @return string
	 */
	function getPublicSuppFileId() {
		// Ensure that blanks are treated as nulls.
		$returner = $this->getData('publicSuppFileId');
		if ($returner === '') return null;
		return $returner;
	}

	/**
	 * Set public ID of supplementary file.
	 * @param $suppFileId string
	 */
	function setPublicSuppFileId($publicSuppFileId) {
		return $this->setData('publicSuppFileId', $publicSuppFileId);
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
	 * Get localized title
	 * @return string
	 */
	function getSuppFileTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get title.
	 * @param $locale string
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * Set title.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get localized creator
	 * @return string
	 */
	function getSuppFileCreator() {
		return $this->getLocalizedData('creator');
	}

	/**
	 * Get creator.
	 * @param $locale string
	 * @return string
	 */
	function getCreator($locale) {
		return $this->getData('creator', $locale);
	}

	/**
	 * Set creator.
	 * @param $creator string
	 * @param $locale string
	 */
	function setCreator($creator, $locale) {
		return $this->setData('creator', $creator, $locale);
	}

	/**
	 * Get localized subject
	 * @return string
	 */
	function getSuppFileSubject() {
		return $this->getLocalizedData('subject');
	}

	/**
	 * Get subject.
	 * @param $locale string
	 * @return string
	 */
	function getSubject($locale) {
		return $this->getData('subject', $locale);
	}

	/**
	 * Set subject.
	 * @param $subject string
	 * @param $locale string
	 */
	function setSubject($subject, $locale) {
		return $this->setData('subject', $subject, $locale);
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
	 * Get localized subject
	 * @return string
	 */
	function getSuppFileTypeOther() {
		return $this->getLocalizedData('typeOther');
	}

	/**
	 * Get custom type.
	 * @param $locale string
	 * @return string
	 */
	function getTypeOther($locale) {
		return $this->getData('typeOther', $locale);
	}

	/**
	 * Set custom type.
	 * @param $typeOther string
	 * @param $locale string
	 */
	function setTypeOther($typeOther, $locale) {
		return $this->setData('typeOther', $typeOther, $locale);
	}

	/**
	 * Get localized description
	 * @return string
	 */
	function getSuppFileDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get file description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set file description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}

	/**
	 * Get localized publisher
	 * @return string
	 */
	function getSuppFilePublisher() {
		return $this->getLocalizedData('publisher');
	}

	/**
	 * Get publisher.
	 * @param $locale string
	 * @return string
	 */
	function getPublisher($locale) {
		return $this->getData('publisher', $locale);
	}

	/**
	 * Set publisher.
	 * @param $publisher string
	 * @param $locale string
	 */
	function setPublisher($publisher, $locale) {
		return $this->setData('publisher', $publisher, $locale);
	}

	/**
	 * Get localized sponsor
	 * @return string
	 */
	function getSuppFileSponsor() {
		return $this->getLocalizedData('sponsor');
	}

	/**
	 * Get sponsor.
	 * @param $locale string
	 * @return string
	 */
	function getSponsor($locale) {
		return $this->getData('sponsor', $locale);
	}

	/**
	 * Set sponsor.
	 * @param $sponsor string
	 * @param $locale string
	 */
	function setSponsor($sponsor, $locale) {
		return $this->setData('sponsor', $sponsor, $locale);
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
	 * Get localized source
	 * @return string
	 */
	function getSuppFileSource() {
		return $this->getLocalizedData('source');
	}

	/**
	 * Get source.
	 * @param $locale string
	 * @return string
	 */
	function getSource($locale) {
		return $this->getData('source', $locale);
	}

	/**
	 * Set source.
	 * @param $source string
	 * @param $locale string
	 */
	function setSource($source, $locale) {
		return $this->setData('source', $source, $locale);
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

	/**
	 * Get sequence order of supplementary file.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence order of supplementary file.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Return the "best" supp file ID -- If a public ID is set,
	 * use it; otherwise use the internal Id. (Checks the journal
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $journal Object the journal this article is in
	 * @return string
	 */
	function getBestSuppFileId($journal = null) {
		// Retrieve the journal, if necessary.
		if (!isset($journal)) {
			$articleDao = &DAORegistry::getDAO('ArticleDAO');
			$article = &$articleDao->getArticleById($this->getArticleId());
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournal($article->getJournalId());
		}

		if ($journal->getSetting('enablePublicSuppFileId')) {
			$publicSuppFileId = $this->getPublicSuppFileId();
			if (!empty($publicSuppFileId)) return $publicSuppFileId;
		}
		return $this->getSuppFileId();
	}
}

?>
