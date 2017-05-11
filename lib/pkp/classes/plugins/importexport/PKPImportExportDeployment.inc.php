<?php
/**
 * @defgroup classes_plugins_importexport import/export deployment
 */

/**
 * @file classes/plugins/importexport/PKPImportExportDeployment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPImportExportDeployment
 * @ingroup plugins_importexport
 *
 * @brief Base class configuring the import/export process to an
 * application's specifics.
 */

class PKPImportExportDeployment {
	/** @var Context The current import/export context */
	var $_context;

	/** @var User The current import/export user */
	var $_user;

	/** @var Submission The current import/export submission */
	var $_submission;

	/** @var array The processed import objects IDs */
	var $_processedObjectsIds;

	/** @var array Connection between the file and revision IDs from the XML import file and the DB file IDs */
	var $_fileDBIds;

	/**
	 * Constructor
	 * @param $context Context
	 * @param $user User optional
	 */
	function __construct($context, $user=null) {
		$this->setContext($context);
		$this->setUser($user);
		$this->setSubmission(null);
		$this->setFileDBIds(array());
		$this->_processedObjectsIds = array();
	}

	//
	// Deployment items for subclasses to override
	//
	/**
	 * Get the submission node name
	 * @return string
	 */
	function getSubmissionNodeName() {
		assert(false);
	}

	/**
	 * Get the submissions node name
	 * @return string
	 */
	function getSubmissionsNodeName() {
		assert(false);
	}

	/**
	 * Get the representation node name
	 */
	function getRepresentationNodeName() {
		assert(false);
	}

	/**
	 * Get the namespace URN
	 * @return string
	 */
	function getNamespace() {
		assert(false);
	}

	/**
	 * Get the schema filename.
	 * @return string
	 */
	function getSchemaFilename() {
		assert(false);
	}


	//
	// Getter/setters
	//
	/**
	 * Set the import/export context.
	 * @param $context Context
	 */
	function setContext($context) {
		$this->_context = $context;
	}

	/**
	 * Get the import/export context.
	 * @return Context
	 */
	function getContext() {
		return $this->_context;
	}

	/**
	 * Set the import/export submission.
	 * @param $submission Submission
	 */
	function setSubmission($submission) {
		$this->_submission = $submission;
		if ($submission) $this->addProcessedObjectId(ASSOC_TYPE_SUBMISSION, $submission->getId());
	}

	/**
	 * Get the import/export submission.
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * Add the processed object ID.
	 * @param $assocType integer ASSOC_TYPE_...
	 * @param $assocId integer
	 */
	function addProcessedObjectId($assocType, $assocId) {
		$this->_processedObjectsIds[$assocType][$assocId] = array();
	}

	/**
	 * Add the error message to the processed object ID.
	 * @param $assocType integer ASSOC_TYPE_...
	 * @param $assocId integer
	 * @param $errorMsg string
	 */
	function addError($assocType, $assocId, $errorMsg) {
		$this->_processedObjectsIds[$assocType][$assocId][] = $errorMsg;
	}

	/**
	 * Get the processed objects IDs.
	 * @param $assocType integer ASSOC_TYPE_...
	 * @return array Associative array (assoc object Id => array of errors)
	 */
	function getProcessedObjectsIds($assocType) {
		if (array_key_exists($assocType, $this->_processedObjectsIds)) {
			return $this->_processedObjectsIds[$assocType];
		}
		return null;
	}

	/**
	 * Remove the processed objects.
	 * @param $assocType integer ASSOC_TYPE_...
	 */
	function removeImportedObjects($assocType) {
		switch ($assocType) {
			case ASSOC_TYPE_SUBMISSION:
				$processedSubmisssionsIds = $this->getProcessedObjectsIds(ASSOC_TYPE_SUBMISSION);
				if (!empty($processedSubmisssionsIds)) {
					$submissionDao = Application::getSubmissionDAO();
					foreach ($processedSubmisssionsIds as $submissionId => $errorMessages) {
						$submissionDao->deleteById($submissionId);
					}
				}
				break;
		}
	}

	/**
	 * Set the import/export user.
	 * @param $user User
	 */
	function setUser($user) {
		$this->_user = $user;
	}

	/**
	 * Get the import/export user.
	 * @return User
	 */
	function getUser() {
		return $this->_user;
	}

	/**
	 * Get the array of the inserted file DB Ids.
	 * @return array
	 */
	function getFileDBIds() {
		return $this->_fileDBIds;
	}

	/**
	 * Set the array of the inserted file DB Ids.
	 * @param $fileDBIds array
	 */
	function setFileDBIds($fileDBIds) {
		return $this->_fileDBIds = $fileDBIds;
	}

	/**
	 * Get the file DB Id.
	 * @param $fileId integer
	 * @param $revisionId integer
	 * @return integer
	 */
	function getFileDBId($fileId, $revisionId = null) {
		if (array_key_exists($fileId, $this->_fileDBIds)) {
			// is there already the revisionId?
			if ($revisionId) {
				if (array_key_exists($revisionId, $this->_fileDBIds[$fileId])) {
					return $this->_fileDBIds[$fileId][$revisionId];
				} else {
					return null;
				}
			} else {
				// the revisionId is not important, but the fileId
				// the DB Id is unique for a fileId
				return current($this->_fileDBIds[$fileId]);
			}
		}
		return null;
	}

	/**
	 * Set the file DB Id.
	 * @param $fileId integer
	 * @param $revisionId integer
	 * @param $DBId integer
	 */
	function setFileDBId($fileId, $revisionId, $DBId) {
		return $this->_fileDBIds[$fileId][$revisionId]= $DBId;
	}
}

?>
