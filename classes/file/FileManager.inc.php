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


class FileManager {
	/**
	 * Constructor.
	 * Empty constructor.
	 */
	function FileManager() {	
	}
	
	/**
	 * Upload a file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $dest string the path where the file is to be saved
	 * @return boolean returns true if successful
	 */
	function uploadFile($fileName, $dest) {
		return move_uploaded_file($_FILES[$fileName]['tmp_name'] , $dest);
	}
	
	/**
	 * Download a file.
	 * @param $filePath string the location of the file to be sent
	 * @param $type string the MIME type of the file, optional
	 * @return string returns HTTP headers and file for download
	 */
	function downloadFile($filePath, $type = null) {
		$f = @fopen($filePath,"r");
		if (!$f) {
			if ($type == null ) {
				$type = "application/octet-stream";
			}
			header("Content-Type: $type");
			header("Content-Length: ".filesize($filePath));
			header("Content-Disposition: attachment; filename=" .basename($filePath));
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
		return unlink($filePath);	
	}
	
	/**
	 * Create a new diretory.
	 * @param $dirPath string the full path of the directory to be created
	 * @param $perms string the permissions level of the directory, optional, default 0700
	 * @return boolean returns true if successful
	 */
	function mkdir($dirPath, $perms=0700) {
		return @mkdir($dirPath, $perms);
	}
	
	/**
	 * Recursively remove all contents of a directory.
	 * @param $dirPath string the full path of the directory to be removed
	 */
	function rmtree($dirPath) {
		exec("rm -rf $dirPath");
	}
}

?>
