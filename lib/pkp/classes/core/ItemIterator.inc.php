<?php

/**
 * @file classes/core/ItemIterator.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ItemIterator
 * @ingroup db
 *
 * @brief Generic iterator class; needs to be overloaded by subclasses
 * providing specific implementations.
 */


class ItemIterator {
	/**
	 * Constructor
	 */
	function __construct() {
	}

	/**
	 * Return the next item in the iterator.
	 * @return object
	 */
	function &next() {
		$nullVar = null;
		return $nullVar;
	}

	/**
	 * Return the next item with key.
	 * @return array ($key, $value);
	 */
	function nextWithKey() {
		return array(null, null);
	}

	/**
	 * Determine whether this iterator represents the first page of a set.
	 * @return boolean
	 */
	function atFirstPage() {
		return true;
	}

	/**
	 * Determine whether this iterator represents the last page of a set.
	 * @return boolean
	 */
	function atLastPage() {
		return true;
	}

	/**
	 * Get the page number of a set that this iterator represents.
	 * @return int
	 */
	function getPage() {
		return 1;
	}

	/**
	 * Get the total number of items in the set.
	 * @return int
	 */
	function getCount() {
		return 0;
	}

	/**
	 * Get the total number of pages in the set.
	 * @return int
	 */
	function getPageCount() {
		return 0;
	}

	/**
	 * Return a boolean indicating whether or not we've reached the end of results
	 * @return boolean
	 */
	function eof() {
		return true;
	}

	/**
	 * Return a boolean indicating whether or not this iterator was empty from the beginning
	 * @return boolean
	 */
	function wasEmpty() {
		return true;
	}

	/**
	 * Convert this iterator to an array.
	 * @return array
	 */
	function &toArray() {
		$returner = array();
		return $returner;
	}
}

?>
