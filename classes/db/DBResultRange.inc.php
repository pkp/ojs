<?php

/**
 * DBResultRange.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Container class for range information when retrieving a result set.
 *
 * $Id$
 */

class DBResultRange {
	/** The number of items to display */
	var $count;

	/** The number of items to skip */
	var $offset;

	/**
	 * Constructor.
	 * Initialize the DBResultRange.
	 */
	function DBResultRange($count, $offset = 0) {
		$this->count = $count;
		$this->offset = $offset;
	}

	/**
	 * Checks to see if the DBResultRange is valid.
	 * @return boolean
	 */
	function isValid() {
		return (($this->count>0) && ($this->offset>=0));
	}

	/**
	 * Returns the count of items to skip.
	 * @return int
	 */
	function getOffset() {
		return $this->offset;
	}

	/**
	 * Returns the count of items in this range to display.
	 * @return int
	 */
	function getCount() {
		return $this->count;
	}
}

?>
