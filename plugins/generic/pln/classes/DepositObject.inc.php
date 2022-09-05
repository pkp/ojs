<?php

/**
 * @file classes/DepositObject.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DepositObject
 * @brief Basic class describing a deposit stored in the PLN
 */

class DepositObject extends DataObject {
	/**
	 * Get the content object that's referenced by this deposit object
	 * @return Object (Issue,Submission)
	 */
	public function getContent() {
		switch ($this->getObjectType()) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
				return $issueDao->getIssueById($this->getObjectId(),$this->getJournalId());
			case 'PublishedArticle': // Legacy (OJS pre-3.2)
			case PLN_PLUGIN_DEPOSIT_OBJECT_SUBMISSION:
				$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /** @var $submissionDao SubmissionDAO */
				$submission = $submissionDao->getById($this->getObjectId());
				if ($submission->getContextId() != $this->getJournalId()) throw new Exception('Submission context and context ID do not agree!');
				return $submission;
		}
		throw new Exception('Unknown object type!');
	}

	/**
	 * Set the content object that's referenced by this deposit object
	 * @param $content Object (Issue,Submission)
	 */
	public function setContent($content) {
		if (is_a($content, PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE) || is_a($content, PLN_PLUGIN_DEPOSIT_OBJECT_SUBMISSION)) {
			$this->setObjectId($content->getId());
			$this->setObjectType(get_class($content));
		} else {
			throw new Exception('Unknown content type!');
		}
	}

	/**
	 * Get type of the object being referenced by this deposit object
	 * @return string
	 */
	public function getObjectType() {
		return $this->getData('objectType');
	}

	/**
	 * Set type of the object being referenced by this deposit object
	 * @param string
	 */
	public function setObjectType($objectType) {
		$this->setData('objectType', $objectType);
	}

	/**
	 * Get the id of the object being referenced by this deposit object
	 * @return int
	 */
	public function getObjectId() {
		return $this->getData('objectId');
	}

	/**
	 * Set the id of the object being referenced by this deposit object
	 * @param int
	 */
	public function setObjectId($objectId) {
		$this->setData('objectId', $objectId);
	}

	/**
	 * Get the journal id of this deposit object
	 * @return int
	 */
	public function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal id of this deposit object
	 * @param int
	 */
	public function setJournalId($journalId) {
		$this->setData('journalId', $journalId);
	}

	/**
	 * Get the id of the deposit which includes this deposit object
	 * @return int
	 */
	public function getDepositId() {
		return $this->getData('depositId');
	}

	/**
	 * Set the id of the deposit which includes this deposit object
	 * @param int
	 */
	public function setDepositId($depositId) {
		$this->setData('depositId', $depositId);
	}

	/**
	 * Get the date of deposit object creation
	 * @return DateTime
	 */
	public function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * Set the date of deposit object creation
	 * @param $dateCreated DateTime
	 */
	public function setDateCreated($dateCreated) {
		$this->setData('dateCreated', $dateCreated);
	}

	/**
	 * Get the modification date of the deposit object
	 * @return DateTime
	 */
	public function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * Set the modification date of the deposit object
	 * @param $dateModified DateTime
	 */
	public function setDateModified($dateModified) {
		$this->setData('dateModified', $dateModified);
	}
}
