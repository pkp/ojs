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
	
	/**
	* Constructor.
	* Create a manager for handling article file uploads.
	* @param $articleId int
	*/
	function ArticleFileManager($articleId) {	
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($articleId);
		$journalId = $article->getJournalId();
		$this->filesDir = Config::getVar('general', 'files_dir') . "/journals/" . $journalId .
		"/articles/" . $articleId. "/";
	}
	
	/**
	* Upload a file.
	* @param $fileName string the name of the file used in the POST form
	* @param $dest string the path where the file is to be saved
	 * @return boolean returns true if successful
	*/
	function uploadSubmissionFile($fileName, $fileId = null) {
		return uploadFile($fileName, $this->filesDir . "submission/");
	}
	
	function uploadSuppFile($fileName, $fileId = null) {
		return uploadFile($fileName, $this->filesDir . "supp/");
	}
	
	function uploadReviewFile($fileName, $fileId = null) {
		return uploadFile($fileName, $this->filesDir . "review/");
	}
	
	function uploadPublicFile($fileName, $fileId = null) {
		return uploadFile($fileName, $this->filesDir . "public/");
	}
	
	function downloadFile($fileId) {
		// get the files path and type
		
		FileManager::downloadFile($filePath, $type);
	}
	
}

?>
