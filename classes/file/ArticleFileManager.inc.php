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
 * $Id$
 */


class ArticleFileManager extends FileManager {
	
	/** @var string the path to location of the files */
	var $filesDir;
	
	/** @var int the ID of the associated article */
	var $articleId;
	
	/** @var Article the associated article */
	var $article;
	
	// FIXME Should have removeFile($fileId) function.
	
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
		$this->filesDir = Config::getVar('files', 'files_dir') . "/journals/" . $journalId .
		"/articles/" . $articleId. "/";
	}
	
	/**
	 * Upload a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/author/", "submission/author", $fileId);
	}

	/**
	 * Remove a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function removeSubmissionFile($fileName) {
		return $this->deleteFile($this->filesDir . "submission/author/" . $fileName);
	}	
	
	/**
	 * Upload an author's revised file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadAuthorFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/author/", "submission/author", $fileId);
	}
	
	/**
	 * Upload a reviewer's annotated file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadReviewerFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/reviewer/", "submission/reviewer", $fileId);
	}
	
	/**
	 * Upload a copyeditor's copyeditted file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadCopyeditorFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/copyeditor/", "submission/copyeditor", $fileId);
	}

	/**
	 * Upload a section editor's post-review file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadEditorFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/editor/", "submission/editor", $fileId);
}

	/**
	 * Upload a section editor's layout editing file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is null if failure
	 */
	function uploadLayoutFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/layout/", "submission/layout", $fileId, $overwrite);
	}	

	/**
	 * Upload a supp file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSuppFile($fileName, $fileId = null, $ovewrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . "supp/", "supp", $fileId, $ovewrite);
	}

	/**
	 * Remove a supp file.
	 * @param $fileName string filename on disk
	 * @return boolean
	 */
	function removeSuppFile($fileName) {
		return $this->deleteFile($this->filesDir . "supp/" . $fileName);
	}	
	
	/**
	 * Upload a review file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadReviewFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "review/", "review", $fileId);
	}
	
	/**
	 * Upload a public file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadPublicFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . "public/", "public", $fileId, $overwrite);
	}

	/**
	 * Remove a public file.
	 * @param $fileName string filename on disk
	 * @return boolean
	 */
	function removePublicFile($fileName) {
		return $this->deleteFile($this->filesDir . "public/" . $fileName);
	}	
	
	/**
	 * Upload a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionNoteFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, $this->filesDir . "note/", "note", $fileId, $overwrite);
	}
	
	/**
	 * remove a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function removeSubmissionNoteFile($fileName) {
		return $this->deleteFile($this->filesDir."note/".$fileName);
	}
	
	/**
	 * return path article note
	 * @param $fileName string the name of the file used in the POST form
	 * @return string
	 */
	function getSubmissionNotePath() {
		return $this->filesDir."note/";
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
	 * Copies the original submission file to make a review file.
	 * @param $originalFileId int the file id of the original file.
	 * @param $originalRevision int the revision of the original file.
	 * @return int the file id of the new file.
	 */
	function originalToReviewFile($fileId, $revision = null) {
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . "submission/author/", $fileId, $revision, $this->filesDir . "submission/editor/");
	}
	
	/**
	 * Copies the review submission file to make a editor file.
	 * @param $fileId int the file id of the review file.
	 * @param $revision int the revision of the review file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function reviewToEditorFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . "submission/editor/", $fileId, $revision, $this->filesDir . "submission/editor/", $destFileId);
	}
	
	/**
	* Copies the editor file to make a copyedit file.
	* @param $fileId int the file id of the editor file.
	* @param $revision int the revision of the editor file.
	* @return int the file id of the new file.
	*/
	function editorToCopyeditFile($fileId, $revision = null) {
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . "submission/editor/", $fileId, $revision, $this->filesDir . "submission/editor/");
	}
	
	/**
	 * Copies the editor file to make a review file.
	 * @param $fileId int the file id of the editor file.
	 * @param $revision int the revision of the editor file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function editorToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . "submission/editor/", $fileId, $revision, $this->filesDir . "submission/editor/", $destFileId);
	}
	
	/**
	 * Copies the author file to make a copyedit file.
	 * @param $fileId int the file id of the author file.
	 * @param $revision int the revision of the author file.
	 * @return int the file id of the new file.
	 */
	function authorToCopyeditFile($fileId, $revision = null) {
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . "submission/author/", $fileId, $revision, $this->filesDir . "submission/editor/");
	}
	
	/**
	 * Copies the author file to make a review file.
	 * @param $fileId int the file id of the author file.
	 * @param $revision int the revision of the author file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
 	 */
	function authorToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . "submission/author/", $fileId, $revision, $this->filesDir . "submission/editor/", $destFileId);
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
		
		$newFileName = $this->articleId.'-'.$fileId.'-'.$revision.'.'.$fileExtension;
		
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
		if (!isset($fileExtension) || $fileExtension == '.php') {
			$fileExtension = 'txt';
		}
			
		$newFileName = $this->articleId.'-'.$fileId.'-'.$revision.'.'.$fileExtension;
		
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
