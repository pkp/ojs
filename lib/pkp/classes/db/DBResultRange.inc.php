<?php

/**
 * @file classes/db/DBResultRange.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DBResultRange
 * @ingroup db
 *
 * @brief Container class for range information when retrieving a result set.
 */


class DBResultRange {
	/** The number of items to display */
	var $count;

	/** The number of pages to skip */
	var $page;

	/**
	 * Constructor.
	 * Initialize the DBResultRange.
	 */
	function __construct($count, $page = 1) {
		$this->count = $count;
		$this->page = $page;
	}

	/**
	 * Checks to see if the DBResultRange is valid.
	 * @return boolean
	 */
	function isValid() {
		return (($this->count>0) && ($this->page>=0));
	}

	/**
	 * Returns the count of pages to skip.
	 * @return int
	 */
	function getPage() {
		return $this->page;
	}

	/**
	 * Set the count of pages to skip.
	 * @param $page int
	 */
	function setPage($page) {
		$this->page = $page;
	}

	/**
	 * Returns the count of items in this range to display.
	 * @return int
	 */
	function getCount() {
		return $this->count;
	}

	/**
	 * Set the count of items in this range to display.
	 * @param $count int
	 */
	function setCount($count) {
		$this->count = $count;
	}
}

?>
