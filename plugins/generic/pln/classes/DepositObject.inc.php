<?php

/**
 * @file plugins/generic/pln/classes/DepositObject.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositObject
 * @ingroup plugins_generic_pln
 *
 * @brief Basic class describing a deposit stored in the PLN
 */

class DepositObject extends DataObject {

	/**
	 * Constructor
	 * @return DepositObject
	 */
	function DepositObject() {
		parent::DataObject();
	}

	/**
	 * Get the content object that's referenced by this deposit object
	 * @return Object (Issue,Article)
	 */
	function getContent() {
		switch ($this->getObjectType()) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				return $issueDao->getIssueById($this->getObjectId(),$this->getJournalId());
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				return $articleDao->getArticle($this->getObjectId(),$this->getJournalId());
		}
	}

	/**
	 * Set the content object that's referenced by this deposit object
	 * @param $content Object (Issue,Article)
	 */
	function setContent(&$content) {
		switch (get_class($content)) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
				$objectType = get_class($content);
				$objectId = $content->getId();
				$this->setData('object_id', $objectId);
				$this->setData('object_type', $objectType);
				break;
			default:
		}
		
	}

	/**
	 * Get type of the object being referenced by this deposit object
	 * @return string
	 */
	function getObjectType() {
		return $this->getData('object_type');
	}

	/**
	 * Set type of the object being referenced by this deposit object
	 * @param string
	 */
	function setObjectType($objectType) {
		$this->setData('object_type', $objectType);
	}

	/**
	 * Get the id of the object being referenced by this deposit object
	 * @return string
	 */
	function getObjectId() {
		return $this->getData('object_id');
	}

	/**
	 * Set the id of the object being referenced by this deposit object
	 * @param string
	 */
	function setObjectId($objectId) {
		$this->setData('object_id', $objectId);
	}

	/**
	 * Get the journal id of this deposit object
	 * @return string
	 */
	function getJournalId() {
		return $this->getData('journal_id');
	}

	/**
	 * Set the journal id of this deposit object
	 * @param string
	 */
	function setJournalId($journalId) {
		$this->setData('journal_id', $journalId);
	}

	/**
	 * Get the id of the deposit which includes this deposit object
	 * @return string
	 */
	function getDepositId() {
		return $this->getData('deposit_id');
	}

	/**
	 * Set the id of the deposit which includes this deposit object
	 * @param string
	 */
	function setDepositId($depositId) {
		$this->setData('deposit_id', $depositId);
	}

	/**
	 * Get the date of deposit object creation
	 * @return DateTime
	 */
	function getDateCreated() {
		return $this->getData('date_created');
	}

	/**
	 * Set the date of deposit object creation
	 * @param $dateCreated boolean
	 */
	function setDateCreated($dateCreated) {
		$this->setData('date_created', $dateCreated);
	}

	/**
	 * Get the modification date of the deposit object
	 * @return DateTime
	 */
	function getDateModified() {
		return $this->getData('date_modified');
	}

	/**
	 * Set the modification date of the deposit object
	 * @param $dateModified boolean
	 */
	function setDateModified($dateModified) {
		$this->setData('date_modified', $dateModified);
	}

}

?>
