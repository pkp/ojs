<?php

/**
 * ArrayIterator.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Provides paging and iteration for arrays.
 *
 * $Id$
 */

import('core.Iterator');

class ArrayIterator extends Iterator {
	/** The array of contents of this iterator. */
	var $theArray;

	/** Number of items to iterate through on this page */
	var $itemsPerPage;

	/** The current page. */
	var $page;

	/** The total number of items. */
	var $count;

	/** Whether or not the iterator was empty from the start */
	var $wasEmpty;

	/**
	 * Constructor.
	 * @param $theArray array The array of items to iterate through
	 * @param $page int the current page number
	 * @param $itemsPerPage int Number of items to display per page
	 */
	function &ArrayIterator(&$theArray, $page=-1, $itemsPerPage=-1) {
		if ($page>=1 && $itemsPerPage>=1) {
			$this->theArray = &array_slice(&$theArray, ($page-1) * $itemsPerPage, $itemsPerPage);
			$this->page = $page;
		} else {
			$this->theArray = &$theArray;
			$this->page = 1;
			$this->itemsPerPage = max(count($this->theArray),1);
		}
		$this->count = count($theArray);
		$this->itemsPerPage = $itemsPerPage;
		$this->wasEmpty = count($this->theArray)==0;
		reset(&$this->theArray);
	}

	/**
	 * Return the next item in the iterator.
	 * @return object
	 */
	function &next() {
		$value = &current(&$this->theArray);
		if (next(&$this->theArray)==null) {
			$this->theArray = null;
		}
		return $value;
	}

	function atFirstPage() {
		return $this->page==1;
	}

	function atLastPage() {
		return ($this->page * $this->itemsPerPage) + 1 > $this->count;
	}

	function getPage() {
		return $this->page;
	}

	function getCount() {
		return $this->count;
	}

	function getPageCount() {
		return max(1, ceil($this->count / $this->itemsPerPage));
	}

	/**
	 * Return a boolean indicating whether or not we've reached the end of results
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

	function &toArray() {
		return $this->theArray;
	}
}

?>
