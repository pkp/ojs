<?php

/**
 * @file classes/file/LibraryFileManager.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LibraryFileManager
 * @ingroup file
 *
 * @brief Wrapper class for uploading files to a site/context' library directory.
 */

import('lib.pkp.classes.file.PKPLibraryFileManager');

class LibraryFileManager extends PKPLibraryFileManager {

	/**
	 * Constructor
	 * @param $contextId int
	 */
	function LibraryFileManager($contextId) {
		parent::PKPLibraryFileManager($contextId);
	}


	/**
	 * Get the file suffix for the given file type
	 * @param $type int LIBRARY_FILE_TYPE_...
	 */
	function getFileSuffixFromType($type) {
		$typeSuffixMap =& $this->getTypeSuffixMap();
		return $typeSuffixMap[$type];
	}

	/**
	 * Get the type => suffix mapping array
	 * @return array
	 */
	function &getTypeSuffixMap() {
		static $map = array();
		$parent = parent::getTypeSuffixMap();
		$map = array_merge($map, $parent);
		return $map;
	}

	/**
	 * Get the type => locale key mapping array
	 * @return array
	 */
	function &getTypeTitleKeyMap() {
		static $map = array();
		$parent = parent::getTypeTitleKeyMap();
		$map = array_merge($map, $parent);
		return $map;
	}

	/**
	 * Get the type => name mapping array
	 * @return array
	 */
	function &getTypeNameMap() {
		static $map = array();
		$parent = parent::getTypeNameMap();
		$map = array_merge($map, $parent);
		return $map;
	}
}

?>
