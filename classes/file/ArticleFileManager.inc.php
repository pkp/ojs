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
	var $articleId;
	
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
		$this->filesDir = Config::getVar('general', 'files_dir') . "/journals/" . $journalId .
		"/articles/" . $articleId. "/";
	}
	
	/**
	* Upload a submission file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadSubmissionFile($fileName, $fileId = null) {
		return handleUpload($fileName, $this->filesDir . "submission/", "submission", $fileId);
	}
	/**
	* Upload a supp file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadSuppFile($fileName, $fileId = null) {
		return handleUpload($fileName, $this->filesDir . "supp/", "supp", $fileId);
	}
	
	/**
	* Upload a review file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadReviewFile($fileName, $fileId = null) {
		return handleUpload($fileName, $this->filesDir . "review/", "review", $fileId);
	}
	
	/**
	* Upload a public file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	* @return $articleFile is null if failure
	*/
	function uploadPublicFile($fileName, $fileId = null) {
		return handleUpload($fileName, $this->filesDir . "public/", "public", $fileId);
	}
	
	function downloadFile($fileId) {
		// get the files path and type
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile;
		$articleFile = $articleFileDao->getArticleFile($fileId);
		$fileType = $articleFile->getFileType();
		$type = $articleFile->getType();
		$fileName = $articleFile->getFileName();
		$filePath = $this->filesDir . "$type/$fileName"
		
		FileManager::downloadFile($filePath, $fileType);
	}
	
	// private
	
	function handleUpload($fileName, $dir, $type, $fileId = null) {
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile;
		
		if ($fileId == null) {
			if (uploadFile($fileName, $dir)) {
				$articleFile->setArticleId($this->articleId);
				$articleFile->setFileName($fileName);
				$articleFile->setFileType($_FILES[$fileName][type]);
				$articleFile->setType($type);
				$articleFile->setDateUploaded(date("Y-m-d g:i:s");
				$articleFile->setDateModified(date("Y-m-d g:i:s");
				return $articleFileDao->insertArticleFile($articleFile);
			} else {
				return null;
			}
		} else {
			$articleFile = $articleFileDao->getArticleFile($fileId);
			// unlink old file
			deleteFile($dir . $articleFile->getFileName());
			// upload new file
			if (uploadFile($fileName, $dir)) {
				// update database entry for file
				$articleFile->setFileName($fileName);
				$articleFile->setFileType($_FILES[$fileName][type]);
				$articleFile->setType($type);
				$articleFile->setDateModified(date("Y-m-d g:i:s");
				return $articleFileDao->updateArticleFile($articleFile);
			} else {
				return null;	
			}
		}	
	}
}

?>
