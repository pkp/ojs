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
		$article = &$articleDao->getArticle($articleId);
		$journalId = $article->getJournalId();
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
		return $this->handleUpload($fileName, $this->filesDir . "submission/author/", "submission", $fileId);
	}
	
	/**
	* Upload a reviewer's annotated file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadReviewerFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/reviewer/", "submission", $fileId);
	}

	/**
	* Upload a section editor's post-review file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadEditorFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, $this->filesDir . "submission/editor/", "submission", $fileId);
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
	
	function downloadFile($fileId) {
		// get the files path and type
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile;
		$articleFile = $articleFileDao->getArticleFile($fileId);
		$fileType = $articleFile->getFileType();
		$type = $articleFile->getType();
		$fileName = $articleFile->getFileName();
		$filePath = $this->filesDir . "$type/$fileName";
		
		FileManager::downloadFile($filePath, $fileType);
	}
	
	// private
	
	function handleUpload($fileName, $dir, $type, $fileId = null) {
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile;
		
		if ($this->uploadFile($fileName, $dir)) {
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileName($_FILES[$fileName]['name']);
			$articleFile->setFileType($_FILES[$fileName]['type']);
			$articleFile->setFileSize($_FILES[$fileName]['size']);
			$articleFile->setType($type);
			$articleFile->setStatus('something');
			$articleFile->setDateUploaded(date("Y-m-d g:i:s"));
			$articleFile->setDateModified(date("Y-m-d g:i:s"));
			if ($fileId == null) {
				$revision = 1;
				$articleFile->setRevision($revision);
			} else {
				$currentRevision = $articleFileDao->getRevisionNumber($fileId);
				$revision = $currentRevision + 1;
				
				$articleFile->setFileId($fileId);
				$articleFile->setRevision($revision);
			}
			
			
			$fileId = $articleFileDao->insertArticleFile($articleFile);
			
			// Rename the file.
			$fileParts = explode('.', $_FILES[$fileName]['name']);
			if (is_array($fileParts)) {
				$fileExtension = $fileParts[count($fileParts) - 1];
			} else {
				$fileExtension = 'txt';
			}
			
			$newFileName = $this->articleId.'-'.$fileId.'-'.$revision.'.'.$fileExtension;
			
			rename($dir.$_FILES[$fileName]['name'], $dir.$newFileName);
			
			$articleFile->setFileName($newFileName);
			$articleFileDao->updateArticleFile($articleFile);
			
			return $fileId;
		} else {
			return null;
		}
	}
}

?>
