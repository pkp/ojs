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

	function DepositObject() {
		parent::DataObject();
	}
	
	/**
	* Get/Set content helpers
	*/
	function getContent() {
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
		switch ($this->getObjectType()) {
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE]:
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				return $issueDao->getIssueById($this->getObjectId(),$this->getJournalId());
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE]:
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				return $articleDao->getArticle($this->getObjectId(),$this->getJournalId());
		}
	}
	function setContent(&$content) {
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
		switch (get_class($content)) {
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE]:
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE]:
				$objectType = get_class($content);
				$objectId = $content->getId();
				$this->setData('object_id', $objectId);
				$this->setData('object_type', $objectType);
				break;
			default:
		}
		
	}

	/**
	* Get/Set object type
	*/
	function getObjectType() {
		return $this->getData('object_type');
	}
	function setObjectType($objectType) {
		$this->setData('object_type', $objectType);
	}

	/**
	* Get/Set object id
	*/
	function getObjectId() {
		return $this->getData('object_id');
	}
	function setObjectId($objectId) {
		$this->setData('object_id', $objectId);
	}

	/**
	* Get/Set deposit journal id
	*/
	function getJournalId() {
		return $this->getData('journal_id');
	}
	function setJournalId($journalId) {
		$this->setData('journal_id', $journalId);
	}
	
	/**
	* Get/Set deposit id
	*/
	function getDepositId() {
		return $this->getData('deposit_id');
	}
	function setDepositId($depositId) {
		$this->setData('deposit_id', $depositId);
	}

	/**
	* Get/Set deposit creation date
	*/
	function getDateCreated() {
		return $this->getData('date_created');
	}
	function setDateCreated($dateCreated) {
		$this->setData('date_created', $dateCreated);
	}

	/**
	* Get/Set deposit modification date
	*/
	function getDateModified() {
		return $this->getData('date_modified');
	}
	function setDateModified($dateModified) {
		$this->setData('date_modified', $dateModified);
	}

}

?>
