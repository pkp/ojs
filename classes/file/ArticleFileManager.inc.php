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
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadSubmissionFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/author/", "submission/author", $fileId);
	}
	
	/**
	* Upload an author's revised file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadAuthorFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/author/", "submission/author", $fileId);
	}
	
	/**
	* Upload a reviewer's annotated file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadReviewerFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/reviewer/", "submission/reviewer", $fileId);
	}
	
	/**
	* Upload a copyeditor's copyeditted file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadCopyeditorFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/copyeditor/", "submission/copyeditor", $fileId);
	}

	/**
	* Upload a section editor's post-review file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadEditorFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/editor/", "submission/editor", $fileId);
}

	/**
	* Upload a section editor's layout editing file.
	* @param $fileName string the name of the file used in the POST form
	* @param $fileId int file ID if updating an existing file
	* @return int file ID, is null if failure
	*/
	function uploadLayoutFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/layout/", "submission/layout", $fileId);
	}	

	/**
	* Upload a supp file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadSuppFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "supp/", "supp", $fileId);
	}
	
	/**
	* Upload a review file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadReviewFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "review/", "review", $fileId);
	}
	
	/**
	* Upload a public file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadPublicFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "public/", "public", $fileId);
	}
	
	/**
	* Download a file.
	* @param $fileId int the file id of the file to download
	* @param $revision int the revision of the file to download
	*/
	function downloadFile($fileId, $revision = null) {
		// get the files path and type
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();
		if ($revision != null) {
			$articleFile = $articleFileDao->getArticleFile($fileId);
		} else {
			$articleFile = $articleFileDao->getArticleFile($fileId, $revision);
		}
		$fileType = $articleFile->getFileType();
		$type = $articleFile->getType();
		$fileName = $articleFile->getFileName();
		$filePath = $this->filesDir . "$type/$fileName";
		
		FileManager::downloadFile($filePath, $fileType);
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
	* @return int the file id of the new file.
	*/
	function authorToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . "submission/author/", $fileId, $revision, $this->filesDir . "submission/editor/", $destFileId);
	}
	
	/**
	* Duplicate a copyedit file.
	*
	* This is used when we take a copyedit revision and use it
	* as the default copyedit revision for an additional round
	* of copyediting.
	*
	* @param $fileId int the file id of the author file.
	* @param $revision int the revision of the author file.
	* @return int the file id of the new file.
	*/
	function duplicateCopyeditFile($fileId, $revision) {
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		
		$articleFile = &$articleFileDao->getArticleFile($fileId, $revision);
			
		return $this->copyAndRenameFile("submission/editor", $this->filesDir . $articleFile->getType() . "/", $fileId, $revision, $this->filesDir . "submission/editor/", $fileId);
	}	
	
	/**
	* Copies an existing ArticleFile and renames it.
	* @param $oldDir string the directory that the file is located in.
	* @param $oldFileId int the file that is being copied.
	* @param $dir string the directory to copy the file to.
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
		
		if ($sourceRevision == null) {
			$sourceArticleFile = $articleFileDao->getArticleFile($sourceFileId);
		} else {
			$sourceArticleFile = $articleFileDao->getArticleFile($sourceFileId, $sourceRevision);
		}
		
		if ($destFileId != null) {
			$articleFile->setFileId($destFileId);
		}
		$articleFile->setArticleId($this->articleId);
		$articleFile->setFileName($sourceArticleFile->getFileName());
		$articleFile->setFileType($sourceArticleFile->getFileType());
		$articleFile->setFileSize($sourceArticleFile->getFileSize());
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
	
	// private
	
	function handleUpload($fileName, $dir, $type, $fileId = null) {
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();
		
		if ($fileId == null) {
			// Insert dummy file to generate file id
			$dummyFile = true;
			$revision = 1;
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileName('temp');
			$articleFile->setFileType('temp');
			$articleFile->setFileSize(0);
			$articleFile->setType('temp');
			$articleFile->setStatus('temp');
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
			$articleFile->setRound($this->article->getCurrentRound());
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
		} else {
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
			$articleFile->setType($type);
			$articleFile->setStatus('something');
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
			$articleFile->setRound($this->article->getCurrentRound());
			$articleFile->setRevision($revision);
		
			if ($dummyFile) {
				$articleFileDao->updateArticleFile($articleFile);
			} else {
				$articleFileDao->insertArticleFile($articleFile);
			}
			
			return $fileId;
		} else {
		
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($fileId, 0);
			
			return null;
		}
	}
}

?>
