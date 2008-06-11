<?php

/**
 * @file FilesHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class FilesHandler
 *
 * Handle requests for files browser functions. 
 *
 * $Id$
 */

class FilesHandler extends ManagerHandler {

	/**
	 * Display the files associated with a journal.
	 */
	function files($args) {
		parent::validate();
		parent::setupTemplate(true);

		import('file.FileManager');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'manager'), 'manager.journalManagement')));

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);
		$currentPath = FilesHandler::getRealFilesDir($currentDir);

		if (@is_file($currentPath)) {
			$fileMgr = &new FileManager();
			if (Request::getUserVar('download')) {
				$fileMgr->downloadFile($currentPath);
			} else {
				$fileMgr->viewFile($currentPath, FilesHandler::fileMimeType($currentPath));
			}

		} else {
			$files = array();
			if ($dh = @opendir($currentPath)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' && $file != '..') {
						$filePath = $currentPath . '/'. $file;
						$isDir = is_dir($filePath);
						$info = array(
							'name' => $file,
							'isDir' => $isDir,
							'mimetype' => $isDir ? '' : FilesHandler::fileMimeType($filePath),
							'mtime' => filemtime($filePath),
							'size' => $isDir ? '' : FileManager::getNiceFileSize(filesize($filePath)),
						);
						$files[$file] = $info;
					}
				}
				closedir($dh);
			}
			ksort($files);
			$templateMgr->assign_by_ref('files', $files);
			$templateMgr->assign('currentDir', $currentDir);
			$templateMgr->assign('parentDir', $parentDir);
			$templateMgr->assign('helpTopicId','journal.managementPages.fileBrowser');
			$templateMgr->display('manager/files/index.tpl');
		}
	}

	/**
	 * Upload a new file.
	 */
	function fileUpload($args) {
		parent::validate();

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);
		$currentPath = FilesHandler::getRealFilesDir($currentDir);

		import('file.FileManager');
		$fileMgr = &new FileManager();
		if ($fileMgr->uploadedFileExists('file')) {
			$destPath = $currentPath . '/' . FilesHandler::cleanFileName($fileMgr->getUploadedFileName('file'));
			@$fileMgr->uploadFile('file', $destPath);
		}

		Request::redirect(null, null, 'files', explode('/', $currentDir));

	}

	/**
	 * Create a new directory
	 */
	function fileMakeDir($args) {
		parent::validate();

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);

		if ($dirName = Request::getUserVar('dirName')) {
			$currentPath = FilesHandler::getRealFilesDir($currentDir);
			$newDir = $currentPath . '/' . FilesHandler::cleanFileName($dirName);

			import('file.FileManager');
			$fileMgr = &new FileManager();
			@$fileMgr->mkdir($newDir);
		}

		Request::redirect(null, null, 'files', explode('/', $currentDir));
	}

	function fileDelete($args) {
		parent::validate();

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);
		$currentPath = FilesHandler::getRealFilesDir($currentDir);

		import('file.FileManager');
		$fileMgr = &new FileManager();

		if (@is_file($currentPath)) {
			$fileMgr->deleteFile($currentPath);
		} else {
			// TODO Use recursive delete (rmtree) instead?
			@$fileMgr->rmdir($currentPath);
		}

		Request::redirect(null, null, 'files', explode('/', $parentDir));
	}


	//
	// Helper functions
	// FIXME Move some of these functions into common class (FileManager?)
	//

	function parseDirArg($args, &$currentDir, &$parentDir) {
		$pathArray = array_filter($args, array('FilesHandler', 'fileNameFilter'));
		$currentDir = join($pathArray, '/');
		array_pop($pathArray);
		$parentDir = join($pathArray, '/');
	}

	function getRealFilesDir($currentDir) {
		$journal = &Request::getJournal();
		return Config::getVar('files', 'files_dir') . '/journals/' . $journal->getJournalId() .'/' . $currentDir;
	}

	function fileNameFilter($var) {
		return (!empty($var) && $var != '..' && $var != '.');
	}

	function cleanFileName($var) {
		$var = String::regexp_replace('/[^\w\-\.]/', '', $var);
		if (!FilesHandler::fileNameFilter($var)) {
			$var = time() . '';
		}
		return $var;
	}

	function fileMimeType($filePath) {
		return String::mime_content_type($filePath);
	}

}
?>
