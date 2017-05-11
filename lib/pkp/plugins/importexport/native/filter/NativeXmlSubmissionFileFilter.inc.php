<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlSubmissionFileFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlSubmissionFileFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a Native XML document to a submission file
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportFilter');

class NativeXmlSubmissionFileFilter extends NativeImportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML submission file import');
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from NativeImportFilter
	//
	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		return 'submission_files';
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		return 'submission_file';
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.native.filter.NativeXmlSubmissionFileFilter';
	}


	/**
	 * Handle a submission file element
	 * @param $node DOMElement
	 * @return array Array of SubmissionFile objects
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$stageName = $node->getAttribute('stage');
		$fileId = $node->getAttribute('id');
		$stageNameIdMapping = $deployment->getStageNameStageIdMapping();
		assert(isset($stageNameIdMapping[$stageName]));
		$stageId = $stageNameIdMapping[$stageName];

		$submissionFiles = array();
		// Handle metadata in subelements
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				$this->handleChildElement($n, $stageId, $fileId, $submissionFiles);
			}
		}
		return $submissionFiles;
	}

	/**
	 * Handle a child node of the submission file element; add new files, if
	 * any, to $submissionFiles
	 * @param $node DOMElement
	 * @param $stageId int SUBMISSION_FILE_...
	 * @param $fileId int File id
	 * @param $submissionFiles array
	 */
	function handleChildElement($node, $stageId, $fileId, &$submissionFiles) {
		$deployment = $this->getDeployment();
		$submission = $deployment->getSubmission();
		switch ($node->tagName) {
			case 'revision':
				$submissionFile = $this->handleRevisionElement($node, $stageId, $fileId);
				if ($submissionFile) $submissionFiles[] = $submissionFile;
				break;
			default:
				$deployment->addError(ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $node->tagName)));
		}
	}

	/**
	 * Handle a revision element
	 * @param $node DOMElement
	 * @param $fileId int File id
	 * @param $stageId int SUBMISSION_FILE_...
	 */
	function handleRevisionElement($node, $stageId, $fileId) {
		static $genresByContextId = array();

		$deployment = $this->getDeployment();
		$submission = $deployment->getSubmission();
		$context = $deployment->getContext();

		$errorOccured = false;

		$revisionId = $node->getAttribute('number');

		$source = $node->getAttribute('source');
		$sourceFileAndRevision = null;
		if ($source) {
			$sourceFileAndRevision = explode('-', $source);
		}

		$genreId = null;
		$genreName = $node->getAttribute('genre');
		if ($genreName) {
			// Build a cached list of genres by context ID by name
			if (!isset($genresByContextId[$context->getId()])) {
				$genreDao = DAORegistry::getDAO('GenreDAO');
				$genres = $genreDao->getByContextId($context->getId());
				while ($genre = $genres->next()) {
					foreach ($genre->getName(null) as $locale => $name) {
						$genresByContextId[$context->getId()][$name] = $genre;
					}
				}
			}
			if (!isset($genresByContextId[$context->getId()][$genreName])) {
				$deployment->addError(ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownGenre', array('param' => $genreName)));
				$errorOccured = true;
			} else {
				$genre = $genresByContextId[$context->getId()][$genreName];
				$genreId = $genre->getId();
			}
		}

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFile = $submissionFileDao->newDataObjectByGenreId($genreId);
		$submissionFile->setSubmissionId($submission->getId());
		$submissionFile->setGenreId($genreId);
		$submissionFile->setFileStage($stageId);
		$submissionFile->setDateUploaded(Core::getCurrentDate());
		$submissionFile->setDateModified(Core::getCurrentDate());
		if ($node->getAttribute('available') == 'true') $submissionFile->setViewable(true);

		$submissionFile->setOriginalFileName($filename = $node->getAttribute('filename'));
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				$filename = $this->handleRevisionChildElement($n, $submission, $submissionFile);
			}
		}

		$uploaderUsername = $node->getAttribute('uploader');
		$uploaderUserGroup = $node->getAttribute('user_group_ref');

		// Determine the user group based on the user_group_ref element.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getByContextId($context->getId());
		while ($userGroup = $userGroups->next()) {
			if (in_array($uploaderUserGroup, $userGroup->getName(null))) {
				$submissionFile->setUserGroupId($userGroup->getId());
				break;
			}
		}
		if (!$submissionFile->getUserGroupId()) {
			$deployment->addError(ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownUserGroup', array('param' => $uploaderUserGroup)));
			$errorOccured = true;
		}

		// Do the same for the user.
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getByUsername($uploaderUsername);
		if ($user) {
			$submissionFile->setUploaderUserId($user->getId());
		} else {
			$deployment->addError(ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownUploader', array('param' => $uploaderUsername)));
			$errorOccured = true;
		}

		$fileSize = $node->getAttribute('filesize');
		$submissionFile->setFileSize($fileSize);

		$fileType = $node->getAttribute('filetype');
		$submissionFile->setFileType($fileType);

		$submissionFile->setRevision($revisionId);

		if ($sourceFileAndRevision) {
			// the source file revision should already be processed, so get the new source file ID
			$sourceFileId = $deployment->getFileDBId($sourceFileAndRevision[0], $sourceFileAndRevision[1]);
			if ($sourceFileId) {
				$submissionFile->setSourceFileId($sourceFileId);
				$submissionFile->setSourceRevision($sourceFileAndRevision[1]);
			}
		}

		if ($errorOccured) {
			// if error occured, the file cannot be inserted into DB, becase
			// genre, uploader and user group are required (e.g. at name generation).
			$submissionFile = null;
		} else {
			// if the same file is already inserted, take its DB file ID
			$DBId = $deployment->getFileDBId($fileId);
			if ($DBId) $submissionFile->setFileId($DBId);
			$insertedSubmissionFile = $submissionFileDao->insertObject($submissionFile, $filename, false);
			$deployment->setFileDBId($fileId, $revisionId, $insertedSubmissionFile->getFileId());
		}

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$fileManager->deleteFile($filename);
		return $submissionFile;
	}

	/**
	 * Handle a child of the revision element
	 * @param $node DOMElement
	 * @param $submission Submission
	 * @param $submissionFile SubmissionFile
	 * @return string Filename for new file
	 */
	function handleRevisionChildElement($node, $submission, $submissionFile) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$submission = $deployment->getSubmission();
		switch ($node->tagName) {
			case 'id':
				$this->parseIdentifier($node, $submissionFile);
				break;
			case 'name':
				$locale = $node->getAttribute('locale');
				if (empty($locale)) $locale = $context->getPrimaryLocale();
				$submissionFile->setName($node->textContent, $locale);
				break;
			case 'href':
				$submissionFile->setFileType($node->getAttribute('mime_type'));
				// Allow wrappers to handle URLs
				return $node->getAttribute('src');
				break;
			case 'embed':
				$submissionFile->setFileType($node->getAttribute('mime_type'));
				import('lib.pkp.classes.file.TemporaryFileManager');
				$temporaryFileManager = new TemporaryFileManager();
				$temporaryFilename = tempnam($temporaryFileManager->getBasePath(), 'embed');
				if (($e = $node->getAttribute('encoding')) != 'base64') {
					$deployment->addError(ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownEncoding', array('param' => $e)));
				} else {
					file_put_contents($temporaryFilename, base64_decode($node->textContent));
				}
				return $temporaryFilename;
				break;
		}
	}

	/**
	 * Parse an identifier node and set up the representation object accordingly
	 * @param $element DOMElement
	 * @param $submissionFile SubmissionFile
	 */
	function parseIdentifier($element, $submissionFile) {
		$deployment = $this->getDeployment();
		$advice = $element->getAttribute('advice');
		switch ($element->getAttribute('type')) {
			case 'internal':
				// "update" advice not supported yet.
				assert(!$advice || $advice == 'ignore');
				break;
			case 'public':
				if ($advice == 'update') {
					$submissionFile->setStoredPubId('publisher-id', $element->textContent);
				}
				break;
			default:
				if ($advice == 'update') {
					// Load pub id plugins
					$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $deployment->getContext()->getId());
					$submissionFile->setStoredPubId($element->getAttribute('type'), $element->textContent);
				}
		}
	}

	/**
	 * Instantiate a submission file.
	 * @param $tagName string
	 * @return SubmissionFile
	 */
	function instantiateSubmissionFile($tagName) {
		assert(false); // Subclasses should override
	}
}

?>
