<?php

/**
 * ArticleFileManager.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * Class defining operations for article file management.
 *
 * Article directory structure:
 * [article id]/note
 * [article id]/public
 * [article id]/submission
 * [article id]/submission/original
 * [article id]/submission/review
 * [article id]/submission/editor
 * [article id]/submission/copyedit
 * [article id]/submission/layout
 * [article id]/supp
 *
 * $Id$
 */

/* File type suffixes */
define('ARTICLE_FILE_SUBMISSION',	'SM');
define('ARTICLE_FILE_REVIEW',		'RV');
define('ARTICLE_FILE_EDITOR',		'ED');
define('ARTICLE_FILE_COPYEDIT',		'CE');
define('ARTICLE_FILE_LAYOUT',		'LE');
define('ARTICLE_FILE_PUBLIC',		'PB');
define('ARTICLE_FILE_SUPP',		'SP');
define('ARTICLE_FILE_NOTE',		'NT');


class ArticleFileManager extends FileManager {
	
	/** @var string the path to location of the files */
	var $filesDir;
	
	/** @var int the ID of the associated article */
	var $articleId;
	
	/** @var Article the associated article */
	var $article;
	
	/**
	 * Constructor.
	 * Create a manager for handling article file uploads.
	 * @param $articleId int
	 */
	function ArticleFileManager($articleId) {
		$this->articleId = $articleId;
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->article = &$articleDao->getArticle($articleId);
		$journalId = $this->article->getJournalId();
		$this->filesDir = Config::getVar('files', 'files_dir') . '/journals/' . $journalId .
		'/articles/' . $articleId . '/';
	}
	
