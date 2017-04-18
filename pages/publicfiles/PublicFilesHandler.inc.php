<?php

/**
 * @file pages/publicfiles/PublicFilesHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicFilesHandler
 * @ingroup pages_publicfiles
 *
 * @brief Handle requests for public files functions.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.file.FileManager');

class PublicFilesHandler extends Handler {
	
	/* @var fileManager FileManager object (see lib/pkp/classes/file/FileManager.inc.php) */
	var $fileManager;
	
	//Constructor
	function PublicFilesHandler() {
		$this->fileManager = new FileManager();
	}
	
	/**
	 * Delete a file in the directory [files_dir]/publicuploads/[journalId]
	 * @param $args array
	 * @param $request PKPRequest
	 * @return bool
	 */
	function delete($args, $request) {
		$user = $request->getUser();
		
		$sessionManager = SessionManager::getManager();
		$userSession = $sessionManager->getUserSession();
		$journalId = $userSession->getSessionVar('journalId');
		$fileName = $args[1];
		
		if(!$user) {
			return false;
		} else {
			import('plugins.generic.tinymce.TinyMCEPlugin');
			$tinyMCEPlugin = new TinyMCEPlugin();
			
			$isPermitted = $tinyMCEPlugin->publicUploadValidate($user->getId(), $journalId);
			if (!$isPermitted) {
				return false;
			}
		}
		
		$publicDir = $this->_getRealPublicFilesDir($journalId);
		$filePath = $publicDir . $fileName;

		if($this->fileManager->deleteFile($filePath)) {
			if (isset($_SERVER['HTTP_REFERER'])) {
				$request->redirectUrl($_SERVER['HTTP_REFERER']);
			}
			return true;
		}
	}


	/**
	 * Download a file in the directory [files_dir]/publicuploads/[journalId]
	 * @param $args array
	 * @return bool
	 */
	function download($args) {
		$journalId = $args[0];
		$fileName = $args[1];
		
		$publicDir = $this->_getRealPublicFilesDir($journalId);
		$filePath = $publicDir . $fileName;
		
		// display file in the browser
		$this->fileManager->downloadFile($filePath, String::mime_content_type($filePath), true);
	}
	
	
	/**
	 * Get the path of a journal's public files directory
	 * @param $journalId int
	 * @return string
	 */
	function _getRealPublicFilesDir($journalId) {
		$publicDir = Config::getVar('files', 'files_dir') . '/publicuploads/' . $journalId . '/';
		return $publicDir;
	}
}
?>