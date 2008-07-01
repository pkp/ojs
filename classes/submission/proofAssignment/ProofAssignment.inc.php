<?php

/**
 * @file classes/submission/proofAssignment/ProofAssignment.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProofAssignment
 * @ingroup submission
 * @see ProofAssignmentDAO
 *
 * @brief Describes proofread assignment properties.
 */

// $Id$


class ProofAssignment extends DataObject {

	/**
	 * Constructor.
	 */
	function ProofAssignment() {
		parent::DataObject();
	}

	/**
	 * Get ID of proof assignment.
	 * @return int
	 */
	function getProofId() {
		return $this->getData('proofId');
	}

	/**
	 * Set ID of proof assignment
	 * @param $proofId int
	 */
	function setProofId($proofId) {
		return $this->setData('proofId', $proofId);
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
	 * Get ID of proofreader.
	 * @return int
	 */
	function getProofreaderId() {
		return $this->getData('proofreaderId');
	}

	/**
	 * Set ID of proofreader.
	 * @param $proofreaderId int
	 */
	function setProofreaderId($proofreaderId) {
		return $this->setData('proofreaderId', $proofreaderId);
	}

	/**
	 * Get the author notified date.
	 * @return string
	 */
	function getDateAuthorNotified() {
		return $this->getData('dateAuthorNotified');
	}

	/**
	 * Set the author notified date.
	 * @param $dateAuthorNotified string
	 */
	function setDateAuthorNotified($dateAuthorNotified) {
		return $this->setData('dateAuthorNotified', $dateAuthorNotified);
	}

	/**
	 * Get the author underway date.
	 * @return string
	 */
	function getDateAuthorUnderway() {
		return $this->getData('dateAuthorUnderway');
	}

	/**
	 * Set the author underway date.
	 * @param $dateAuthorUnderway string
	 */
	function setDateAuthorUnderway($dateAuthorUnderway) {
		return $this->setData('dateAuthorUnderway', $dateAuthorUnderway);
	}

	/**
	 * Get the author completed date.
	 * @return string
	 */
	function getDateAuthorCompleted() {
		return $this->getData('dateAuthorCompleted');
	}

	/**
	 * Set the author completed date.
	 * @param $dateAuthorCompleted string
	 */
	function setDateAuthorCompleted($dateAuthorCompleted) {
		return $this->setData('dateAuthorCompleted', $dateAuthorCompleted);
	}

	/**
	 * Get the author acknowledged date.
	 * @return string
	 */
	function getDateAuthorAcknowledged() {
		return $this->getData('dateAuthorAcknowledged');
	}

	/**
	 * Set the author acknowledged date.
	 * @param $dateAuthorAcknowledged string
	 */
	function setDateAuthorAcknowledged($dateAuthorAcknowledged) {
		return $this->setData('dateAuthorAcknowledged', $dateAuthorAcknowledged);
	}

	/**
	 * Get the proofreader notified date.
	 * @return string
	 */
	function getDateProofreaderNotified() {
		return $this->getData('dateProofreaderNotified');
	}

	/**
	 * Set the proofreader notified date.
	 * @param $dateProofreaderNotified string
	 */
	function setDateProofreaderNotified($dateProofreaderNotified) {
		return $this->setData('dateProofreaderNotified', $dateProofreaderNotified);
	}

	/**
	 * Get the proofreader underway date.
	 * @return string
	 */
	function getDateProofreaderUnderway() {
		return $this->getData('dateProofreaderUnderway');
	}

	/**
	 * Set the proofreader underway date.
	 * @param $dateProofreaderUnderway string
	 */
	function setDateProofreaderUnderway($dateProofreaderUnderway) {
		return $this->setData('dateProofreaderUnderway', $dateProofreaderUnderway);
	}

	/**
	 * Get the proofreader completed date.
	 * @return string
	 */
	function getDateProofreaderCompleted() {
		return $this->getData('dateProofreaderCompleted');
	}

	/**
	 * Set the proofreader completed date.
	 * @param $dateProofreaderCompleted string
	 */
	function setDateProofreaderCompleted($dateProofreaderCompleted) {
		return $this->setData('dateProofreaderCompleted', $dateProofreaderCompleted);
	}

	/**
	 * Get the proofreader acknowledged date.
	 * @return string
	 */
	function getDateProofreaderAcknowledged() {
		return $this->getData('dateProofreaderAcknowledged');
	}

	/**
	 * Set the proofreader acknowledged date.
	 * @param $dateProofreaderAcknowledged string
	 */
	function setDateProofreaderAcknowledged($dateProofreaderAcknowledged) {
		return $this->setData('dateProofreaderAcknowledged', $dateProofreaderAcknowledged);
	}

	/**
	 * Get the layoutEditor notified date.
	 * @return string
	 */
	function getDateLayoutEditorNotified() {
		return $this->getData('dateLayoutEditorNotified');
	}

	/**
	 * Set the layoutEditor notified date.
	 * @param $dateLayoutEditorNotified string
	 */
	function setDateLayoutEditorNotified($dateLayoutEditorNotified) {
		return $this->setData('dateLayoutEditorNotified', $dateLayoutEditorNotified);
	}

	/**
	 * Get the layoutEditor underway date.
	 * @return string
	 */
	function getDateLayoutEditorUnderway() {
		return $this->getData('dateLayoutEditorUnderway');
	}

	/**
	 * Set the layoutEditor underway date.
	 * @param $dateLayoutEditorUnderway string
	 */
	function setDateLayoutEditorUnderway($dateLayoutEditorUnderway) {
		return $this->setData('dateLayoutEditorUnderway', $dateLayoutEditorUnderway);
	}

	/**
	 * Get the layoutEditor completed date.
	 * @return string
	 */
	function getDateLayoutEditorCompleted() {
		return $this->getData('dateLayoutEditorCompleted');
	}

	/**
	 * Set the layoutEditor completed date.
	 * @param $dateLayoutEditorCompleted string
	 */
	function setDateLayoutEditorCompleted($dateLayoutEditorCompleted) {
		return $this->setData('dateLayoutEditorCompleted', $dateLayoutEditorCompleted);
	}

	/**
	 * Get the layoutEditor acknowledged date.
	 * @return string
	 */
	function getDateLayoutEditorAcknowledged() {
		return $this->getData('dateLayoutEditorAcknowledged');
	}

	/**
	 * Set the layoutEditor acknowledged date.
	 * @param $dateLayoutEditorAcknowledged string
	 */
	function setDateLayoutEditorAcknowledged($dateLayoutEditorAcknowledged) {
		return $this->setData('dateLayoutEditorAcknowledged', $dateLayoutEditorAcknowledged);
	}

	/**
	 * Get the proofreader's first name.
	 * @return string
	 */
	function getProofreaderFirstName() {
		return $this->getData('proofreaderFirstName');
	}

	/**
	 * Set the proofreader's first name.
	 * @param $proofreaderFirstName string
	 */
	function setProofreaderFirstName($proofreaderFirstName) {
		return $this->setData('proofreaderFirstName', $proofreaderFirstName);
	}

	/**
	 * Get the proofreader's last name.
	 * @return string
	 */
	function getProofreaderLastName() {
		return $this->getData('proofreaderLastName');
	}

	/**
	 * Set the proofreader's last name.
	 * @param $proofreaderLastName string
	 */
	function setProofreaderLastName($proofreaderLastName) {
		return $this->setData('proofreaderLastName', $proofreaderLastName);
	}

	/**
	 * Get the proofreader's email.
	 * @return string
	 */
	function getProofreaderEmail() {
		return $this->getData('proofreaderEmail');
	}

	/**
	 * Set the proofreader's email.
	 * @param $proofreaderEmail string
	 */
	function setProofreaderEmail($proofreaderEmail) {
		return $this->setData('proofreaderEmail', $proofreaderEmail);
	}

	/**
	 * Get the proofreader's full name
	 * @return string
	 */
	function getProofreaderFullName() {
		return $this->getProofreaderFirstName() . ' ' . $this->getProofreaderLastName();
	}

}

?>
