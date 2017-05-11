<?php

/**
 * @file classes/submission/SubmissionFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFile
 * @ingroup submission
 *
 * @brief Submission file class.
 */

import('lib.pkp.classes.file.PKPFile');

// Define the file stage identifiers.
define('SUBMISSION_FILE_PUBLIC', 1);
define('SUBMISSION_FILE_SUBMISSION', 2);
define('SUBMISSION_FILE_NOTE', 3);
define('SUBMISSION_FILE_REVIEW_FILE', 4);
define('SUBMISSION_FILE_REVIEW_ATTACHMENT', 5);
//	SUBMISSION_FILE_REVIEW_REVISION defined below (FIXME: re-order before release)
define('SUBMISSION_FILE_FINAL', 6);
define('SUBMISSION_FILE_FAIR_COPY', 7);
define('SUBMISSION_FILE_EDITOR', 8);
define('SUBMISSION_FILE_COPYEDIT', 9);
define('SUBMISSION_FILE_PROOF', 10);
define('SUBMISSION_FILE_PRODUCTION_READY', 11);
define('SUBMISSION_FILE_ATTACHMENT', 13);
define('SUBMISSION_FILE_REVIEW_REVISION', 15);
define('SUBMISSION_FILE_DEPENDENT', 17);
define('SUBMISSION_FILE_QUERY', 18);

class SubmissionFile extends PKPFile {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get a piece of data for this object, localized to the current
	 * locale if possible.
	 * @param $key string
	 * @param $preferredLocale string
	 * @return mixed
	 */
	function &getLocalizedData($key, $preferredLocale = null) {
		if (is_null($preferredLocale)) $preferredLocale = AppLocale::getLocale();
		$localePrecedence = array($preferredLocale, $this->getSubmissionLocale());
		foreach ($localePrecedence as $locale) {
			if (empty($locale)) continue;
			$value =& $this->getData($key, $locale);
			if (!empty($value)) return $value;
			unset($value);
		}

		// Fallback: Get the first available piece of data.
		$data =& $this->getData($key, null);
		foreach ((array) $data as $dataValue) {
			if (!empty($dataValue)) return $dataValue;
		}

		// No data available; return null.
		unset($data);
		$data = null;
		return $data;
	}

	//
	// Getters and Setters
	//
	/**
	 * Get ID of file.
	 * @return int
	 */
	function getFileId() {
		// WARNING: Do not modernize getter/setters without considering
		// ID clash with subclasses ArticleGalley and ArticleNote!
		return $this->getData('fileId');
	}

	/**
	 * Set ID of file.
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		// WARNING: Do not modernize getter/setters without considering
		// ID clash with subclasses ArticleGalley and ArticleNote!
		$this->setData('fileId', $fileId);
	}

	/**
	 * Get the locale of the submission.
	 * This is not properly a property of the submission file
	 * (e.g. it won't be persisted to the DB with the update function)
	 * It helps solve submission locale requirement for file's multilingual metadata
	 * @return string
	 */
	function getSubmissionLocale() {
		return $this->getData('submissionLocale');
	}

	/**
	 * Set the locale of the submission.
	 * This is not properly a property of the submission file
	 * (e.g. it won't be persisted to the DB with the update function)
	 * It helps solve submission locale requirement for file's multilingual metadata
	 * @param $submissionLocale string
	 */
	function setSubmissionLocale($submissionLocale) {
		$this->setData('submissionLocale', $submissionLocale);
	}

	/**
	 * Get source file ID of this file.
	 * @return int
	 */
	function getSourceFileId() {
		return $this->getData('sourceFileId');
	}

	/**
	 * Set source file ID of this file.
	 * @param $sourceFileId int
	 */
	function setSourceFileId($sourceFileId) {
		$this->setData('sourceFileId', $sourceFileId);
	}

	/**
	 * Get source revision of this file.
	 * @return int
	 */
	function getSourceRevision() {
		return $this->getData('sourceRevision');
	}

	/**
	 * Set source revision of this file.
	 * @param $sourceRevision int
	 */
	function setSourceRevision($sourceRevision) {
		$this->setData('sourceRevision', $sourceRevision);
	}

