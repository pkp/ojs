<?php

/**
 * @file classes/core/VirtualArrayIterator.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VirtualArrayIterator
 * @ingroup db
 *
 * @brief Provides paging and iteration for "virtual" arrays -- arrays for which only
 * the current "page" is available, but are much bigger in entirety.
 */

// $Id$


import('core.ItemIterator');

class VirtualArrayIterator extends ItemIterator {
	/** @var $theArray array The array of contents of this iterator. */
	var $theArray;

	/** @var $itemsPerPage int Number of items to iterate through on this page */
	var $itemsPerPage;

	/** @var $page int The current page. */
	var $page;

	/** @var $count int The total number of items. */
	var $count;

	/** @var $wasEmpty boolean Whether or not the iterator was empty from the start */
	var $wasEmpty;

	/**
	 * Constructor.
	 * @param $theArray array The array of items to iterate through
	 * @param $totalItems int The total number of items in the virtual "larger" array
	 * @param $page int the current page number
	 * @param $itemsPerPage int Number of items to display per page
	 */
	function VirtualArrayIterator(&$theArray, $totalItems, $page=-1, $itemsPerPage=-1) {
		if ($page>=1 && $itemsPerPage>=1) {
			$this->page = $page;
		} else {
			$this->page = 1;
			$this->itemsPerPage = max(count($this->theArray),1);
		}
		$this->theArray = &$theArray;
		$this->count = $totalItems;
		$this->itemsPerPage = $itemsPerPage;
		$this->wasEmpty = count($this->theArray)==0;
		reset($this->theArray);
	}

	/**
	 * Return the next item in the iterator.
	 * @return object
	 */
	function &next() {
		if (!is_array($this->theArray)) {
			$nullVar = null;
			return $nullVar;
		}
		$value = &current($this->theArray);
		if (next($this->theArray)==null) {
			$this->theArray = null;
		}
		return $value;
	}

	/**
	 * Return the next item in the iterator, with key.
	 * @return array (key, value)
	 */
	function &nextWithKey() {
		$key = key($this->theArray);
		$value = $this->next();
		return array($key, $value);
	}

	/**
	 * Check whether or not this iterator is for the first page of a sequence
	 * @return boolean
	 */
	function atFirstPage() {
		return $this->page==1;
	}

	/**
	 * Check whether or not this iterator is for the last page of a sequence
	 * @return boolean
	 */
	function atLastPage() {
		return ($this->page * $this->itemsPerPage) + 1 > $this->count;
	}

	/**
	 * Get the page number that this iterator represents
	 * @return int
	 */
	function getPage() {
		return $this->page;
	}

	/**
	 * Get the total number of items in the virtual array
	 * @return int
	 */
	function getCount() {
		return $this->count;
	}

	/**
	 * Get the total number of pages in the virtual array
	 * @return int
	 */
	function getPageCount() {
		return max(1, ceil($this->count / $this->itemsPerPage));
	}

	/**
	 * Return a boolean indicating whether or not we've reached the end of results
	 * Note: This implementation requires that next() be called before every eof() will
	 * function properly (except the first call).
	 * @return boolean
	 */
	function eof() {
		return (($this->theArray == null) || (count($this->theArray)==0));
	}

	/**
	 * Return a boolean indicating whether or not this iterator was empty from the beginning
	 * @return boolean
	 */
	function wasEmpty() {
		return $this->wasEmpty;
	}

	/**
	 * Convert the iterator into an array
	 * @return array
	 */
	function &toArray() {
		return $this->theArray;
	}

	function array_slice_key($array, $offset, $len=-1) {
		// A version of array_slice that takes keys into account.
		// Thanks to pies at sputnik dot pl. (Retrieved from
		// http://ca3.php.net/manual/en/function.array-slice.php)

		// This is made redundant by PHP 5.0.2's updated
		// array_slice, but we can't assume everyone has that.

		if (!is_array($array)) return false;

		$return = array();
		$length = $len >= 0? $len: count($array);
		$keys = array_slice(array_keys($array), $offset, $length);
		foreach($keys as $key) {
			$return[$key] = $array[$key];
		}

		return $return;
	}
}

?>
