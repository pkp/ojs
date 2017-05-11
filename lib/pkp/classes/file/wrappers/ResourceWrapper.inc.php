<?php

/**
 * @file classes/file/wrappers/ResourceWrapper.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ResourceWrapper
 * @ingroup file_wrappers
 *
 * @brief Class abstracting operations for accessing resources.
 */

class ResourceWrapper extends FileWrapper {
	/**
	 * Constructor.
	 * @param $fp Resource
	 */
	function __construct(&$fp) {
		// Parent constructor intentionally not called
		$this->fp =& $fp;
	}

	function open($mode = 'r') {
		// The resource should already be open
		return true;
	}
}

?>