	/**
	 * Upload a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionFile($fileName, $fileId = null, $overwrite = false) {
		return $this->handleUpload($fileName, $this->filesDir . 'submission/original/', 'submission/original', $fileId, $overwrite);
	}

	/**
	 * Remove a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function removeSubmissionFile($fileName) {
		return $this->deleteFile($this->filesDir . 'submission/original/' . $fileName);
	}	
	
	/**
	 * Upload a file to the review file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadReviewFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . 'submission/review/', 'submission/review', $fileId);
	}
	
	/**
	 * Remove a file from the review file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function removeReviewFile($fileName) {
		return $this->deleteFile($this->filesDir . 'submission/review/' . $fileName);
	}

	/**
	 * Upload a file to the editor decision file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadEditorDecisionFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . 'submission/editor/', 'submission/editor', $fileId);
	}
	
	/**
	 * Remove a file from the editor decision file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function removeEditorDecisionFile($fileName) {
		return $this->deleteFile($this->filesDir . 'submission/editor/' . $fileName);
	}	

	/**
	 * Upload a file to the copyedit file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadCopyeditFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . 'submission/copyedit/', 'submission/copyedit', $fileId);
	}

	/**
	 * Upload a section editor's layout editing file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is null if failure
	 */
	function uploadLayoutFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . 'submission/layout/', 'submission/layout', $fileId, $overwrite);
	}	

	/**
	 * Upload a supp file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSuppFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . 'supp/', 'supp', $fileId, $overwrite);
	}

	/**
	 * Remove a supp file.
	 * @param $fileName string filename on disk
	 * @return boolean
	 */
	function removeSuppFile($fileName) {
		return $this->deleteFile($this->filesDir . 'supp/' . $fileName);
	}	
	
	/**
	 * Upload a public file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadPublicFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . 'public/', 'public', $fileId, $overwrite);
	}

	/**
	 * Remove a public file.
	 * @param $fileName string filename on disk
	 * @return boolean
	 */
	function removePublicFile($fileName) {
		return $this->deleteFile($this->filesDir . 'public/' . $fileName);
	}	
	
	/**
	 * Upload a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionNoteFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . 'note/', 'note', $fileId, $overwrite);
	}
	
	/**
	 * remove a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function removeSubmissionNoteFile($fileName) {
		return $this->deleteFile($this->filesDir . 'note/' . $fileName);
	}
	
	/**
	 * return path article note
	 * @param $fileName string the name of the file used in the POST form
	 * @return string
	 */
	function getSubmissionNotePath() {
		return $this->filesDir . 'note/';
	}
	
	/**
	 * Retrieve file information by file ID.
	 * @return ArticleFile
	 */
	function &getFile($fileId, $revision = null) {
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = &$articleFileDao->getArticleFile($fileId, $revision, $this->articleId);
		return $articleFile;
	}
	
	/**
	 * Read a file's contents.
	 * @param $output boolean output the file's contents instead of returning a string
	 * @return boolean
	 */
	function readFile($fileId, $revision = null, $output = false) {
		$articleFile = &$this->getFile($fileId, $revision);
		
		if (isset($articleFile)) {
			$fileType = $articleFile->getFileType();
			$filePath = $this->filesDir . $articleFile->getType() . '/' . $articleFile->getFileName();
	
			return parent::readFile($filePath, $output);
			
		} else {
			return false;
		}
	}
	
	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $revision int the revision of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($fileId, $revision = null, $inline = false) {
		$articleFile = &$this->getFile($fileId, $revision);
		
		if (isset($articleFile)) {
			$fileType = $articleFile->getFileType();
			$filePath = $this->filesDir . $articleFile->getType() . '/' . $articleFile->getFileName();
	
			return parent::downloadFile($filePath, $fileType, $inline);
			
		} else {
			return false;
		}
	}
	
	/**
	 * View a file inline (variant of downloadFile).
	 * @see ArticleFileManager::downloadFile
	 */
	function viewFile($fileId, $revision = null) {
		$this->downloadFile($fileId, $revision, true);
	}
	
	/**
	 * Copies the original submission file to create a review file.
	 * @param $originalFileId int the file id of the original file.
	 * @param $originalRevision int the revision of the original file.
	 * @return int the file id of the new file.
	 */
	function originalToReviewFile($fileId, $revision = null) {
		return $this->copyAndRenameFile('submission/original', $this->filesDir . 'submission/original/', $fileId, $revision, $this->filesDir . 'submission/review/');
	}
	
	/**
	 * Copies a review file to create an editor decision file.
	 * @param $fileId int the file id of the review file.
	 * @param $revision int the revision of the review file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function reviewToEditorDecisionFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile('submission/review', $this->filesDir . 'submission/review/', $fileId, $revision, $this->filesDir . 'submission/editor/', $destFileId);
	}
	
	/**
	* Copies an editor decision file to create a copyedit file.
	* @param $fileId int the file id of the editor file.
	* @param $revision int the revision of the editor file.
	* @return int the file id of the new file.
	*/
	function editorDecisionToCopyeditFile($fileId, $revision = null) {
		return $this->copyAndRenameFile('submission/editor', $this->filesDir . 'submission/editor/', $fileId, $revision, $this->filesDir . 'submission/copyedit/');
	}
	
	/**
	 * Copies an editor decision file to create a review file.
	 * @param $fileId int the file id of the editor file.
	 * @param $revision int the revision of the editor file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function editorDecisionToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile('submission/editor', $this->filesDir . 'submission/editor/', $fileId, $revision, $this->filesDir . 'submission/review/', $destFileId);
	}
	
	/**
	 * Copies the copyedit file to make a layout file.
	 * @param $fileId int the file id of the copyedit file.
	 * @param $revision int the revision of the copyedit file.
	 * @return int the file id of the new file.
	 */
	function copyeditToLayoutFile($fileId, $revision = null) {
		return $this->copyAndRenameFile('submission/layout', $this->filesDir . 'submission/author/', $fileId, $revision, $this->filesDir . 'submission/layout/');
	}
	
	/**
	 * Return code associated with a specific file type.
	 * @return String
	 */
	function typeToCode($type) {
		switch ($type) {
			case 'public': return ARTICLE_FILE_PUBLIC;
			case 'supp': return ARTICLE_FILE_SUPP;
			case 'note': return ARTICLE_FILE_NOTE;
			case 'submission/review': return ARTICLE_FILE_REVIEW;
			case 'submission/editor': return ARTICLE_FILE_EDITOR;
			case 'submission/copyedit': return ARTICLE_FILE_COPYEDIT;
			case 'submission/layout': return ARTICLE_FILE_LAYOUT;
			case 'submission/original': default: return ARTICLE_FILE_SUBMISSION;
		}
	}
	
	/**
	 * Copies an existing ArticleFile and renames it.
	 * @param $type string
	 * @param $sourceDir string
	 * @param $sourceFileId int
	 * @param $sourceRevision int
	 * @param $destDir string
	 * @param $destFileId int
	 */
	function copyAndRenameFile($type, $sourceDir, $sourceFileId, $sourceRevision, $destDir, $destFileId = null) {
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();
		
		if ($destFileId != null) {
			$currentRevision = $articleFileDao->getRevisionNumber($destFileId);
			$revision = $currentRevision + 1;
		} else {
			$revision = 1;
		}	
		
		$sourceArticleFile = $articleFileDao->getArticleFile($sourceFileId, $sourceRevision, $this->articleId);

		if (!isset($sourceArticleFile)) {
			return false;
		}
		
		if ($destFileId != null) {
			$articleFile->setFileId($destFileId);
		}
		$articleFile->setArticleId($this->articleId);
		$articleFile->setFileName($sourceArticleFile->getFileName());
		$articleFile->setFileType($sourceArticleFile->getFileType());
		$articleFile->setFileSize($sourceArticleFile->getFileSize());
		$articleFile->setOriginalFileName($sourceArticleFile->getFileName());
		$articleFile->setType($type);
		$articleFile->setStatus($sourceArticleFile->getStatus());
		$articleFile->setDateUploaded(Core::getCurrentDate());
		$articleFile->setDateModified(Core::getCurrentDate());
		$articleFile->setRound($this->article->getCurrentRound());
		$articleFile->setRevision($revision);
		
		$fileId = $articleFileDao->insertArticleFile($articleFile);
		
		// Rename the file.
		$fileParts = explode('.', $sourceArticleFile->getFileName());
		if (is_array($fileParts)) {
			$fileExtension = $fileParts[count($fileParts) - 1];
		} else {
			$fileExtension = 'txt';
		}
		
		$newFileName = $this->articleId.'-'.$fileId.'-'.$revision.'-'.$this->typeToCode($type).'.'.$fileExtension;
		
		if (!$this->fileExists($destDir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($destDir);
		}
		
		copy($sourceDir.$sourceArticleFile->getFileName(), $destDir.$newFileName);
		
		$articleFile->setFileName($newFileName);
		$articleFileDao->updateArticleFile($articleFile);
		
		return $fileId;
	}
	
	/**
	 * PRIVATE routine to upload the file and add it to the database.
	 * @param $fileName string index into the $_FILES array
	 * @param $dir string directory to put the file into
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleUpload($fileName, $dir, $type, $fileId = null, $overwrite = false) {
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();
		
		if (!$fileId) {
			// Insert dummy file to generate file id
			$dummyFile = true;
			$revision = 1;
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileName('temp');
			$articleFile->setOriginalFileName('temp');
			$articleFile->setFileType('temp');
			$articleFile->setFileSize(0);
			$articleFile->setType('temp');
			$articleFile->setStatus('temp');
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
			$articleFile->setRound($this->article->getCurrentRound()); // FIXME This is review-specific and should NOT be here
			$articleFile->setRevision($revision);
			
			$fileId = $articleFileDao->insertArticleFile($articleFile);
		} else {
			$dummyFile = false;
			$currentRevision = $articleFileDao->getRevisionNumber($fileId);
			$revision = $currentRevision + 1;
		}
		
		// Get the file extension, then rename the file.
		$fileParts = explode('.', $this->getUploadedFileName($fileName));
		
		if (is_array($fileParts)) {
			$fileExtension = $fileParts[count($fileParts) - 1];
		}
		
		// FIXME Rename certain disallowed file extensions?
		if (!isset($fileExtension) || strstr($fileExtension, 'php') || !preg_match('/^\w+$/', $fileExtension)) {
			$fileExtension = 'txt';
		}
			
		$newFileName = $this->articleId.'-'.$fileId.'-'.$revision.'-'.$this->typeToCode($type).'.'.$fileExtension;
		
		if (!$this->fileExists($dir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($dir);
		}
	
		if ($this->uploadFile($fileName, $dir.$newFileName)) {
			$articleFile->setFileId($fileId);
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileName($newFileName);
			$articleFile->setFileType($_FILES[$fileName]['type']);
			$articleFile->setFileSize($_FILES[$fileName]['size']);
			$articleFile->setOriginalFileName($_FILES[$fileName]['name']);
			$articleFile->setType($type);
			$articleFile->setStatus('something'); // FIXME wtf is this for?
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
			$articleFile->setRound($this->article->getCurrentRound());
			$articleFile->setRevision($revision);
		
			if ($dummyFile) {
				$articleFileDao->updateArticleFile($articleFile);
			} else {
				$articleFileDao->insertArticleFile($articleFile);
			}
			
			if ($overwrite) {
				// Remove all previous revisions
				$revisions = $articleFileDao->getArticleFileRevisions($fileId);
				foreach ($revisions as $revisionFile) {
					if ($revisionFile->getRevision() != $revision) {
						$this->deleteFile($this->filesDir . '/' . $revisionFile->getType() . '/' . $revisionFile->getFileName());
						$articleFileDao->deleteArticleFileById($fileId, $revisionFile->getRevision());
					}
				}
			}
			
			return $fileId;
			
		} else {
		
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($fileId);
			
			return false;
		}
	}
}

?>