	/**
	 * Get associated ID of file.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set associated ID of file.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Get stored public ID of the file.
	 * @param @literal $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>). @endliteral
	 * @return int
	 */
	function getStoredPubId($pubIdType) {
		return $this->getData('pub-id::'.$pubIdType);
	}

	/**
	 * Set the stored public ID of the file.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function setStoredPubId($pubIdType, $pubId) {
		$this->setData('pub-id::'.$pubIdType, $pubId);
	}

	/**
	 * Get price of submission file.
	 * A null return indicates "not available"; 0 is free.
	 * @return numeric|null
	 */
	function getDirectSalesPrice() {
		return $this->getData('directSalesPrice');
	}

	/**
	 * Set direct sales price.
	 * A null return indicates "not available"; 0 is free.
	 * @param $directSalesPrice numeric|null
	 */
	function setDirectSalesPrice($directSalesPrice) {
		$this->setData('directSalesPrice', $directSalesPrice);
	}

	/**
	 * Get sales type of submission file.
	 * @return string
	 */
	function getSalesType() {
		return $this->getData('salesType');
	}

	/**
	 * Set sales type.
	 * @param $salesType string
	 */
	function setSalesType($salesType) {
		$this->setData('salesType', $salesType);
	}

	/**
	 * Set the name of the file
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		$this->setData('name', $name, $locale);
	}

	/**
	 * Get the name of the file
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Get the localized name of the file
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the file's extension.
	 * @return string
	 */
	function getExtension() {
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		return strtoupper($fileManager->parseFileExtension($this->getOriginalFileName()));
	}

	/**
	 * Get the file's document type (enumerated types)
	 * @return string
	 */
	function getDocumentType() {
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		return $fileManager->getDocumentType($this->getFileType());
	}

	/**
	 * Set the genre id of this file (i.e. referring to Manuscript, Index, etc)
	 * Foreign key into genres table
	 * @param $genreId int
	 */
	function setGenreId($genreId) {
		$this->setData('genreId', $genreId);
	}

	/**
	 * Get the genre id of this file (i.e. referring to Manuscript, Index, etc)
	 * Foreign key into genres table
	 * @return int
	 */
	function getGenreId() {
		return $this->getData('genreId');
	}

	/**
	 * Get revision number.
	 * @return int
	 */
	function getRevision() {
		return $this->getData('revision');
	}

	/**
	 * Return the "best" file ID -- If a public ID is set,
	 * use it; otherwise use the internal ID and revision.
	 * @return string
	 */
	function getBestId() {
		$publicFileId = $this->getStoredPubId('publisher-id');
		if (!empty($publicFileId)) return $publicFileId;
		return $this->getFileIdAndRevision();
	}

	/**
	 * Get the combined key of the file
	 * consisting of the file id and the revision.
	 * @return string
	 */
	function getFileIdAndRevision() {
		$id = $this->getFileId();
		$revision = $this->getRevision();
		$idAndRevision = $id;
		if ($revision) {
			$idAndRevision .= '-'.$revision;
		}
		return $idAndRevision;
	}

	/**
	 * Set revision number.
	 * @param $revision int
	 */
	function setRevision($revision) {
		$this->setData('revision', $revision);
	}

