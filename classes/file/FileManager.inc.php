<?php

/**
 * FileManager.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * Class defining basic operations for file management.
 *
 * $Id$
 */


// Default permissions for new directories, if none configured
define('DEFAULT_DIR_PERM', 0755);

class FileManager {

	/**
	 * Constructor.
	 * Empty constructor.
	 */
	function FileManager() {	
	}
	
	/**
	 * Return true if an uploaded file exists.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function uploadedFileExists($fileName) {
		if (isset($_FILES[$fileName]['tmp_name']) && is_uploaded_file($_FILES[$fileName]['tmp_name'])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Return the (temporary) path to an uploaded file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return string (boolean false if no such file)
	 */
	function getUploadedFilePath($fileName) {
		if (isset($_FILES[$fileName]['tmp_name']) && is_uploaded_file($_FILES[$fileName]['tmp_name'])) {
			return $_FILES[$fileName]['tmp_name'];
		} else {
			return false;
		}
	}
	
	/**
	 * Upload a file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $dest string the path where the file is to be saved
	 * @return boolean returns true if successful
	 */
	function uploadFile($fileName, $destFileName) {
		if ($this->fileExists(dirname($destFileName), 'dir')) {
			return move_uploaded_file($_FILES[$fileName]['tmp_name'], $destFileName);
		} else {
			return false;
		}
	}
	
	/**
	 * Download a file.
	 * @param $filePath string the location of the file to be sent
	 * @param $type string the MIME type of the file, optional
	 * @return string returns HTTP headers and file for download
	 */
	function downloadFile($filePath, $type = null) {
		$f = @fopen($filePath, 'r');
		if (!$f) {
			if ($type == null) {
				$type = "application/octet-stream";
			}
			header("Content-Type: $type");
			header("Content-Length: ".filesize($filePath));
			header("Content-Disposition: attachment; filename=" .basename($filePath));
			header("Cache-Control: private"); // Workarounds for IE weirdness
			header("Pragma: public");
			$data = "";
			while (!feof($f)) {
				$data .= fread($f, 64000);
			}
			fclose($f);
			echo $data;
		}
	}
	
	/**
	 * Delete a file.
	 * @param $filePath string the location of the file to be deleted
	 * @return boolean returns true if successful
	 */
	function deleteFile($filePath) {
		if ($this->fileExists($filePath)) {
			return unlink($filePath);
		} else {
			return false;
		}
	}
	
	/**
	 * Create a new diretory.
	 * @param $dirPath string the full path of the directory to be created
	 * @param $perms string the permissions level of the directory, optional, default dir_perm
	 * @return boolean returns true if successful
	 */
	function mkdir($dirPath, $perms = null) {
		if ($perms == null) {
			$perms = Config::getVar('security','dir_perm');
			if ($perms == null) {
				$perms = DEFAULT_DIR_PERM;
			}
		}
		return mkdir($dirPath, $perms);
	}	
	
	/**
	 * Delete all contents including directory
	 * @param $file string the full path of the directory to be removed
	 */
	function rmtree($file) {
		if (file_exists($file)) {
			if (is_dir($file)) {
				$handle = opendir($file); 
				while (($filename = readdir($handle)) !== false) {
					if ($filename != '.' && $filename != '..') {
						FileManager::rmtree($file . DIRECTORY_SEPARATOR . $filename);
					}
				}
				closedir($handle);
				rmdir($file);
				
			} else {
				unlink($file);
			}
		}
	}
	
	/**
	 * Check if a file path is valid;
	 * @param $filePath string the file/directory to check
	 * @param $type string (file|dir) the type of path
	 */
	function fileExists($filePath, $type = 'file') {
		switch ($type) {
			case 'file':
				return file_exists($filePath);
			case 'dir':
				return file_exists($filePath) && is_dir($filePath);
			default:
				return false;
		}
	}
	
}

?>
