<?php

/**
 * @file classes/article/ArticleTombstone.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleTombstone
 * @ingroup article
 * @see ArticleTombstoneDAO
 *
 * @brief Class for article tombstones.
 */

class ArticleTombstone extends DataObject {
	/**
	 * Constructor.
	 */
	function ArticleTombstone() {
		parent::DataObject();
	}

	/**
	 * get journal id
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * set journal id
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * get article id
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}

	/**
	 * set article id
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}
	
	/**
	 * get date deleted
	 * @return date
	 */
	function getDateDeleted() {
		return $this->getData('dateDeleted');
	}

	/**
	 * set date deleted
	 * @param $dateDeleted date
	 */
	function setDateDeleted($dateDeleted) {
		return $this->setData('dateDeleted', $dateDeleted);
	}
	
	/**
	 * Stamp the date of the deletion to the current time.
	 */
	function stampDateDeleted() {
		return $this->setDateDeleted(Core::getCurrentDate());
	}	
	
	/**
	 * get section id
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}

	/**
	 * set section id
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
	}

	/**
	 * Get oai setSpec.
	 * @return string
	 */
	function getSetSpec() {
		return $this->getData('setSpec');
	}

	/**
	 * Set oai setSpec.
	 * @param $setSpec string
	 */
	function setSetSpec($setSpec) {
		return $this->setData('setSpec', $setSpec);
	}	
		
	/**
	 * Get oai setName.
	 * @return string
	 */
	function getSetName() {
		return $this->getData('setName');
	}

	/**
	 * Set oai setName.
	 * @param $setName string
	 */
	function setSetName($setName) {
		return $this->setData('setName', $setName);
	}	
	
	/**
	 * Get oai identifier.
	 * @return string
	 */
	function getOAIIdentifier() {
		return $this->getData('oaiIdentifier');
	}

	/**
	 * Set oai identifier.
	 * @param $oaiIdentifier string
	 */
	function setOAIIdentifier($oaiIdentifier) {
		return $this->setData('oaiIdentifier', $oaiIdentifier);
	}	
		

}

?>