	/**
	 * Get ID of submission.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set ID of submission.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		$this->setData('submissionId', $submissionId);
	}

	/**
	 * Get type of the file.
	 * @return int
	 */
	function getType() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getFileStage();
	}

	/**
	 * Set type of the file.
	 * @param $type int
	 */
	function setType($type) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setFileStage($type);
	}

	/**
	 * Get file stage of the file.
	 * @return int SUBMISSION_FILE_...
	 */
	function getFileStage() {
		return $this->getData('fileStage');
	}

	/**
	 * Set file stage of the file.
	 * @param $fileStage int SUBMISSION_FILE_...
	 */
	function setFileStage($fileStage) {
		$this->setData('fileStage', $fileStage);
	}

	/**
	 * Get modified date of file.
	 * @return date
	 */

	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * Set modified date of file.
	 * @param $dateModified date
	 */

	function setDateModified($dateModified) {
		return $this->SetData('dateModified', $dateModified);
	}

	/**
	 * Get round.
	 * @return int
	 */

	function getRound() {
		return $this->getData('round');
	}

	/**
	 * Set round.
	 * @param $round int
	 */
	function setRound($round) {
		return $this->SetData('round', $round);
	}

	/**
	 * Get viewable.
	 * @return boolean
	 */
	function getViewable() {
		return $this->getData('viewable');
	}


	/**
	 * Set viewable.
	 * @param $viewable boolean
	 */
	function setViewable($viewable) {
		return $this->SetData('viewable', $viewable);
	}

	/**
	 * Set the uploader's user id.
	 * @param $uploaderUserId integer
	 */
	function setUploaderUserId($uploaderUserId) {
		$this->setData('uploaderUserId', $uploaderUserId);
	}

	/**
	 * Get the uploader's user id.
	 * @return integer
	 */
	function getUploaderUserId() {
		return $this->getData('uploaderUserId');
	}

	/**
	 * Set the uploader's user group id
	 * @param $userGroupId int
	 */
	function setUserGroupId($userGroupId) {
		$this->setData('userGroupId', $userGroupId);
	}

	/**
	 * Get the uploader's user group id
	 * @return int
	 */
	function getUserGroupId() {
		return $this->getData('userGroupId');
	}

	/**
	 * Get type that is associated with this file.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set type that is associated with this file.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get the submission chapter id.
	 * @return int
	 */
	function getChapterId() {
		return $this->getData('chapterId');
	}

	/**
	 * Set the submission chapter id.
	 * @param $chapterId int
	 */
	function setChapterId($chapterId) {
		$this->setData('chapterId', $chapterId);
	}

	/**
	 * Return a context-aware file path.
	 */
	function getFilePath() {
		// Get the context ID
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($this->getSubmissionId());
		if (!$submission) return null;
		$contextId = $submission->getContextId();
		unset($submission);

		// Construct the file path
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($contextId, $this->getSubmissionId());
		return $submissionFileManager->getBasePath() . $this->_fileStageToPath($this->getFileStage()) . '/' . $this->getServerFileName();
	}

	/**
	 * Build a file name label.
	 * @return string
	 */
	function getFileLabel($locale = null) {
		// Retrieve the localized file name as basis for the label.
		if ($locale) {
			$fileLabel = $this->getName($locale);
		} else {
			$fileLabel = $this->getLocalizedName();
		}

		// If we have no file name then use a default name.
		if (empty($fileLabel)) $fileLabel = $this->getOriginalFileName();

		// Add the revision number to the label if we have more than one revision.
		if ($this->getRevision() > 1) $fileLabel .= ' (' . $this->getRevision() . ')';

		return $fileLabel;
	}


	/**
	 * Copy the user-facing (editable) metadata from another submission
	 * file.
	 * @param $submissionFile SubmissionFile
	 */
	function copyEditableMetadataFrom($submissionFile) {
		assert(is_a($submissionFile, 'SubmissionFile'));
		$this->setName($submissionFile->getName(null), null);
		$this->setChapterId($submissionFile->getChapterId());
	}

	/**
	 * Get the filename that should be sent to clients when downloading.
	 * @return string
	 */
	function getClientFileName() {
		// Generate a human readable time stamp.
		$timestamp = date('Ymd', strtotime($this->getDateUploaded()));

		$genreDao = DAORegistry::getDAO('GenreDAO');
		$genre = $genreDao->getById($this->getGenreId());

		// Make the file name unique across all files and file revisions.
		// Also make sure that files can be ordered sensibly by file name.
		return	$this->getSubmissionId() . '-'.
			($genre? ($genre->getLocalizedName() . '-'):'') .
			$this->getFileId() . '-' .
			$this->getRevision() . '-' .
			$this->getFileStage() . '-' .
			$timestamp .
			'.' .
			strtolower_codesafe($this->getExtension());
	}

	//
	// Overridden public methods from PKPFile
	//
	/**
	 * @see PKPFile::getServerFileName()
	 * Generate the file name from identification data rather than
	 * retrieving it from the database.
	 */
	function getServerFileName() {
		return $this->_generateFileName();
	}

	/**
	 * @see PKPFile::setFileName()
	 * Do not allow setting the file name of a submission file
	 * directly because it is generated from identification data.
	 */
	function setServerFileName($fileName) {
		assert(false);
	}

	/**
	* Get submission file number of public downloads.
	* @return int
	*/
	function getViews() {
		$application = Application::getApplication();
		return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_SUBMISSION_FILE, $this->getFileId());
	}

	//
	// Private helper methods
	//

	/**
	 * Generate the unique filename for this submission file.
	 * @return string
	 */
	function _generateFileName() {
		// Generate a human readable time stamp.
		$timestamp = date('Ymd', strtotime($this->getDateUploaded()));

		// Make the file name unique across all files and file revisions.
		// Also make sure that files can be ordered sensibly by file name.
		return	$this->getSubmissionId() . '-'.
			$this->getGenreId() . '-' .
			$this->getFileId() . '-' .
			$this->getRevision() . '-' .
			$this->getFileStage() . '-' .
			$timestamp .
			'.' .
			strtolower_codesafe($this->getExtension());
	}

	/**
	 * Generate a user-facing name for the file
	 * @param $anonymous boolean Whether the user name should be excluded
	 * @return string
	 */
	function _generateName($anonymous = false) {
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$genre = $genreDao->getById($this->getGenreId());
		$userGroupDAO = DAORegistry::getDAO('UserGroupDAO');
		$userGroup = $userGroupDAO->getById($this->getUserGroupId());
		$userDAO = DAORegistry::getDAO('UserDAO');
		$user = $userDAO->getById($this->getUploaderUserId());

		$submissionLocale = $this->getSubmissionLocale();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON, $submissionLocale);

		$genreName = '';
		if ($genre) {
			$genreName = $genre->getName($submissionLocale) ? $genre->getName($submissionLocale) : $genre->getLocalizedName();
		}
		$userGroupName = $userGroup->getName($submissionLocale) ? $userGroup->getName($submissionLocale) : $userGroup->getLocalizedName();

		$localeKey = $anonymous ? 'common.file.anonymousNamingPattern' : 'common.file.namingPattern';
		return __($localeKey,
			array(
				'genre'            => $genreName,
				'docType'          => $this->getDocumentType(),
				'originalFilename' => $this->getOriginalFilename(),
				'username'         => $user->getUsername(),
				'userGroup'        => $userGroupName,
			),
			$submissionLocale
		);
	}

	/**
	 * Return path associated with a file stage code.
	 * @param $fileStage string
	 * @return string
	 */
	function _fileStageToPath($fileStage) {
		static $fileStageToPath = array(
				0 => '', // Temporary files do not use stages
				SUBMISSION_FILE_PUBLIC => 'public',
				SUBMISSION_FILE_SUBMISSION => 'submission',
				SUBMISSION_FILE_NOTE => 'note',
				SUBMISSION_FILE_REVIEW_FILE => 'submission/review',
				SUBMISSION_FILE_REVIEW_ATTACHMENT => 'submission/review/attachment',
				SUBMISSION_FILE_REVIEW_REVISION => 'submission/review/revision',
				SUBMISSION_FILE_FINAL => 'submission/final',
				SUBMISSION_FILE_FAIR_COPY => 'submission/fairCopy',
				SUBMISSION_FILE_EDITOR => 'submission/editor',
				SUBMISSION_FILE_COPYEDIT => 'submission/copyedit',
				SUBMISSION_FILE_DEPENDENT => 'submission/proof',
				SUBMISSION_FILE_PROOF => 'submission/proof',
				SUBMISSION_FILE_PRODUCTION_READY => 'submission/productionReady',
				SUBMISSION_FILE_ATTACHMENT => 'attachment',
				SUBMISSION_FILE_QUERY => 'submission/query',
		);

		assert(isset($fileStageToPath[$fileStage]));
		return $fileStageToPath[$fileStage];
	}

	//
	// Public methods
	//
	/**
	 * Get the metadata form for this submission file.
	 * @param $stageId int FILE_STAGE_...
	 * @param $reviewRound ReviewRound
	 * @return Form
	 */
	function getMetadataForm($stageId, $reviewRound) {
		import('lib.pkp.controllers.wizard.fileUpload.form.SubmissionFilesMetadataForm');
		return new SubmissionFilesMetadataForm($this, $stageId, $reviewRound);
	}

	/**
	 * @copydoc DataObject::getDAO()
	 */
	function getDAO() {
		return DAORegistry::getDAO('SubmissionFileDAO');
	}
}

?>
